<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/step.php';



class OdysseyModelSteps extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 's.id',
	      'name', 's.name',
	      'created', 's.created',
	      'created_by', 's.created_by',
	      'published', 's.published',
	      'group_alias', 's.group_alias',
	      'step_type', 's.step_type',
	      'user', 'user_id',
	      'catid', 's.catid', 'category_id',
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

    $userId = $app->getUserStateFromRequest($this->context.'.filter.user_id', 'filter_user_id');
    $this->setState('filter.user_id', $userId);

    $categoryId = $this->getUserStateFromRequest($this->context.'.filter.category_id', 'filter_category_id');
    $this->setState('filter.category_id', $categoryId);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    $stepType = $this->getUserStateFromRequest($this->context.'.filter.step_type', 'filter_step_type');
    $this->setState('filter.step_type', $stepType);

    $groupAlias = $this->getUserStateFromRequest($this->context.'.filter.group_alias', 'filter_group_alias');
    $this->setState('filter.group_alias', $groupAlias);

    $departureData = $this->getUserStateFromRequest($this->context.'.step_sequence.departure_data', 'departure_data');
    $this->setState('step_sequence.departure_data', $departureData);

    // List state information.
    parent::populateState('s.name', 'asc');

    //Check if a step sequence has to be calculated. 
    if(!empty($departureData)) {
      //Reset all the filter select lists and search field.
      $this->state->set('step_sequence.displayed', true);

      //Important: the lines below must be placed AFTER the parent::populateState line
      //or the parent class will override their values.

      //Reset pagination as we want all the list displayed at once as  
      //it is easier to check the ordering between steps.
      $this->state->set('list.limit', 0);
      $this->state->set('list.start', 0);
      $this->state->set('filter.search', '');
      $this->state->set('filter.user_id', '');
      $this->state->set('filter.published', '');
      $this->state->set('filter.category_id', '');
      $this->state->set('filter.step_type', '');
      $this->state->set('filter.group_alias', '');
    }
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.category_id');
    $id .= ':'.$this->getState('filter.step_type');
    $id .= ':'.$this->getState('step_sequence.departure_data');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    $app = JFactory::getApplication();
    $stepOrdering = false;

    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 's.id,s.name,s.created,s.group_alias,s.step_type,s.catid,'.
				   's.travel_code,s.subtitle,s.published,s.created_by,s.checked_out,s.checked_out_time'));
    $query->from('#__odyssey_step AS s');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('LEFT', '#__users AS u ON u.id = s.created_by');

    // Join over the categories.
    $query->select('ca.title AS category_title')
	  ->join('LEFT', '#__categories AS ca ON ca.id = s.catid');

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=s.checked_out');

    // Join over the travel to get the travel code if any.
    //$query->select('IFNULL(t.travel_code, "") AS travel_code');
    //$query->join('LEFT', '#__odyssey_travel AS t ON t.dpt_step_id=s.id');

    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('s.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(s.name LIKE '.$search.')');
      }
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('s.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(s.published IN (0, 1))');
    }

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('s.created_by'.$type.(int) $userId);
    }

    //Filter by component category.
    $categoryId = $this->getState('filter.category_id');
    if(is_numeric($categoryId)) {
      $query->where('s.catid = '.(int)$categoryId);
    }
    elseif(is_array($categoryId)) {
      JArrayHelper::toInteger($categoryId);
      $categoryId = implode(',', $categoryId);
      $query->where('s.catid IN ('.$categoryId.')');
    }

    //Filter by step type.
    $stepType = $this->getState('filter.step_type');
    if(!empty($stepType)) {
      $query->where('s.step_type='.$db->Quote($stepType));
    }

    //Filter by group alias.
    $groupAlias = $this->getState('filter.group_alias');
    if(!empty($groupAlias)) {
      $query->where('s.group_alias='.$db->Quote($groupAlias));
    }

    //Get the possible option sent by a link to a modal window.
    $modalOption = $app->input->get->get('modal_option', '', 'string');

    if($modalOption == 'dpt_only') {
      //Display departure steps only.
      $query->where('s.step_type="departure"');
    }

    if($modalOption == 'published_dpt_only') {
      //Display published departure steps only.
      $query->where('s.step_type="departure"')
	    ->where('s.published=1');
    }

    //Calculate and display a step sequence.
    //Note: When a step sequence is displayed all the normal item filtering (state,
    //category etc..) is disabled.
    $departureData = $this->getState('step_sequence.departure_data');
    if(!empty($departureData)) {
      //Check first that departure data is properly set.
      if(!preg_match('#(^[1-9]{1}[0-9]*):([1-9]{1}[0-9]*)#', $departureData, $matches)) {
	//Disable the step sequence displaying.
        $this->state->set('step_sequence.displayed', false); 
        $app->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_INVALID_DEPARTURE_DATA'), 'warning');
	//Yes, it's the infamous goto operator ! :)
	//But it is cautiously used here (ie: into the same function).
	//It' better than using a lot of if else imbricated conditions.
        goto normal_filtering;
      }

      //Extract the needed values from the data pattern.
      $dptStepId = $matches[1];
      $dptNb = $matches[2];

      $cid = $app->input->get('cid', array(), 'array');
      $timeOffset = $app->input->get('time_offset', '', 'str');

      if(!empty($timeOffset)) {
	StepHelper::applyTimeOffset($timeOffset, $cid, $dptStepId, $dptNb);
      }

      $stepSequence = StepHelper::getStepSequence($dptStepId, $dptNb);

      //Check we have something to display.
      if(empty($stepSequence)) {
	$this->state->set('step_sequence.displayed', false); 
	$app->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_EMPTY_STEP_SEQUENCE'), 'warning');
	goto normal_filtering;
      }

      //The departure step must be published to display a sequence.
      if($stepSequence[0]['published'] != 1) {
	$this->state->set('step_sequence.displayed', false); 
	$app->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_DEPARTURE_STEP_NOT_PUBLISHED'), 'warning');
	goto normal_filtering;
      }

      //
      $this->state->set('step_sequence.step_duration', StepHelper::getStepDuration($stepSequence), array());

      //var_dump($stepSequence);

      //Get the step ids.
      $stepIds = array();
      foreach($stepSequence as $step) {
	$stepIds[] = $step['step_id'];
      }

      //Note: The departure id lies in the first element of the array.
      $dptId = $stepSequence[0]['dpt_id'];

      //Join over the timegap mapping table to sort the steps (link and arrival types) according 
      //to theirs time gap against the departure step.
      $query->select('tg.time_gap, tg.group_prev')
	    ->join('LEFT', '#__odyssey_timegap_step_map AS tg ON tg.step_id=s.id AND tg.dpt_id='.(int)$dptId)
	    //Join also over the departure mapping table as we need the departure step alias.
	    ->join('LEFT', '#__odyssey_departure_step_map AS ds ON ds.step_id='.(int)$dptStepId.' AND ds.dpt_id='.(int)$dptId)
	    ->where('s.id IN('.implode(',', $stepIds).') AND s.published=1')
      //Since the departure step has no time gap attribute its value will be null. By
      //replacing null values with the '000-00:00' pattern, the departure step will end
      //up at the top of the list.
	    ->order('(CASE WHEN time_gap IS NULL THEN "000-00:00" ELSE time_gap END), time_gap');

      //Only the step sequence ordering is taken in account. So we can return the query
      //right now.
//echo $query;
      return $query;
    }

    //The goto label.
    normal_filtering:

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 's.name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));
//echo $query;
    return $query;
  }
}


