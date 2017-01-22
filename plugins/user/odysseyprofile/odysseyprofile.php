<?php
/**
 * @package Odyssey Profile
 * @copyright Copyright (c)2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_BASE') or die;
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/javascript.php';


/**
 * An example custom profile plugin.
 *
 * @package     Joomla.Plugin
 * @subpackage  User.profile
 * @since       1.6
 */
class PlgUserOdysseyprofile extends JPlugin
{
  /**
   * Load the language file on instantiation.
   *
   * @var    boolean
   * @since  3.1
   */
  protected $autoloadLanguage = true;

  /**
   * Constructor
   *
   * @param   object  $subject  The object to observe
   * @param   array   $config   An array that holds the plugin configuration
   *
   * @since   1.5
   */
  public function __construct(& $subject, $config)
  {
    parent::__construct($subject, $config);
    JFormHelper::addFieldPath(__DIR__ . '/fields');
  }

  /**
   * @param   string     $context  The context for the data
   * @param   integer    $data     The user id
   *
   * @return  boolean
   *
   * @since   1.6
   */
  public function onContentPrepareData($context, $data)
  {
    // Check we are manipulating a valid form.
    if(!in_array($context, array('com_users.profile', 'com_users.registration',
				 'com_admin.profile', 'com_odyssey.customer'))) {
      return true;
    }

    if(is_object($data)) {
      //Get the user id if it's defined, or set it to zero if it doesn't.
      $userId = isset($data->id) ? $data->id : 0;

      if(!isset($data->odysseyprofile) and $userId > 0) {
	// Load the profile data from the database.
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->select('a.*, cu.firstname, cu.customer_title')
	      ->join('LEFT', '#__odyssey_customer AS cu ON cu.id='.(int)$userId)
	      ->from('#__odyssey_address AS a')
	      ->where('a.item_id='.(int)$userId.' AND a.item_type="customer"');

	$input = JFactory::getApplication()->input;
	$layout = $input->get('layout', '', 'string');

	//If we're not in edit mode we need to display the country name, not its code.
	//IMPORTANT: This feature works with the User Profile menu item type. It doesn't
	//work with the Edit User Profile menu item type. 
	if(empty($layout) || $layout !== 'edit') {
	  $query->select('c.lang_var')
		->join('LEFT', '#__odyssey_country AS c ON c.alpha_2=a.country_code');
	}

	$db->setQuery($query);

	try {
	  $results = $db->loadAssoc();
	}
	catch(RuntimeException $e) {
	  $this->_subject->setError($e->getMessage());
	  return false;
	}

	// Merge the profile data.
	if(!is_null($results)) {
	  $data->odysseyprofile = array();

	  foreach($results as $key => $value) {
	    //If we're not in edit mode, we set the country name in the appropriate language.
	    //IMPORTANT: This feature works with the User Profile menu item type. It doesn't
	    //work with the Edit User Profile menu item type. 
	    if($key == 'country_code' && (empty($layout) || $layout !== 'edit')) {
	      $value = JText::_($results['lang_var']);
	    }

	    $data->odysseyprofile[$key] = $value;
	  }
	}
      }
    }

    return true;
  }


  /**
   * @param   JForm    $form    The form to be altered.
   * @param   array    $data    The associated data for the form.
   *
   * @return  boolean
   * @since   1.6
   */
  public function onContentPrepareForm($form, $data)
  {
    if(!($form instanceof JForm)) {
      $this->_subject->setError('JERROR_NOT_A_FORM');
      return false;
    }

    // Check we are manipulating a valid form.
    $name = $form->getName();
    if(!in_array($name, array('com_admin.profile', 
			      'com_users.profile', 'com_users.registration',
			      'com_odyssey.customer'))) {
      return true;
    }

    //Load the regions.
    JavascriptHelper::loadFunctions(array('region'));
    JText::script('COM_ODYSSEY_OPTION_SELECT'); 

    // Add the registration fields to the form.
    JForm::addFormPath(__DIR__.'/profiles');
    $form->loadFile('profile', false);

    $fields = array('firstname',
		    'customer_title',
		    'street',
		    'city',
		    'region_code',
		    'country_code',
		    'postcode',
		    'phone');

    foreach ($fields as $field) {
      // Case using the users manager in admin
      if($name == 'com_users.user') {
	// Remove the field if it is disabled in registration and profile
	if($this->params->get('register-require_'.$field, 1) == 0 && $this->params->get('profile-require_'.$field, 1) == 0) {
	  $form->removeField($field, 'odysseyprofile');
	}
      }
      // Case registration
      elseif($name == 'com_users.registration') {
	// Toggle whether the field is required.
	if($this->params->get('register-require_'.$field, 1) > 0) {
	//echo 'register-require_'.$field;
	  $form->setFieldAttribute($field, 'required', ($this->params->get('register-require_'.$field) == 2) ? 'required' : '', 'odysseyprofile');
	}
	else {
	  $form->removeField($field, 'odysseyprofile');
	}
      }
      // Case profile in site or admin
      elseif($name == 'com_users.profile' || $name == 'com_admin.profile' || $name == 'com_odyssey.customer') {
	// Toggle whether the field is required.
	if($this->params->get('profile-require_'.$field, 1) > 0) {
	  $form->setFieldAttribute($field, 'required', ($this->params->get('profile-require_' . $field) == 2) ? 'required' : '', 'odysseyprofile');
	}
	else {
	  $form->removeField($field, 'odysseyprofile');
	}
      }
    }

    return true;
  }


