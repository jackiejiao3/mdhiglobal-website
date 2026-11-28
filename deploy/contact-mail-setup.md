# Contact mail repair — draft, configuration required

This change repairs a mismatch between the current AJAX enquiry form and the older server-only mail handler. It does not remove, bypass or reconfigure Bluehost security checks. HTTP 409 with a `humans_21909` script remains a separate host-level issue.

## Do not deploy until configured

1. Confirm access to the original Google reCAPTCHA registration. This implementation uses reCAPTCHA v2 checkbox: the public Site key in `mdhicont.html` and private Secret key must belong to the same registration and allow `mdhiglobal.com` and `www.mdhiglobal.com`. If the existing registration is a different reCAPTCHA type, adapt the integration first. The draft placeholder deliberately prevents sending.
2. Back up the existing production `sendMail.php` outside `public_html`. Do not put its credentials in GitHub or attach an unredacted copy to a pull request.
3. Copy `deploy/mdhi-mail-config.example.php` to `/home1/mdhiglob/mdhi-mail-config.php`, outside both `public_html` and the repository. Fill in the current SMTP username, password and matching reCAPTCHA Secret on Bluehost only. Restrict its permissions to the account owner where the PHP runtime permits it. The handler expects the existing Microsoft SMTP service with STARTTLS on port 587; verify that this matches the actual server configuration before replacing the handler. A present configuration does not prove SMTP authentication is working.
4. Confirm the existing `public_html/vendor/autoload.php` loads BOTH `PHPMailer\PHPMailer\PHPMailer` and `ReCaptcha\ReCaptcha`, and that the latter supports `verify()` and response `getHostname()`. The file's presence alone does not confirm dependencies are installed correctly. The backend supports PHP 7+; use a currently supported PHP runtime.
5. Replace the public Site-key placeholder in `mdhicont.html` before taking this PR out of draft. No secrets belong in that file.
6. Publish the PHP handler and updated static form together in a maintenance window. The existing `deploy/deploy.sh` intentionally excludes PHP: merging this PR and running Deploy alone does NOT update `public_html/sendMail.php`. Upload the reviewed handler manually to that exact path and publish the static HTML/JS through the existing deployment. Do not expand the deployment allowlist or overwrite unrelated backend files.

## Validation without sending an email

- Local front-end checks: `node --check js/contact-form.js` and `node tests/contact-form.test.cjs`.
- On a PHP-enabled machine: `php -l sendMail.php` and `php -l deploy/mdhi-mail-config.example.php`.
- In Bluehost Terminal, check class availability locally through the existing autoloader; never print passwords or the completed configuration file.
- A GET request should return JSON with HTTP 405; an empty POST should return JSON with HTTP 422 before reading configuration or sending mail. If host security returns 409 or challenge HTML, stop testing the endpoint and resolve the false positive with the host. Do not set challenge cookies in application code, rename endpoints to evade rules, or disable protection.

## Receipt test after deployment

The owner should send one clearly labelled test enquiry and confirm actual receipt at `info@mdhiglobal.com`, then use Reply to confirm it addresses the submitted visitor email. JSON success means the SMTP service accepted the message; inbox delivery must still be verified. No automatic resend is performed after uncertain responses. Failed attempts preserve entered fields; reCAPTCHA is reset because tokens are single-use.

## Behaviour

- Required fields, email and length bounds are validated before mail handling.
- CAPTCHA is mandatory and verified server-side against an explicit hostname allowlist; missing keys, dependencies or verification failures never send mail.
- All application responses use JSON; no success or failure redirects.
- SMTP debug output stays off. Private error logs contain short diagnostic codes, not credentials or enquiry content.
- Form responses safely use `textContent`. Non-JSON responses and 403/409 responses do not show success or erase the visitor's enquiry.
- Recipient remains `info@mdhiglobal.com`; Reply-To is the visitor's validated email.

## Rollback

Restore the backed-up handler and matching previous static page/JS together. Keep the private configuration outside the web root. Do not use a public `.bak` file containing SMTP credentials.
