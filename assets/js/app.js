/**
 * PBMS vanilla JS enhancement layer.
 * The app is fully functional server-side (progressive enhancement):
 *  - confirm() guards on destructive POST buttons
 *  - auto-close dropdown forms after successful submit navigation
 *  - autofocus first field in opened dropdown forms
 */
(function () {
  'use strict';

  // Confirmation guard for destructive actions.
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-confirm]');
    if (btn && !window.confirm(btn.getAttribute('data-confirm') || 'Are you sure?')) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, true); // capture: runs before the submit event fires

  // Close any open <details> dropdowns when clicking elsewhere.
  document.addEventListener('click', function (e) {
    document.querySelectorAll('details.dropdown[open]').forEach(function (d) {
      if (!d.contains(e.target)) { d.removeAttribute('open'); }
    });
  });

  // Focus the first input of a dropdown form when it opens.
  document.querySelectorAll('details.dropdown').forEach(function (d) {
    d.addEventListener('toggle', function () {
      if (d.open) {
        var first = d.querySelector('input:not([type=hidden]), select, textarea');
        if (first) { first.focus(); }
      }
    });
  });

  // Client-side sanity check: payment amount ≤ balance (server re-validates).
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches('form[action*="billing/payments"]')) return;
    var amount = form.querySelector('[name=amount_xaf]');
    var max = amount ? parseInt(amount.getAttribute('max'), 10) : NaN;
    if (amount && max && parseInt(amount.value, 10) > max) {
      e.preventDefault();
      window.alert('Payment cannot exceed the outstanding balance.');
    }
  });
})();