  /**
   * Method is called before user data is stored in the database
   *
   * @param   array    $user   Holds the old user data.
   * @param   boolean  $isnew  True if a new user is stored.
   * @param   array    $data   Holds the new user data.
   *
   * @return    boolean
   *
   * @since   3.1
   * @throws    InvalidArgumentException on invalid date.
   */
  public function onUserBeforeSave($user, $isnew, $data)
  {
    return true;
  }


  public function onUserAfterSave($data, $isNew, $result, $error)
  {
    $userId = JArrayHelper::getValue($data, 'id', 0, 'int');

    //Make sure the user data storage has been successfuly.
    if($userId && $result) {
      try {
	// Create a new query object.
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);

	//The new user id is added into the Odyssey user table.
	if($isNew) {
	  $query->insert($db->quoteName('#__odyssey_customer'))
		->columns('id')
		->values((int)$userId);
	  // Set the query
	  $db->setQuery($query);

	  //Check for error.
	  if(!$db->query()) {
	    throw new Exception($db->getErrorMsg());
	  }

	  //A new empty address row is added for this user.
	  $query->clear();
	  $query->insert('#__odyssey_address');
	  $values = array((int)$userId, $db->quote('customer'));
	  $query->columns(array('item_id', 'item_type'));
	  $query->values(implode(',', $values));
	  $db->setQuery($query);

	  //Check for error.
	  if(!$db->query()) {
	    throw new Exception($db->getErrorMsg());
	  }
	} //endif isNew

      }
      catch(RuntimeException $e) {
	$this->_subject->setError($e->getMessage());
	return false;
      }
    }
    else { //User save failed
      return false;
    }

    //Check whether odyssey profile data is provided.
    if(isset($data['odysseyprofile'])) {
      //Get the data profile array.
      $profile = $data['odysseyprofile'];

      //Update the address fields.
      $fields = array('street='.$db->quote($profile['street']),
		      'city='.$db->quote($profile['city']),
		      'region_code='.$db->quote($profile['region_code']),
		      'country_code='.$db->quote($profile['country_code']),
		      'postcode='.$db->quote($profile['postcode']),
		      'phone='.$db->quote($profile['phone']),
		      'created='.$db->quote(date('Y-m-d H:i:s')));

      $query->clear();
      $query->update('#__odyssey_address');
      $query->set($fields);
      $query->where('item_id='.(int)$userId.' AND item_type="customer"');

      try {
	$db->setQuery($query);

	if(!$db->query()) {
	  throw new Exception($db->getErrorMsg());
	}
      }
      catch(JException $e) {
	$this->_subject->setError($e->getMessage());
	return false;
      }

      $fields = array('firstname='.$db->quote($profile['firstname']),
		      'customer_title='.$db->quote($profile['customer_title']));

      //Update the odyssey customer fields.
      $query->clear();
      $query->update('#__odyssey_customer');
      $query->set($fields);
      $query->where('id='.(int)$userId);

      try {
	$db->setQuery($query);

	if(!$db->query()) {
	  throw new Exception($db->getErrorMsg());
	}
      }
      catch(JException $e) {
	$this->_subject->setError($e->getMessage());
	return false;
      }

      //And last but not least we also have to update the firstname and lastname 
      //attributes in the passenger table
      $fields = array('firstname='.$db->quote($profile['firstname']),
		      'lastname='.$db->quote($data['name']));
      $query->clear();
      $query->update('#__odyssey_passenger');
      $query->set($fields);
      $query->where('customer_id='.(int)$userId.' AND customer=1');

      try {
	$db->setQuery($query);

	if(!$db->query()) {
	  throw new Exception($db->getErrorMsg());
	}
      }
      catch(JException $e) {
	$this->_subject->setError($e->getMessage());
	return false;
      }
    }

    //Get the location session variable (if it exists).
    $session = JFactory::getSession();
    $location = $session->get('location', '', 'odyssey'); 
    //Initialize some variables.
    $app = JFactory::getApplication();
    $uParams = JComponentHelper::getParams('com_users');

