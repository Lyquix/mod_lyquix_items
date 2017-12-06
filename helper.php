<?php
/**
 * @license GNU/GPL v2
 * @copyright  Copyright (c) Lyquix. All rights reserved.
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

class modLyquixItemsHelper {
	static function getList(&$params) {
		
		// Load libraries
		require_once("components/com_flexicontent/classes/flexicontent.helper.php");
		require_once("components/com_flexicontent/helpers/permission.php");
		
		// Initialize
		global $mainframe;
		$mainframe = JFactory::getApplication();
		$db = JFactory::getDBO();
		$query = $db -> getQuery(true);
				
		// build query
		$query -> select("DISTINCT c.id");
		$query -> from("#__content AS c, #__flexicontent_cats_item_relations as fcir, #__flexicontent_items_ext as fie");
		$query -> where("c.id = fcir.itemid AND c.id = fie.item_id AND c.state = 1 AND c.publish_up < UTC_TIMESTAMP() AND (c.publish_down = '0000-00-00 00:00:00' OR c.publish_down > UTC_TIMESTAMP())");
		
		// category scope
		if($params -> get('cats')) {
			$query -> where("fcir.catid " . ($params -> get('cats_scope',0) ? "NOT " : "") . "IN (" . implode(",",$params -> get('cats')) . ")");
		}
		
		// types scope
		if($params -> get('types')) {
			$query -> where("fie.type_id " . ($params -> get('types_scope',0) ? "NOT " : "") . "IN (" . implode(",",$params -> get('types')) . ")");
		}
		
		// current item scope
		if($params -> get('item_scope', 1) && JRequest::getVar('option') == 'com_flexicontent' && JRequest::getVar('id') > 0) {
			$query -> where("c.id <> " . current(explode(":", JRequest::getVar('id'))));
		}
		
		// featured item scope
		if ($params->get('feat_scope') == 1) {
			$query -> where("c.featured = '1'");
		} elseif ($params->get('feat_scope') == 2) {
			$query -> where("c.featured = '0'");
		}
		//print_r($query);
		// language scope
		if($params -> get('lang_scope', 1)) {
			$lang = flexicontent_html::getUserCurrentLang();
			$query -> where("(fie.language = '*' OR fie.language LIKE '" . $lang . "%')");
		}
		
		// remove unauthorized items
		$user = JFactory::getUser();
		$gid = !FLEXI_J16GE ? (int)$user -> get('aid') : max($user -> getAuthorisedViewLevels());
		if(FLEXI_ACCESS && class_exists('FAccess')) {
			$readperms = FAccess::checkUserElementsAccess($user -> gmid, 'read');
			if (isset($readperms['item']) && count($readperms['item']) ) {
				$query -> where("(c.access <= " . $gid . " OR c.id IN (" . implode(",", $readperms['item']) . "))");
			} 
			else {
				$query -> where("c.access <= " . $gid);
			}
		} 
		else {
			$query -> where("c.access <= " . $gid);
		}
		
		$items_selection_mode = $params -> get('items_selection_mode', 'basic');
		// basic selection
		if($items_selection_mode == 'basic') {
			$query -> order($params -> get('ordering','c.created DESC'));
		}


		// events selection
		if($items_selection_mode == 'events' && $params -> get('event_date_field')) {
			
			$fieldname = $params -> get('event_date_field');
			if($fieldname != 'created' && $fieldname != 'modified') {
				$query -> from("#__flexicontent_fields as ff, #__flexicontent_fields_item_relations as ffir");
				$query -> where("ff.name = '" . $fieldname . "'");
				$query -> where("ffir.field_id = ff.id");
				$query -> where("c.id = ffir.item_id");
				$fieldname = 'ffir.value';
			}
			else $fieldname = 'c.' . $fieldname;

			// get anchor date from URL if available
			$anchor_date = JRequest::getString($params -> get('event_url_parameter','date'));	
			if(!$anchor_date){
				// no anchor date from url, get today's date
				$anchor_date = date('Y-m-d');
			}		
			// adjust anchor date to this week, month or year if needed
			$event_date_anchor = $params -> get('event_date_anchor', 'day');
			if($event_date_anchor == 'week') {
				$week_start = $params -> get('event_week_start',0);
				if($params -> get('event_week_start', 'monday') == 'monday'){
					$anchor_date = date("Y-m-d", strtotime($anchor_date.' monday this week'));
				}
				else if($params -> get('event_week_start', 'monday') == 'sunday') {
					$anchor_date = date("Y-m-d", strtotime($anchor_date.' sunday last week'));
				}	 
			}
			else if($event_date_anchor == 'month') {
				$anchor_date = date('Y-m', strtotime($anchor_date)) . '-01';	
			}
			else if($event_date_anchor == 'year') {
				
				$anchor_date = date('Y', strtotime($anchor_date)) . '-01-01';
			}

			// calculate range start
			$range = date("Y-m-d", strtotime($anchor_date . " -" . $params -> get('event_date_range_start', 0) . " " . $params -> get('event_date_range_unit', 'day')));
			$query -> where($fieldname . " >= '" . $range . "'");
			// calculate range end
			$range = date("Y-m-d", strtotime($anchor_date . " +" . $params -> get('event_date_range_end', 0) . " " . $params -> get('event_date_range_unit', 'day')));
			$query -> where($fieldname . " < '" . $range . "'");
			// set ordering
			$query -> order($fieldname . " " . $params -> get('event_ordering', 'ASC'));
		}

		// advanced selection
		if($items_selection_mode == 'advanced') {
			$query -> from($params -> get('advanced_query_from'));
			$query -> where($params -> get('advanced_query_where'));
			$query -> order($params -> get('advanced_query_order'));
		}
		
		// set count limit and execute
		$db -> setQuery($query, 0, $params -> get('count', 5));
		$items = $db -> loadColumn();
		
		return $items;
	}
	
	// function to generate URL from image field
	static function getImage(&$item, $fieldname, $image_size, $image_width = null, $image_height = null, $image_resize = null) {
					
		$url = '';
		$value = '';

		if (isset($item -> fieldvalues[$item -> fields[$fieldname] -> id])) {

			// Unserialize value's properties and check for empty original name property
			$value = unserialize($item -> fieldvalues[$item -> fields[$fieldname] -> id][0]);
			$image_name = trim(@$value['originalname']);

			if (strlen($image_name)) {

				$field = $item -> fields[$fieldname];
				$field -> parameters = json_decode($field -> attribs, true);
				$image_source = $field -> parameters['image_source'];
				$dir_url = str_replace('\\', '/', $field -> parameters['dir']);
				$multiple_image_usages = !$image_source && $field -> parameters['list_all_media_files'] && $field -> parameters['unique_thumb_method'] == 0;
				$extra_prefix = $multiple_image_usages ? 'fld' . $field -> id . '_' : '';
				$of_usage = $field -> untranslatable ? 1 : $field -> parameters['of_usage'];
				$u_item_id = ($of_usage && $item -> lang_parent_id && $item -> lang_parent_id != $item -> id) ? $item -> lang_parent_id : $item -> id;
				$extra_folder = '/item_' . $u_item_id . '_field_' . $field -> id;
						
				if ($image_size == 'custom') {
					
					// get the original image file path
					$image_file = JPATH_SITE . '/';
					
					// supports only db mode and item-field folder mode
					if ($image_source == 0) {
						// db mode
						$cparams = JComponentHelper::getParams('com_flexicontent');
						$image_file .= str_replace('\\', '/', $cparams -> get('file_path', 'components/com_flexicontent/uploads'));
					} else if ($image_source == 1) {
						// item+field specific folder
						$image_file .= $dir_url . $extra_folder . '/original';
					}
					
					$image_file .= '/' .  $image_name;

					$h		= '&amp;h=' . $image_height;
					$w		= '&amp;w=' . $image_width;
					$aoe	= '&amp;aoe=1';
					$q		= '&amp;q=95';
					$ar 	= '&amp;ar=x';
					$zc		= $image_resize ? '&amp;zc=1' : '';
					$ext    = strtolower(pathinfo($image_file, PATHINFO_EXTENSION));
					$f      = in_array( $ext, array('png', 'ico', 'gif') ) ? '&amp;f='.$ext : '';
					$conf	= $w . $h . $aoe . $q . $ar . $zc . $f;

					$url = JURI::root(true) . '/components/com_flexicontent/librairies/phpthumb/phpThumb.php?src=' . urlencode($image_file) . $conf;
					
				}
				
				else {
					
					// Create thumbs URL path
					$url = JURI::root(true) . '/' . $dir_url;
					
					// Extra thumbnails sub-folder
					if ($image_source == 1) {
						// item+field specific folder
						$url .= $extra_folder;
					}

					$url .= '/' . $image_size . '_' . $extra_prefix . $image_name;
					
				}

			}

			$value['url'] = $url;

		}

		return $value;
	}
}