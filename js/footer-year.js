/* Keep the shared copyright end year current; static HTML remains readable without JS. */
(function () {
  'use strict';
  var year = String(Math.max(2026, new Date().getFullYear()));
  document.querySelectorAll('[data-copyright-year]').forEach(function (element) {
    element.textContent = year;
  });
}());