    //Customers are automaticaly logged in to avoid session losses in closing tab/window browser or
    //possible mistakes during login phase.
    //Note: This is performed only if certain conditions are gathered.
    if($isNew && !empty($location) && $app->isSite() && $uParams->get('useractivation') == 0) {
      JRequest::checkToken('post') or jexit(JText::_('JInvalid_Token'));

      //Since onUserAfterSave function is triggered before any email is send to
      //the user by the register function (component/com_users/models/registration.php from line 341) 
      //we can take advantage of this to override this function.
      //So we send a registration email to the customer, perform log in then
      //redirect the user (which it causes the cancellation of the sending email
      //by the register function).

      //A reference to the global mail object (JMail) is fetched through the JFactory object. 
      //This is the object creating our mail.
      $mailer = JFactory::getMailer();

      $config = JFactory::getConfig();
      $sender = array($config->get('mailfrom'),
		      $config->get('fromname'));
   
      $mailer->setSender($sender);

      $recipient = $data['email'];
       
      $mailer->addRecipient($recipient);

      $mailer->setSubject(JText::sprintf('PLG_USER_EMAIL_ACCOUNT_DETAILS', $data['name'], $config->get('sitename')));
      $mailer->setBody(JText::sprintf('PLG_USER_EMAIL_REGISTERED_BODY', $data['name'],
									$config->get('sitename'), JURI::root()));
      //Send the confirmation email to the customer.
      $send = $mailer->Send();

      //Check for error.
      if($send !== true) {
	JError::raiseWarning(500, JText::_('PLG_USER_REGISTRATION_SEND_MAIL_FAILED'));
      }
      else {
        JFactory::getApplication()->enqueueMessage(JText::_('PLG_USER_REGISTRATION_SAVE_SUCCESS'));
      }

      // Get the log in credentials.
      $credentials = array();
      $credentials['username'] = $data['username'];
      $credentials['password'] = $data['password_clear'];

      // Perform the log in.
      if(true === $app->login($credentials)) {
	// Success
	$app->setUserState('users.login.form.data', array());
        $app->redirect(JRoute::_('index.php?option=com_odyssey&view='.$location, false));
      }
      else {
	// Login failed !
	$app->setUserState('users.login.form.data', $data);
	$app->redirect(JRoute::_('index.php?option=com_users&view=login', false));
      }
    }

    return true;
  }


  /**
   * Remove all user profile information for the given user ID
   *
   * Method is called after user data is deleted from the database
   *
   * @param   array    $user     Holds the user data
   * @param   boolean  $success  True if user was succesfully stored in the database
   * @param   string   $msg      Message
   *
   * @return  boolean
   */
  public function onUserAfterDelete($user, $success, $msg)
  {
    if(!$success) {
      return false;
    }

    $userId = JArrayHelper::getValue($user, 'id', 0, 'int');

    if($userId) {
      try {
	// Create a new query object.
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);

	//We first delete the user address.
	$query->delete('#__odyssey_address');
	$query->where('item_id='.(int)$userId.' AND item_type="customer"');
	$db->setQuery($query);

	//Check for error.
	if(!$db->query()) {
	  throw new Exception($db->getErrorMsg());
	}

	//Then we remove the customer from the Odyssey user table.
	$query->clear();
	$query->delete('#__odyssey_customer');
	$query->where('id='.(int)$userId);
	$db->setQuery($query);

	//Check for error.
	if(!$db->query()) {
	  throw new Exception($db->getErrorMsg());
	}
      }
      catch(Exception $e) {
	$this->_subject->setError($e->getMessage());
	return false;
      }
    }

    return true;
  }


  public function onUserLogin($user, $options = array())
  {
    return true;
  }


  public function onUserAfterLogin($options = array())
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $location = $session->get('location', '', 'odyssey'); 

    //The user is booking a travel.
    if($location == 'passengers') {
      $user = JFactory::getUser();
      $groups = JAccess::getGroupsByUser($user->get('id'));
      //Get current date and time (equal to NOW() in SQL).
      $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);

      $filteredGroups = array();
      foreach($groups as $groupId) {
	//Rule out Public (1) and Guest (9) groups from the list.
	if($groupId != 1 && $groupId != 9) {
	  $filteredGroups[] = $groupId;
	}
      }

      //Check if some price rules are linked to the user's account or to a group he
      //belongs to.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select('COUNT(pr.id)')
	    ->from('#__odyssey_pricerule AS pr')
	    ->join('INNER', '#__odyssey_prule_recipient AS prr ON prr.prule_id=pr.id')
	    ->where('pr.published=1')
	    ->where('((pr.recipient="customer" AND prr.item_id='.$user->get('id').') OR '.
		    '(pr.recipient="customer_group" AND prr.item_id IN('.implode(',', $filteredGroups).')))')
	    //Check against publication dates (start and stop).
	    ->where('('.$db->quote($now).' < pr.publish_down OR pr.publish_down = "0000-00-00 00:00:00")')
	    ->where('('.$db->quote($now).' > pr.publish_up OR pr.publish_up = "0000-00-00 00:00:00")');
      $db->setQuery($query);
      $result = $db->loadResult();

      //Inform the user that some price rules have been detected.
      if($result) {
	JFactory::getApplication()->enqueueMessage(JText::_('PLUG_USER_ODYSSEY_MESSAGE_PRICERULE_DETECTED'), 'message');
      }
    }

    //Redirect the user to the location he was before log in (when purchasing a travel).
    if(!empty($location)) {
      JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_odyssey&view='.$location, false));
      return true;
    }

    return true;
  }
}

