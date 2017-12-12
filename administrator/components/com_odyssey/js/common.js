/*

   *** item container (div) ******
   *                             *
   *  ** item 1 (div) *********  *      
   *  *                       *  *
   *  *                       *  *
   *  *                       *  *
   *  *                       *  *
   *  *************************  *      
   *                             *
   *                             *
   *  ** item 2 (div) *********  *      
   *  *                       *  *
   *  *                       *  *
   *  *                       *  *
   *  *                       *  *
   *  *************************  *      
   *                             *
   *  etc...                     *
   *                             *
   *******************************
  
  Both item type and id number can be easily retrieved from the id value.

  Pattern of the id value of an item div:

  #type-itemname-extra-12
     |                 |
     |                 |
   item type         id number

*/


(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Set as function the global variable previously declared in check.js file.
    showTab = $.fn.showTab;
    //Set as function the global variable previously declared in both step and travel
    //edit.php file.
    checkAlias = $.fn.checkAlias;
    reverseOrder = $.fn.reverseOrder;
  });


  //Create an item container (ie: a div).
  $.fn.getContainer = function() {
    //The value of the id is taken as the type of the items to create. 
    var type = $(this).attr('id');
    //Create the button allowing to add items dynamicaly.
    $('#'+type).createButton('add');  
    $('#'+type).append('<span id="'+type+'-button-separator">&nbsp;</span>');  
    //Create the container for the given item type.
    $('#'+type).append('<div id="'+type+'-container" class="odyssey-'+type+'-container">');  

    return this;
  };


  $.fn.createButton = function(action, href, modal) {
    //Get the id value of the item.
    var idValue = $(this).prop('id');
    //Extract the type and id number of the item.
    var itemType = idValue.replace(/^([a-zA-Z0-9_]+)-.+$/, '$1');
    //Note: If no id number is found we set it to zero.
    var idNb = parseInt(idValue.replace(/.+-(\d+)$/, '$1')) || 0;
    //Build the link id.
    var linkId = action+'-'+itemType+'-button-'+idNb;

    if(href === undefined) {
      href = '#';
    }

    //Create the button.
    $(this).append('<div class="btn-wrapper" id="btn-'+action+'-'+idNb+'">');
    //Create the button link which trigger the action when it is clicked.
    //Note: Find the last element linked to the .btn-wrapper class in case one 
    //button or more already exist into the item. 
    $(this).find('.btn-wrapper:last').append('<a href="'+href+'" id="'+linkId+'" class="btn btn-small">');
    //Create the label button according to its action.
    var label = 'COM_ODYSSEY_BUTTON_'+action.toUpperCase()+'_LABEL'

    if(action == 'remove_reorder' || action == 'remove_image') {
      //The remove reorder label is just a remove label. 
      label = 'COM_ODYSSEY_BUTTON_REMOVE_LABEL'
    }

    //Insert the icon and bind a function to the button according to the required action.
    switch(action) {
      case 'add':
	$(this).find('.btn-wrapper:last a').append('<span class="icon-save-new"/>');
	$(this).find('.btn-wrapper:last a').click( function() { $.fn.createItem(itemType); });
	break;

      case 'remove':
	$(this).find('.btn-wrapper:last a').append('<span class="icon-remove"/>');
	$(this).find('.btn-wrapper:last a').click( function() { $('#'+itemType+'-container').removeItem(idNb); });
	break;

      case 'remove_reorder':
	$(this).find('.btn-wrapper:last a').append('<span class="icon-remove"/>');
	$(this).find('.btn-wrapper:last a').click( function() { $.fn.itemReorder(idNb, itemType); });
	break;

      case 'remove_image':
	$(this).find('.btn-wrapper:last a').append('<span class="icon-remove"/>');
	$(this).find('.btn-wrapper:last a').click( function() { $.fn.imageReorder(idNb); });
	break;

      case 'select':
	$(this).find('.btn-wrapper:last a').append('<span class="icon-checkbox"/>');
	$(this).find('.btn-wrapper:last a').click( function() { $.fn.openIntoIframe(modal); });
	break;
    }
   
    //Insert the label.
    $(this).find('.btn-wrapper:last a').append(Joomla.JText._(label));

    return this;
  };


  //Create any html tag.
  $.fn.createHTMLTag = function(tag, properties, className) {
    var newTag = $(tag).attr(properties);
    if(className !== undefined) {
      newTag.addClass(className);
    }
    //Add the tag.
    $(this).append(newTag);

    return this;
  };


  //Remove a given item.
  $.fn.removeItem = function(idNb) {
    //If no id number is passed as argument we just remove all of the container
    //children (ie: all of the items).
    if(idNb === undefined) {
      $(this).children().remove();
    } else {
      //Searching for the item to remove.
      for(var i = 0, lgh = $(this).children('div').length; i < lgh; i++) {
	//Extract (thanks to a regex) the id number of the item which is
	//contained at the end of its id value.
	/.+-(\d+)$/.test($(this).children().eq(i).attr('id'));
	//If the id number matches we remove the item from the container.
	if(RegExp.$1 == idNb) {
	  $(this).children().eq(i).remove();
	  break;
	}
      }
    }

    return this;
  };


  //A generic function which initialize then create a basic item of the given type.
  $.fn.createItem = function(itemType, data) {
    //If no data is passed we get an empty data set.
    if(data === undefined) {
      data = $.fn.getDataSet(itemType);
    }

    //First of all we need an id number for the item.
    var idNb = $('#'+itemType+'-container').getIdNumber();

    //Now we can create the basic structure of the item.
    var properties = {'id':itemType+'-item-'+idNb};
    $('#'+itemType+'-container').createHTMLTag('<div>', properties, itemType+'-item');

    //Build the name of the specific function from the type name (ie: create+Type+Item). 
    var functionName = 'create'+$.fn.upperCaseFirstLetter(itemType)+'Item';

    //Call the specific function.
    $.fn[functionName](idNb, data);

    return this;
  };


  //Remove the selected item then reset the order of the other items left.
  $.fn.itemReorder = function(idNb, itemType) {
    //Remove the selected item.
    $('#'+itemType+'-container').removeItem(idNb);

    //List all of the div children (ie: items) of the item container 
    //in order to reset their ordering value.
    $('#'+itemType+'-container').children('div').each(function(i, div) {
	//Reset the ordering input tag value.
	$(div).children('.item-ordering').val(i+1);
    });

    $.fn.setOrderManagement(itemType); 
  };


  $.fn.setOrderManagement = function(itemType) {
    var idNbs = new Array();
    //Get the id numbers of the exiting items.
    $('#'+itemType+'-container').children('div').each(function(i, div) {
      var idNb = parseInt($(div).prop('id').replace(/.+-(\d+)$/, '$1')) || 0;
      //Store the id number.
      idNbs.push(idNb);
    });

    var arrLength = idNbs.length;
    //No need to go further if there is no item.
    if(arrLength == 0) {
      return;
    }

    //Create and set the management tags for each item.
    for(i = 0; i < arrLength; i++) {
      var idNb = idNbs[i];
      var ordering = i + 1;

      //First remove all the previous management tags.
      $('#'+itemType+'-up-ordering-'+idNb).remove();
      $('#'+itemType+'-down-ordering-'+idNb).remove();
      $('#'+itemType+'-prev-'+idNb).remove();
      $('#'+itemType+'-next-'+idNb).remove();
      $('#'+itemType+'-order-transparent-'+idNb).remove();

      if(ordering > 1) {
	//Create and set the link which allows to reverse the position with the upper item.
	var properties = {'href':'#', 'id':itemType+'-up-ordering-'+idNb, 'onclick':'reverseOrder(\'up\',\''+itemType+'\','+idNb+')'};
	$('#'+itemType+'-item-'+idNb).createHTMLTag('<a>', properties, 'up-ordering');
	var arrowUp = '<img src="../media/com_odyssey/images/arrow_up.png" title="'+Joomla.JText._('COM_ODYSSEY_REORDER_TITLE')+'" alt="arrow up" height="16" width="16" />';
	$('#'+itemType+'-item-'+idNb+' .up-ordering').prepend(arrowUp);
	//Move the element just after the ordering input tag.
	$('#'+itemType+'-up-ordering-'+idNb).insertAfter($('#'+itemType+'-ordering-'+idNb));

	//Create the hidden field which holds the id number of the previous item.
	properties = {'type':'hidden', 'name':itemType+'_prev_'+idNb, 'id':itemType+'-prev-'+idNb, 'value':idNbs[i - 1]};
	$('#'+itemType+'-item-'+idNb).createHTMLTag('<input>', properties);
      }

      if(ordering < arrLength) {
	//Create and set the link which allows to reverse the position with the lower item.
	properties = {'href':'#', 'id':itemType+'-down-ordering-'+idNb, 'onclick':'reverseOrder(\'down\',\''+itemType+'\','+idNb+')'};
	$('#'+itemType+'-item-'+idNb).createHTMLTag('<a>', properties, 'down-ordering');
	var arrowDown = '<img src="../media/com_odyssey/images/arrow_down.png" title="'+Joomla.JText._('COM_ODYSSEY_REORDER_TITLE')+'" alt="arrow down" height="16" width="16" />';
	$('#'+itemType+'-item-'+idNb+' .down-ordering').prepend(arrowDown);
	//Move the element just before the ordering input tag.
	$('#'+itemType+'-down-ordering-'+idNb).insertBefore($('#'+itemType+'-ordering-'+idNb));

	//Create the hidden field which holds the id number of the next item.
	properties = {'type':'hidden', 'name':itemType+'_next_'+idNb, 'id':itemType+'-next-'+idNb, 'value':idNbs[i + 1]};
	$('#'+itemType+'-item-'+idNb).createHTMLTag('<input>', properties);
      }

      //Add a transparent png to the first and last items of the list in order their row
      //has the same width as the other item rows.

      if(ordering == 1 && arrLength > 1) {
	var transparent = '<img src="../media/com_odyssey/images/transparent.png" id="'+itemType+'-order-transparent-'+idNb+'" class="order-transparent" alt="transparent" height="16" width="16" />';
	$(transparent).insertAfter($('#'+itemType+'-ordering-'+idNb));
      }

      if(ordering == arrLength) {
	var transparent = '<img src="../media/com_odyssey/images/transparent.png" id="'+itemType+'-order-transparent-'+idNb+'" class="order-transparent" alt="transparent" height="16" width="16" />';
	$(transparent).insertBefore($('#'+itemType+'-ordering-'+idNb));
      }
    }
  };


  $.fn.reverseOrder = function(direction, itemType, idNb) {
    //Get the id and name of the current item.
    var currentItemId = $('#'+itemType+'-id-'+idNb).val();
    var currentItemName = $('#'+itemType+'-name-'+idNb).val();

    //Get the id number of the previous or next item.
    if(direction == 'up') {
      var idNbToReverse = $('#'+itemType+'-prev-'+idNb).val();
    }
    else {
      var idNbToReverse = $('#'+itemType+'-next-'+idNb).val();
    }

    //Reverse the order of the 2 items.
    $('#'+itemType+'-id-'+idNb).val($('#'+itemType+'-id-'+idNbToReverse).val());
    $('#'+itemType+'-name-'+idNb).val($('#'+itemType+'-name-'+idNbToReverse).val());
    $('#'+itemType+'-id-'+idNbToReverse).val(currentItemId);
    $('#'+itemType+'-name-'+idNbToReverse).val(currentItemName);

    if(itemType == 'addon' || itemType == 'transitcity') {
      //Get the departure checkbox sets of both the current item and the item to shift with.
      var checkboxes = $('input[id^='+itemType+'-dpt-][id$=-'+idNb+']');
      var checkboxesToReverse = $('input[id^='+itemType+'-dpt-][id$=-'+idNbToReverse+']');
      var tmp = [];

      for(var i = 0; i < checkboxes.length; i++) {
	//Store the checkbox state into a temporary array before modifying it.  
	tmp.push(checkboxesToReverse[i].checked);
	if(checkboxes[i].checked === true) {
	  checkboxesToReverse[i].checked = true;
	}
	else {
	  checkboxesToReverse[i].checked = false;
	}
      }

      //Use the temporary array data to set the checkboxes of the current item.
      for(var i = 0; i < tmp.length; i++) {
	if(tmp[i] === true) {
	  checkboxes[i].checked = true;
	}
	else {
	  checkboxes[i].checked = false;
	}
      }
    }

    if(itemType == 'addon') {
      //Status information images must be reversed too.
      var currentStatus = $('#'+itemType+'-status-'+idNb).attr('src');
      var statusToReverse = $('#'+itemType+'-status-'+idNbToReverse).attr('src');
      $('#'+itemType+'-status-'+idNb).attr('src', statusToReverse);
      $('#'+itemType+'-status-'+idNbToReverse).attr('src', currentStatus);
    }

    if(itemType == 'option') {
      //Get checkboxes.
      var checkbox = $('input[id^=published-'+idNb+']');
      var checkboxToReverse = $('input[id^=published-'+idNbToReverse+']');
      var tmp = false;
      //Get the state of the main checkbox.
      if(checkbox.prop('checked')) {
	tmp = true;
      }

      //Shift states of checkboxes.
      checkbox.prop('checked', checkboxToReverse.prop('checked'))
      checkboxToReverse.prop('checked', tmp);

      //Get the code of the current item.
      var currentItemCode = $('#'+itemType+'-code-'+idNb).val();
      //Reverse the order of the code items.
      $('#'+itemType+'-code-'+idNb).val($('#'+itemType+'-code-'+idNbToReverse).val());
      $('#'+itemType+'-code-'+idNbToReverse).val(currentItemCode);

      //Get the description of the current item.
      var currentItemDesc = $('#'+itemType+'-description-'+idNb).val();
      //Reverse the order of the description items.
      $('#'+itemType+'-description-'+idNb).val($('#'+itemType+'-description-'+idNbToReverse).val());
      $('#'+itemType+'-description-'+idNbToReverse).val(currentItemDesc);

      //Get the image url of the current item.
      var currentItemImgUrl = $('#'+itemType+'-image-'+idNb).val();
      //Reverse the order of the image items.
      $('#'+itemType+'-image-'+idNb).val($('#'+itemType+'-image-'+idNbToReverse).val());
      $('#'+itemType+'-image-'+idNbToReverse).val(currentItemImgUrl);
      //Also reverse the order of the image preview by shifting the src attribute value.
      $('#'+itemType+'-preview-image-'+idNb).attr('src', $('#'+itemType+'-preview-image-'+idNbToReverse).attr('src'));
      $('#'+itemType+'-preview-image-'+idNbToReverse).attr('src', currentItemImgUrl);
    }
  };


  //Function called from a child window, so we have to be specific
  //and use the window object and the jQuery alias.
  window.jQuery.selectItem = function(id, name, idNb, itemType) {
    //Check if the current id is different from the new one.
    if($('#'+itemType+'-id-'+idNb).val() != id) {
      //Set the values of the selected item.
      $('#'+itemType+'-id-'+idNb).val(id);
      $('#'+itemType+'-name-'+idNb).val(name);

      //Some values can be passed as extra argument in order to keep selectItem
      //function generic (with the 4 basic arguments). 
      if(arguments[4]) { //Extra arguments start at index number 4 (ie: the 5th argument).
	//$('#'+itemType+'-name-'+idNb).val(arguments[4]);
      }
    }
    
    SqueezeBox.close();

    return this;
  };


  $.fn.openIntoIframe = function(link)
  {
    SqueezeBox.open(link, {handler: 'iframe', size: {x: 900, y: 530}});
    return this;
  };

  //Note: All the utility functions below are not chainable. 

  //Return a valid item id number which can be used in a container.
  $.fn.getIdNumber = function() {
    var newId = 0;
    //Check if the container has any div children. 
    if($(this).children('div').length > 0) {

      //Searching for the highest id number of the container.
      for(var i = 0, lgh = $(this).children('div').length; i < lgh; i++) {
	var idValue = $(this).children('div').eq(i).attr('id');
	//Extract the id number of the item from the end of its id value and
	//convert it into an integer.
	idNb = parseInt(idValue.replace(/.+-(\d+)$/, '$1'));

	//If the item id number is greater than ours, we use it.
	if(idNb > newId) {
	  newId = idNb;
	}
      }
    }

    //Return a valid id number (ie: the highest id number of the container plus 1).
    return newId + 1;
  };


  //Extract from the current url query the value of a given parameter.
  $.fn.getQueryParamByName = function(name) {
    //Get the query of the current url. 
    var query = decodeURIComponent($(location).attr('search'));
    //Create a regex which capture the value of the given parameter.
    var regex = new RegExp(name+'=([0-9a-zA-Z_-]+)');
    var result = regex.exec(query);

    return result[1];
  };


  //Return a data set corresponding to the given item type.
  //Data is initialised with empty or default values.
  $.fn.getDataSet = function(itemType) {
    var data = '';
    if(itemType == 'departure') {
      data = {'date_time':'', 'date_time_2':'', 'month':'', 'day':'', 'time':'00:00', 'city_id':'', 'dpt_id':0};
    } else if(itemType == 'city') {
      data = {'city_ordering':''};
    } else if(itemType == 'sequence') {
      data = {'sequence_ordering':''};
    } else if(itemType == 'addon') {
      data = {'addon_ordering':''};
    } else if(itemType == 'option') {
      data = {'option_ordering':'', 'option_description':'', 'option_image':''};
    } else if(itemType == 'image') {
      data = {'alt':'', 'ordering':'', 'src':'', 'width':'', 'height':''};
    } else { //
      //data = {};
    }

    return data;
  };


  $.fn.inArray = function(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
      if(haystack[i] == needle) return 1;
    }
    return 0;
  };


  $.fn.upperCaseFirstLetter = function(str) {
    return str.slice(0,1).toUpperCase() + str.slice(1);
  };


  $.fn.checkValueType = function(value, valueType) {
    switch(valueType) {
      case 'string':
	var regex = /^.+$/;
	//Check for string which doesn't start with a space character.
	//var regex = /^[^\s].+$/;
	break;

      case 'int':
	var regex = /^-?[0-9]+$/;
	break;

      case 'unsigned_int':
	var regex = /^[0-9]+$/;
	break;

      case 'float':
	var regex = /^-?[0-9]+(\.[0-9]+)?$/;
	break;

      case 'unsigned_float':
	var regex = /^[0-9]+(\.[0-9]+)?$/;
	break;

      default: //Unknown type.
	return false;
    }

    return regex.test(value);
  };


  $.fn.showTab = function(tabId) {
    var $tab = $('[data-toggle="tab"][href="#'+tabId+'"]');
    //Show the tab.
    $tab.show();
    $tab.tab('show');
  };


  //Prevent the user to change again the item type once the item is saved. 
  $.fn.lockTypeOption = function(itemId, itemName) {
    if(itemId != 0) {
      //Show the fake step type field.
      $('#jform_locked_'+itemName+'_type').parent().parent().css({'visibility':'visible','display':'block'});
      //Hide the step type drop down list.
      $('#jform_'+itemName+'_type').parent().parent().css({'visibility':'hidden','display':'none'});
    }
    else {
      //Show the step type drop down list.
      $('#jform_'+itemName+'_type').parent().parent().css({'visibility':'visible','display':'block'});
      //Hide the fake step type field.
      $('#jform_locked_'+itemName+'_type').parent().parent().css({'visibility':'hidden','display':'none'});
    }
  };


  $.fn.checkAlias = function(task, itemType) {
    //No need to check alias for the steps of link type.
    if(itemType == 'step' && $('#jform_step_type').val() == 'link') {
      return true;
    }

    var checking, field, value;
    var id = $('#jform_id').val();
    var catid = $('#jform_catid').val();

    //Set the id of the alias field according to the item type.
    var aliasId = 'jform_alias';
    if(itemType == 'step') {
      aliasId = 'jform_group_alias';
    }

    var alias = $('#'+aliasId).val();
    var name = $('#jform_name').val();
    var code = $('#jform_code').val();

    //Set the url parameters for the Ajax call.
    var urlQuery = {'task':task, 'id':id, 'catid':catid, 'item_type':itemType, 'alias':encodeURIComponent(alias), 'name':encodeURIComponent(name), 'code':code};
    //Ajax call which check for unique alias.
    $.ajax({
	type: 'GET', 
	url: 'components/com_odyssey/js/ajax/checkalias.php', 
	dataType: 'json',
	async: false, //We need a synchronous calling here.
	data: urlQuery,
	//Get result.
	success: function(result, textStatus, jqXHR) {
	  checking = result.checking;
	  value = result.value;
	  field = result.field;
	},
	error: function(jqXHR, textStatus, errorThrown) {
	  //Display the error.
	  alert(textStatus+': '+errorThrown);
	}
    });

    if(!checking) {
      var langVar = 'COM_ODYSSEY_DATABASE_ERROR_TRAVEL_UNIQUE_ALIAS';
      if(itemType == 'step') {
	langVar = 'COM_ODYSSEY_ERROR_DEPARTURE_UNIQUE_GROUP_ALIAS';
      }

      if(itemType == 'step' && field == 'code') {
	langVar = 'COM_ODYSSEY_DATABASE_ERROR_TRAVEL_UNIQUE_CODE';
	aliasId = 'jform_code';
      }

      alert(Joomla.JText._(langVar));
      var $itemTab = $('[data-toggle="tab"][href="#details"]');
      $itemTab.show();
      $itemTab.tab('show');
      $('#'+aliasId).addClass('invalid');
      $('#'+aliasId).val(value);
      return false;
    }

    return checking;
  };


  $.fn.createTableHeader = function(tableId) {
    //Check for pricerule tables.
    rule = '';
    if(/^travel\-pricerules/.test(tableId)) {
      //Get the corresponding heading.
      rule = 'RULE';
    }

    var thDatetime = '<tr><th class="datetime">'+Joomla.JText._('COM_ODYSSEY_HEADING_DEPARTURE_DATE')+'</th>';
    var thCity = '<th class="city">'+Joomla.JText._('COM_ODYSSEY_HEADING_DEPARTURE_CITY')+'</th>';
    var thMaxPas = '<th class="max-passengers">'+Joomla.JText._('COM_ODYSSEY_HEADING_MAX_PASSENGERS')+'</th>'; 
    var thPrice = '<th class="prices">'+Joomla.JText._('COM_ODYSSEY_HEADING_PRICE'+rule+'_PER_PASSENGER')+'</th>'; 
    var thSelect = '<th class="expand-collapse">#</th></tr>';

    $('#'+tableId).append(thDatetime+thCity+thMaxPas+thPrice+thSelect);

    return;
  };


  $.fn.createPriceRow = function(tableId, data, itemType, idNb) {
    //Set an id string according to the item type.
    //travel type (by default).
    var ids = data.dpt_id;
    var nameIds = data.dpt_id;
    var fieldName = 'price';

    if(itemType == 'addon') {
      ids = data.step_id+'-'+data.addon_id+'-'+data.dpt_id;
      nameIds = data.step_id+'_'+data.addon_id+'_'+data.dpt_id;
    }
    else if(itemType == 'addon_option') {
      ids = data.step_id+'-'+data.addon_id+'_'+data.addon_option_id+'-'+data.dpt_id;
      nameIds = data.step_id+'_'+data.addon_id+'_'+data.addon_option_id+'_'+data.dpt_id;
    }
    else if(itemType == 'transit_city') {
      ids = data.city_id+'-'+data.dpt_id;
      nameIds = data.city_id+'_'+data.dpt_id;
    }
    else if(itemType == 'travel_pricerule') {
      ids = data.dpt_id+'-'+idNb;
      nameIds = data.dpt_id+'_'+idNb;
      //Note: This function is also used to create travel price rule rows as the tables are similar.
      //However, for semantical reason a field name must be renamed.
      fieldName = 'value';
    }
    
    //Build the departure row (according to the item type).
    if(itemType == 'addon_option') {
      $('#'+tableId).append('<tr id="row-'+ids+'"><td id="col-1-'+ids+'" class="addon-option">'+Joomla.JText._('COM_ODYSSEY_OPTION')+'</td>'+
			    '<td id="col-2-'+ids+'" colspan="2" class="option-name">'+data.addon_option_name+'</td>'+
			    '<td id="col-4-'+ids+'"></td><td id="col-5-'+ids+'"></td></tr>');
    }
    else {
      var dateTime = data.date_time;
      var dateTime_2 = '';
      //Check for period date type.
      if(data.date_time_2 != '0000-00-00 00:00:00') {
	dateTime_2 = '<br />'+data.date_time_2;
      }

      $('#'+tableId).append('<tr id="row-'+ids+'"><td id="col-1-'+ids+'">'+dateTime+dateTime_2+'</td>'+
			    '<td id="col-2-'+ids+'">'+data.city+'</td>'+
			    '<td id="col-3-'+ids+'">'+data.max_passengers+'</td>'+
			    '<td id="col-4-'+ids+'"></td><td id="col-5-'+ids+'"></td></tr>');
    }

    //var dataIndexes = Object.keys(data.price_per_psgr).length;
    //Build the required labels and fields.
    for(var i = 0; i < data.max_passengers; i++) {
      var psgrNb = i + 1;
      //Note: For practical reason, in case of price rule rows the data attribute is also 
      //called price_per_psgr and not value_per_psgr. 
      var price = data.price_per_psgr[psgrNb];

      //Create a div wraper to contain both label and input tags.
      var properties = {'id':'wrap-'+psgrNb+'-'+ids};
      $('#col-4-'+ids).createHTMLTag('<div>', properties, 'wrap-price-psgr');

      properties = {'for':'price-psgr-'+psgrNb+'-'+ids};
      $('#wrap-'+psgrNb+'-'+ids).createHTMLTag('<label>', properties, 'price-psgr-label-'+psgrNb+'-'+ids);
      $('#wrap-'+psgrNb+'-'+ids+' .price-psgr-label-'+psgrNb+'-'+ids).text(psgrNb);

      properties = {'type':'text', 'name':fieldName+'_psgr_'+psgrNb+'_'+nameIds, 'id':'price-psgr-'+psgrNb+'-'+ids, 'value':price};
      $('#wrap-'+psgrNb+'-'+ids).createHTMLTag('<input>', properties, 'price-psgr');
    }
  };


  $.fn.createAddonItem = function(idNb, data) {
    var stepType = $('#jform_step_type').val();
    var modalOption = '';

    if(stepType == 'link') {
      var dptStepId = $('#jform_dpt_step_id_id').val();
      var currentDptStepId = $('#current-dpt-step-id').val();
      //Don't allow to select global addons into link steps
      modalOption = '&modal_option=no_global';

      //In case the departure step is removed in a link type step.
      if(dptStepId == '') {
	alert(Joomla.JText._('COM_ODYSSEY_ERROR_NO_DEPARTURE_STEP_SELECTED'));
	return;
      }

      //In case a new departure step has been replaced over the current one.
      if(dptStepId != currentDptStepId) {
	alert(Joomla.JText._('COM_ODYSSEY_ERROR_NEW_DEPARTURE_STEP_NOT_SAVED'));
	return;
      }
    }

    //Create the hidden input tag for the departure step id.
    var properties = {'type':'hidden', 'name':'addon_id_'+idNb, 'id':'addon-id-'+idNb, 'value':data.addon_id};
    $('#addon-item-'+idNb).createHTMLTag('<input>', properties);

    var linkToModal = 'index.php?option=com_odyssey&view=addons&layout=modal&tmpl=component&item_type=addon'+modalOption+'&id_nb='+idNb;
    $('#addon-item-'+idNb).createButton('select', '#', linkToModal);

    //Create the "name" label.
    properties = {'title':Joomla.JText._('COM_ODYSSEY_ITEM_NAME_TITLE')};
    $('#addon-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
    $('#addon-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_ODYSSEY_ITEM_NAME_LABEL'));

    var addonName = data.addon_name;
    if(data.addon_name != '' && data.addon_global == 1) {
      addonName = data.addon_name+Joomla.JText._('COM_ODYSSEY_INFO_GLOBAL_ADDON');
    }

    // Create a dummy text field to store the name.
    properties = {'type':'text', 'disabled':'disabled', 'id':'addon-name-'+idNb, 'value':addonName};
    $('#addon-item-'+idNb).createHTMLTag('<input>', properties, 'item-name');

    //Get the number of items within the container then use it as ordering
    //number for the current item.
    var ordering = $('#addon-container').children('div').length;
    if(data.addon_ordering !== '') {
      ordering = data.addon_ordering;
    }
    //Create the "order" input.
    properties = {'type':'text', 'name':'addon_ordering_'+idNb, 'id':'addon-ordering-'+idNb, 'readonly':'readonly', 'value':ordering};
    $('#addon-item-'+idNb).createHTMLTag('<input>', properties, 'item-ordering');
    $.fn.setOrderManagement('addon');

    if(data.addon_status !== undefined) {
      //Create an image corresponding to the status information.
      var addonStatus = '<img src="../media/com_odyssey/images/status_'+data.addon_status+'.png" title="'+Joomla.JText._('COM_ODYSSEY_STATUS_TITLE')+'" alt="status" id="addon-status-'+idNb+'" class="addon-status" height="16" width="16" />';
      $('#addon-item-'+idNb).append(addonStatus);
    }

    //Create the item removal button.
    $('#addon-item-'+idNb).createButton('remove_reorder');

    //Add departure checkboxes to the addons dynamicaly added in the step item.
    $.fn.getDepartureCheckboxes(idNb, data, 'addon');
  };


  $.fn.getDepartureCheckboxes = function(idNb, data, itemType) {
    //Display and set the departures of the step as checkboxes.
    if(data.departures == undefined) {
      //Create unchecked checkboxes from the return data of the generic function.
      var departures = odyssey.getAddonDepartures();
    }
    else {
      //Create and set checkboxes from the given data.
      var departures = data.departures;
    }

    properties = {'id':'wrap-'+itemType+'-departures-'+idNb};
    $('#'+itemType+'-item-'+idNb).createHTMLTag('<div>', properties, 'wrap-departures');

    properties = {'title':Joomla.JText._('COM_ODYSSEY_DEPARTURES_TITLE')};
    $('#wrap-'+itemType+'-departures-'+idNb).createHTMLTag('<span>', properties, 'departures-label');
    $('#wrap-'+itemType+'-departures-'+idNb+' .departures-label').text(Joomla.JText._('COM_ODYSSEY_DEPARTURES_LABEL'));

    //Create departure checkboxes.
    for(var i = 0; i < departures.length; i++) {
      var dptId = departures[i].dpt_id;
      var dptNb = i + 1;

      $('#wrap-'+itemType+'-departures-'+idNb).append('<span class="departure-number" id="'+itemType+'-departure-number-'+dptId+'-'+idNb+'">'+dptNb+'</span>');

      properties = {'type':'checkbox', 'name':itemType+'_dpt_'+dptId+'_'+idNb, 'id':itemType+'-dpt-'+dptId+'-'+idNb, 'value':dptId};

      if(departures[i].selected != '') {
	properties.checked = true;
      }

      //In case of a link type step some departures might be inactive.
      if(departures[i].active == '') {
	properties.disabled = 'disabled';
	properties.checked = false;
	$('#'+itemType+'-departure-number-'+dptId+'-'+idNb).addClass('muted');
      }

      $('#wrap-'+itemType+'-departures-'+idNb).createHTMLTag('<input>', properties, 'departure-checkbox');
    }
  };

 })(jQuery);

