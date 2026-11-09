/* Hover, keyboard and touch selection share one map state. No external libraries. */
(function () {
  'use strict';
  document.querySelectorAll('[data-location-map]').forEach(function (map) {
    var stage = map.querySelector('.location-map-stage');
    var live = map.querySelector('.location-map-live');
    var image = live.querySelector('img');
    var controls = map.querySelector('.location-map-controls');
    var select = controls.querySelector('select');
    var status = map.querySelector('.location-map-status');
    var pins = Array.from(map.querySelectorAll('[data-location]'));
    var callouts = Array.from(map.querySelectorAll('[data-callout]'));
    var selected = '';
    var hovered = '';
    var focused = '';
    function paint() {
      var active = hovered || focused || selected;
      pins.forEach(function (pin) {
        pin.classList.toggle('is-active', pin.dataset.location === active);
        pin.setAttribute('aria-pressed', String(pin.dataset.location === selected));
      });
      callouts.forEach(function (box) { box.classList.toggle('is-active', box.dataset.callout === active); });
      var pin = pins.find(function (item) { return item.dataset.location === active; });
      var message = pin ? pin.getAttribute('aria-label') : 'No location selected.';
      if (status.textContent !== message) status.textContent = message;
      select.value = selected;
    }
    function clear() { selected = ''; hovered = ''; focused = ''; paint(); }
    pins.forEach(function (pin) {
      pin.addEventListener('pointerenter', function (event) {
        if (event.pointerType === 'touch') return;
        hovered = pin.dataset.location; paint();
      });
      pin.addEventListener('pointerleave', function () { hovered = ''; paint(); });
      pin.addEventListener('focus', function () { focused = pin.dataset.location; paint(); });
      pin.addEventListener('blur', function () { focused = ''; paint(); });
      pin.addEventListener('click', function () {
        var key = pin.dataset.location;
        selected = selected === key ? '' : key;
        hovered = ''; focused = ''; paint();
      });
    });
    stage.addEventListener('pointerdown', function (event) {
      if (!event.target.closest('[data-location]')) {
        if (stage.contains(document.activeElement)) document.activeElement.blur();
        clear();
      }
    });
    select.addEventListener('change', function () {
      selected = select.value; hovered = ''; focused = ''; paint();
      var pin = pins.find(function (item) { return item.dataset.location === selected; });
      if (pin) {
        var scroll = map.querySelector('.location-map-scroll');
        scroll.scrollLeft = pin.offsetLeft - scroll.clientWidth / 2 + pin.offsetWidth / 2;
      }
    });
    map.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') { clear(); event.stopPropagation(); }
    });
    function enable() {
      if (!image.naturalWidth) return;
      map.querySelector('.location-map-fallback').hidden = true;
      live.hidden = false; controls.hidden = false; status.hidden = false;
      map.classList.add('is-ready');
    }
    image.addEventListener('load', enable, { once: true });
    image.loading = 'eager';
    if (image.complete) enable();
    paint();
  });
}());
