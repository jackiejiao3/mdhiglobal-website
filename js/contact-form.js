(() => {
  'use strict';
  const form = document.querySelector('.quote-form');
  if (!form) return;
  form.elements.Field5.required = false;
  const button = form.querySelector('button[type="submit"]');
  const status = form.querySelector('.quote-status');
  let sending = false;
  function feedback(message, kind) {
    status.hidden = false;
    status.className = 'quote-status' + (kind ? ' is-' + kind : '');
    status.textContent = message;
  }
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (sending || !form.reportValidity()) return;
    const body = new FormData(form);
    sending = true;
    button.disabled = true;
    feedback('Sending your enquiry…');
    try {
      const response = await fetch(form.action, {
        method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json' }
      });
      // A host-level browser challenge is not the mail handler's JSON response.
      if (response.status === 409 || response.status === 403) {
        feedback('Our website could not accept this submission. Please email your enquiry to info@mdhiglobal.com.', 'error');
        return;
      }
      if (!(response.headers.get('Content-Type') || '').toLowerCase().includes('application/json')) {
        feedback('Your enquiry could not be confirmed. Please email info@mdhiglobal.com before submitting again.', 'error');
        return;
      }
      const result = await response.json();
      if (!response.ok || !result || result.success !== true) {
        feedback(result && typeof result.message === 'string' ? result.message :
          'Your enquiry could not be sent. Please email info@mdhiglobal.com.', 'error');
        return;
      }
      feedback(typeof result.message === 'string' ? result.message : 'Your enquiry has been sent. Thank you.', 'success');
      form.reset();
    } catch (_) {
      feedback('Your enquiry could not be confirmed. Please email info@mdhiglobal.com before submitting again.', 'error');
    } finally {
      sending = false;
      button.disabled = false;

    }
  });
})();
