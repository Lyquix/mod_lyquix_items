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
		$user = JFactory::getUser();
				
		// get null date and now
		$nullDate	= $db->getNullDate();
		$date = JFactory::getDate();
		$now = $date->toSql();

		// build query
		$query = "SELECT DISTINCT c.id FROM #__content AS c, #__flexicontent_cats_item_relations as fcir, #__flexicontent_items_ext as fie WHERE c.id = fcir.itemid AND c.id = fie.item_id AND c.state = 1 AND c.publish_up < '".$now."' AND (c.publish_down = '0000-00-00 00:00:00' OR c.publish_down > '".$now."')";
		
		// category scope
		if($params->get('cats')) {
			$query .= " AND fcir.catid ".($params->get('cats_scope',0) ? "NOT " : "")."IN (".implode(",",$params->get('cats')).")";
		}
		
		// types scope
		if($params->get('types')) {
			$query .= " AND fie.type_id ".($params->get('types_scope',0) ? "NOT " : "")."IN (".implode(",",$params->get('types')).")";
		}
		
		// current item scope
		if($params->get('item_scope',1) && JRequest::getVar('option') == 'com_flexicontent' && JRequest::getVar('id') > 0) {
			$query .= " AND c.id <> ".current(explode(":", JRequest::getVar('id')));
		}
		
		// language scope
		if($params->get('lang_scope',1)) {
			$lang = flexicontent_html::getUserCurrentLang();
			$query .= " AND (fie.language = '*' OR fie.language LIKE '".$lang."%')";
		}
		
		// remove unauthorized items
		$gid = !FLEXI_J16GE ? (int)$user->get('aid') : max($user->getAuthorisedViewLevels());
		if(FLEXI_ACCESS && class_exists('FAccess')) {
			$readperms = FAccess::checkUserElementsAccess($user->gmid, 'read');
			if (isset($readperms['item']) && count($readperms['item']) ) {
				$query .= " AND (c.access <= ".$gid." OR c.id IN (".implode(",", $readperms['item'])."))";
			} 
			else {
				$query .= " AND c.access <= ".$gid;
			}
		} 
		else {
			$query .= " AND c.access <= ".$gid;
		}
		
		// add ordering
		$query .= " ORDER BY ".$params->get('ordering','c.created DESC');
		
		// Execute query
		$db->setQuery($query, 0);
		$items = $db->loadColumn();
		return array_slice($items, 0, $params->get('count',5), true);
	}
}