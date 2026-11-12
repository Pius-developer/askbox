<?php
declare(strict_types=1);

/**
 * Welcome email, sent right after someone creates their AskBox.
 * Expects $user (row from the users table). Returns subject, text and html.
 *
 * Email apps ignore most modern CSS, so the HTML uses tables and inline styles.
 */

$name      = $user['display_name'];
$firstName = preg_split('/\s+/u', trim($name))[0] ?: $name;
$boxUrl    = base_url() . '/@' . $user['username'];
$boxLabel  = host() . '/@' . $user['username'];
$dashboard = base_url() . '/dashboard';
$settings  = base_url() . '/settings';
$creatorX  = config('creator_x');

$subject = "Your AskBox is live, $firstName";

$text = <<<TXT
Hi $firstName,

Your AskBox is ready. This is your link:

$boxUrl

Three things to do now:

1. Put the link in your X, Instagram, TikTok or WhatsApp bio.
2. Post it and tell people they can ask you anything. They stay anonymous.
3. Answer from your dashboard: $dashboard
   Every answer becomes a card you can share.

Want to approve questions before anyone sees them? Turn on review mode in your settings: $settings

If anything breaks, reply to this email. I read every one.

Ejike
Built AskBox · x.com/$creatorX

You're getting this because you created an AskBox with this email address.
TXT;

$font  = "-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,Helvetica,Arial,sans-serif";
$serif = "Lora,Georgia,'Times New Roman',serif";

ob_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title><?= e($subject) ?></title>
</head>
<body style="margin:0;padding:0;background:#ffffff;">
<div style="display:none;max-height:0;overflow:hidden;">Here's your link: <?= e($boxLabel) ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;">
<tr><td align="center" style="padding:40px 20px;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;font-family:<?= $font ?>;color:#111111;">

    <tr><td style="padding-bottom:36px;font-size:18px;font-weight:800;letter-spacing:-0.3px;">
      <span style="background:#FFA26B;padding:2px 1px 2px 6px;">Ask</span>Box
    </td></tr>

    <tr><td style="font-family:<?= $serif ?>;font-style:italic;font-size:30px;line-height:1.25;padding-bottom:20px;">
      Your box is open, <?= e($firstName) ?>.
    </td></tr>

    <tr><td style="font-size:16px;line-height:1.6;color:#333333;padding-bottom:14px;">
      People can now ask you anything without telling you who they are. This is your link:
    </td></tr>

    <tr><td style="padding-bottom:28px;">
      <a href="<?= e($boxUrl) ?>" style="display:inline-block;font-size:19px;font-weight:700;color:#111111;text-decoration:none;background:#FFE3D2;border-left:4px solid #FFA26B;padding:14px 18px;word-break:break-all;"><?= e($boxLabel) ?></a>
    </td></tr>

    <tr><td style="padding-bottom:36px;">
      <a href="<?= e($boxUrl) ?>" style="display:inline-block;background:#111111;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;padding:13px 22px;border-radius:999px;">Open my AskBox</a>
    </td></tr>

    <tr><td style="font-size:16px;font-weight:700;padding-bottom:12px;">Three things to do now</td></tr>
    <tr><td style="font-size:15px;line-height:1.6;color:#333333;padding-bottom:28px;">
      <table role="presentation" cellpadding="0" cellspacing="0">
        <tr><td valign="top" style="padding:0 12px 10px 0;font-weight:700;color:#FFA26B;">1</td>
            <td style="padding-bottom:10px;">Put the link in your X, Instagram, TikTok or WhatsApp bio.</td></tr>
        <tr><td valign="top" style="padding:0 12px 10px 0;font-weight:700;color:#FFA26B;">2</td>
            <td style="padding-bottom:10px;">Post it and tell people they can ask you anything. They stay anonymous.</td></tr>
        <tr><td valign="top" style="padding:0 12px 0 0;font-weight:700;color:#FFA26B;">3</td>
            <td>Answer from your <a href="<?= e($dashboard) ?>" style="color:#111111;">dashboard</a>. Every answer becomes a card you can share.</td></tr>
      </table>
    </td></tr>

    <tr><td style="font-size:14px;line-height:1.6;color:#555555;background:#F6F6F6;padding:16px 18px;">
      Want to approve questions before anyone sees them? Turn on review mode in your <a href="<?= e($settings) ?>" style="color:#111111;">settings</a>.
    </td></tr>

    <tr><td style="font-size:15px;line-height:1.6;color:#333333;padding:32px 0 6px;">
      If anything breaks, just reply to this email. I read every one.
    </td></tr>
    <tr><td style="font-size:15px;line-height:1.5;padding-bottom:40px;">
      <strong>Ejike</strong><br>
      <span style="color:#777777;">Built AskBox · <a href="https://x.com/<?= e($creatorX) ?>" style="color:#777777;">@<?= e($creatorX) ?></a></span>
    </td></tr>

    <tr><td style="border-top:1px solid #EEEEEE;padding-top:18px;font-size:12px;line-height:1.5;color:#999999;">
      You're getting this because you created an AskBox with this email address.
    </td></tr>

  </table>
</td></tr>
</table>
</body>
</html>
<?php
$html = ob_get_clean();

return compact('subject', 'text', 'html');
