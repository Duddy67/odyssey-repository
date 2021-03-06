
(function($) {
  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //Get required variable.
    var dptStepId = $('#jform_dpt_step_id_id').val();
    var travelId = $('#jform_id').val();
    var searchFilters = $('#search-filters').val();

    //Create container according to the filter global setting.
    if(searchFilters != 'region' && searchFilters != 'city' && searchFilters != 'region_city') {
      $('#countryfilter').getContainer();
    }

    if(searchFilters != 'country' && searchFilters != 'city' && searchFilters != 'country_city') {
      $('#regionfilter').getContainer();
    }

    if(searchFilters != 'country' && searchFilters != 'region' && searchFilters != 'country_region') {
      $('#cityfilter').getContainer();
    }

    $('#image').getContainer();

    //Set as functions the global variables previously declared.
    checkTravelData = $.fn.checkTravelData;
    createPriceTables = $.fn.createPriceTables;

    if(dptStepId !== '') {
      $.fn.createPriceTables(dptStepId);
    }

    //If the travel item exists we need to get the data of the dynamical items.
    if(travelId != 0) {
      var urlQuery = {'travel_id':travelId};

      //Ajax call which get item data previously set.
      $.ajax({
	  type: 'GET', 
	  url: 'components/com_odyssey/js/ajax/travel.php', 
	  dataType: 'json',
	  data: urlQuery,
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {
	    //Create an item type for each result retrieved from the database.
	    $.each(results.country, function(i, result) { $.fn.createItem('countryfilter', result); });
	    $.each(results.region, function(i, result) { $.fn.createItem('regionfilter', result); });
	    $.each(results.city, function(i, result) { $.fn.createItem('cityfilter', result); });
	    $.each(results.image, function(i, result) { $.fn.createItem('image', result); });
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	  }
      });
    }
  });


  $.fn.createPriceTables = function(dptStepId) {
    var travelId = $('#jform_id').val();
    var langTag = $('#lang-tag').val();
    var urlQuery = {'travel_id':travelId, 'dpt_step_id':dptStepId, 'lang_tag':langTag};

    $.ajax({
	type: 'GET', 
	url: 'components/com_odyssey/js/ajax/pricerows.php', 
	dataType: 'json',
	data: urlQuery,
	//Get results as a json array.
	success: function(results, textStatus, jqXHR) {

	  $.fn.createTableHeader('travel-prices');
	  //Create and insert a price row for each departure in the travel table.
	  $.each(results.travel, function(i, result) {
	    $.fn.createPriceRow('travel-prices', result, 'travel');
	  });

	  var stepIds = [];
	  var addonIds = [];
	  $.each(results.addons, function(i, result) {
	    //Create a new div for each step in the addons div container.
	    if(jQuery.inArray(result.step_id, stepIds) === -1) {
	      stepIds.push(result.step_id);
	      var properties = {'id':'step-'+result.step_id};
	      $('#addons').createHTMLTag('<div>', properties, 'step-price-rows');
	      //Display the step name.
	      properties = {'id':'step-title-'+result.step_id};
	      $('#step-'+result.step_id).createHTMLTag('<h2>', properties, 'step-title');
	      //$('#step-title-'+result.step_id).text(result.step_name);
	      $('#step-title-'+result.step_id).html('<span class="item-label">'+Joomla.JText._('COM_ODYSSEY_STEP_LABEL')+'</span>'+result.step_name);
	      //Reset the addon id array as the same addon can be include in different step.
	      addonIds = [];
	    }

	    //Create a new div for each addon in the step div previously created.
	    if(jQuery.inArray(result.addon_id, addonIds) === -1) {
	      addonIds.push(result.addon_id);
	      properties = {'id':'addon-'+result.step_id+'-'+result.addon_id};
	      $('#step-'+result.step_id).createHTMLTag('<div>', properties, 'addon-price-rows');

	      var addonName = result.addon_name;
	      if(result.addon_name != '' && result.addon_global == 1) {
		addonName = result.addon_name+Joomla.JText._('COM_ODYSSEY_INFO_GLOBAL_ADDON');
	      }

	      //Display the addon name.
	      properties = {'id':'addon-title-'+result.step_id+'-'+result.addon_id};
	      $('#addon-'+result.step_id+'-'+result.addon_id).createHTMLTag('<h3>', properties, 'addon-title');
	      $('#addon-title-'+result.step_id+'-'+result.addon_id).html('<span class="item-label">'+Joomla.JText._('COM_ODYSSEY_ADDON_LABEL')+'</span>'+addonName);
	      //Create a table to insert the addon price rows.
	      properties = {'id':'addon-prices-'+result.step_id+'-'+result.addon_id};
	      $('#addon-'+result.step_id+'-'+result.addon_id).createHTMLTag('<table>', properties, 'table table-striped price-rows');
	      $.fn.createTableHeader('addon-prices-'+result.step_id+'-'+result.addon_id);
	    }

	    //Set the item type according to the data.
	    var itemType = 'addon';
	    if(result.addon_option_id !== undefined) {
	      itemType = 'addon_option';
	    }

	    $.fn.createPriceRow('addon-prices-'+result.step_id+'-'+result.addon_id, result, itemType);
	  });

	  if(addonIds.length == 0) {
	    $('#addons').html('<span class="information">'+Joomla.JText._('COM_ODYSSEY_NO_ADDON_FOUND')+'</span>');
	  }

	  var transCityIds = [];
	  $.each(results.transitcities, function(i, result) {
	    //Create a new div for each transit city in the transitcities div container.
	    if(jQuery.inArray(result.city_id, transCityIds) === -1) {
	      transCityIds.push(result.city_id);
	      var properties = {'id':'transitcity-'+result.city_id};
	      $('#transitcities').createHTMLTag('<div>', properties, 'transitcity-price-rows');
	      //Display the transit city name.
	      properties = {'id':'transitcity-title-'+result.city_id};
	      $('#transitcity-'+result.city_id).createHTMLTag('<h2>', properties, 'transitcity-title');
	      $('#transitcity-title-'+result.city_id).html('<span class="item-label">'+Joomla.JText._('COM_ODYSSEY_TRANSIT_CITY_LABEL')+'</span>'+result.transitcity_name);

	      //Create a table to insert the transit city price rows.
	      properties = {'id':'transitcity-prices-'+result.city_id};
	      $('#transitcity-'+result.city_id).createHTMLTag('<table>', properties, 'table table-striped price-rows');
	      $.fn.createTableHeader('transitcity-prices-'+result.city_id);
	    }

	    //Add a row in the appropriate table for each departure.
	    $.fn.createPriceRow('transitcity-prices-'+result.city_id, result, 'transit_city');
	  });

	  if(transCityIds.length == 0) {
	    $('#transitcities').html('<span class="information">'+Joomla.JText._('COM_ODYSSEY_NO_TRANSIT_CITY_FOUND')+'</span>');
	  }
	},
	error: function(jqXHR, textStatus, errorThrown) {
	  //Display the error.
	  alert(textStatus+': '+errorThrown);
	}
    });
  };


  $.fn.createCountryfilterItem = function(idNb, data) {
    //Create a country select tag.
    var properties = {'name':'country_code_'+idNb, 'id':'country-code-'+idNb};
    $('#countryfilter-item-'+idNb).createHTMLTag('<select>', properties, 'country-select');

    //Get the country codes and names.
    var countries = odyssey.getCountries();
    var length = countries.length;
    var options = '<option value="">'+Joomla.JText._('COM_ODYSSEY_OPTION_SELECT')+'</option>';
    //Create an option tag for each country.
    for(var i = 0; i < length; i++) {
      options += '<option value="'+countries[i].code+'">'+countries[i].text+'</option>';
    }   

    //Add the country options to the select tag.
    $('#country-code-'+idNb).html(options);

    if(data !== undefined) {
      //Set the selected option.
      $('#country-code-'+idNb+' option[value="'+data+'"]').attr('selected', true);
    }   

    //Use Chosen jQuery plugin.
    $('#country-code-'+idNb).trigger('liszt:updated');
    $('#country-code-'+idNb).chosen();

    //Create the item removal button.
    $('#countryfilter-item-'+idNb).createButton('remove');
  };


  $.fn.createRegionfilterItem = function(idNb, data) {
    //Create a region select tag.
    var properties = {'name':'region_code_'+idNb, 'id':'region-code-'+idNb};
    $('#regionfilter-item-'+idNb).createHTMLTag('<select>', properties, 'region-select');

    //Get the region codes and names.
    var regions = odyssey.getRegions();
    var length = regions.length;
    var options = '<option value="">'+Joomla.JText._('COM_ODYSSEY_OPTION_SELECT')+'</option>';
    //Create an option tag for each region.
    for(var i = 0; i < length; i++) {
      options += '<option value="'+regions[i].code+'">'+regions[i].text+'</option>';
    }   

    //Add the region options to the select tag.
    $('#region-code-'+idNb).html(options);

    if(data !== undefined) {
      //Set the selected option.
      $('#region-code-'+idNb+' option[value="'+data+'"]').attr('selected', true);
    }   

    //Use Chosen jQuery plugin.
    $('#region-code-'+idNb).trigger('liszt:updated');
    $('#region-code-'+idNb).chosen();

    //Create the item removal button.
    $('#regionfilter-item-'+idNb).createButton('remove');
  };


  $.fn.createCityfilterItem = function(idNb, data) {
    //Create a city select tag.
    var properties = {'name':'city_id_'+idNb, 'id':'city-id-'+idNb};
    $('#cityfilter-item-'+idNb).createHTMLTag('<select>', properties, 'city-select');

    //Get the city ids and names.
    var cities = odyssey.getCities();
    var length = cities.length;
    var options = '<option value="">'+Joomla.JText._('COM_ODYSSEY_OPTION_SELECT')+'</option>';
    //Create an option tag for each city.
    for(var i = 0; i < length; i++) {
      options += '<option value="'+cities[i].id+'">'+cities[i].text+'</option>';
    }   

    //Add the region options to the select tag.
    $('#city-id-'+idNb).html(options);

    if(data !== undefined) {
      //Set the selected option.
      $('#city-id-'+idNb+' option[value="'+data+'"]').attr('selected', true);
    }   

    //Use Chosen jQuery plugin.
    $('#city-id-'+idNb).trigger('liszt:updated');
    $('#city-id-'+idNb).chosen();

    //Create the item removal button.
    $('#cityfilter-item-'+idNb).createButton('remove');
  };


  $.fn.checkTravelData = function() {
    //Get the Bootstrap item tag. 
    var $itemTab = $('[data-toggle="tab"][href="#travel-sequences"]');
    var result = true;
    var idValue = '';
    var sequenceExists = 0;

    /*$('input[id^="sequence-id-"]').each(function() { 
      //A step sequence item is added and a step sequence is selected.
      if($(this).val() != '') {
	//Confirm that at least one sequence has been set.
	sequenceExists = 1;
      }
    });

    if(!sequenceExists || !result) {
      alert(Joomla.JText._('COM_ODYSSEY_ERROR_NO_STEP_SEQUENCE_SELECTED'));
      $itemTab.show();
      $itemTab.tab('show');
      return false;
    }*/

    return true;
  };


  $.fn.createImageItem = function(idNb, data) {
    //Get the id of the current user.
    var userId = odyssey.getUserId();
    //Build the link to the Joomla image server.
    var link = 'index.php?option=com_media&view=images&tmpl=component&asset=com_odyssey&author='+userId+'&fieldid='+idNb+'&folder=odyssey';
    $('#image-item-'+idNb).createButton('select', '#', link);

    //Create the "alt" label.
    var properties = {'title':Joomla.JText._('COM_ODYSSEY_IMAGE_ALT_TITLE')};
    $('#image-item-'+idNb).createHTMLTag('<span>', properties, 'image-alt-label');
    $('#image-item-'+idNb+' .image-alt-label').text(Joomla.JText._('COM_ODYSSEY_IMAGE_ALT_LABEL'));
    //Create the "alt" input.
    properties = {'type':'text', 'name':'image_alt_'+idNb, 'value':data.alt};
    $('#image-item-'+idNb).createHTMLTag('<input>', properties, 'image-alt');

    //Create the "order" label.
    properties = {'title':Joomla.JText._('COM_ODYSSEY_IMAGE_ORDERING_TITLE')};
    $('#image-item-'+idNb).createHTMLTag('<span>', properties, 'image-ordering-label');
    $('#image-item-'+idNb+' .image-ordering-label').text(Joomla.JText._('COM_ODYSSEY_IMAGE_ORDERING_LABEL'));

    //Get the number of image items within the container then use it as ordering
    //number for the current item.
    var ordering = $('#image-container').children('div').length;
    if(data.ordering !== '') {
      ordering = data.ordering;
    }
    //Create the "order" input.
    properties = {'type':'text', 'name':'image_ordering_'+idNb, 'readonly':'readonly', 'value':ordering};
    $('#image-item-'+idNb).createHTMLTag('<input>', properties, 'item-ordering');
    //Create the removal button.
    $('#image-item-'+idNb).createButton('remove_image');

    //Create a div in which img tag is nested.
    properties = {'id':'img-div-'+idNb};
    $('#image-item-'+idNb).createHTMLTag('<div>', properties, 'div-travel-image');
    //Create the img tag within the div.
    properties = {'src':data.src, 'width':data.width, 'height':data.height, 'id':'travel-img-'+idNb};
    $('#img-div-'+idNb).createHTMLTag('<img>', properties, 'travel-image');

    if(data.src !== '') {
      //Div is resized to fit the image dimensions and a 1px gray border is defined. 
      $('#img-div-'+idNb).css({'width':data.width+'px','height':data.height+'px','border':'1px solid #c0c0c0'});
    }

    //Create the hidden inputs needed to save image data.
    properties = {'type':'hidden', 'name':'image_src_'+idNb, 'id':'image-src-'+idNb, 'value':data.src};
    $('#image-item-'+idNb).createHTMLTag('<input>', properties);
    properties = {'type':'hidden', 'name':'image_width_'+idNb, 'id':'image-width-'+idNb, 'value':data.width};
    $('#image-item-'+idNb).createHTMLTag('<input>', properties);
    properties = {'type':'hidden', 'name':'image_height_'+idNb, 'id':'image-height-'+idNb, 'value':data.height};
    $('#image-item-'+idNb).createHTMLTag('<input>', properties);
  };

  //Remove the selected image item then reset the order of the other items left.
  $.fn.imageReorder = function(idNb) {
    //Remove the selected image.
    $('#image-container').removeItem(idNb);

    //List all of the div children (ie: image items) of the image container 
    //in order to reset their ordering value.
    $('#image-container').children('div').each(function(i, div) {
	//Reset the ordering input tag value.
	$(div).children('.image-ordering').val(i+1);
	});
  };


})(jQuery);

