<?php
declare(strict_types=1);

/**
 * Generates the 1200x630 PNG that X/LinkedIn/WhatsApp show when an
 * answer link is shared. Same two voices as the site: the question in
 * Lora italic on a highlighter mark, the answer in Inter.
 * Cached on disk; the cache key includes the text, so edits refresh it.
 */
final class ShareCard
{
    private const W = 1200;
    private const H = 630;
    private const PAD = 80;
    private string $voice;
    private string $sans;
    private string $sansBold;
    private string $display;

    public function __construct(private string $cacheDir)
    {
        $f = ROOT . '/public/assets/fonts/';
        $this->voice    = $f . 'Lora-Italic-Variable.ttf';
        $this->sans     = $f . 'Inter-Regular.otf';
        $this->sansBold = $f . 'Inter-Bold.otf';
        $this->display  = $f . 'InterDisplay-Black.otf';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }
    }

    public function pathFor(array $q, string $host): string
    {
        $key  = substr(hash('sha256', 'v4|' . $q['body'] . '|' . ($q['answer'] ?? '') . '|' . $q['display_name'] . '|' . $host), 0, 16);
        $file = $this->cacheDir . '/' . (int) $q['id'] . '-' . $key . '.png';
        if (!is_file($file)) {
            foreach (glob($this->cacheDir . '/' . (int) $q['id'] . '-*.png') ?: [] as $old) {
                @unlink($old);
            }
            $this->render($q, $host, $file);
        }
        return $file;
    }

    private function render(array $q, string $host, string $file): void
    {
        $im = imagecreatetruecolor(self::W, self::H);
        imageantialias($im, true);
        $white  = imagecolorallocate($im, 255, 255, 255);
        $black  = imagecolorallocate($im, 0, 0, 0);
        $grey   = imagecolorallocate($im, 107, 107, 107);
        $marker = imagecolorallocate($im, 255, 162, 107);
        imagefilledrectangle($im, 0, 0, self::W, self::H, $white);

        $x = self::PAD;
        $maxW = self::W - 2 * self::PAD;

        // Question: Lora italic on a highlighter mark, one bar per line
        $answered = !empty($q['answer']);
        $qSize = $answered ? 40 : 52;
        $qLead = (int) round($qSize * 1.45);
        $qLines = $this->wrap($q['body'], $qSize, $this->voice, $maxW - 24, $answered ? 3 : 4);
        $y = 96 + $qSize;
        foreach ($qLines as $line) {
            $w = $this->width($line, $qSize, $this->voice);
            imagefilledrectangle($im, $x - 8, $y - (int) ($qSize * 0.92), $x + $w + 10, $y + (int) ($qSize * 0.28), $marker);
            imagettftext($im, $qSize, 0, $x, $y, $black, $this->voice, $line);
            $y += $qLead;
        }

        // Answer: a black rule on the left, like on the site
        if ($answered) {
            $y += 26;
            $top = $y;
            imagettftext($im, 22, 0, $x + 28, $y + 22, $black, $this->sansBold, $q['display_name'] . ' answered');
            $y += 22 + 48;
            $room = max(1, intdiv(self::H - 120 - $y, 42) + 1);
            foreach ($this->wrap($q['answer'], 28, $this->sans, $maxW - 28, min(4, $room)) as $line) {
                imagettftext($im, 28, 0, $x + 28, $y, $black, $this->sans, $line);
                $y += 42;
            }
            imagefilledrectangle($im, $x, $top - 4, $x + 4, $y - 30, $black);
        } else {
            imagettftext($im, 24, 0, $x, $y + 16, $grey, $this->sans, 'Not answered yet. Upvote it so ' . explode(' ', $q['display_name'])[0] . ' sees it first.');
        }

        // Footer: wordmark left, the asker's way in on the right
        $fy = self::H - 64;
        imageline($im, $x, $fy - 40, self::W - self::PAD, $fy - 40, imagecolorallocate($im, 230, 230, 230));
        imagettftext($im, 26, 0, $x, $fy, $black, $this->display, 'AskBox');
        $qx = $x + $this->width('AskBox', 26, $this->display) + 4;
        imagefilledrectangle($im, $qx, $fy - 26, $qx + 20, $fy + 6, $marker);
        imagettftext($im, 26, 0, $qx + 3, $fy, $black, $this->voice, '?');
        $link = 'Ask anonymously at ' . $host . '/@' . $q['username'];
        imagettftext($im, 20, 0, self::W - self::PAD - $this->width($link, 20, $this->sans), $fy - 2, $grey, $this->sans, $link);

        imagepng($im, $file, 6);
        imagedestroy($im);
    }

    private function width(string $text, int $size, string $font): int
    {
        $b = imagettfbbox($size, 0, $font, $text);
        return (int) abs($b[2] - $b[0]);
    }

    /** Word-wrap to a pixel width, truncating with an ellipsis after $maxLines. */
    private function wrap(string $text, int $size, string $font, int $maxWidth, int $maxLines): array
    {
        $text  = preg_replace('/\s+/u', ' ', trim($text));
        $lines = [];
        $line  = '';
        foreach (explode(' ', $text) as $word) {
            $try = $line === '' ? $word : "$line $word";
            if ($this->width($try, $size, $font) <= $maxWidth) {
                $line = $try;
                continue;
            }
            if ($line !== '') {
                $lines[] = $line;
            }
            $line = $word;
            while ($this->width($line, $size, $font) > $maxWidth && mb_strlen($line) > 1) {
                $cut = mb_strlen($line);
                while ($cut > 1 && $this->width(mb_substr($line, 0, $cut), $size, $font) > $maxWidth) {
                    $cut--;
                }
                $lines[] = mb_substr($line, 0, $cut);
                $line = mb_substr($line, $cut);
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $last  = $lines[$maxLines - 1];
            while ($last !== '' && $this->width($last . '…', $size, $font) > $maxWidth) {
                $last = mb_substr($last, 0, -1);
            }
            $lines[$maxLines - 1] = rtrim($last) . '…';
        }
        return $lines;
    }
}
