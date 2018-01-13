<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/odms.php';
 

class OdysseyViewDocuments extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $pagination;
  protected $myForm;
  protected $allowedExtensions;
  protected $maxFileSize;

  public function display($tpl = null)
  {
    /*$this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');*/

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //Gets and sets the file informations.
    $params = JComponentHelper::getParams('com_odyssey');
    $this->maxFileSize = $params->get('max_file_size');
    $this->allowedExtensions = preg_replace('#;#', ', ', $params->get('allowed_extensions'));

    //$this->myForm = new JForm('myForm');
    // Load any form .xml file you want (like registration.xml)
    //$this->myForm->loadFile(JPATH_ROOT.'/administrator/components/com_odyssey/models/forms/customer.xml');
    //$this->setDocument();
    //echo '<pre>';
    //var_dump($fieldset);
    //echo '</pre>';

    //Display the template.
    parent::display($tpl);
  }


  protected function setDocument() 
  {
    $doc = JFactory::getDocument();
  }
}


