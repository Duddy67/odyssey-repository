<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



/**
 * Methods supporting a list of travel records.
 *
 * @since  1.6
 */
class OdysseyModelTravels extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array('id', 't.id',
				       'name', 't.name', 
				       'alias', 't.alias',
				       'created', 't.created', 
				       'created_by', 't.created_by',
				       'published', 't.published', 
			               'access', 't.access', 'access_level',
				       'user', 'user_id',
				       'ordering', 't.ordering', 'tm.ordering', 'tm_ordering',
				       'language', 't.language',
				       'hits', 't.hits',
				       'catid', 't.catid', 'category_id',
				       'tag'
				      );
    }

    parent::__construct($config);
  }


  protected function populateState($ordering = null, $direction = null)
  {
    // Initialise variables.
    $app = JFactory::getApplication();
    $session = JFactory::getSession();

    // Adjust the context to support modal layouts.
    if($layout = JFactory::getApplication()->input->get('layout')) {
      $this->context .= '.'.$layout;
    }

    //Get the state values set by the user.
    $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $search);

    $access = $this->getUserStateFromRequest($this->context.'.filter.access', 'filter_access');
    $this->setState('filter.access', $access);

    $userId = $app->getUserStateFromRequest($this->context.'.filter.user_id', 'filter_user_id');
    $this->setState('filter.user_id', $userId);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    $categoryId = $this->getUserStateFromRequest($this->context.'.filter.category_id', 'filter_category_id');
    $this->setState('filter.category_id', $categoryId);

    $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language');
    $this->setState('filter.language', $language);

    $tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag');
    $this->setState('filter.tag', $tag);

    // List state information.
    parent::populateState('t.name', 'asc');

    // Force a language
    $forcedLanguage = $app->input->get('forcedLanguage');

    if(!empty($forcedLanguage)) {
      $this->setState('filter.language', $forcedLanguage);
      $this->setState('filter.forcedLanguage', $forcedLanguage);
    }
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.access');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.category_id');
    $id .= ':'.$this->getState('filter.language');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 't.id,t.name,t.alias,t.created,t.published,t.catid,t.hits,'.
				   't.dpt_step_id,t.access,t.ordering,t.created_by,t.checked_out,t.checked_out_time,t.language'))
	  ->from('#__odyssey_travel AS t');

    //Get the user name.
    $query->select('us.name AS user')
	  ->join('LEFT', '#__users AS us ON us.id = t.created_by');

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor')
	  ->join('LEFT', '#__users AS uc ON uc.id=t.checked_out');

    // Join over the categories.
    $query->select('ca.title AS category_title')
	  ->join('LEFT', '#__categories AS ca ON ca.id = t.catid');

    // Join over the language
    $query->select('lg.title AS language_title')
	  ->join('LEFT', $db->quoteName('#__languages').' AS lg ON lg.lang_code = t.language');

    // Join over the asset groups.
    $query->select('al.title AS access_level')
	  ->join('LEFT', '#__viewlevels AS al ON al.id = t.access');

    //Filter by component category.
    $categoryId = $this->getState('filter.category_id');
    if(is_numeric($categoryId)) {
      $query->where('t.catid = '.(int)$categoryId);
    }
    elseif(is_array($categoryId)) {
      JArrayHelper::toInteger($categoryId);
      $categoryId = implode(',', $categoryId);
      $query->where('t.catid IN ('.$categoryId.')');
    }

    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('t.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(t.name LIKE '.$search.')');
      }
    }

    // Filter by access level.
    if($access = $this->getState('filter.access')) {
      $query->where('t.access='.(int) $access);
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('t.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(t.published IN (0, 1))');
    }

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('t.created_by'.$type.(int) $userId);
    }

    //Filter by language.
    if($language = $this->getState('filter.language')) {
      $query->where('t.language = '.$db->quote($language));
    }

    // Filter by a single tag.
    $tagId = $this->getState('filter.tag');

    if(is_numeric($tagId)) {
      $query->where($db->quoteName('tagmap.tag_id').' = '.(int)$tagId)
	    ->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap').
		   ' ON '.$db->quoteName('tagmap.content_item_id').' = '.$db->quoteName('t.id').
		   ' AND '.$db->quoteName('tagmap.type_alias').' = '.$db->quote('com_odyssey.travel'));
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 't.name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    //In case only the tag filter is selected we want the travels to be displayed according
    //to our mapping table ordering.
    if(is_numeric($tagId) && OdysseyHelper::checkSelectedFilter('tag', true) && $orderCol == 't.ordering') {
      //Join over the travel/tag mapping table.
      $query->select('tm.ordering AS tm_ordering')
	    ->join('LEFT', '#__odyssey_travel_tag_map AS tm ON t.id=tm.travel_id AND tm.tag_id='.(int)$tagId);

      //Switch to the mapping table ordering.
      $orderCol = 'tm_ordering';
    }

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


