# Rewritten contact mail handler

The enquiry form and handler now share a JSON response contract. This version does not require Google reCAPTCHA or its keys. Basic spam controls are a hidden website field, validation, browser Origin checks and locked server-side attempt limits (5 per IP per 15 minutes; 100 total per hour). These are not equivalent to a managed CAPTCHA service; sophisticated low-volume spam can pass. Private rate state stores timestamps and HMAC-hashed IPs, not enquiry content or raw IP addresses. Do not change host/WAF protection to deploy this code.

## Configure on Bluehost

1. Download a private backup of the current production `sendMail.php`; keep it outside `public_html` and GitHub. Never publish a `.bak` containing passwords.
2. Copy `deploy/mdhi-mail-config.example.php` to `/home1/mdhiglob/mdhi-mail-config.php`. Fill in only the CURRENT SMTP username and password, on Bluehost, using the values from the existing handler. Keep valid quoted PHP strings (escape single quotes and backslashes if needed). Do not upload the completed config to GitHub or chat. Restrict its permissions to the account owner where the PHP runtime permits it.
3. Confirm `public_html/vendor/autoload.php` loads `PHPMailer\PHPMailer\PHPMailer`. No Google ReCaptcha dependency is needed. The handler uses the current Microsoft SMTP service, STARTTLS and port 587: verify these non-secret settings match the working mailbox configuration. Credentials being present does not establish that the service permits this authentication method.
4. PHP must be able to create `/home1/mdhiglob/mdhi-contact-state/` (0700) and its rate-state file (0600), outside the web root. If storage or locking fails, the handler refuses to send. Use PHP 7+ syntax on a currently supported PHP runtime.
5. After checking PHP syntax, replace `/home1/mdhiglob/public_html/sendMail.php` manually with the reviewed file. The existing deployment intentionally excludes PHP. Do not broaden the deployment allowlist or overwrite other backend files. The rewritten handler also accepts the current static form, which has no honeypot field, so PHP can be installed before publishing the updated HTML/JS.
6. Merge/publish the matching static page, JS and CSS through the normal GitHub → Bluehost workflow. The new hidden field must remain empty for legitimate submissions. No Site key or Google account is required.

## Checks

- Frontend: `node --check js/contact-form.js`; `node tests/contact-form.test.cjs`.
- Backend without network mail: `python3 tests/contact-mail.test.py` on a machine with PHP CLI. This copies the actual handler to an isolated directory with a fake SMTP class; no production secrets or network delivery are used. It covers JSON status codes, invalid inputs, honeypot, browser Origin, individual/global limits, private storage failures, escaped HTML, Reply-To and failed SMTP.
- PHP lint: `php -l sendMail.php` and `php -l deploy/mdhi-mail-config.example.php`.
- On Bluehost, GET should return JSON/405; empty POST should return JSON/422 before config or mail handling. If host security returns 409/challenge HTML, stop and resolve the host false positive. Do not set challenge cookies in app code, rename endpoints to evade rules, or disable security protections.
- Once deployed, the owner should send ONE labelled test and confirm receipt at `info@mdhiglobal.com`, including spam folders. Reply should address the visitor's email. SMTP acceptance does not guarantee inbox delivery. No automatic retry is made after uncertain network responses.

## Current limitations

Production SMTP login/delivery and host security acceptance are unverified. PHP CLI was unavailable in the implementation environment, so PHP lint/backend runtime tests must be run before replacing the server file. JavaScript syntax and frontend regression checks passed. No test email was sent.

The observed HTTP 409 with `humans_21909` is a separate host-level challenge and is not fixed by this handler. The existing endpoint name remains unchanged.

## Rollback

Restore the privately backed-up handler and matching old static files if needed. Leave the new private configuration and rate-state directory outside the web root. They do not contain the email body. Shared NAT addresses are counted as one IP; proxy address mapping must be configured correctly by the host. Do not trust arbitrary forwarded IP headers to bypass rate limits.
