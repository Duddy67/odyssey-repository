
(function($) {
  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //Firstly, hide the jsloader field as it is only used to load this js file.
    $('#jform_params_jsloader-lbl').parent().parent().css({'visibility':'hidden','display':'none'});
    $('input[id^="jform_params_linked_travels"]').change( function() { $.fn.setTravelsField(this.value); });

    var currentChoice = 1;
    //Warning: jform id are upside down. jform_params_linked_travels1 is for the No option.
    if($('#jform_params_linked_travels1').attr('checked')) {
      currentChoice = 0;
    }

    $.fn.setTravelsField(currentChoice);

  });

  $.fn.setTravelsField = function(value) {
    if(value == 1) {
      $('#jform_params_travel_ids-lbl').parent().parent().css({'visibility':'hidden','display':'none'});
    }
    else {
      $('#jform_params_travel_ids-lbl').parent().parent().css({'visibility':'visible','display':'block'});
    }
  }
})(jQuery);
