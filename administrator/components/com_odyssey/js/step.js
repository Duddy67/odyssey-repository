
(function($) {
  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Get some variables.
    var stepId = $('#jform_id').val();
    var stepType = $('#jform_step_type').val();
    var dateType = $('#jform_date_type').val();
    var dptStepId = $('#jform_dpt_step_id_id').val();

    //Create a container for each item type.
    $('#departure').getContainer();
    $('#city').getContainer();

    //If the step item exists we need to get the data of the dynamical items.
    if(stepId != 0) {
      //Create a container for each item type.
      $('#addon').getContainer();
      $('#transitcity').getContainer();

      var urlQuery = {'step_id':stepId, 'dpt_step_id':dptStepId, 'step_type':stepType, 'date_type':dateType};

      //Ajax call which get item data previously set.
      $.ajax({
	  type: 'GET', 
	  url: 'components/com_odyssey/js/ajax/step.php', 
	  dataType: 'json',
	  data: urlQuery,
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {
	    //Create an item type for each result retrieved from the database.
	    $.each(results.departure, function(i, result) { $.fn.createItem('departure', result); });
	    $.each(results.city, function(i, result) { $.fn.createItem('city', result); });
	    $.each(results.addon, function(i, result) { $.fn.createItem('addon', result); });
	    $.each(results.transitcity, function(i, result) { $.fn.createItem('transitcity', result); });
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	  }
      });
    }

    $('#jform_step_type').change( function() { $.fn.setStepType(); });
    $.fn.setStepType();

    //Remove current date items whenever the date type is changed.
    $('#jform_date_type').change( function() { $('#departure-container').removeItem(); });
    
    $.fn.lockTypeOption(stepId, 'step');

    //Set as functions the global variables previously declared.
    checkStepData = $.fn.checkStepData;
    createTimeGapItems = $.fn.createTimeGapItems;

    $.fn.createTimeGapItems(dptStepId);
  });


  $.fn.setStepType = function() {
    //Set the step form interface (tabs, fields etc...) according to the type of the step.
    if($('#jform_step_type').val() == 'departure') {
      //Hide the departure step div and tab. 
      $('#departure-step').css({'visibility':'hidden','display':'none'});
      $('a[href="#step-departure-step"]').parent().css({'visibility':'hidden','display':'none'});

      //Hide the cities div and tab. 
      $('#cities').css({'visibility':'hidden','display':'none'});
      $('a[href="#step-cities"]').parent().css({'visibility':'hidden','display':'none'});

      //Show the departures div and tab. 
      $('#departures').css({'visibility':'visible','display':'block'});
      $('a[href="#step-departures"]').parent().css({'visibility':'visible','display':'block'});

      //Show the transit cities div and tab. 
      $('#transitcities').css({'visibility':'visible','display':'block'});
      $('a[href="#step-transitcities"]').parent().css({'visibility':'visible','display':'block'});

      //Enable the group alias field.
      $('#jform_group_alias').prop('readonly', false);
      $('#jform_group_alias').removeClass('readonly');

      //Show the travel code field.
      $('#jform_travel_code').parent().parent().css({'visibility':'visible','display':'block'});

      //Show the category drop down list.
      $('#jform_catid').parent().parent().css({'visibility':'visible','display':'block'});
      //Hide the fake link step category field.
      $('#jform_link_step_category').parent().parent().css({'visibility':'hidden','display':'none'});
    }
    else {
      //Hide the departures div and tab. 
      $('#departures').css({'visibility':'hidden','display':'none'});
      $('a[href="#step-departures"]').parent().css({'visibility':'hidden','display':'none'});

      //Show the cities div and tab. 
      $('#cities').css({'visibility':'visible','display':'block'});
      $('a[href="#step-cities"]').parent().css({'visibility':'visible','display':'block'});

      //Show the departure step div and tab. 
      $('#departure-step').css({'visibility':'visible','display':'block'});
      $('a[href="#step-departure-step"]').parent().css({'visibility':'visible','display':'block'});

      //Hide the transit cities div and tab. 
      $('#transitcities').css({'visibility':'hidden','display':'none'});
      $('a[href="#step-transitcities"]').parent().css({'visibility':'hidden','display':'none'});

      //Disable the group alias field.
      $('#jform_group_alias').prop('readonly', true);
      $('#jform_group_alias').addClass('readonly');

      //Hide the travel code field.
      $('#jform_travel_code').parent().parent().css({'visibility':'hidden','display':'none'});

      //Hide the category drop down list.
      $('#jform_catid').parent().parent().css({'visibility':'hidden','display':'none'});
      //Show the fake link step category field.
      $('#jform_link_step_category').parent().parent().css({'visibility':'visible','display':'block'});
    }
  };


  $.fn.createDepartureItem = function(idNb, data) {
    //Get the date type to create.
    var dateType = $('#jform_date_type').val();
    //var idName = 'departure-item-';
    var idName = 'wrap-dpt-row-';

    if(dateType == 'standard' || dateType == 'period') {
      //Add a class to the wrapping div to have calendar button embedded in the field.
      $('#departure-item-'+idNb).addClass('input-append');

      //Wrap date time field(s), city and remove button elements in a div.
      var properties = {'id':'wrap-dpt-row-'+idNb};
      $('#departure-item-'+idNb).createHTMLTag('<div>', properties, 'wrap-dpt-row');

      //A parent wraping div with a "field-calendar" class is required to get the calendar working.
      var properties = {'id':'field-calendar-'+idNb};
      $('#wrap-dpt-row-'+idNb).createHTMLTag('<div>', properties, 'field-calendar');

      properties = {'type':'text', 'name':'date_time_'+idNb, 'id':'date-time-'+idNb,
		    'value':data.date_time, 'data-alt-value':data.date_time, 'autocomplete':'off'};

      //Set format according to the date type.
      var dateFormat = '%Y-%m-%d %H:%M';
      var time = true;
      if(dateType == 'period') {
	dateFormat = '%Y-%m-%d';
	time = false;
	//properties.readonly = 'readonly';
      }

      $('#field-calendar-'+idNb).createHTMLTag('<input>', properties, 'input-medium date-time dpt-date-time');

      //Create the calendar button.
      properties = {'type':'button', 'id':'button-date-'+idNb, 'data-weekend':'0,6'};
      $('#field-calendar-'+idNb).createHTMLTag('<button>', properties, 'btn dpt-btn');
      properties = {};
      $('#button-date-'+idNb).createHTMLTag('<span>', properties, 'icon-calendar');

      //Set the Joomla calendar to the new date item.
      Calendar.setup({
	  // Id of the input field
	  inputField: 'date-time-'+idNb,
	  // Format of the input field
	  ifFormat: dateFormat,
	  // Trigger for the calendar (button ID)
	  button: 'button-date-'+idNb,
	  // Alignment (defaults to "Bl")
	  align: 'Tl',
	  showsTime: time,
	  singleClick: true,
	  firstDay: 0
      });

      //Add the second date time field for the period type.
      if(dateType == 'period') {
	//Add an extra wraping div or calendar won't work.
	var properties = {'id':'wrap-calendar-2-'+idNb};
	$('#wrap-dpt-row-'+idNb).createHTMLTag('<div>', properties, 'wrap-calendar-2');
	//A parent wraping div with a "field-calendar" class is required to get the calendar working.
	var properties = {'id':'field-calendar-2-'+idNb};
	$('#wrap-calendar-2-'+idNb).createHTMLTag('<div>', properties, 'field-calendar');

	properties = {'type':'text', 'name':'date_time_2_'+idNb, 'id':'date-time-2-'+idNb, 'value':data.date_time_2,
		      'data-alt-value':data.date_time_2, 'autocomplete':'off'};
	$('#field-calendar-2-'+idNb).createHTMLTag('<input>', properties, 'input-medium date-time dpt-date-time');

	//Create the calendar button.
	properties = {'type':'button', 'id':'button-date-2-'+idNb, 'data-weekend':'0,6'};
	$('#field-calendar-2-'+idNb).createHTMLTag('<button>', properties, 'btn dpt-btn');
	properties = {};
	$('#button-date-2-'+idNb).createHTMLTag('<span>', properties, 'icon-calendar');

	//Set the Joomla calendar to the new date item.
	Calendar.setup({
	    // Id of the input field
	    inputField: 'date-time-2-'+idNb,
	    // Format of the input field
	    ifFormat: "%Y-%m-%d",
	    // Trigger for the calendar (button ID)
	    button: 'button-date-2-'+idNb,
	    // Alignment (defaults to "Bl")
	    align: 'Tl',
	    showsTime: false,
	    singleClick: true,
	    firstDay: 0
	});
      }
    }
    else if(dateType == 'every_year') {
      $.fn.getEveryYearDate(idNb, data);
    }
    else { //every_month
      //TODO
    }

    //Create the city drop down list.
    $.fn.getCityOptions(idNb, data, 'departure', idName);
    //Create the item removal button.
    $('#departure-item-'+idNb).createButton('remove');
    //Move the button into the wraping div.
    var button = $('#remove-departure-button-'+idNb).parent();
    button.prependTo('#'+idName+idNb);

    //Create the second departure data row.

    //Wrap all of the data departure in a div.
    properties = {'id':'wrap-dpt-row2-'+idNb};
    $('#departure-item-'+idNb).createHTMLTag('<div>', properties, 'wrap-dpt-row2');

    properties = {'id':'wrap-dpt-data-1-'+idNb};
    $('#wrap-dpt-row2-'+idNb).createHTMLTag('<div>', properties, 'wrap-dpt-data');

    properties = {'title':Joomla.JText._('COM_ODYSSEY_MAX_PASSENGERS_TITLE')};
    $('#wrap-dpt-data-1-'+idNb).createHTMLTag('<span>', properties, 'max-passengers-label');
    $('#wrap-dpt-data-1-'+idNb+' .max-passengers-label').text(Joomla.JText._('COM_ODYSSEY_MAX_PASSENGERS_LABEL'));

    properties = {'type':'text', 'name':'max_passengers_'+idNb, 'id':'max-passengers-'+idNb, 'value':data.max_passengers};
    $('#wrap-dpt-data-1-'+idNb).createHTMLTag('<input>', properties, 'max-passengers');

    properties = {'id':'wrap-dpt-data-2-'+idNb};
    $('#wrap-dpt-row2-'+idNb).createHTMLTag('<div>', properties, 'wrap-dpt-data');

    properties = {'title':Joomla.JText._('COM_ODYSSEY_ALLOTMENT_TITLE')};
    $('#wrap-dpt-data-2-'+idNb).createHTMLTag('<span>', properties, 'allotment-label');
    $('#wrap-dpt-data-2-'+idNb+' .allotment-label').text(Joomla.JText._('COM_ODYSSEY_ALLOTMENT_LABEL'));

    properties = {'type':'text', 'name':'allotment_'+idNb, 'id':'allotment-'+idNb, 'value':data.allotment};
    $('#wrap-dpt-data-2-'+idNb).createHTMLTag('<input>', properties, 'allotment');

    properties = {'id':'wrap-dpt-data-3-'+idNb};
    $('#wrap-dpt-row2-'+idNb).createHTMLTag('<div>', properties, 'wrap-dpt-data');

    properties = {'title':Joomla.JText._('COM_ODYSSEY_SUBTRACT_TITLE')};
    $('#wrap-dpt-data-3-'+idNb).createHTMLTag('<span>', properties, 'subtract-label');
    $('#wrap-dpt-data-3-'+idNb+' .subtract-label').text(Joomla.JText._('COM_ODYSSEY_SUBTRACT_LABEL'));

    $('#wrap-dpt-data-3-'+idNb).createHTMLTag('<span>', properties, 'yes-label');
    $('#wrap-dpt-data-3-'+idNb+' .yes-label').text(Joomla.JText._('JYES'));

    properties = {'type':'radio', 'name':'altm_subtract_'+idNb, 'id':'altm-subtract-'+idNb, 'value':1};
    //Yes button is checked by default.
    if(data.altm_subtract === undefined || data.altm_subtract == 1) {
      properties.checked = true;
    }
    $('#wrap-dpt-data-3-'+idNb).createHTMLTag('<input>', properties, 'departure-checkbox');

    $('#wrap-dpt-data-3-'+idNb).createHTMLTag('<span>', properties, 'no-label');
    $('#wrap-dpt-data-3-'+idNb+' .no-label').text(Joomla.JText._('JNO'));

    properties = {'type':'radio', 'name':'altm_subtract_'+idNb, 'id':'altm-subtract-'+idNb, 'value':0};
    if(data.altm_subtract == 0) {
      properties.checked = true;
    }
    $('#wrap-dpt-data-3-'+idNb).createHTMLTag('<input>', properties, 'departure-checkbox');

    properties = {'id':'wrap-dpt-data-4-'+idNb};
    $('#wrap-dpt-row2-'+idNb).createHTMLTag('<div>', properties, 'wrap-dpt-data');

    properties = {'title':Joomla.JText._('COM_ODYSSEY_NB_DAYS_TITLE')};
    $('#wrap-dpt-data-4-'+idNb).createHTMLTag('<span>', properties, 'nb-days-label');
    $('#wrap-dpt-data-4-'+idNb+' .nb-days-label').text(Joomla.JText._('COM_ODYSSEY_NB_DAYS_LABEL'));

    properties = {'type':'text', 'name':'nb_days_'+idNb, 'id':'nb-days-'+idNb, 'value':data.nb_days};
    $('#wrap-dpt-data-4-'+idNb).createHTMLTag('<input>', properties, 'nb-days');

    properties = {'id':'wrap-dpt-data-5-'+idNb};
    $('#wrap-dpt-row2-'+idNb).createHTMLTag('<div>', properties, 'wrap-dpt-data');

    properties = {'title':Joomla.JText._('COM_ODYSSEY_NB_NIGHTS_TITLE')};
    $('#wrap-dpt-data-5-'+idNb).createHTMLTag('<span>', properties, 'nb-nights-label');
    $('#wrap-dpt-data-5-'+idNb+' .nb-nights-label').text(Joomla.JText._('COM_ODYSSEY_NB_NIGHTS_LABEL'));

    properties = {'type':'text', 'name':'nb_nights_'+idNb, 'id':'nb-nights-'+idNb, 'value':data.nb_nights};
    $('#wrap-dpt-data-5-'+idNb).createHTMLTag('<input>', properties, 'nb-nights');

    properties = {'id':'wrap-dpt-data-6-'+idNb};
    $('#wrap-dpt-row2-'+idNb).createHTMLTag('<div>', properties, 'wrap-dpt-data');

    properties = {'title':Joomla.JText._('COM_ODYSSEY_CODE_TITLE')};
    $('#wrap-dpt-data-6-'+idNb).createHTMLTag('<span>', properties, 'code-label');
    $('#wrap-dpt-data-6-'+idNb+' .code-label').text(Joomla.JText._('COM_ODYSSEY_CODE_LABEL'));

    properties = {'type':'text', 'name':'code_'+idNb, 'id':'code-'+idNb, 'value':data.code};
    $('#wrap-dpt-data-6-'+idNb).createHTMLTag('<input>', properties, 'code');

    properties = {'id':'wrap-dpt-data-7-'+idNb};
    $('#wrap-dpt-row2-'+idNb).createHTMLTag('<div>', properties, 'wrap-dpt-data');

    properties = {'title':Joomla.JText._('COM_ODYSSEY_PUBLISHED_TITLE')};
    $('#wrap-dpt-data-7-'+idNb).createHTMLTag('<span>', properties, 'published-label');
    $('#wrap-dpt-data-7-'+idNb+' .published-label').text(Joomla.JText._('COM_ODYSSEY_PUBLISHED_LABEL'));

    $('#wrap-dpt-data-7-'+idNb).createHTMLTag('<span>', properties, 'yes-label');
    $('#wrap-dpt-data-7-'+idNb+' .yes-label').text(Joomla.JText._('JYES'));

    properties = {'type':'radio', 'name':'published_'+idNb, 'id':'published-'+idNb, 'value':1};
    //Yes button is checked by default.
    if(data.published === undefined || data.published == 1) {
      properties.checked = true;
    }
    $('#wrap-dpt-data-7-'+idNb).createHTMLTag('<input>', properties, 'departure-checkbox');

    $('#wrap-dpt-data-7-'+idNb).createHTMLTag('<span>', properties, 'no-label');
    $('#wrap-dpt-data-7-'+idNb+' .no-label').text(Joomla.JText._('JNO'));

    properties = {'type':'radio', 'name':'published_'+idNb, 'id':'published-'+idNb, 'value':0};
    if(data.published == 0) {
      properties.checked = true;
    }
    $('#wrap-dpt-data-7-'+idNb).createHTMLTag('<input>', properties, 'departure-checkbox');

    properties = {'type':'hidden', 'name':'dpt_id_'+idNb, 'value':data.dpt_id};
    $('#departure-item-'+idNb).createHTMLTag('<input>', properties);
  };


  $.fn.getEveryYearDate = function(idNb, data) {

    var properties = {'id':'ev-year-container-'+idNb};
    $('#wrap-dpt-row-'+idNb).createHTMLTag('<div>', properties, 'ev-year-container');

    //Create the month select tag.
    var properties = {'name':'ev_year_month_'+idNb, 'id':'ev-year-month-'+idNb};
    $('#ev-year-container-'+idNb).createHTMLTag('<select>', properties, 'ev-year-select');
    var options = '';
    var months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
    var monthNumbers = ['01','02','03','04','05','06','07','08','09','10','11','12'];

    for(var i = 0; i < months.length; i++) {
      options += '<option value="'+monthNumbers[i]+'">'+Joomla.JText._('COM_ODYSSEY_OPTION_'+months[i])+'</option>';
    }

    //Add the month options to the select tag.
    $('#ev-year-month-'+idNb).html(options);

    if(data.month !== '') {
      //Set the selected option.
      $('#ev-year-month-'+idNb+' option[value="'+data.month+'"]').attr('selected', true);
    }

    //Use Chosen jQuery plugin.
    $('#ev-year-month-'+idNb).trigger('liszt:updated');
    $('#ev-year-month-'+idNb).chosen();

    //Create the date select tag.
    var properties = {'name':'ev_year_date_'+idNb, 'id':'ev-year-date-'+idNb};
    $('#ev-year-container-'+idNb).createHTMLTag('<select>', properties, 'ev-year-select');
    var options = '';
    var dates = ['01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31'];

    for(var i = 0; i < dates.length; i++) {
      options += '<option value="'+dates[i]+'">'+dates[i]+'</option>';
    }

    //Add the date options to the select tag.
    $('#ev-year-date-'+idNb).html(options);

    if(data.date !== '') {
      //Set the selected option.
      $('#ev-year-date-'+idNb+' option[value="'+data.date+'"]').attr('selected', true);
    }

    //Use Chosen jQuery plugin.
    $('#ev-year-date-'+idNb).trigger('liszt:updated');
    $('#ev-year-date-'+idNb).chosen();

    //Create the time field.
    properties = {'type':'text', 'name':'ev_year_time_'+idNb, 'id':'ev-year-time-'+idNb, 'value':data.time};
    $('#ev-year-container-'+idNb).createHTMLTag('<input>', properties, 'date-item');

  };


  $.fn.getCityOptions = function(idNb, data, itemType, idName) {
    //Create the city label.
    /*properties = {'title':Joomla.JText._('COM_ODYSSEY_CITY_TITLE')};
    $('#'+itemType+'-item-'+idNb).createHTMLTag('<span>', properties, 'city-label');
    $('#'+itemType+'-item-'+idNb+' .city-label').text(Joomla.JText._('COM_ODYSSEY_CITY_LABEL'));*/

    //City and transit city items share the same form so we have to be specific regarding
    //the name of the drop down lists.
    var itemName = 'city';
    if(itemType == 'transitcity') {
      itemName = 'transitcity';
    }

    if(idName === undefined) {
      idName = itemType+'-item-';
    }

    //Create the select tag.
    var properties = {'name':itemName+'_id_'+idNb, 'id':itemName+'-id-'+idNb};
    $('#'+idName+idNb).createHTMLTag('<select>', properties, 'country-select');

    //Get the cities.
    var cities = odyssey.getCities();
    var length = cities.length;
    var options = '<option value="">'+Joomla.JText._('COM_ODYSSEY_OPTION_SELECT_CITY')+'</option>';

    //Create an option tag for each city.
    for(var i = 0; i < length; i++) {
      options += '<option value="'+cities[i].id+'">'+cities[i].text+'</option>';
    }

    //Add the city options to the select tag.
    $('#'+itemName+'-id-'+idNb).html(options);

    if(data.city_id !== '') {
      //Set the selected option.
      $('#'+itemName+'-id-'+idNb+' option[value="'+data.city_id+'"]').attr('selected', true);
    }

    //Use Chosen jQuery plugin.
    $('#'+itemName+'-id-'+idNb).trigger('liszt:updated');
    $('#'+itemName+'-id-'+idNb).chosen();
  };


  $.fn.createCityItem = function(idNb, data) {
    $.fn.getCityOptions(idNb, data, 'city');

    //Create the ordering label.
    properties = {'title':Joomla.JText._('COM_ODYSSEY_ORDERING_TITLE')};
    $('#city-item-'+idNb).createHTMLTag('<span>', properties, 'ordering-label');
    $('#city-item-'+idNb+' .ordering-label').text(Joomla.JText._('COM_ODYSSEY_ORDERING_LABEL'));

    //Get the number of image items within the container then use it as ordering
    //number for the current item.
    var ordering = $('#city-container').children('div').length;
    if(data.city_ordering !== '') {
      ordering = data.city_ordering;
    }
    //Create the "order" input.
    properties = {'type':'text', 'name':'city_ordering_'+idNb, 'id':'city-ordering-'+idNb, 'readonly':'readonly', 'value':ordering};
    $('#city-item-'+idNb).createHTMLTag('<input>', properties, 'item-ordering');

    //Create the item removal button.
    $('#city-item-'+idNb).createButton('remove_reorder');
  };


  $.fn.createTransitcityItem = function(idNb, data) {
    $.fn.getCityOptions(idNb, data, 'transitcity');

    properties = {'title':Joomla.JText._('COM_ODYSSEY_HOURS_MINUTES_MINUS_TITLE'), 'for':'transitcity-hr-mn-'+idNb};
    $('#transitcity-item-'+idNb).createHTMLTag('<label>', properties, 'hours-minutes-label');
    $('#transitcity-item-'+idNb+' .hours-minutes-label').text(Joomla.JText._('COM_ODYSSEY_HOURS_MINUTES_MINUS_LABEL'));

    var hrMn = '00:00';
    if(data.hr_mn !== undefined) {
      hrMn = data.hr_mn;
    }

    properties = {'type':'text', 'name':'transitcity_hr_mn_'+idNb, 'id':'transitcity-hr-mn-'+idNb, 'maxlength':5, 'value':hrMn};
    $('#transitcity-item-'+idNb).createHTMLTag('<input>', properties, 'hours-minutes');

    //Create the item removal button.
    $('#transitcity-item-'+idNb).createButton('remove');

    $.fn.getDepartureCheckboxes(idNb, data, 'transitcity');
  };


  //Enable or disable the days time and step alias fields of the row according to the checkbox state.
  $.fn.setTimeGapRow = function(dptId) {
    if($('#dpt-id-'+dptId).is(':checked')) {
      $('#days-'+dptId).prop('readonly', false).removeClass('readonly').css('color', 'black');
      $('#hr-mn-'+dptId).prop('readonly', false).removeClass('readonly').css('color', 'black');
      $('#group-prev-'+dptId).prop('disabled', false).removeClass('readonly');
      $('#group-prev-'+dptId).prop('readonly', false);
    }
    else { //unchecked
      $('#days-'+dptId).prop('readonly', true).addClass('readonly').css('color', '#c0c0c0');
      $('#hr-mn-'+dptId).prop('readonly', true).addClass('readonly').css('color', '#c0c0c0');
      $('#group-prev-'+dptId).prop('disabled', true).addClass('readonly');
      $('#group-prev-'+dptId).prop('readonly', true);
    }
  };


  $.fn.createTimeGapItems = function(dptStepId) {
    //Create the table header.
    if(dptStepId) {
      var thDatetime = '<tr><th class="datetime">'+Joomla.JText._('COM_ODYSSEY_HEADING_DATETIME')+'</th>';
      var thCity = '<th class="city">'+Joomla.JText._('COM_ODYSSEY_HEADING_DEPARTURE_CITY')+'</th>';
      var thTmg = '<th class="time-gap">'+Joomla.JText._('COM_ODYSSEY_HEADING_TIME_GAP')+'</th>'; 
      var thGrpPrev = '<th class="grouped">'+Joomla.JText._('COM_ODYSSEY_HEADING_GROUPED')+'</th>'; 
      var thSelect = '<th class="select">#</th></tr>';

      $('#time-gaps').append(thDatetime+thCity+thTmg+thGrpPrev+thSelect);
    }

    var stepId = $('#jform_id').val();
    var langTag = $('#lang-tag').val();
    var currentDptStepId = $('#current-dpt-step-id').val();

    //In case the current departure step is replaced by a new one.
    if(currentDptStepId != 0 && dptStepId != currentDptStepId) {
      //Don't load previous data linked to the step.
      stepId = 0;
    }

    var urlQuery = {'step_id':stepId, 'dpt_step_id':dptStepId, 'lang_tag':langTag};

    $.ajax({
	type: 'GET', 
	url: 'components/com_odyssey/js/ajax/timegaps.php', 
	dataType: 'json',
	data: urlQuery,
	//Get results as a json array.
	success: function(results, textStatus, jqXHR) {
	  //Create an time gap item for each result retrieved from the database.
	  $.each(results, function(i, result) { 

	    var dptId = result.dpt_id;

	    //Build the time gap row.
	    //Remove the seconds part ":00" from the end of the string.
	    var dateTime = result.date_time.slice(0, -3); 
	    //Set datetime_2 in case of period date type.
	    var dateTime_2 = '';
	    if(result.date_time_2 != '0000-00-00 00:00:00') {
	      //Remove time value as it is not used with period date type. 
	      dateTime = result.date_time.slice(0, -9); 
	      dateTime_2 = result.date_time_2.slice(0, -9); 
	      dateTime_2 = '<br />'+dateTime_2;
	    }

	    $('#time-gaps').append('<tr id="row-'+dptId+'"><td id="col-1-'+dptId+'">'+dateTime+dateTime_2+'</td>'+
	                           '<td id="col-2-'+dptId+'">'+result.city+'</td><td id="col-3-'+dptId+'"></td>'+
				   '<td id="col-4-'+dptId+'"></td><td id="col-5-'+dptId+'"></td></tr>');

	    //Build the required labels and fields.

	    properties = {'for':'days-'+dptId};
	    $('#col-3-'+dptId).createHTMLTag('<label>', properties, 'days-label');
	    $('#col-3-'+dptId+' .days-label').text(Joomla.JText._('COM_ODYSSEY_FIELD_DAYS_LABEL'));

	    properties = {'type':'text', 'name':'days_'+dptId, 'id':'days-'+dptId, 'maxlength':3, 'value':result.days};
	    $('#col-3-'+dptId).createHTMLTag('<input>', properties, 'days');

	    properties = {'for':'hr-mn-'+dptId};
	    $('#col-3-'+dptId).createHTMLTag('<label>', properties, 'hours-minutes-label');
	    $('#col-3-'+dptId+' .hours-minutes-label').text(Joomla.JText._('COM_ODYSSEY_FIELD_HOURS_MINUTES_LABEL'));

	    properties = {'type':'text', 'name':'hr_mn_'+dptId, 'id':'hr-mn-'+dptId, 'maxlength':5, 'value':result.hr_mn};
	    $('#col-3-'+dptId).createHTMLTag('<input>', properties, 'hours-minutes');

	    properties = {'type':'checkbox', 'name':'group_prev_'+dptId, 'id':'group-prev-'+dptId, 'value':result.group_prev};
	    $('#col-4-'+dptId).createHTMLTag('<input>', properties);

	    properties = {'type':'checkbox', 'name':'dpt_id_'+dptId, 'id':'dpt-id-'+dptId, 'value':dptId};
	    $('#col-5-'+dptId).createHTMLTag('<input>', properties);

	    //Set the checkbox state.
	    if(result.selected !== "") { //checked
	      $('#dpt-id-'+result.dpt_id).prop('checked', true);
	    }
	    else { //unchecked
	      $.fn.setTimeGapRow(result.dpt_id);
	    }

	    if(result.group_prev == 1) { //checked
	      $('#group-prev-'+result.dpt_id).prop('checked', true);
	    }

	    //Bind the checkbox event to the setTimeGapRow function.
	    $('#dpt-id-'+result.dpt_id).change( function() { $.fn.setTimeGapRow(result.dpt_id); });

	  });
	},
	error: function(jqXHR, textStatus, errorThrown) {
	  //Display the error.
	  alert(textStatus+': '+errorThrown);
	}
    });
  };


  $.fn.checkStepData = function(stepType, stepId) {
    var result = true;
    var idValue = '';

    //Check data according to the step type.
    if(stepType == 'departure') {
      var isInTravel = 0;
      var dateType = $('#jform_date_type').val(); 

      if($('#jform_published').val() != 1 && stepId != 0) {
	var urlQuery = {'dpt_step_id':stepId, 'check_status':1};
	$.ajax({
	    type: 'GET', 
	    url: 'components/com_odyssey/js/ajax/step.php', 
	    dataType: 'json',
	    async: false, //We need a synchronous calling here.
	    data: urlQuery,
	    //Get results as a json array.
	    success: function(results, textStatus, jqXHR) {
	      isInTravel = results.is_in_travel;
	    },
	    error: function(jqXHR, textStatus, errorThrown) {
	      //Display the error.
	      alert(textStatus+': '+errorThrown);
	    }
	});

	if(isInTravel) {
	  alert(Joomla.JText._('COM_ODYSSEY_WARNING_DEPARTURE_STEP_USED_IN_TRAVEL'));
	  //Get the Bootstrap item tag. 
	  var $itemTab = $('[data-toggle="tab"][href="#details"]');
	  $itemTab.show();
	  $itemTab.tab('show');
	  $('#jform_published').addClass('invalid');
	  return false;
	}
      }

      //Get the Bootstrap item tag. 
      var $itemTab = $('[data-toggle="tab"][href="#step-departures"]');
      //Set data used for the checking.
      var dptExists = 0;
      var timeId = 'date-time';
      var regex = /^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$/;
      var periods = [];

      //Modify some checking data according to the date type.
      if(dateType == 'period') {
	regex = /^[0-9]{4}-[0-9]{2}-[0-9]{2}$/;
      }

      if(dateType == 'every_year') {
	timeId = 'ev-year-time';
	regex = /^[0-9]{2}:[0-9]{2}$/;
      }

      $('input[id^="'+timeId+'-"]').each(function() { 
	//Confirm that at least one departure has been set.
	dptExists = 1;

	//Check that date time (or just time) is correctly set.
	if(!regex.test($(this).val())) {
	  alert(Joomla.JText._('COM_ODYSSEY_ERROR_INVALID_DATETIME_VALUE'));
	  idValue = $(this).prop('id');
	  result = false;
	  return false;
	}
	else { 
	  //Extract the id number of the item from the end of its id value.
	  idValue = $(this).prop('id');
	  idNb = parseInt(idValue.replace(/.+-(\d+)$/, '$1'));

	  //Check that a city has been selected.
	  if($('#city-id-'+idNb).val() == '') {
	    alert(Joomla.JText._('COM_ODYSSEY_ERROR_NO_CITY_SELECTED'));
	    idValue = 'city-id-'+idNb;
	    result = false;
	    return false;
	  }

	  //Check that the max passengers value is properly set.
	  if(!/^[1-9]{1}[0-9]*$/.test($('#max-passengers-'+idNb).val())) {
	    alert(Joomla.JText._('COM_ODYSSEY_ERROR_INVALID_MAX_PASSENGERS_VALUE'));
	    idValue = 'max-passengers-'+idNb;
	    result = false;
	    return false;
	  }

	  //Check that the allotment value is properly set.
	  if(!/^[0-9]+$/.test($('#allotment-'+idNb).val())) {
	    alert(Joomla.JText._('COM_ODYSSEY_ERROR_INVALID_ALLOTMENT_VALUE'));
	    idValue = 'allotment-'+idNb;
	    result = false;
	    return false;
	  }

	  //Check time periods.
	  if(dateType == 'period') {
	    if($('#date-time-'+idNb).val() >= $('#date-time-2-'+idNb).val()) {
	      alert(Joomla.JText._('COM_ODYSSEY_ERROR_INVALID_TIME_PERIOD'));
	      idValue = 'date-time-'+idNb;
	      result = false;
	      return false;
	    }

            var period = {'from':$('#date-time-'+idNb).val(), 'to':$('#date-time-2-'+idNb).val(), 'id_nb':idNb};
	    periods.push(period);
	  }
	}
      });

      if(!dptExists) {
	alert(Joomla.JText._('COM_ODYSSEY_ERROR_NO_DEPARTURE_DEFINED'));
	$itemTab.show();
	$itemTab.tab('show');
	return false;
      }

      if(!result) {
	$itemTab.show();
	$itemTab.tab('show');
	$('#'+idValue).addClass('invalid');
	return false;
      }
    }
    else { //link
      //Get the Bootstrap item tag. 
      var $itemTab = $('[data-toggle="tab"][href="#step-departure-step"]');

      //Check first a departure step is selected.
      if($('#jform_dpt_step_id_id').val() == '') {
	alert(Joomla.JText._('COM_ODYSSEY_ERROR_DEPARTURE_STEP_MISSING'));
	$itemTab.show();
	$itemTab.tab('show');
	return false;
      }

      //Check the days hours minutes values for each departure row selected.
      $('input[id^="dpt-id-"]').each(function() { 
	if($(this).prop('checked')) {
	  //Extract the dpt_id number of the item from the end of its id value.
	  idValue = $(this).prop('id');
	  var dptId = parseInt(idValue.replace(/.+-(\d+)$/, '$1'));

	  var days = $('#days-'+dptId).val();
	  var hrMn = $('#hr-mn-'+dptId).val();

	  if(!/^[0-9]{1,3}$/.test(days)) {
	    alert(Joomla.JText._('COM_ODYSSEY_ERROR_INVALID_DAYS_VALUE'));
	    idValue = 'days-'+dptId;
	    result = false;
	    return false;
	  }

	  if(!/^[0-9]{2}:[0-9]{2}$/.test(hrMn)) {
	    alert(Joomla.JText._('COM_ODYSSEY_ERROR_INVALID_TIME_VALUE'));
	    idValue = 'hr-mn-'+dptId;
	    result = false;
	    return false;
	  }

	  //Separate hours and minutes values.
	  //Note: Leading zeros are removed from the figure.
	  var hr = parseInt(hrMn.replace(/^([0-9]{2}):[0-9]{2}$/, '$1'), 10);
	  var mn = parseInt(hrMn.replace(/^[0-9]{2}:([0-9]{2})$/, '$1'), 10);

	  if(hr > 23 || mn > 59) {
	    alert(Joomla.JText._('COM_ODYSSEY_ERROR_INVALID_TIME_VALUE'));
	    idValue = 'hr-mn-'+dptId;
	    result = false;
	    return false;
	  }

	  //Time gap cannot be zero.
	  if(days == 0 && hr == 0 && mn == 0) {
	    alert(Joomla.JText._('COM_ODYSSEY_ERROR_INVALID_TIME_GAP'));
	    $('#days-'+dptId).addClass('invalid');
	    idValue = 'hr-mn-'+dptId;
	    result = false;
	    return false;
	  }
	}
      });

      if(!result) {
	$itemTab.show();
	$itemTab.tab('show');
	$('#'+idValue).addClass('invalid');
	return false;
      }

      //Move to cities.

      //Change the tab to toggle.
      $itemTab = $('[data-toggle="tab"][href="#step-cities"]');
      var cityExists = 0;
      $('select[id^="city-id-"]').each(function() { 
	//A city item is added and a city is selected in the drop down list.
	if($(this).val() != '') {
	  //Confirm that at least one city has been added.
	  cityExists = 1;
	}
      });

      if(!cityExists) {
	alert(Joomla.JText._('COM_ODYSSEY_ERROR_NO_CITY_SELECTED'));
	$itemTab.show();
	$itemTab.tab('show');
	return false;
      }
    }

    return true;
  };


  $.fn.checkPeriods = function(periods) {
    //TODO
  };

})(jQuery);
