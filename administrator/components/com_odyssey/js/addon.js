
(function($) {
  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //Create a container for each dynamical item.
    $('#option').getContainer();

    //Get some variables.
    var addonId = $('#jform_id').val();
    var addonType = $('#jform_addon_type').val();

    //If the step item exists we need to get the data of the dynamical items.
    if(addonId != 0 && addonType != 'addon_option') {
      var urlQuery = {'addon_id':addonId};

      //Ajax call which get item data previously set.
      $.ajax({
	  type: 'GET', 
	  url: 'components/com_odyssey/js/ajax/addon.php', 
	  dataType: 'json',
	  data: urlQuery,
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {
	    //Create an item type for each result retrieved from the database.
	    $.each(results.option, function(i, result) { $.fn.createItem('option', result); });
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	  }
      });
    }

    $('#jform_addon_type').change( function() { $.fn.setAddonType(); });
    $.fn.setAddonType();

    $.fn.lockTypeOption(addonId, 'addon');
  });


  $.fn.createOptionItem = function(idNb, data) {
    //Create the hidden input tag for the option id.
    var properties = {'type':'hidden', 'name':'option_id_'+idNb, 'id':'option-id-'+idNb, 'value':data.option_id};
    $('#option-item-'+idNb).createHTMLTag('<input>', properties);

    //Create the "name" label.
    properties = {'title':Joomla.JText._('COM_ODYSSEY_ITEM_NAME_TITLE')};
    $('#option-item-'+idNb).createHTMLTag('<span>', properties, 'item-name-label');
    $('#option-item-'+idNb+' .item-name-label').text(Joomla.JText._('COM_ODYSSEY_ITEM_NAME_LABEL'));

    //Create the "name" input.
    var properties = {'type':'text', 'name':'option_name_'+idNb, 'id':'option-name-'+idNb, 'value':data.option_name};
    $('#option-item-'+idNb).createHTMLTag('<input>', properties, 'item-name');

    //Create the "code" label.
    properties = {'title':Joomla.JText._('COM_ODYSSEY_ITEM_CODE_TITLE')};
    $('#option-item-'+idNb).createHTMLTag('<span>', properties, 'item-code-label');
    $('#option-item-'+idNb+' .item-code-label').text(Joomla.JText._('COM_ODYSSEY_ITEM_CODE_LABEL'));

    //Create the "code" input.
    var properties = {'type':'text', 'name':'option_code_'+idNb, 'id':'option-code-'+idNb, 'value':data.option_code};
    $('#option-item-'+idNb).createHTMLTag('<input>', properties, 'item-code');

    //Create the "published" label.
    properties = {'title':Joomla.JText._('COM_ODYSSEY_PUBLISHED_TITLE')};
    $('#option-item-'+idNb).createHTMLTag('<span>', properties, 'published-label');
    $('#option-item-'+idNb+' .published-label').text(Joomla.JText._('COM_ODYSSEY_PUBLISHED_LABEL'));

    //Create the "published" checkbox.
    var properties = {'type':'checkbox', 'name':'published_'+idNb, 'id':'published-'+idNb, 'value':idNb};
    $('#option-item-'+idNb).createHTMLTag('<input>', properties, 'option-name-item');

    //Set the checkbox state.
    if(data.published == 1) { //checked
      $('#published-'+idNb).prop('checked', true);
    }

    //Get the number of items within the container then use it as ordering
    //number for the current item.
    var ordering = $('#option-container').children('div').length;
    if(data.option_ordering !== '') {
      ordering = data.option_ordering;
    }
    //Create the "order" input.
    properties = {'type':'text', 'name':'option_ordering_'+idNb, 'id':'option-ordering-'+idNb, 'readonly':'readonly', 'value':ordering};
    $('#option-item-'+idNb).createHTMLTag('<input>', properties, 'item-ordering');
    $.fn.setOrderManagement('option');

    //Create the item removal button.
    $('#option-item-'+idNb).createButton('remove_reorder');
  };


  $.fn.setAddonType = function() {
    var addonType = $('#jform_addon_type').val();
    if(addonType == 'hosting' || addonType == 'routing') {
      $('#jform_nb_persons-lbl').parent().parent().css({'visibility':'visible','display':'block'});
    }
    else {
      $('#jform_nb_persons-lbl').parent().parent().css({'visibility':'hidden','display':'none'});
    }
  };

})(jQuery);
