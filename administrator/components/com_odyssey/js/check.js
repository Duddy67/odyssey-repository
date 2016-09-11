
//Check that all characters are figures.
function checkNumber(itemId, allowZero, signed)
{
  var number = document.getElementById(itemId).value;
  //Decimal numbers must be separated by a dot.
  var decimalPattern = /^[0-9]{1,}\.[0-9]+$/;
  //Only figures are allowed.
  var integerPattern = /^[0-9]+$/;

  if(signed === true) {
    decimalPattern = /^-?[0-9]{1,}\.[0-9]+$/;
    integerPattern = /^-?[0-9]+$/;
  }

  //Check for 0, 0., 0.0, 0.00 etc...
  var zeroPattern = /^0+\.?0*$/;

  //Parameter used with alertRed function.
  var tabId = '';
  //Get tabId as extra argument, (if any).
  if(arguments[3]) {
    tabId = arguments[3];
  }

  if(decimalPattern.test(number) || integerPattern.test(number)) {
    if(!allowZero) { //Zero is not allowed
      if(zeroPattern.test(number)) {
	alertRed(itemId, tabId);
	return false;
      }
    }

    return true;
  }
  else {
    alertRed(itemId, tabId);
    return false;
  }
}


function checkMinMax(minItemId, maxItemId, type, allowZero)
{
  //Parameter used with alertRed function.
  var tabId = '';
  //Get tabId as extra argument, (if any).
  if(arguments[4]) {
    tabId = arguments[4];
  }

  //Check first if numbers are properly defined.
  if(!checkNumber(minItemId, allowZero, tabId)) {
    return false;
  }

  if(!checkNumber(maxItemId, allowZero, tabId)) {
    return false;
  }
  
  //Get numbers from the form.
  var min = document.getElementById(minItemId).value;
  var max = document.getElementById(maxItemId).value;

  //Max value must be greater than min value.
  //Note: numbers are casted according to the type setting.
  if(type == 'Int') {
    if(parseInt(min) >= parseInt(max)) {
      alertRed(minItemId, tabId);
      alertRed(maxItemId, tabId);
      return false;
    }
  }
  else {
    if(parseFloat(min) >= parseFloat(max)) {
      alertRed(minItemId, tabId);
      alertRed(maxItemId, tabId);
      return false;
    }
  }

  return true;
}


//Global variables. It will be set as function in common.js file.
var showTab;

//Color the given element in red.
function alertRed(itemId, tabId, labelId)
{
  //Get labelId as extra argument, (if any).
  if(arguments[2]) {
    labelId = arguments[2];
  }
  else { //Use itemId as labelId.
    labelId = itemId;
  }

  document.getElementById(labelId+'-lbl').style.color='red';
  document.getElementById(labelId+'-lbl').style.fontWeight='bold';
  document.getElementById(itemId).style.fontWeight='bold';
  document.getElementById(itemId).style.borderColor='#fa5858';
  //document.getElementById(itemId + '_chzn').style.borderColor='red';

  //Check if a tab must be shown.
  if(tabId) {
    showTab(tabId);
  }

  return;
}

