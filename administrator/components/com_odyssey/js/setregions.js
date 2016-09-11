
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    $('#jform_country_code').change( function() { $.fn.setRegions(this.value); });

    $.fn.initRegions();
  });


  $.fn.setRegions = function(country_code) {
    var regions = odyssey.getRegions();
    var length = regions.length;
    var options = '<option value="">'+Joomla.JText._('COM_ODYSSEY_OPTION_SELECT')+'</option>';

    var regex = new RegExp('^'+country_code+'-');
    //Create an option tag for each region.
    for(var i = 0; i < length; i++) {
      //Test the regex to get only regions from the selected country.
      if(regex.test(regions[i].code)) {
	options += '<option value="'+regions[i].code+'">'+regions[i].text+'</option>';
      }
    }

    //Empty the previous options.
    $('#jform_region_code').empty();
    //Add the new region options to the select tag.
    $('#jform_region_code').append(options);

    //Use Chosen jQuery plugin.
    $('#jform_region_code').trigger('liszt:updated');
  };


  $.fn.initRegions = function() {
    //Get the value of the previously selected regions if any.
    var regionCode = $('#hidden-region-code').val();

    //Empty the options previously set by the regionlist field function.
    $('#jform_region_code').empty();
    $('#jform_region_code').trigger('liszt:updated');

    if(regionCode != '') {
      //Build the region option list according to the previously selected country.
      $.fn.setRegions($('#jform_country_code').val());
      //Set the region value previously selected. 
      $('#jform_region_code').val(regionCode);
      $('#jform_region_code').trigger('liszt:updated');
    }
  };

})(jQuery);

