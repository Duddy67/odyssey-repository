<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_COMPONENT_SITE.'/helpers/route.php';
require_once JPATH_COMPONENT_SITE.'/helpers/travel.php';
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/utility.php';

/**
 * HTML View class for the Odyssey component.
 */
class OdysseyViewSearch extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $pagination;
  public $config;
  public $currency;


  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    // Compute the travel slugs.
    foreach($this->items as $item) {
      $item->slug = $item->alias ? ($item->id.':'.$item->alias) : $item->id;
    }

    $this->config = JComponentHelper::getParams('com_odyssey');
    $this->currency = UtilityHelper::getCurrency();

    //$this->setDocument();

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include css files (if needed).
    $doc = JFactory::getDocument();
    //$doc->addStyleSheet(JURI::base().'components/com_odyssey/css/odyssey.css');
  }
}

