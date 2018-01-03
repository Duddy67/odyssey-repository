
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    $('input[type=checkbox]').click( function() { $.fn.setTotalAmount($(this), 'checkbox'); });
    $('input[type=radio]').click( function() { $.fn.setTotalAmount($(this), 'radio'); });
    //
    $.fn.initTotalAmount();
  });

  $.fn.setTotalAmount = function(obj, type) {
    //Gets the price of the checked (or unchecked) element as well as the current total amount.
    var addonPrice = $.fn.getElementPrice(obj);
    var totalAmount = parseFloat($('#js_total_amount').val());
    var isOption = false;
    var addonOptionAmount = 0;
    var prevAddonOptionAmount = 0;
    
    if(obj.attr('name').substring(0,6) == 'option') {
      //Ensure first that the "parent" addon is checked. If it's not there is no need to
      //go further.
      if(!$.fn.isAddonChecked(obj, type)) {
        return;
      }

      isOption = true;
    }

    if(!isOption) {
      //Gets the amount of the group of options (if any) for this addon.
      addonOptionAmount = $.fn.getAddonOptionAmount(obj);
    }

    if(type == 'checkbox' && addonPrice > 0) {
      //Detects whether the checkbox is checked or unchecked.
      if(obj.attr('checked') == 'checked') { // add to amount
	var newAmount = addonPrice + addonOptionAmount + totalAmount;
      }
      else { // subtract from amount
	var newAmount = totalAmount - (parseFloat(addonPrice) + parseFloat(addonOptionAmount));
      }
    }
    else { // radio (the trickiest part !)
      //Gets both the type and the id of the element from its name (eg: (single)_(2_23))
      regex = /^(\w+)_([0-9]+_[0-9]+)$/;
      result = regex.exec(obj.attr('name'));

      //Checks if we're dealing with an addon or an addon option (eg: option_single_6_32)
      //and sets the option variable accordingly.
      var option = '';
      if(isOption) {
	option = 'option_';
      }

      //Builds the id of the tag that contain the value of the radio button previously checked.
      var prevRadioCheckedId = 'js_prev_'+option+'radio_checked_'+result[2];
      //Gets the value of the radio button previously checked. 
      var prevRadioCheckedVal = $('#'+prevRadioCheckedId).val();

      //Gets the value of the radio button currently checked. 
      var value = obj.val();
      //The same radio button has been clicked twice or more.
      if(value == prevRadioCheckedVal) {
	//Don't go further.
	return;
      }

      //Gets the addon price of the radio button previously checked. 
      var prevAddonPrice = $('#js_'+result[1]+'_'+result[2]+'_'+prevRadioCheckedVal).val();

      //Last but not least, if we're dealing with an addon we also need to get the amount of the group 
      //of options (if any) for this addon.
      if(!isOption) {
	//Gets the previous checked element.
	var prevCheckedAddon = $('input[value="'+prevRadioCheckedVal+'"][name="single_'+result[2]+'"]');
	prevAddonOptionAmount = $.fn.getAddonOptionAmount(prevCheckedAddon);
      }

      //Subtracts its price to the current total amount.
      totalAmount = totalAmount - (parseFloat(prevAddonPrice) + parseFloat(prevAddonOptionAmount));
      //Computes the new total amount.
      var newAmount = totalAmount + addonPrice + addonOptionAmount;
      //The current radio button is now also the previously checked one.
      $('#'+prevRadioCheckedId).val(value);
    }

    //Sets the total amount.
    $('#js_total_amount').val(newAmount);
    $('#js_display_total_amount').text(newAmount);
  },

  //Gets all the elements checked by default then adds their prices (if any) to the total amount.
  $.fn.initTotalAmount = function() {
    //Gets all the radio button and checkboxes checked by default.
    $("input:checked").each(function(){
      //In case of an addon option, ensure first that the "parent" addon is checked. If it's not 
      //there is no need to go further.
      if($(this).attr('name').substring(0,6) == 'option' && !$.fn.isAddonChecked($(this), $(this).attr('type'))) {
	return false;
      }

      //Gets the price of the addon or addon option.
      var elementPrice = $.fn.getElementPrice($(this));

      if(elementPrice > 0) {
        //Gets the current total amount.
	var totalAmount = parseFloat($('#js_total_amount').val());
        //Computes the new total amount.
	var newAmount = totalAmount + elementPrice;
	//Sets the total amount.
	$('#js_total_amount').val(newAmount);
	$('#js_display_total_amount').text(newAmount);
      }
    });
  },

  $.fn.getElementPrice = function(elem) {
    //Gets the name of the element without the brakets "[]" (used with checkboxes).
    var regex = /^(\w+)/;
    var result = regex.exec(elem.attr('name'));
    //Builds the id of the tag that contain the price of the current element.
    var id = 'js_'+result[1]+'_'+elem.val();

    return parseFloat($('#'+id).val());
  },

  $.fn.isAddonChecked = function(elem, type) {
    //Gets the step and addon ids from the name of the addon option.
    var regex = /_([0-9]+)_([0-9]+)$/;
    if(type == 'checkbox') {
      //Removes the brackets from the result. 
      regex = /_([0-9]+)_([0-9]+)\[\]$/;
    }

    var result = regex.exec(elem.attr('name'));
    var stepId = result[1];
    var addonId = result[2];
    //Gets the addon input from its value, AND with its name ends by the step id (with OR
    //without brakets) AND with its name does not start with "option_".
    var addon = $('input[value="'+addonId+'"]').filter('[name$="'+stepId+'"],[name$="'+stepId+'[]"]').not('[name^="option_"]');
    //hidden type is used for no selectable addons (ie: addons displayed by default) 
    //These kind of addons are considerated as checked by default. 
    if(addon.attr('type') == 'hidden') {
      return true;
    }

    //Radio button or checkbox.
    if(addon.attr('checked') == 'checked') {
      return true;
    }

    return false;
  },

  $.fn.getAddonOptionAmount = function(elem) {
    //Gets the step id from the name of the addon.
    var regex = /_([0-9]+)$/;
    if(elem.attr('type') == 'checkbox') {
      //Removes the brackets from the result. 
      regex = /_([0-9]+)\[\]$/;
    }

    var result = regex.exec(elem.attr('name'));
    var stepId = result[1];
    var addonOptionAmount = 0;
    //Checks if the addon has addon options as checkboxes.
    if($('input[name="option_multi_'+stepId+'_'+elem.val()+'[]"]').val() !== undefined) {
      //Loops through the checkboxes.
      $('input[name="option_multi_'+stepId+'_'+elem.val()+'[]"]').each(function(){
	//if the checkbox is checked we add its price (if any) to the option amount.
	if($(this).attr('checked') == 'checked') {
	  addonOptionAmount = addonOptionAmount + $.fn.getElementPrice($(this));
	}
      });

      return addonOptionAmount;
    }

    //Checks if the addon has addon options as radio button.
    if($('input[name="option_single_'+stepId+'_'+elem.val()+'"]').val() !== undefined) {
      //Loops through the radio buttons.
      $('input[name="option_single_'+stepId+'_'+elem.val()+'"]').each(function(){
	//if the checkbox is checked we add its price (if any) to the option amount.
	if($(this).attr('checked') == 'checked') {
	  addonOptionAmount = addonOptionAmount + $.fn.getElementPrice($(this));
	  //Quits the each function as it can only be one radio button checked at the time.
	  return;
	}
      });

      return addonOptionAmount;
    }

    //There is no option for this addon.
    return addonOptionAmount;
  }
})(jQuery);

