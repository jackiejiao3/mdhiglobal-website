(() => {
  const form = document.querySelector('.quote-form');
  if (!form) return;
  const phone = form.elements.Field5;
  phone.required = false;
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
      const result = await response.json();
      if (!response.ok || result.success !== true) throw new Error(result.message || 'Sending failed.');
      status.classList.add('is-success');
      status.textContent = result.message;
      form.reset();
    } catch (error) {
      status.classList.add('is-error');
      status.textContent = 'Your enquiry could not be confirmed. Please try again or email info@mdhiglobal.com.';
    } finally {
      button.disabled = false;
    }
  });
})();
