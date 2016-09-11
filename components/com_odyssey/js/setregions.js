
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //As this script is used with both registration and edit customer forms there is a
    //slight difference in the id name. So we test and set the extra id part according to
    //the form we're in.
    var extraId = '';
    if($('#jform_odysseyprofile_country_code').val() !== undefined) {
      extraId = 'odysseyprofile_';
    }

    $('#jform_'+extraId+'country_code').change( function() { $.fn.setRegions(this.value, extraId); });

    $.fn.initRegions(extraId);
  });


  $.fn.setRegions = function(country_code, extraId) {
    var regions = odyssey.getRegions();
    var length = regions.length;
    var options = '<option value="">'+Joomla.JText._('COM_ODYSSEY_OPTION_SELECT')+'</option>';

    var regex = new RegExp('^'+country_code);
    //Create an option tag for each region.
    for(var i = 0; i < length; i++) {
      //Test the regex to get only regions from the selected country.
      if(regex.test(regions[i].code)) {
	options += '<option value="'+regions[i].code+'">'+regions[i].text+'</option>';
      }
    }

    //Empty the previous options.
    $('#jform_'+extraId+'region_code').empty();
    //Add the new region options to the select tag.
    $('#jform_'+extraId+'region_code').append(options);

    //Use Chosen jQuery plugin.
    $('#jform_'+extraId+'region_code').trigger('liszt:updated');
  };


  $.fn.initRegions = function(extraId) {
    //Get the value of the previously selected regions if any.
    var regionCode = $('#hidden-region-code').val();

    //Empty the options previously set by the regionlist field function.
    $('#jform_'+extraId+'region_code').empty();
    $('#jform_'+extraId+'region_code').trigger('liszt:updated');

    if(regionCode !== undefined) {
      //Build the region option list according to the previously selected country.
      $.fn.setRegions($('#jform_'+extraId+'country_code').val(), extraId);
      //Set the region value previously selected. 
      $('#jform_'+extraId+'region_code').val(regionCode);
      $('#jform_'+extraId+'region_code').trigger('liszt:updated');
    }
  };

})(jQuery);

