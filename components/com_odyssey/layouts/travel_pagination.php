<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

//Since frontend Joomla pagination is not used with a form (as it does in
//backend), we need to rebuild a similar pagination html code and replace url
//links with Javascript functions, (identical to those used in backend).
//Note: See functions in file libraries/cms/pagination/pagination.php 

//Gather pagination data in an array for more convenience.
$pagination = array('limitstart' => $displayData->get('limitstart'),
		    'limit' =>  $displayData->get('limit'),
		    'total' => $displayData->get('total'),
		    'pages.total' => $displayData->get('pages.total'),
		    'pages.current' => $displayData->get('pages.current'),
		    'pages.start' => $displayData->get('pages.start'),
		    'pages.stop' => $displayData->get('pages.stop'));
?>

   <ul>
    <?php if($pagination['pages.current'] > 1) : 
	    $prev = ($pagination['pages.current'] - 2) * $pagination['limit']; ?>
      <li class="pagination-start">
	<a href="#" title="<?php echo JText::_('JLIB_HTML_START'); ?>" onclick="document.siteForm.limitstart.value=0; odyssey.submitForm(); return false;">
	    <?php echo JText::_('JLIB_HTML_START'); ?></a>
      </li>
      <li class="pagination-prev">
	<a href="#" title="<?php echo JText::_('JPREV'); ?>" onclick="document.siteForm.limitstart.value=<?php echo $prev; ?>; odyssey.submitForm(); return false;"><?php echo JText::_('JPREV'); ?></a>
      </li>
   <?php else : ?>
      <li class="pagination-start">
	<span class="pagenav"><?php echo JText::_('JLIB_HTML_START'); ?></span>
      </li>
      <li class="pagination-prev">
	<span class="pagenav"><?php echo JText::_('JPREV'); ?></span>
      </li>
   <?php endif; ?>

   <?php for($i = 0; $i < $pagination['pages.total']; $i++) :
	   $page = $i + 1;
	   $limitStart = $pagination['limit'] * $i;

	   if($page != $pagination['pages.current']) : ?>
	     <li><a href="#" title="<?php echo $page; ?>" onclick="document.siteForm.limitstart.value=<?php echo $limitStart; ?>;
							  odyssey.submitForm(); return false;"> <?php echo $page ?></a></li>
       <?php else : ?>
	    <li><span><?php echo $page; ?></span></li>
       <?php endif; ?>
   <?php endfor; ?>

   <?php if($pagination['pages.current'] < $pagination['pages.stop']) : 
	   $nextLimitStart = $pagination['limitstart'] + $pagination['limit']; ?>
	   <li class="pagination-next">
	     <a href="#" title="<?php echo JText::_('JNEXT'); ?>" onclick="document.siteForm.limitstart.value=<?php echo $nextLimitStart; ?>;
						      odyssey.submitForm(); return false;"><?php echo JText::_('JNEXT'); ?></a>
	   </li>
	   <li class="pagination-end">
	     <a href="#" title="<?php echo JText::_('JLIB_HTML_END'); ?>" onclick="document.siteForm.limitstart.value=<?php echo $limitStart; ?>;
						     odyssey.submitForm(); return false;"><?php echo JText::_('JLIB_HTML_END'); ?></a>
	   </li>
   <?php else : ?>
	   <li class="pagination-next">
	    <span class="pagenav"><?php echo JText::_('JNEXT'); ?></span>
	   </li>
	   <li class="pagination-end">
	    <span class="pagenav"><?php echo JText::_('JLIB_HTML_END'); ?></span>
	   </li>
   <?php endif; ?>



