<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * Odyssey categories view.
 *
 */
class OdysseyViewCategories extends JViewCategories
{
  protected $item = null;

  /**
   * @var    string  Default title to use for page title
   * @since  3.2
   */
  protected $defaultPageTitle = 'COM_ODYSSEY_DEFAULT_PAGE_TITLE';

  /**
   * @var    string  The name of the extension for the category
   * @since  3.2
   */
  protected $extension = 'com_odyssey';

  /**
   * @var    string  The name of the view to link individual items to
   * @since  3.2
   */
  protected $viewName = 'travel';

  /**
   * Execute and display a template script.
   *
   * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
   *
   * @return  mixed  A string if successful, otherwise a Error object.
   */
  public function display($tpl = null)
  {
    $state = $this->get('State');
    $items = $this->get('Items');
    $parent = $this->get('Parent');

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseWarning(500, implode("\n", $errors));
      return false;
    }

    if($items === false) {
      return JError::raiseError(404, JText::_('JGLOBAL_CATEGORY_NOT_FOUND'));
    }

    if($parent == false) {
      return JError::raiseError(404, JText::_('JGLOBAL_CATEGORY_NOT_FOUND'));
    }

    $params = &$state->params;

    $items = array($parent->id => $items);

    // Escape strings for HTML output
    $this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx'));

    $this->maxLevelcat = $params->get('maxLevelcat', -1);
    $this->params = &$params;
    $this->parent = &$parent;
    $this->items  = &$items;

    $this->setDocument();

    return parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include css file (if needed).
    //$doc = JFactory::getDocument();
    //$doc->addStyleSheet(JURI::base().'components/com_odyssey/css/odyssey.css');
  }
}

