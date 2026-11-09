/* Progressive enhancement: image links still open full-size without JavaScript. */
(function () {
  'use strict';
  var viewer = document.getElementById('infographic-viewer');
  if (!viewer || typeof viewer.showModal !== 'function') return;
  var title = document.getElementById('infographic-viewer-title');
  var image = document.getElementById('infographic-viewer-image');
  var original = document.getElementById('infographic-viewer-original');
  var opener = null;
  document.querySelectorAll('a[data-infographic-title]').forEach(function (link) {
    link.setAttribute('aria-haspopup', 'dialog');
    link.addEventListener('click', function (event) {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
      event.preventDefault();
      opener = link;
      title.textContent = link.getAttribute('data-infographic-title');
      image.alt = title.textContent + ' infographic';
      image.src = link.href;
      original.href = link.href;
      viewer.showModal();
      document.documentElement.classList.add('infographic-viewer-open');
      viewer.querySelector('.infographic-dialog-scroll').scrollTop = 0;
    });
  });
  viewer.querySelector('[data-close-viewer]').addEventListener('click', function () { viewer.close(); });
  viewer.addEventListener('click', function (event) {
    if (event.target !== viewer) return;
    var bounds = viewer.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) viewer.close();
  });
  viewer.addEventListener('close', function () {
    document.documentElement.classList.remove('infographic-viewer-open');
    image.removeAttribute('src');
    if (opener) opener.focus({ preventScroll: true });
  });
}());
