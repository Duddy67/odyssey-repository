
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //Bind drop down list to some functions.
    $('#departures').change( function() { $.fn.changeDeparture(this.value); });
    $('#nb-psgr').change( function() { $.fn.changeNbPsgr(); });
    $('#dpt-cities').change( function() { $.fn.changeDepartureCity(); });
    //Initialise drop down lists.
    $.fn.setDropDownLists();
    //$('#datepicker').datepicker( { onSelect: function(date) { alert(date); selectedDate = date; } });
  });


  $.fn.setDropDownLists = function() {
    var departures = odyssey.getDepartures();
    var dateType = $('#date-type').val();
    //Get departure ids ordered by dates.
    var dptIds = odyssey.getDptIds();
    var options = '';

    $.each(departures, function(index, value) {
      if(dateType == 'standard') {
	options += '<option value="'+value[0]+'">'+value[1]+'</option>';
      }
      else { //period
	options += '<option value="'+value[0]+'">'+value[1]+' '+value[2]+'</option>';
      }
    });

    //Add options to the select tag.
    $('#departures').html(options);
    //Update the chosen plugin.
    $('#departures').trigger('liszt:updated');

    //Initialise the drop down lists with the first departure of the travel.
    $.fn.changeDeparture(dptIds[0]);
  };


  $.fn.changeDeparture = function(dptId) {
    //We need a date picker for the period date type.
    if($('#date-type').val() == 'period') {
      $.fn.setDatepicker(dptId);
    }
    else {
      //Date picker is not used with standard date type.
      $('#datepicker').css({'visibility':'hidden','display':'none'});
    }

    $.fn.setNbPsgr(dptId);
    $.fn.setDptCities(dptId);
    $.fn.displayPrices();
  };


  $.fn.changeNbPsgr = function() {
    $.fn.displayPrices();
  };


  $.fn.changeDepartureCity = function() {
    $.fn.displayPrices();
  };


  $.fn.setNbPsgr = function(dptId) {
    var nbPsgr = odyssey.getNbPsgr();
    var options = '';

    //Empty the possible previous options.
    $('#nb-psgr').empty();

    for(var i = 0; i < nbPsgr[dptId]; i++) {
      var nb = i + 1;
      options += '<option value="'+nb+'">'+nb+'</option>';
    }

    //Add options to the select tag.
    $('#nb-psgr').html(options);
    //Update the chosen plugin.
    $('#nb-psgr').trigger('liszt:updated');
  };


  $.fn.setDptCities = function(dptId) {
    var dptCities = odyssey.getDptCities();
    var options = '';

    //Empty the possible previous options.
    $('#dpt-cities').empty();

    $.each(dptCities[dptId], function(index, value) {
      options += '<option value="'+value[0]+'">'+value[1]+'</option>';
    });

    //Add options to the select tag.
    $('#dpt-cities').html(options);
    //Update the chosen plugin.
    $('#dpt-cities').trigger('liszt:updated');
  };


  $.fn.setDatepicker = function(dptId) {
    //Get both the starting and ending dates of the period.
    var periods = odyssey.getPeriods();
    //Get the period array corresponding to the given departure id.
    var period = periods[dptId];

    //Remove the previous selection (if any).
    $('#datepicker').val('');
    //Destroy the previous date picker (if any).
    $('#datepicker').datepicker('destroy');
    //Set the date range which match to the departure period.
    $('#datepicker').datepicker({dateFormat: 'yy-mm-dd', //Set date format as ISO (for MySQL)
				 minDate: new Date(period[0], period[1], period[2]),
				 maxDate: new Date(period[3], period[4], period[5]),
				 onSelect: function(date) { //Store the ISO date format in an hidden field.
							    $('#date-picker').val(date);
							  },
				 onClose: function(dateText) { //Convert date in desired format and display it (note: use Moment library).
							       var convertedDate = moment(dateText).format('MMM DD YYYY');
							       $('#datepicker').val(convertedDate);
							     },
                                  });
  };


  $.fn.displayPrices = function() {
    var dptIds = odyssey.getDptIds();
    var dptId = $('#departures').val();

    for(var i = 0; i < dptIds.length; i++) {
      if(dptIds[i] == dptId) {
	$('#departure-'+dptIds[i]).css({'visibility':'visible','display':'block'});
      }
      else {
	$('#departure-'+dptIds[i]).css({'visibility':'hidden','display':'none'});
      }
    }

    var nbPsgr = odyssey.getNbPsgr();
    var transitCityIds = odyssey.getTransCityIds();
    var selectedNb = $('#nb-psgr').val();
    var selected = $('#dpt-cities').val();

    for(var i = 0; i < nbPsgr[dptId]; i++) {
      var psgrNb = i + 1;

      if(selectedNb == psgrNb) {
	$('#price-psgr-'+psgrNb+'-'+dptId).css({'visibility':'visible','display':'block'});
	//Note: In case of price rules.
	$('#normal-price-psgr-'+psgrNb+'-'+dptId).css({'visibility':'visible','display':'block'});
      }
      else {
	$('#price-psgr-'+psgrNb+'-'+dptId).css({'visibility':'hidden','display':'none'});
	//Note: In case of price rules.
	$('#normal-price-psgr-'+psgrNb+'-'+dptId).css({'visibility':'hidden','display':'none'});
      }
    }

    for(var i = 0; i < transitCityIds.length; i++) {
      if(selected == transitCityIds[i]) {
	$('#transitcity-'+dptId+'-'+transitCityIds[i]).css({'visibility':'visible','display':'block'});

	for(var j = 0; j < nbPsgr[dptId]; j++) {
	  var psgrNb = j + 1;

	  if(selectedNb == psgrNb) {
	    $('#transitcity-price-psgr-'+psgrNb+'-'+dptId+'-'+transitCityIds[i]).css({'visibility':'visible','display':'block'});
	  }
	  else {
	    $('#transitcity-price-psgr-'+psgrNb+'-'+dptId+'-'+transitCityIds[i]).css({'visibility':'hidden','display':'none'});
	  }
	}
      }
      else {
	$('#transitcity-'+dptId+'-'+transitCityIds[i]).css({'visibility':'hidden','display':'none'});
      }
    }
  };

})(jQuery);
