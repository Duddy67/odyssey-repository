<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_BASE') or die;

JFormHelper::loadFieldClass('list');
require_once __DIR__.'/../../helpers/odyssey.php';


class JFormFieldComponentuser extends JFormFieldList
{
  /**
   * The form field type.
   *
   * @var		string
   * @since   1.6
   */
  protected $type = 'Componentuser';
  protected $exceptions = array('deliverypoint' => 'delivery_point','paymentmode' => 'payment_mode');

  /**
   * Method to get the field options.
   *
   * @return  array  The field option objects.
   *
   * @since   1.6
   */
  public function getOptions()
  {
    //Get the item name from the form filter name. 
    preg_match('#^com_odyssey\.([a-zA-Z0-9_-]+)\.filter$#', $this->form->getName(), $matches);
    $itemName = $matches[1];
    //We need the item name in the singular in order to build the SQL table name.
    if(preg_match('#ies$#', $itemName)) { //countries, currencies etc...
      $itemName = preg_replace('#ies$#', 'y', $itemName);
    }
    elseif(preg_match('#xes$#', $itemName)) { //taxes, boxes etc...
      $itemName = preg_replace('#es$#', '', $itemName);
    }
    else { //Regular plurials.
      $itemName = preg_replace('#s$#', '', $itemName);
    }

    //Note: Some SQL table names are separated with underscore.
    if(array_key_exists($itemName, $this->exceptions)) {
      $itemName = $this->exceptions[$itemName];
    }

    $options = OdysseyHelper::getUsers($itemName);

    return  array_merge(parent::getOptions(), $options);
  }
}
