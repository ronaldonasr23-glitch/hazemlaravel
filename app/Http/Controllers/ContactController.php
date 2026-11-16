<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        $data = $request->validate([
            'your-name'    => 'nullable|string|max:200',
            'your-email'   => 'required|email|max:200',
            'your-subject' => 'nullable|string|max:300',
            'your-message' => 'nullable|string|max:5000',
        ], [], [
            'your-email' => 'email',
        ]);

        // The floating chat widget sends only email/subject/message, so fall
        // back to the email address when no name is provided.
        $name = $data['your-name'] ?? $request->input('your-name') ?: $data['your-email'];

        $message = $data['your-message']
            ?? $request->input('message')
            ?? $request->input('textarea')
            ?? '';

        $subject = $data['your-subject'] ?? null;

        $recipients = $this->recipients();

        // 1) Notify the company of the new submission.
        $body = "New contact form submission\n\n"
            . 'Name: ' . $name . "\n"
            . 'Email: ' . $data['your-email'] . "\n"
            . 'Subject/Phone: ' . ($subject ?? '-') . "\n\n"
            . "Message:\n" . $message;

        Mail::raw($body, function ($mail) use ($recipients, $data, $name, $subject) {
            $mail->to($recipients)
                ->replyTo($data['your-email'], $name)
                ->subject('Website contact: ' . ($subject ?? $name));
        });

        // 2) Send an auto-reply confirmation to the visitor (best effort).
        $this->sendConfirmation($data['your-email'], $name, $subject, $message);

        return response()->json([
            'status'  => 'mail_sent',
            'message' => 'Thank you for your message. It has been sent.',
        ]);
    }

    /**
     * Branded "we received your message" auto-reply to the sender.
     * Never let a failure here break the main submission.
     */
    protected function sendConfirmation(string $email, string $name, ?string $subject, string $message): void
    {
        try {
            $html = $this->confirmationHtml($name, $subject, $message);

            Mail::html($html, function ($mail) use ($email) {
                $mail->to($email)
                    ->subject('We\'ve received your message — ' . $this->companyName());
            });
        } catch (\Throwable $e) {
            Log::warning('Contact confirmation email failed: ' . $e->getMessage());
        }
    }

    /**
     * All inboxes that should receive a contact submission: the main
     * contact_email plus any additional recipients set in the dashboard
     * (separated by new lines, commas or semicolons). Invalid entries are
     * dropped and duplicates removed.
     */
    protected function recipients(): array
    {
        $primary = (string) Setting::get('contact_email', 'admin@chemgrowgas.test');
        $extra   = (string) Setting::get('contact_email_extra', '');

        $all = array_merge([$primary], preg_split('/[\s,;]+/', $extra, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        $valid = [];
        foreach ($all as $email) {
            $email = trim($email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $valid[strtolower($email)] = $email; // de-dupe, keep original case
            }
        }

        return array_values($valid) ?: ['admin@chemgrowgas.test'];
    }

    protected function companyName(): string
    {
        return Setting::get('mail_from_name')
            ?: Setting::get('site_name')
            ?: config('app.name', 'Grow Gas');
    }

    protected function confirmationHtml(string $name, ?string $subject, string $message): string
    {
        $company  = e($this->companyName());
        $greeting = e($name);
        $site = rtrim((string) config('app.url'), '/');
        // Never expose the local dev URL (chem.test / localhost) in emails.
        if ($site === '' || preg_match('#(localhost|127\.0\.0\.1|\.test)$#i', parse_url($site, PHP_URL_HOST) ?: $site)) {
            $site = 'https://chemgrowgas.com';
        }
        $siteHost = preg_replace('#^https?://#', '', $site);

        // Contact identity (shown in the footer).
        $phone    = '+20 100 0018804';
        $phoneRaw = '201000018804';
        $mail     = Setting::get('contact_email') ?: 'info@growgas.com';
        $address  = '9 St 314 from Fawzy Moaz St, Smouha, Alexandria';

        $recap = '';
        if ($subject || trim($message) !== '') {
            $recap = '<tr><td style="padding:0 32px 8px;">'
                . '<div style="background:#f4f7f5;border-left:4px solid #ff5316;border-radius:6px;padding:14px 16px;">'
                . ($subject
                    ? '<div style="font-size:13px;color:#555;margin-bottom:6px;"><strong style="color:#ff5316;">Subject:</strong> ' . e($subject) . '</div>'
                    : '')
                . ($message !== ''
                    ? '<div style="font-size:13px;color:#555;line-height:1.6;white-space:pre-wrap;"><strong style="color:#ff5316;">Your message:</strong><br>' . nl2br(e($message)) . '</div>'
                    : '')
                . '</div></td></tr>';
        }

        return <<<HTML
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#eef1f0;font-family:Arial,Helvetica,sans-serif;color:#2b2f33;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef1f0;padding:24px 12px;">
    <tr><td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 6px 24px rgba(0,0,0,.08);">
        <!-- Header -->
        <tr><td style="background:#192437;padding:26px 32px;text-align:center;">
          <div style="color:#ffffff;font-size:26px;font-weight:800;letter-spacing:.5px;">{$company}</div>
          <div style="color:#9fb0c3;font-size:12px;margin-top:4px;">Integrated solutions of Chemical Engineering &amp; Gas industries</div>
        </td></tr>
        <!-- Body -->
        <tr><td style="padding:30px 32px 10px;">
          <div style="display:inline-block;background:#fff0ea;color:#ff5316;font-size:13px;font-weight:700;padding:6px 14px;border-radius:999px;">✔ Message received</div>
          <h1 style="font-size:22px;color:#192437;margin:16px 0 8px;">Thank you, {$greeting}!</h1>
          <p style="font-size:15px;line-height:1.7;color:#4b5563;margin:0;">
            We've received your message and a member of our team will get back to you as soon as possible —
            usually within one business day. We appreciate you reaching out to {$company}.
          </p>
        </td></tr>
        {$recap}
        <tr><td style="padding:14px 32px 26px;">
          <p style="font-size:14px;line-height:1.7;color:#4b5563;margin:0;">
            If your request is urgent, you can reach us directly on the details below.
          </p>
        </td></tr>
        <!-- Contact footer -->
        <tr><td style="background:#f8faf9;border-top:1px solid #e9edeb;padding:22px 32px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td style="font-size:13px;color:#4b5563;line-height:1.9;">
                <strong style="color:#ff5316;">Call / WhatsApp:</strong> <a href="tel:+{$phoneRaw}" style="color:#2b2f33;text-decoration:none;">{$phone}</a><br>
                <strong style="color:#ff5316;">Email:</strong> <a href="mailto:{$mail}" style="color:#2b2f33;text-decoration:none;">{$mail}</a><br>
                <strong style="color:#ff5316;">Address:</strong> {$address}<br>
                <strong style="color:#ff5316;">Website:</strong> <a href="{$site}" style="color:#2b2f33;text-decoration:none;">{$siteHost}</a>
              </td>
            </tr>
          </table>
        </td></tr>
        <tr><td style="background:#192437;padding:14px 32px;text-align:center;">
          <div style="color:#9fb0c3;font-size:12px;">This is an automated confirmation. &copy; {$company}</div>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
