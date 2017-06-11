<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');


//Build 2 input tags relative to the maximum file size allowed.
//The first tag contains the value set by the user and make sure a proper size
//format is typed.
//The second tag displays the maximum size upload (in megabyte) allowed by php.ini. 

class JFormFieldMaxfilesize extends JFormField
{
  protected $type = 'maxfilesize';

  protected function getInput()
  {
    //Calculate the maximum size upload (in megabyte) allowed by php.ini. 
    $max_upload = (int)(ini_get('upload_max_filesize'));
    $max_post = (int)(ini_get('post_max_size'));
    $memory_limit = (int)(ini_get('memory_limit'));
    $upload_mb = min($max_upload, $max_post, $memory_limit);

    //Get some attribute values.
    $default = $this->element->attributes()->default;
    $size = $this->element->attributes()->size;

    //Force the user to type an integer. If the number is higher than the upload value, it
    //is replaced with the upload value.
    $js = 'var Odms = {'."\n";
    $js .= 'checkFileSize : function(fileSize) {'."\n";
    $js .= '  var regex = /^[0-9]+$/;'."\n";
    $js .= '  fileSize.value = fileSize.value.match(regex);'."\n";
    $js .= '  if(fileSize.value > '.$upload_mb.') {'."\n";
    $js .= '    fileSize.value = '.$upload_mb.';'."\n";
    $js .= '  }'."\n";
    $js .= ' }';
    $js .= '}';
    //Place the Javascript function into the html page header.
    $doc = JFactory::getDocument();
    $doc->addScriptDeclaration($js);

    //Build the input tags.
    $html = '';
    $html .= '<input type="text" name="'.$this->name.'" id="'.$this->id.'" '.
             ' value="'.$this->value.'" default="'.$default.'" size="'.$size.'" onkeyup="javascript:Odms.checkFileSize(this)" />';
    $html .= '<br /><input type="text" name="upload_mb" id="upload_mb" readonly="readonly" '.
             'class="readonly" value="  php.ini upload = '.$upload_mb.'M" />';

    return $html;
  }
}

