(() => {
  const form = document.querySelector('.quote-form');
  if (!form) return;
  const phone = form.elements.Field5;
  if (phone) phone.required = false;
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const button = form.querySelector('button[type="submit"]');
    const status = form.querySelector('.quote-status');
    button.disabled = true;
    status.hidden = false;
    status.className = 'quote-status';
    status.textContent = 'Sending your enquiry…';
    try {
      const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
      const contentType = response.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        const error = new Error('Server response unavailable');
        error.userMessage = response.status === 406
          ? 'The server blocked this enquiry. Please email info@mdhiglobal.com or call +86 512 8278 0668.'
          : 'Your enquiry could not be confirmed. Please email info@mdhiglobal.com.';
        throw error;
      }
      const result = await response.json();
      if (!response.ok || result.success !== true) {
        const error = new Error('Sending failed');
        error.userMessage = result.message;
        throw error;
      }
      status.classList.add('is-success');
      status.textContent = result.message;
      form.reset();
    } catch (error) {
      status.classList.add('is-error');
      status.textContent = error.userMessage || 'Your enquiry could not be confirmed. Please try again or email info@mdhiglobal.com.';
    } finally {
      button.disabled = false;
    }
  });
})();
