<?php
declare(strict_types=1);

/**
 * Sends email with no packages.
 *
 * Three drivers, set in config ('mail' => ['driver' => ...]):
 *   smtp : log in to a real mailbox (e.g. hello@ejikepius.dev) and send. Most reliable.
 *   mail : PHP's built-in mail(). Works on most cPanel hosts but often lands in spam.
 *   log  : don't send, just write the email to data/mail.log. Default for local development.
 */
final class Mailer
{
    private const TIMEOUT = 10;

    public function __construct(private array $config) {}

    public static function fromConfig(): self
    {
        return new self((array) config('mail'));
    }

    /** Returns true if the email was sent (or logged). Never throws. */
    public function send(string $to, string $subject, string $text, string $html): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $message = $this->build($to, $subject, $text, $html);

            match ($this->config['driver'] ?? 'log') {
                'smtp'  => $this->sendSmtp($to, $message),
                'mail'  => $this->sendMail($to, $subject, $message),
                default => $this->writeLog($to, $subject, $text),
            };
            return true;
        } catch (Throwable $e) {
            $this->log('FAILED to ' . $to . ': ' . $e->getMessage());
            return false;
        }
    }

    // ---- Building the message ---------------------------------------------

    /** @return array{headers: string, body: string} */
    private function build(string $to, string $subject, string $text, string $html): array
    {
        $boundary = 'askbox_' . bin2hex(random_bytes(12));
        $from     = $this->config['from_email'];
        $fromName = $this->config['from_name'] ?? 'AskBox';
        $domain   = substr(strrchr($from, '@') ?: '@localhost', 1);

        $headers = implode("\r\n", [
            'From: ' . mb_encode_mimeheader($fromName) . ' <' . $from . '>',
            'Reply-To: ' . ($this->config['reply_to'] ?? $from),
            'To: ' . $to,
            'Subject: ' . mb_encode_mimeheader($subject),
            'Date: ' . date('r'),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ]);

        // Plain text first, HTML second: email apps show the last version they understand.
        $body = "--$boundary\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text))
            . "--$boundary\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--$boundary--\r\n";

        return ['headers' => $headers, 'body' => $body];
    }

    // ---- Drivers ------------------------------------------------------------

    private function sendMail(string $to, string $subject, array $message): void
    {
        // mail() adds To and Subject itself, so remove them from our headers.
        $headers = preg_replace('/^(To|Subject): .*\r\n/m', '', $message['headers'] . "\r\n");
        $ok = mail($to, mb_encode_mimeheader($subject), $message['body'], rtrim($headers),
                   '-f' . $this->config['from_email']);
        if (!$ok) {
            throw new RuntimeException('mail() returned false');
        }
    }

    private function sendSmtp(string $to, array $message): void
    {
        $host = $this->config['host'];
        $port = (int) ($this->config['port'] ?? 465);
        // ssl = encrypted from the start (usually port 465).
        // tls = connect plain, then upgrade with STARTTLS (usually port 587).
        $implicit = ($this->config['encryption'] ?? ($port === 465 ? 'ssl' : 'tls')) === 'ssl';
        $remote   = ($implicit ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $socket = @stream_socket_client($remote, $errno, $errstr, self::TIMEOUT);
        if (!$socket) {
            throw new RuntimeException("Could not connect to $host:$port ($errstr)");
        }
        stream_set_timeout($socket, self::TIMEOUT);

        try {
            $this->expect($socket, 220);
            $this->command($socket, 'EHLO ' . $this->ehloName(), 250);

            if (!$implicit) {
                $this->command($socket, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('STARTTLS failed');
                }
                $this->command($socket, 'EHLO ' . $this->ehloName(), 250);
            }

            $this->command($socket, 'AUTH LOGIN', 334);
            $this->command($socket, base64_encode($this->config['username']), 334);
            $this->command($socket, base64_encode($this->config['password']), 235, hide: true);

            $this->command($socket, 'MAIL FROM:<' . $this->config['from_email'] . '>', 250);
            $this->command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->command($socket, 'DATA', 354);

            // A line starting with "." would end the message early, so double it ("dot-stuffing").
            $data = preg_replace('/^\./m', '..', $message['headers'] . "\r\n\r\n" . $message['body']);
            $this->command($socket, $data . "\r\n.", 250);
            $this->command($socket, 'QUIT', 221);
        } finally {
            fclose($socket);
        }
    }

    private function writeLog(string $to, string $subject, string $text): void
    {
        $this->log("To: $to\nSubject: $subject\n\n$text\n" . str_repeat('-', 60));
    }

    // ---- SMTP helpers -------------------------------------------------------

    /** @param resource $socket */
    private function command($socket, string $line, int|array $expect, bool $hide = false): void
    {
        fwrite($socket, $line . "\r\n");
        $this->expect($socket, $expect, $hide ? '(password)' : strtok($line, "\r\n"));
    }

    /** Read the server's reply and check its 3-digit code. @param resource $socket */
    private function expect($socket, int|array $codes, string $after = 'connect'): void
    {
        $reply = '';
        while (($line = fgets($socket, 515)) !== false) {
            $reply .= $line;
            if (isset($line[3]) && $line[3] === ' ') {   // "250 OK" ends a reply, "250-..." continues it
                break;
            }
        }
        $code = (int) substr($reply, 0, 3);
        if (!in_array($code, (array) $codes, true)) {
            throw new RuntimeException("SMTP said '" . trim($reply) . "' after $after");
        }
    }

    private function ehloName(): string
    {
        return parse_url(base_url(), PHP_URL_HOST) ?: 'localhost';
    }

    private function log(string $entry): void
    {
        @file_put_contents(ROOT . '/data/mail.log', '[' . date('c') . "] $entry\n", FILE_APPEND | LOCK_EX);
    }
}
