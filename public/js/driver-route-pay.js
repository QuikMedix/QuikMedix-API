/*
 * The Completed button on a driver's route page. The pay choice (Half, Full, Flat rate and its
 * amount) sits in the route form; closing the route is a separate form, so the choice is copied
 * into it before it is sent.
 */
(function (root) {
  'use strict';

  var doc = root.document;
  var FLAT_RATE = '1';

  /** Why the route cannot be closed with this choice, or null when it can. */
  function problem(type, amount) {
    if (!type) {
      return 'Choose Half, Full or Flat rate first.';
    }
    if (type === FLAT_RATE && !(parseFloat(amount) > 0)) {
      return 'Enter the amount to pay the driver.';
    }
    return null;
  }

  function closeRoute() {
    var chosen = doc.querySelector('.tariff [name="type_pay"]:checked');
    var amount = doc.querySelector('.tariff [name="pay_value"]');
    var form = doc.getElementById('close_route');
    var type = chosen ? chosen.value : '';
    var stopped = problem(type, amount ? amount.value : '');
    if (stopped !== null) {
      root.alert(stopped);
      return false;
    }
    if (!root.confirm('Credit the driver for their completed stops at this rate?')) {
      return false;
    }
    form.querySelector('[name="type_pay"]').value = type;
    form.querySelector('[name="pay_value"]').value = type === FLAT_RATE ? amount.value : '';
    form.submit();
    return true;
  }

  root.driverRoutePay = { problem: problem, closeRoute: closeRoute };
  var button = doc.getElementById('close-route-button');
  if (button) {
    button.addEventListener('click', closeRoute);
  }
})(window);
