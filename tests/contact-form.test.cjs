const { readFileSync } = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = readFileSync(process.argv[2] || 'js/contact-form.js', 'utf8');

function setup(response) {
  let handler, requests = 0, resets = 0, cleared = 0;
  const button = { disabled: false };
  const status = { hidden: true, className: '', textContent: '' };
  const form = {
    elements: { Field5: { required: true } }, action: 'https://example.invalid/sendMail.php',
    querySelector: selector => selector === '.quote-status' ? status : button,
    reportValidity: () => true,
    addEventListener: (_, callback) => { handler = callback; },
    reset: () => { cleared++; }
  };
  class FormDataStub { set(key, value) { this[key] = value; } }
  vm.runInNewContext(source, {
    document: { querySelector: () => form }, window: {}, FormData: FormDataStub,
    fetch: async (url, options) => {
      requests++;
      assert.equal(url, form.action);
      if (response instanceof Error) throw response;
      return response;
    }
  });
  return { submit: () => handler({ preventDefault() {} }), button, status,
    counts: () => ({ requests, resets, cleared }) };
}
const json = (status, result) => ({ status, ok: status >= 200 && status < 300,
  headers: { get: () => 'application/json; charset=utf-8' }, json: async () => result });

(async () => {
  for (const code of [403, 409]) {
    const state = setup({ status: code, headers: { get: () => 'text/html' },
      json: async () => { throw new Error('Must not parse a host challenge as JSON'); } });
    await state.submit();
    assert.match(state.status.textContent, /could not accept/);
    assert.equal(state.counts().cleared, 0);
    assert.equal(state.button.disabled, false);
  }
  const html = setup({ status: 200, headers: { get: () => 'text/html' } });
  await html.submit(); assert.match(html.status.textContent, /could not be confirmed/);
  assert.equal(html.counts().cleared, 0);
  const failed = setup(json(502, { success: false, message: 'Mail unavailable.' }));
  await failed.submit(); assert.equal(failed.status.textContent, 'Mail unavailable.');
  assert.equal(failed.counts().cleared, 0);
  const success = setup(json(200, { success: true, message: 'Sent.' }));
  await success.submit(); assert.equal(success.status.className, 'quote-status is-success');
  assert.equal(success.counts().cleared, 1);
  const network = setup(new Error('Network failure'));
  await network.submit(); assert.equal(network.counts().cleared, 0);
  assert.equal(network.button.disabled, false);
  const duplicate = setup(json(200, { success: true }));
  await Promise.all([duplicate.submit(), duplicate.submit()]);
  assert.equal(duplicate.counts().requests, 1);
  console.log('PASS: host challenge, HTML response, SMTP failure, success, network failure and duplicate submission.');
})().catch(error => { console.error(error); process.exitCode = 1; });
