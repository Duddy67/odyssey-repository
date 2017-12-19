<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
// import the list field type
jimport('joomla.form.helper');

//Only used to load our js file as it seems there is no other option to do that.

class JFormFieldModjsloader extends JFormField
{
  protected $type = 'modjsloader';

  protected function getInput()
  {
    //Loads the js file. 
    $doc = JFactory::getDocument();
    $doc->addScript(JURI::root().'modules/mod_odysplay/js/setting.js');
  }
}



