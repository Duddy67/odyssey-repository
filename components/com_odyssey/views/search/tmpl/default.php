<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;
?>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=search');?>" method="post" name="adminForm" id="adminForm">
<?php
// Search tools bar 
//echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
echo JLayoutHelper::render('search_filters', array('view' => $this), JPATH_SITE.'/components/com_odyssey/layouts/');
?>


<input type="hidden" name="option" value="com_odyssey" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

