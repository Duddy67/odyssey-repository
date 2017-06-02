
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    $('#search-btn-clear').click( function() { $.fn.clearFilters(); });
    $.fn.setFilters($('#nb-items').val());
  });

  $.fn.clearFilters = function() {
    //Enable then empty drop down lists.
    $('#filter_country').prop('disabled', false);
    $('#filter_country').val('');
    $('#filter_region').prop('disabled', false);
    $('#filter_region').val('');
    $('#filter_city').prop('disabled', false);
    $('#filter_city').val('');
    $('#filter_price').prop('disabled', false);
    $('#filter_price').val('');
    $('#filter_duration').prop('disabled', false);
    $('#filter_duration').val('');
    $('#filter_date').prop('disabled', false);
    $('#filter_date').val('');
    //Reload the form.
    $('#siteForm').submit();
  };


  $.fn.setFilters = function(nbItems) {
    //Get filter values.
    var country = $('#filter_country').val();
    var region = $('#filter_region').val();
    var city = $('#filter_city').val();
    var price = $('#filter_price').val();
    var duration = $('#filter_duration').val();
    var departure = $('#filter_date').val();

    if(region !== undefined && region !== '') {
      $('#filter_country').prop('disabled', true);
    }

    if(city !== undefined && city !== '') {
      $('#filter_country').prop('disabled', true);
      $('#filter_region').prop('disabled', true);
    }

    if(country === '' && region === '' && city === '') {
      //Don't enable departure filter as long as no filter value above is set.
      $('#filter_date').prop('disabled', true);
    }

    if($('#filter_price').children('option').length == 2) {
      //If there is only 1 value left (plus the select value) there is no need to 
      //enable the filter. Duration value can also be read in the result array.
      $('#filter_price').prop('disabled', true);
    }

    if($('#filter_duration').children('option').length == 2) {
      //If there is only 1 value left (plus the select value) there is no need to 
      //enable the filter. Duration value can also be read in the result array.
      $('#filter_duration').prop('disabled', true);
    }

    //Single result.
    if(nbItems == 1 && $('#filter_duration').children('option').length == 2) {
      $('#filter_date option[value=""]').text('- Next departures -');
      //Prevent from loading the form again.
      $('#filter_date').removeAttr('onchange');
    }

    if(nbItems == 0) {
      $('#filter_duration').prop('disabled', true);
      $('#filter_date').prop('disabled', true);
    }
  };
})(jQuery);


