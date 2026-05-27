<?php
// Run with: wp --path=/path/to/wp eval-file bin/set-cf7-mail.php

$form_id = 919;

// ── Admin notification ──────────────────────────────────────────────────────

$admin_body = '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:40px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;">

<tr><td style="background-color:#111111;padding:24px 32px;border-radius:4px 4px 0 0;">
<p style="margin:0;color:#ffffff;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;font-weight:600;">New contact message</p>
</td></tr>

<tr><td style="background-color:#ffffff;padding:32px;border:1px solid #e8e8e8;border-top:none;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">

<tr><td style="padding-bottom:20px;border-bottom:1px solid #f0f0f0;">
<p style="margin:0 0 4px 0;font-size:11px;color:#888888;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">From</p>
<p style="margin:0;font-size:16px;color:#111111;font-weight:600;">[your-name]</p>
<p style="margin:2px 0 0 0;font-size:14px;color:#555555;">[your-email]</p>
</td></tr>

<tr><td style="padding:20px 0;border-bottom:1px solid #f0f0f0;">
<p style="margin:0 0 4px 0;font-size:11px;color:#888888;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Subject</p>
<p style="margin:0;font-size:15px;color:#111111;">[your-subject]</p>
</td></tr>

<tr><td style="padding-top:20px;">
<p style="margin:0 0 8px 0;font-size:11px;color:#888888;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Message</p>
<p style="margin:0;font-size:15px;color:#333333;line-height:1.7;white-space:pre-line;">[your-message]</p>
</td></tr>

</table>
</td></tr>

<tr><td style="background-color:#f9f9f9;padding:16px 32px;border:1px solid #e8e8e8;border-top:none;border-radius:0 0 4px 4px;">
<p style="margin:0;font-size:12px;color:#aaaaaa;">Sent via contact form on your website.</p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>';

$mail = array(
	'active'              => true,
	'subject'             => '[your-subject] — New Contact',
	'sender'              => '[your-name] <[your-email]>',
	'recipient'           => get_option( 'admin_email' ),
	'body'                => $admin_body,
	'additional_header'   => 'Reply-To: [your-name] <[your-email]>',
	'additional_attaches' => '',
	'use_html'            => true,
	'exclude_blank'       => false,
);

update_post_meta( $form_id, '_mail', $mail );
echo "Admin mail updated.\n";

// ── User auto-reply ─────────────────────────────────────────────────────────

$user_body = '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:40px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;">

<tr><td style="background-color:#111111;padding:32px 32px 28px;border-radius:4px 4px 0 0;">
<p style="margin:0 0 4px 0;color:#ffffff;font-size:28px;font-weight:700;line-height:1.1;">[skate_unterschrift]</p>
<p style="margin:0 0 20px 0;color:rgba(255,255,255,0.5);font-size:14px;">[skate_website]</p>
<p style="margin:0;color:rgba(255,255,255,0.4);font-size:11px;letter-spacing:0.12em;text-transform:uppercase;font-weight:600;">Message received</p>
</td></tr>

<tr><td style="background-color:#ffffff;padding:32px;border:1px solid #e8e8e8;border-top:none;">

<p style="margin:0 0 12px 0;font-size:22px;font-weight:700;color:#111111;line-height:1.2;">Hi [your-name],</p>
<p style="margin:0 0 28px 0;font-size:15px;color:#444444;line-height:1.7;">Thank you for reaching out. We received your message and will get back to you within one business day.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9f9f9;border:1px solid #eeeeee;border-radius:4px;margin-bottom:32px;">
<tr><td style="padding:20px 24px;">
<p style="margin:0 0 6px 0;font-size:11px;color:#888888;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Your message</p>
<p style="margin:0;font-size:14px;color:#555555;line-height:1.7;white-space:pre-line;">[your-message]</p>
</td></tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #eeeeee;">
<tr><td style="padding-top:24px;padding-right:24px;width:50%;vertical-align:top;">
<p style="margin:0 0 4px 0;font-size:11px;color:#888888;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Email</p>
<p style="margin:0;font-size:14px;color:#333333;">[skate_email]</p>
</td><td style="padding-top:24px;width:50%;vertical-align:top;">
<p style="margin:0 0 4px 0;font-size:11px;color:#888888;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">Phone</p>
<p style="margin:0;font-size:14px;color:#333333;">[skate_phone]</p>
</td></tr>
</table>

</td></tr>

<tr><td style="background-color:#f9f9f9;padding:16px 32px;border:1px solid #e8e8e8;border-top:none;border-radius:0 0 4px 4px;">
<p style="margin:0;font-size:12px;color:#aaaaaa;">[skate_address_street], [skate_address_city]</p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>';

$mail_2 = array(
	'active'              => true,
	'subject'             => 'We received your message',
	'sender'              => '[skate_site_name] <[skate_email]>',
	'recipient'           => '[your-email]',
	'body'                => $user_body,
	'additional_header'   => '',
	'additional_attaches' => '',
	'use_html'            => true,
	'exclude_blank'       => false,
);

update_post_meta( $form_id, '_mail_2', $mail_2 );
echo "User auto-reply updated.\n";
