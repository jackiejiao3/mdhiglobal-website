"""Run the real PHP handler against an isolated SMTP stub; never sends network mail.

Usage: python3 tests/contact-mail.test.py
Requires php CLI. All PHP dependencies and config used here are disposable fixtures.
"""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

PHP = shutil.which('php')
if not PHP:
    raise SystemExit('PHP CLI is required; backend tests were NOT run.')
source = Path(__file__).resolve().parents[1] / 'sendMail.php'
stub = r'''<?php
namespace PHPMailer\PHPMailer;
class PHPMailer {
    private $values = [];
    public function __construct($exceptions = false) {}
    public function __set($name, $value) { $this->values[$name] = $value; }
    public function isSMTP() {}
    public function isHTML($value) {}
    public function setFrom($email, $name) { $this->values['from'] = $email; }
    public function addAddress($email) { $this->values['recipient'] = $email; }
    public function addReplyTo($email) { $this->values['reply'] = $email; }
    public function send() {
        if (getenv('MDHI_TEST_FAIL') === '1') throw new \RuntimeException('stub failure');
        file_put_contents(getenv('MDHI_TEST_CAPTURE'), json_encode($this->values));
        return true;
    }
}
'''
with tempfile.TemporaryDirectory() as temp:
    root = Path(temp)
    public = root / 'public_html'
    (public / 'vendor').mkdir(parents=True)
    shutil.copy2(source, public / 'sendMail.php')
    (public / 'vendor/autoload.php').write_text(stub)
    config = root / 'mdhi-mail-config.php'
    config.write_text("<?php return ['smtp_username'=>'sender@example.invalid', 'smtp_password'=>'test-only-password'];")
    runner = public / 'runner.php'
    runner.write_text('''<?php
$_POST = json_decode(stream_get_contents(STDIN), true);
$_SERVER['REQUEST_METHOD'] = getenv('MDHI_TEST_METHOD') ?: 'POST';
$_SERVER['REMOTE_ADDR'] = getenv('MDHI_TEST_IP') ?: '192.0.2.1';
$_SERVER['HTTP_ORIGIN'] = getenv('MDHI_TEST_ORIGIN') ?: 'https://mdhiglobal.com';
register_shutdown_function(function () { echo "\\nSTATUS:" . http_response_code(); });
require __DIR__ . '/sendMail.php';
''')
    baseline = dict(Field2='Test', Field3='Visitor', Field4='visitor@example.invalid',
                    Field5='', Field6='General Contractor', Field8='Request For Quotation',
                    Field16='<script>alert(1)</script>', website='')
    env = dict(os.environ, MDHI_MAIL_CONFIG_PATH=str(config), MDHI_TEST_CAPTURE=str(root / 'mail.json'))

    def request(fields=None, **options):
        result = subprocess.run([PHP, str(runner)], input=json.dumps(baseline if fields is None else fields),
                                text=True, capture_output=True,
                                env=dict(env, **{'MDHI_TEST_' + key.upper(): value for key, value in options.items()}), check=True)
        body, status = result.stdout.rsplit('\nSTATUS:', 1)
        return int(status), json.loads(body)

    for path in [public / 'sendMail.php', config]:
        subprocess.run([PHP, '-l', str(path)], check=True, capture_output=True)
    assert request(method='GET')[0] == 405
    assert request({})[0] == 422
    assert request(origin='https://other.example')[0] == 403
    assert request(dict(baseline, website='spam.example'))[0] == 422
    assert request(dict(baseline, Field4='invalid'))[0] == 422
    assert request(dict(baseline, Field2='injected\r\nheader'))[0] == 422
    assert request(dict(baseline, Field16='x' * 4001))[0] == 422
    assert not (root / 'mail.json').exists()
    assert request()[0] == 200
    mail = json.loads((root / 'mail.json').read_text())
    assert mail['recipient'] == 'info@mdhiglobal.com'
    assert mail['reply'] == baseline['Field4']
    assert '<script>' not in mail['Body'] and '&lt;script&gt;' in mail['Body']
    assert mail['SMTPDebug'] == 0
    for _ in range(4):
        assert request()[0] == 200
    assert request()[0] == 429
    assert request(ip='192.0.2.2', fail='1')[0] == 502
    assert request(ip='192.0.2.3')[0] == 200
    state = root / 'mdhi-contact-state/rate.json'
    stored = state.read_text()
    assert '192.0.2.' not in stored and 'example.invalid' not in stored
    state.write_text(json.dumps([[int(time.time()), 'test-hash']] * 100))
    assert request(ip='192.0.2.4')[0] == 429
    state.write_text('invalid')
    assert request(ip='192.0.2.4')[0] == 503
    print('PASS: JSON contract, validation, honeypot, origin, rate limits, mail escaping, receipt address and failed SMTP.')
