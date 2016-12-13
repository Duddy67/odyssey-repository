<?php
//No direct access.
defined('_JEXEC') or die;


class ModOdysplayHelper {

  public static function getTravels($travelIds, $params) {
    //Get the user view levels groups.
    $user = JFactory::getUser();
    $groups = $user->getAuthorisedViewLevels();

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $nullDate = $db->quote($db->getNullDate());
    $nowDate = $db->quote(JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true));

    $query->select('t.id,t.name,t.alias,t.intro_text,t.full_text,t.catid,'.
		   't.published,t.checked_out,t.checked_out_time,t.created,'.
		   't.created_by,t.access,t.params,t.metadata,t.metakey,t.metadesc,t.hits,'.
		   't.publish_up,t.publish_down,t.language,t.modified,t.modified_by')
	  ->from($db->qn('#__odyssey_travel').' AS t')
	  ->where('t.access IN('.implode(',', $groups).')')
	  ->where('t.published=1')
	  ->where('(t.publish_up = '.$nullDate.' OR t.publish_up <= '.$nowDate.')')
	  ->where('(t.publish_down = '.$nullDate.' OR t.publish_down >= '.$nowDate.')')
	  ->where('t.id IN('.implode(',', $travelIds).')');

	  //Id order is computed in a different way.
	  if($params->get('ordering') != 'ids') {
	    $query->order($params->get('ordering'));
	  }

    $results = $db->setQuery($query)
		  ->loadObjectList();

    $travels = array();
    if($params->get('ordering') == 'ids') {
      //Order the travels according to the id order set in the travel_ids field. (ie: 5;2;9).
      foreach($travelIds as $travelId) {
	foreach($results as $result) {
	  if($result->id == $travelId) {
	    $travels[] = $result;
	  }
	}
      }
    }
    else {
      $travels = $results;
    }

    //Get the starting prices of the travels.
    $pricesStartingAt = TravelHelper::getPricesStartingAt($travelIds);
    $catIds = array();

    foreach($travels as $travel) {
      $travel->slug = $travel->alias ? ($travel->id.':'.$travel->alias) : $travel->id;
      $catIds[] = $travel->catid;

      //Set the default image.
      $travel->image = 'modules/mod_odysplay/camera-icon.jpg';

      //Get the first image (if any) detected in the intro text.
      if(preg_match('#<img.* src="(.+)"#iU', $travel->intro_text, $matches)) {
	$travel->image = $matches[1];
	//Remove all images from the intro text.
	$travel->intro_text = preg_replace('#<img .+>#iU', '', $travel->intro_text);
      }
      //Get the first image (if any) detected in the full text.
      elseif(preg_match('#<img.* src="(.+)"#iU', $travel->full_text, $matches)) {
	$travel->image = $matches[1];
      }

      //Get the image width and height then retrieve the new image size according to the
      //reduction rate.
      $imageSize = getimagesize($travel->image);
      $size = ModOdysplayHelper::getThumbnailSize($imageSize[0], $imageSize[1], $params->get('img_reduction_rate'));
      $travel->img_width = $size['width'];
      $travel->img_height = $size['height'];

      //Set the starting price for each travel.
      foreach($pricesStartingAt as $travelId => $priceStartingAt) {
	if($travelId == $travel->id) {
	  $travel->price_starting_at = $priceStartingAt;
	}
      }
    }

    //Get possible price rules.
    $pricesStartingAtPrules = PriceruleHelper::getPricesStartingAt($travelIds, $catIds);
    foreach($travels as $travel) {
      //Set the possible price rules for each travel.
      foreach($pricesStartingAtPrules as $travelId => $priceStartingAtPrules) {
	if($travelId == $travel->id) {
	  $travel->price_starting_at_prules = $priceStartingAtPrules;
	}
      }
    }

    return $travels;
  }


  //Return width and height of an image according to its reduction rate.
  public static function getThumbnailSize($width, $height, $reductionRate)
  {
    $size = array();

    if($reductionRate == 0) {
      //Just return the original values.
      $size['width'] = $width;
      $size['height'] = $height;
    }   
    else { //Compute the new image size.
      $widthReduction = ($width / 100) * $reductionRate;
      $size['width'] = $width - $widthReduction;

      $heightReduction = ($height / 100) * $reductionRate;
      $size['height'] = $height - $heightReduction;
    }   

    return $size;
  }
}



