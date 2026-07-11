var dateFormatter = (function() {
  var df = {};

  df.getTodayDate = function(date) {
    var today = moment().utcOffset('-0500');
    var inputDate = moment(date).utcOffset('-0500');
    var outputDate;

    if (today.format('YYYYMMDD') == inputDate.format('YYYYMMDD')) {
      outputDate = 'Today - ';
    }
    else {
      outputDate = inputDate.format('MMMM D - ');
    }
    outputDate = outputDate + inputDate.format('hh:mm A');

    return outputDate;
  }

  return df;
})();
