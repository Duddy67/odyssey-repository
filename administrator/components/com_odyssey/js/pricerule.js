
(function($) {
  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Create a container for each dynamical item.
    $('#condition').getContainer();
    $('#target').getContainer();
    $('#recipient').getContainer();

    var pruleId = $('#jform_id').val();
    var pruleType = $('#jform_prule_type').val();
    var target = $('#jform_target').val();
    var recipient = $('#jform_recipient').val();

    if(pruleId != 0) {
      var urlQuery = {'prule_id':pruleId, 'prule_type':pruleType, 'target':target, 'recipient':recipient};

      //Ajax call which get item data previously set.
      $.ajax({
	  type: 'GET', 
	  url: 'components/com_odyssey/js/ajax/pricerule.php', 
	  dataType: 'json',
	  data: urlQuery,
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {
	    //Create an item type for each result retrieved from the database.
	    $.each(results.recipient, function(i, result) { $.fn.createItem('recipient', result); });
	    $.each(results.target, function(i, result) { $.fn.createItem('target', result); });
	    $.each(results.condition, function(i, result) { $.fn.createItem('condition', result); });
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	  }
      });
    }

    //Bind some select tags to functions which set some of the price rule tags
    //whenever a different option is selected.
    $('#jform_prule_type').change( function() { $.fn.changePriceruleType(); });
    $('#jform_target').change( function() { $.fn.changeTarget(); });
    $('#jform_condition').change( function() { $.fn.changeCondition(); });
    $('#jform_recipient').change( function() { $.fn.changeRecipient(); });

    //Set as functions the global variables previously declared.
    checkPriceruleData = $.fn.checkPriceruleData;

    //Set the form according to the selected price rule type.
    $.fn.changePriceruleType();
  });


  $.fn.changePriceruleType = function() {
    //Hide or show tabs and fields according to the price rule type selected.
    if($('#jform_prule_type').val() == 'catalog') {
      $('a[href="#pricerule-conditions"]').parent().css({'visibility':'hidden','display':'none'});
      $('#jform_show_rule').parent().parent().css({'visibility':'visible','display':'block'});
    }
    else { //cart
      $('a[href="#pricerule-conditions"]').parent().css({'visibility':'visible','display':'block'});
      $('#jform_show_rule').parent().parent().css({'visibility':'hidden','display':'none'});
    }

    //Update the target drop down list.
    $.fn.changeTarget();
  };


  $.fn.changeRecipient = function() {
    //Remove previous recipient items whenever a new option is selected.
    $('#recipient-container').removeItem();
  };

  //Switch the target options according to the current price rule type.
  $.fn.changeTarget = function() {
    //First remove all of the target items from the container
    $('#target-container').removeItem();
    //Get the selected option.
    var selected = $('#jform_target').val();
    //then empty the previous target options
    $('#jform_target').empty();
    var pruleType = $('#jform_prule_type').val();

    //Set a default target option whenever the price rule type changes. 
    if(pruleType == 'catalog' && selected == 'cart_amount') {
      selected = 'travel';
    }

    //Note: For now the cart price rule type has only one target.
    if(pruleType == 'cart' && selected != 'cart_amount') {
      selected = 'cart_amount';
    }

    //Show or hide the value field according to the selection.
    if(selected != 'travel') {
      $('#jform_value').parent().parent().css({'visibility':'visible','display':'block'});
    }
    else {
      $('#jform_value').parent().parent().css({'visibility':'hidden','display':'none'});
    }

    //Create the require target options.
    if($('#jform_prule_type').val() == 'catalog' || $('#jform_prule_type').val() == 'coupon') {
      var options = '<option value="travel">'+Joomla.JText._('COM_ODYSSEY_OPTION_TRAVEL')+'</option>';
      options += '<option value="travel_cat">'+Joomla.JText._('COM_ODYSSEY_OPTION_TRAVEL_CAT')+'</option>';
      options += '<option value="addon">'+Joomla.JText._('COM_ODYSSEY_OPTION_ADDON')+'</option>';
      //Display the "add" button.
      $('#add-target-button-0').parent().css({'visibility':'visible','display':'block'});
    }
    else { //cart
      var options = '<option value="cart_amount">'+Joomla.JText._('COM_ODYSSEY_OPTION_CART_AMOUNT')+'</option>';
      //No need dynamical items here, so we hide the "add" button.
      $('#add-target-button-0').parent().css({'visibility':'hidden','display':'none'});
    }
    //Add options to the select tag.
    $('#jform_target').html(options);
    //Set the tag to the proper value.
    $('#jform_target').val(selected);
    //Update the chosen plugin.
    $('#jform_target').trigger('liszt:updated');
  };


  $.fn.changeCondition = function() {
    //First remove of all the items from the container.
    $('#condition-container').removeItem();

    //Get the selected option.
    var selected = $('#jform_condition').val();
  };


  $.fn.createRecipientItem = function(idNb, data) {
    //Create the hidden input tag for the recipient id.
    var properties = {'type':'hidden', 'name':'recipient_id_'+idNb, 'id':'recipient-id-'+idNb, 'value':data.id};
    $('#recipient-item-'+idNb).createHTMLTag('<input>', properties);

    //Set the view to use according to the recipient type (customer or customer_group).
    var view = 'users';
    //Check for the recipient type.
    if($('#jform_recipient').val() == 'customer_group') {
      view = 'groups';
    }
    //Create the select button.
    var linkToModal = 'index.php?option=com_odyssey&view='+view+'&layout=modal&tmpl=component&id_nb='+idNb;
    $('#recipient-item-'+idNb).createButton('select', '#', linkToModal);

    //Create the "name" label.
    properties = {'title':Joomla.JText._('COM_ODYSSEY_ITEM_NAME_TITLE')};
    $('#recipient-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
    $('#recipient-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_ODYSSEY_ITEM_NAME_LABEL'));

    // Create a dummy text field to store the name.
    properties = {'type':'text', 'disabled':'disabled', 'id':'recipient-name-'+idNb, 'value':data.name};
    $('#recipient-item-'+idNb).createHTMLTag('<input>', properties, 'item-name');
    //Create the removal button.
    $('#recipient-item-'+idNb).createButton('remove');
  };


  $.fn.createTargetItem = function(idNb, data) {
    //Create the hidden input tag for the target id.
    var properties = {'type':'hidden', 'name':'target_id_'+idNb, 'id':'target-id-'+idNb, 'value':data.id};
    $('#target-item-'+idNb).createHTMLTag('<input>', properties);

    var pruleType = $('#jform_prule_type').val();
    var targetType = $('#jform_target').val();

    //Create the select button.
    var linkToModal = $.fn.createLinkToModal('target', idNb);
    //A function must be called when travel target type is selected. 
    if(pruleType == 'catalog' && targetType == 'travel') {
      linkToModal = linkToModal+'&function=createPriceRuleTable';
    }

    $('#target-item-'+idNb).createButton('select', '#', linkToModal);

    //Create the "name" label.
    properties = {'title':Joomla.JText._('COM_ODYSSEY_ITEM_NAME_TITLE')};
    $('#target-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
    $('#target-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_ODYSSEY_ITEM_NAME_LABEL'));

    // Create a dummy text field to store the name.
    properties = {'type':'text', 'disabled':'disabled', 'id':'target-name-'+idNb, 'value':data.name};
    $('#target-item-'+idNb).createHTMLTag('<input>', properties, 'item-name');
    //Create the removal button.
    $('#target-item-'+idNb).createButton('remove');

    if(pruleType == 'catalog') {
      //Load the travel price rule data.
      if(targetType == 'travel' && data.travel_id !== undefined) {
	jQuery.createPriceRuleTable(data.travel_id, data.name, data.dpt_step_id, idNb);
      }

      //Create the required fields for the number lists.

      if(targetType != 'travel') {
	//Wrap ids elements in a div.
	var properties = {'id':'wrap-ids-'+idNb};
	$('#target-item-'+idNb).createHTMLTag('<div>', properties, 'wrap-ids');

	if(targetType == 'addon') {
	  properties = {'title':Joomla.JText._('COM_ODYSSEY_TRAVEL_IDS_TITLE')};
	  $('#wrap-ids-'+idNb).createHTMLTag('<span>', properties, 'travel-ids-label');
	  $('#wrap-ids-'+idNb+' .travel-ids-label').text(Joomla.JText._('COM_ODYSSEY_TRAVEL_IDS_LABEL'));
	  properties = {'type':'text', 'name':'travel_ids_'+idNb, 'id':'travel-ids-'+idNb, 'value':data.travel_ids};
	  $('#wrap-ids-'+idNb).createHTMLTag('<input>', properties, 'travel-ids');
	  properties = {'title':Joomla.JText._('COM_ODYSSEY_STEP_IDS_TITLE')};
	  $('#wrap-ids-'+idNb).createHTMLTag('<span>', properties, 'step-ids-label');
	  $('#wrap-ids-'+idNb+' .step-ids-label').text(Joomla.JText._('COM_ODYSSEY_STEP_IDS_LABEL'));
	  properties = {'type':'text', 'name':'step_ids_'+idNb, 'id':'step-ids-'+idNb, 'value':data.step_ids};
	  $('#wrap-ids-'+idNb).createHTMLTag('<input>', properties, 'step-ids');
	}

	properties = {'title':Joomla.JText._('COM_ODYSSEY_PASSENGER_NUMBERS_TITLE')};
	$('#wrap-ids-'+idNb).createHTMLTag('<span>', properties, 'psgr-nbs-label');
	$('#wrap-ids-'+idNb+' .psgr-nbs-label').text(Joomla.JText._('COM_ODYSSEY_PASSENGER_NUMBERS_LABEL'));
	//
	properties = {'type':'text', 'name':'psgr_nbs_'+idNb, 'id':'psgr-nbs-'+idNb, 'value':data.psgr_nbs};
	$('#wrap-ids-'+idNb).createHTMLTag('<input>', properties, 'psgr-nbs');
      }
    }
  };


  //Note: Function called from a child window, so we have to be specific and use the window object and the jQuery alias.
  window.jQuery.createPriceRuleTable = function(travelId, itemName, dptStepId, idNb) {

    //Set the travel name and id in the corresponding target tags.
    $('#target-id-'+idNb).val(travelId);
    $('#target-name-'+idNb).val(itemName);

    //Create the hidden input tag for the departure step id.
    var properties = {'type':'hidden', 'name':'dpt_step_id_'+idNb, 'id':'dpt-step-id-'+idNb, 'value':dptStepId};
    $('#target-item-'+idNb).createHTMLTag('<input>', properties);

    //Remove a possible previous table.
    $('#travel-pricerules-'+idNb).empty();

    //Create the travel table.
    var properties = {'id':'travel-pricerules-'+idNb};
    $('#target-item-'+idNb).createHTMLTag('<table>', properties, 'table table-striped price-rows');
    $.fn.createTableHeader('travel-pricerules-'+idNb);

    //Set the required arguments for the ajax function.
    var pruleId = $('#jform_id').val();
    var urlQuery = {'travel_id':travelId, 'dpt_step_id':dptStepId, 'prule_id':pruleId};
    //Get the travel data and load it into the travel table.
    $.ajax({
	type: 'GET', 
	url: 'components/com_odyssey/js/ajax/pricerule.php', 
	dataType: 'json',
	data: urlQuery,
	//Get results as a json array.
	success: function(results, textStatus, jqXHR) {
	  //Create and insert a price rule row for each departure in the travel table.
	  $.each(results.travel_pricerule, function(i, result) {
	    $.fn.createPriceRow('travel-pricerules-'+idNb, result, 'travel_pricerule', idNb);
	  });
	},
	error: function(jqXHR, textStatus, errorThrown) {
	  //Display the error.
	  alert(textStatus+': '+errorThrown);
	}
    });

    SqueezeBox.close();
  };


  $.fn.createConditionItem = function(idNb, data) {
    //Get the value of the item type.
    var valueType = $('#jform_condition').val();

    //Create the hidden input tag for the condition id.
    var properties = {'type':'hidden', 'name':'condition_id_'+idNb, 'id':'condition-id-'+idNb, 'value':data.id};
    $('#condition-item-'+idNb).createHTMLTag('<input>', properties);

    //Create the select button.
    var linkToModal = $.fn.createLinkToModal('condition', idNb);
    $('#condition-item-'+idNb).createButton('select', '#', linkToModal);

    //Create the "name" label.
    properties = {'title':Joomla.JText._('COM_ODYSSEY_ITEM_NAME_TITLE')};
    $('#condition-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
    $('#condition-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_ODYSSEY_ITEM_NAME_LABEL'));

    // Create a dummy text field to store the name.
    properties = {'type':'text', 'disabled':'disabled', 'id':'condition-name-'+idNb, 'value':data.name};
    $('#condition-item-'+idNb).createHTMLTag('<input>', properties, 'item-name');
    //Create the removal button.
    $('#condition-item-'+idNb).createButton('remove');

    //Wrap operator and value fields in a div.
    var properties = {'id':'wrap-condition-'+idNb};
    $('#condition-item-'+idNb).createHTMLTag('<div>', properties, 'wrap-condition');

    //Create the "operator" label (Note: Operator tag is common to each condition types).
    properties = {'title':Joomla.JText._('COM_ODYSSEY_COMPARISON_OPERATOR_TITLE')};
    $('#wrap-condition-'+idNb).createHTMLTag('<span>', properties, 'operator-select-label');
    $('#wrap-condition-'+idNb+' .operator-select-label').text(Joomla.JText._('COM_ODYSSEY_COMPARISON_OPERATOR_LABEL'));
    //Create the operator drop down list.
    properties = {'name':'operator_'+idNb, 'id':'operator-'+idNb};
    $('#wrap-condition-'+idNb).createHTMLTag('<select>', properties, 'operator-select');
    //Set values and texts option.
    //Important: We don't use real comparison signs in option values as < sign
    //causes problem because of the < and > html tags.
    //ie: Equal, gt: Greater Than, lt: Lower Than, gtoet: Greater Than Or Equal To,
    //ltoet: Lower Than Or Equal To.
    var options = '<option value="e">=</option><option value="gt">&gt;</option><option value="lt">&lt;</option>';
    options += '<option value="gtoet">&gt;=</option><option value="ltoet">&lt;=</option>';
    $('#operator-'+idNb).html(options);

    if(data.operator !== '') {
      //Set the selected option.
      $('#operator-'+idNb+' option[value="'+data.operator+'"]').attr('selected', true);
    }

    //Create a quantity or amount label and input tags according to the type of
    //the value.
    if(valueType == 'travel_cat_amount') {
      //Create an amount label.
      properties = {'title':Joomla.JText._('COM_ODYSSEY_ITEM_AMOUNT_TITLE')};
      $('#wrap-condition-'+idNb).createHTMLTag('<span>', properties, 'item-amount-label');
      $('#wrap-condition-'+idNb+' .item-amount-label').text(Joomla.JText._('COM_ODYSSEY_ITEM_AMOUNT_LABEL'));

      //Format item amount if any.
      if(data.item_amount !== '') {
	data.item_amount = parseFloat(data.item_amount).toFixed(2);
      }
      //Create an text input to store the amount to compare.
      properties = {'type':'text', 'name':'condition_item_amount_'+idNb, 'id':'condition-item-amount-'+idNb, 'value':data.item_amount};
      $('#wrap-condition-'+idNb).createHTMLTag('<input>', properties, 'item-amount');
    } else { //The rest of the condition types are compared against quantity.
      //Create a quantity label.
      properties = {'title':Joomla.JText._('COM_ODYSSEY_ITEM_QUANTITY_TITLE')};
      $('#wrap-condition-'+idNb).createHTMLTag('<span>', properties, 'item-quantity-label');
      $('#wrap-condition-'+idNb+' .item-quantity-label').text(Joomla.JText._('COM_ODYSSEY_ITEM_QUANTITY_LABEL'));

      //Create an text input to store the quantity to compare.
      properties = {'type':'text', 'name':'condition_item_qty_'+idNb, 'id':'condition-item-qty-'+idNb, 'value':data.item_qty};
      $('#wrap-condition-'+idNb).createHTMLTag('<input>', properties, 'item-quantity');
    }
  };


  //Build a link to a modal window according to the item type.
  $.fn.createLinkToModal = function(type, idNb) {
    var view = '';

    //Check for the value of the item type.
    switch($('#jform_'+type).val()) {
      case 'travel':
	view = 'travels';
	break;
      case 'travel_cat':
	view = 'categories';
	break;
      case 'addon':
	view = 'addons';
	break;
      case 'travel_cat_amount':
	view = 'categories';
	break;
      case 'customer':
	view = 'users';
	break;
      case 'customer_group':
	view = 'groups';
	break;
    }

    return 'index.php?option=com_odyssey&view='+view+'&layout=modal&tmpl=component&id_nb='+idNb+'&type='+type;
  };


  $.fn.checkPriceruleData = function() {
    var result = true;
    var idValue = '';

    //Get the Bootstrap recipient tag. 
    var $recipientTab = $('[data-toggle="tab"][href="#pricerule-recipients"]');

    //Check for each recipient value.
    if($('#recipient-container > div').length) {
      $('[id^="recipient-id-"]').each(function() { 
	if($(this).val() == '') {
	  alert(Joomla.JText._('COM_ODYSSEY_ERROR_EMPTY_VALUE'));
	  idValue = $(this).prop('id');
	  var idNb = parseInt(idValue.replace(/.+-(\d+)$/, '$1')) || 0;
	  idValue = 'recipient-name-'+idNb;
	  result = false;
	  return false; //Note: Leaves the loop, not the function.
	}
      });

      if(!result) {
	$recipientTab.show();
	$recipientTab.tab('show');
	$('#'+idValue).addClass('invalid');
	return false;
      }
    }
    else { //Recipient container is empty.
      alert(Joomla.JText._('COM_ODYSSEY_ERROR_NO_RECIPIENT_SELECTED'));
      $recipientTab.show();
      $recipientTab.tab('show');
      return false;
    }

    var pruleType = $('#jform_prule_type').val();
    var target = $('#jform_target').val();
    var travelIds = [];
    //Check for either target or condition item according to the price rule type.
    var item = 'target';
    if(pruleType == 'cart') {
      item = 'condition';
    }

    //Get the corresponding Bootstrap item tag (Note: tabs need plural). 
    var $itemTab = $('[data-toggle="tab"][href="#pricerule-'+item+'s"]');

    //Check for each target or condition item value.
    if($('#'+item+'-container > div').length) {
      $('[id^="'+item+'-id-"]').each(function() { 
	if($(this).val() == '') {
	  alert(Joomla.JText._('COM_ODYSSEY_ERROR_EMPTY_VALUE'));
	  idValue = $(this).prop('id');
	  var idNb = parseInt(idValue.replace(/.+-(\d+)$/, '$1')) || 0;
	  idValue = item+'-name-'+idNb;
	  result = false;
	  return false; //Note: Leaves the loop, not the function.
	}

	//Check for duplicate travel entry.
	if(pruleType == 'catalog' && target == 'travel') {
	  if(jQuery.inArray($(this).val(), travelIds) === -1) {
	    travelIds.push($(this).val());
	  }
	  else {
	    alert(Joomla.JText._('COM_ODYSSEY_ERROR_TRAVEL_DUPLICATE_ENTRY'));
	    idValue = $(this).prop('id');
	    var idNb = parseInt(idValue.replace(/.+-(\d+)$/, '$1')) || 0;
	    idValue = item+'-name-'+idNb;
	    result = false;
	    return false; //Note: Leaves the loop, not the function.
	  }
	}
      });

      if(!result) {
	$itemTab.show();
	$itemTab.tab('show');
	$('#'+idValue).addClass('invalid');
	return false;
      }

      //In case of a condition we also have to check the value of the condition itself.
      if(item == 'condition') {
	//Check the since_date datetime value.
	var datetime = $('#jform_since_date').val();
	var regex = /^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$/;
	if(datetime != '' && !regex.test(datetime)) {
	  alert(Joomla.JText._('COM_ODYSSEY_ERROR_INVALID_DATETIME_VALUE'));
	  $itemTab.show();
	  $itemTab.tab('show');
	  $('#jform_since_date').addClass('invalid');
	  return false;
	}

	//Set the type according to the condition type.
	var type = 'qty';
	if($('#jform_condition').val() == 'product_cat_amount') {
	  type = 'amount';
	}

	//Check for each condition value.
	$('[name^="condition_item_'+type+'_"]').each(function() { 
	  //Note: $.isNumeric function check for both numeric and float values.
	  if(!$.isNumeric($(this).val()) || $(this).val() == 0) {
	    alert(Joomla.JText._('COM_ODYSSEY_ERROR_INCORRECT_OR_EMPTY_VALUE'));
	    idValue = $(this).prop('id');
	    result = false;
	    return false; //Note: Leaves the loop, not the function.
	  }
	});

	if(!result) {
	  $itemTab.show();
	  $itemTab.tab('show');
	  $('#'+idValue).addClass('invalid');
	  return false;
	}
      }
    }
    else { //Target or condition container is empty.
      alert(Joomla.JText._('COM_ODYSSEY_ERROR_NO_'+item.toUpperCase()+'_SELECTED'));
      $itemTab.show();
      $itemTab.tab('show');
      return false;
    }

    //If one or more travels are selected all the price rule values must be checked.
    if(pruleType == 'catalog' && target == 'travel') {
      //Check for each price rule value in nested in tables.
      $('[name^="value_psgr_"]').each(function() { 
	//Note: $.isNumeric function check for both numeric and float values.
	if(!$.isNumeric($(this).val())) {
	  alert(Joomla.JText._('COM_ODYSSEY_ERROR_INCORRECT_OR_EMPTY_VALUE'));
	  idValue = $(this).prop('id');
	  result = false;
	  return false; //Note: Leaves the loop, not the function.
	}
      });

      if(!result) {
	$itemTab.show();
	$itemTab.tab('show');
	$('#'+idValue).addClass('invalid');
	return false;
      }
    }

    if(target != 'travel' && !$.isNumeric($('#jform_value').val())) {
      alert(Joomla.JText._('COM_ODYSSEY_ERROR_INCORRECT_OR_EMPTY_VALUE'));
      $itemTab.show();
      $itemTab.tab('show');
      $('#jform_value').addClass('invalid');
      return false;
    }

    return true;
  };

})(jQuery);
