# Contact mail deployment

The branch prepares code; production publishing still requires merging into main and running the cPanel deployment. Do not deploy a feature branch to production with the static script.

## One-time server setup
1. Back up public_html/sendMail.php and existing vendor dependencies.
2. PHPMailer must be loadable from public_html/vendor/autoload.php. If vendor already exists, preserve its dependencies and have the host install PHPMailer using that existing Composer project. Do not replace vendor blindly.
3. Set MDHI_SMTP_PASSWORD in the PHP web runtime, or create /home1/mdhiglob/mdhi-mail-config.php OUTSIDE public_html, readable only by the hosting account. Return an array with MDHI_SMTP_HOST, MDHI_SMTP_USERNAME, MDHI_SMTP_PASSWORD. Never commit real credentials. The handler reads dirname(__DIR__)/mdhi-mail-config.php.
4. Match SMTP host, account and authentication method to the provider. The existing default is smtp.office365.com:587 with STARTTLS and username/password; provider policy may require OAuth or another transactional SMTP service. A correct password alone does not guarantee authentication.
5. Ask Bluehost to inspect ModSecurity rule IDs blocking legitimate POST /sendMail.php. Do not disable global protection.

## Deployment
The deployment now allowlists only sendMail.php alongside static files, backs it up using the existing snapshot process, and checks PHP syntax plus PHPMailer before publishing. All other PHP and credentials remain excluded. Preview uses its own target/vendor and parent config path; configure separately before use.

## Verification
- Empty POST must return HTTP 422 JSON, not HTML or a redirect.
- Valid POST without phone must pass validation.
- Successful SMTP acceptance returns success:true; failures keep the form filled.
- HTTP 406 is reported as a server block, never success.
- Confirm receipt in info@mdhiglobal.com; SMTP acceptance does not prove inbox delivery.
- Review the private PHP error log for configuration/send failures. No passwords or message bodies are logged.
