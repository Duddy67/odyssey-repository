<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

jimport('joomla.application.categories');

/**
 * Build the route for the com_odyssey component
 *
 * @param	array	An array of URL arguments
 *
 * @return	array	The URL arguments to use to assemble the subsequent URL.
 */
function OdysseyBuildRoute(&$query)
{
  $segments = array();

  if(isset($query['view'])) {
    $segments[] = $query['view'];
    unset($query['view']);
  }

  if(isset($query['id'])) {
    $segments[] = $query['id'];
    unset($query['id']);
  }

  if(isset($query['layout'])) {
    $segments[] = $query['layout'];
    unset($query['layout']);
  }

  if(isset($query['alias'])) {
    $segments[] = $query['alias'];
    unset($query['alias']);
  }

  unset($query['catid']);
  unset($query['tagid']);

  return $segments;
}


/**
 * Parse the segments of a URL.
 *
 * @param	array	The segments of the URL to parse.
 *
 * @return	array	The URL attributes to be used by the application.
 */
function OdysseyParseRoute($segments)
{
  $vars = array();

  switch($segments[0])
  {
    case 'categories':
	   $vars['view'] = 'categories';
	   break;
    case 'category':
	   $vars['view'] = 'category';
	   $id = explode(':', $segments[1]);
	   $vars['id'] = (int)$id[0];
	   break;
    case 'tag':
	   $vars['view'] = 'tag';
	   $id = explode(':', $segments[1]);
	   $vars['id'] = (int)$id[0];
	   break;
    case 'travel':
	   $vars['view'] = 'travel';
	   $id = explode(':', $segments[1]);
	   $vars['id'] = (int)$id[0];
	   break;
    case 'form':
	   $vars['view'] = 'form';
	   //Form layout is always set to 'edit'.
	   $vars['layout'] = 'edit';
	   break;
    //Define all of the Odyssey views.
    case 'addons':
	   $vars['view'] = 'addons';
	   $vars['alias'] = $segments[1];
	   break;
    case 'passengers':
	   $vars['view'] = 'passengers';
	   $vars['alias'] = $segments[1];
	   break;
    case 'booking':
	   $vars['view'] = 'booking';
	   $vars['alias'] = $segments[1];
	   break;
    case 'outstdbal':
	   $vars['view'] = 'outstdbal';
	   break;
    case 'payment':
	   $vars['view'] = 'payment';
	   break;
    case 'order':
	   $vars['view'] = 'order';
	   break;
    case 'orders':
	   $vars['view'] = 'orders';
	   break;
    case 'documents':
	   $vars['view'] = 'documents';
	   break;
    case 'search':
	   $vars['view'] = 'search';
	   if(isset($segments[1])) {
	     $layout = explode(':', $segments[1]);
	     $vars['layout'] = $layout[0];
	   }
	   break;
  }

  return $vars;
}

