<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * Odyssey Component Category Tree
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  com_odyssey
 * @since       1.6
 */
class OdysseyCategories extends JCategories
{
  public function __construct($options = array())
  {
    $options['table'] = '#__odyssey_travel';
    $options['extension'] = 'com_odyssey';

    /* IMPORTANT: By default publish parent function invoke a field called "state" to
     *            publish/unpublish (but also archived, trashed etc...) an item.
     *            Since our field is called "published" we must informed the 
     *            JCategories publish function in setting the "statefield" index of the 
     *            options array
    */
    $options['statefield'] = 'published';

    parent::__construct($options);
  }
}
