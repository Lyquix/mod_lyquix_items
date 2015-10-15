<?php // no direct access
defined('_JEXEC') or die('Restricted access');

// load required FLEXIcontent libraries
require_once (JPATH_ADMINISTRATOR . DS . 'components/com_flexicontent/defineconstants.php');
JTable::addIncludePath(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_flexicontent' . DS . 'tables');
require_once ("components/com_flexicontent/models/" . FLEXI_ITEMVIEW . ".php");
require_once ("components/com_flexicontent/classes/flexicontent.fields.php");
require_once ("components/com_flexicontent/classes/flexicontent.helper.php");

// are there any items to show?
if (count($items)) {
	echo '<div class="module mod_lyquix_items' . $params -> get('moduleclass_sfx') . '">';
	// module pre-text
	echo $params -> get('modpretxt');
	
	// get layout order
	$layout_order = explode(',', $params -> get('layout_order', 'image,title,date,author,intro,fields,readmore'));
	
	// cycle through items
	foreach ($items as $id) {
		
		// initialize some working variables
		list($item_title, $item_date, $item_author, $item_titledateauthor, $item_introtext, $item_image, $item_fields) = '';
		
		// create a new FC object for the item
		$itemmodel = new FlexicontentModelItem();
		$item = $itemmodel -> getItem($id, false);
		$items = array($item);
		FlexicontentFields::getFields($items);
		
		// generate item url
		$item_link = JRoute::_(FlexicontentHelperRoute::getItemRoute($item -> slug, $item -> categoryslug));
		
		// get custom CSS from user function
		$css = array();
		eval($params -> get('item_css_func'));		
		
		// item open tag
		$display = '<div class="item ' . implode(' ', $css) . '">';
		
		// item pre-text
		$display .= $params -> get('itempretxt');
		
		// cycle through layout order
		foreach($layout_order as $section) {
			
			switch($section) {
				
				case 'image':
					// Image
					if($params -> get('image_field') != '') {
						
						// process image field
						FlexicontentFields::getFieldDisplay($item, $params -> get('image_field'));
						
						// if there is image content
						if (isset($item -> fieldvalues[$item -> fields[$params -> get('image_field')] -> id])) {
							
							// custom image size
							if ($params -> get('image_size') == 'custom') {
								// custom size image
								$image_field = unserialize($item -> fieldvalues[$item -> fields[$params -> get('image_field')] -> id][0]);
								$image_file = JPATH_SITE . DS . 'images' . DS . 'stories' . DS . 'flexicontent' . DS . 'l_' . $image_field['originalname'];
								$conf = '&amp;w=' . $params -> get('image_width') . '&amp;h=' . $params -> get('image_height') . '&amp;aoe=1&amp;q=95';
								$conf .= $params -> get('image_resize') ? '&amp;zc=' . $params -> get('image_resize') : '';
								$ext = pathinfo($image_file, PATHINFO_EXTENSION);
								$conf .= in_array($ext, array('png', 'ico', 'gif')) ? '&amp;f=' . $ext : '';
								$image_src = JURI::base() . 'components/com_flexicontent/librairies/phpthumb/phpThumb.php?src=' . $image_file . $conf;
								$item_image = '<img src="' . $image_src . '" alt="' . $image_field['alt'] . '" title="' . $image_field['title'] . '" /></a>';
							} 
							// standard thumbnail
							else {
								$image_field = unserialize($item -> fieldvalues[$item -> fields[$params -> get('image_field')] -> id][0]);
								$image_src = JURI::base() . 'images/stories/flexicontent/' . $params -> get('image_size') . '_' . $image_field['originalname'];
								$item_image = '<img src="' . $image_src . '" alt="' . $image_field['alt'] . '" title="' . $image_field['title'] . '" /></a>';
							}
							// make image clickable?
							if ($params -> get('image_link', 1)) {
								$item_image = '<a href="' . $item_link . '">' . $item_image . '</a>';
							}
							$display .= '<div class="image ' . ($params -> get('image_align') != 'none' ? $params -> get('image_align') : '') . ' ' . $params -> get('image_class') . '">' . $item_image . '</div>';
						}
					}
					
					break;
					
				case 'title':
					// Title
					
					// first get the item title (just in case)
					$item_title = $item -> title;
					
					// process the title field if different to title field (confusing?)
					FlexicontentFields::getFieldDisplay($item, $params -> get('title_field'));
					if ($params -> get('title_field', 'title') != 'title' && $item -> fieldvalues[$item -> fields[$params -> get('title_field')] -> id]) {
						// check what kind of field we are using
						switch($item->fields[$params->get('title_field')]->field_type) {
							// for item relations, get the title of the related item
							case 'relateditems':
							case 'relateditems_advanced':
								$relitemmodel = new FlexicontentModelItem();
								$relitem = $relitemmodel -> getItem($item -> fieldvalues[$item -> fields[$params -> get('title_field')] -> id][0], false);
								$item_title = $relitem -> title;
								break;
							
							// otherwise just use the regular field display and strip any html	
							default :
								FlexicontentFields::getFieldDisplay($item, $params -> get('title_field'));
								$item_title = $item -> fields[$params -> get('title_field')] -> display;
								$item_title = strip_tags($item_title);
								break;
						}
					}
					// trim and add ellipsis at the end if needed
					if ($params -> get('title_length', 100) < strlen($item_title) && $params -> get('title_length', 100) > 0) {
						$item_title = trim(substr($item_title, 0, $params -> get('title_length', 100)));
						$item_title = substr($item_title, 0, strrpos($item_title, " ")) . "...";
					}
					// title clickable?
					if ($params -> get('title_link', 1)) {
						$item_title = '<a href="' . $item_link . '">' . $item_title . '</a>';
					}
					
					$display .= '<h' . $params -> get('title_heading_level', 3) . ' class="' . $params -> get('title_class') . '">' . $item_title . '</h' . $params -> get('title_heading_level', 3) . '>';
				
					break;
				
				case 'date':
					// Date
					$item_date = $params -> get('date_label') . JHTML::_('date', $params -> get('date_fields') == 'created' ? $item -> created : $item -> modified, $params -> get('date_format', 'DATE_FORMAT_LC3') != 'custom' ? $params -> get('date_format', 'DATE_FORMAT_LC3') : $params -> get('date_custom'));
					$display .= '<div class="date ' . $params -> get('date_class') . '">' . $item_date . '</div>';
					
					break;
				
				case 'author':
					// Author
					$item_author = $params -> get('author_label') . ($item -> created_by_alias ? $item -> created_by_alias : $item -> creator);
					$display .= '<div class="author ' . $params -> get('author_class') . '">' . $item_author . '</div>';
					
					break;
				
				case 'intro':
					// Intro text
					
					// get intro text from item, just in case
					$item_introtext = strip_tags($item -> text);
					
					// not default text?
					if ($params -> get('introtext_field', 'text') != 'text') {
						// process field used for intro text
						FlexicontentFields::getFieldDisplay($item, $params -> get('introtext_field'));
						if (isset($item -> fieldvalues[$item -> fields[$params -> get('introtext_field')] -> id])) {
							FlexicontentFields::getFieldDisplay($item, $params -> get('introtext_field'), $values = null, $method = 'display');
							$item_introtext = $item -> fields[$params -> get('introtext_field')] -> display;
							$item_introtext = strip_tags($item_introtext);
						} else {
							$item_introtext = '';
						}
					}
					// trim intro text and add ellipsis if needed
					if ($params -> get('introtext_length', 200) < strlen($item_introtext) && $params -> get('introtext_length', 100) > 0) {
						$item_introtext = trim(substr($item_introtext, 0, $params -> get('introtext_length', 200)));
						$item_introtext = substr($item_introtext, 0, strrpos($item_introtext, " ")) . "...";
					}
					// is there intro text?					
					if ($item_introtext) {
						$display .= '<div class="field_' . $params -> get('introtext_field', 'description') . ' ' . $params -> get('intro_class') . '">' . $item_introtext . '</div>';
					}
					
					break;
				
				case 'fields':
					// Additional fields
					// get list of fields
					$fields = explode(",", $params -> get('fields'));
					
					// cycle through fields
					foreach ($fields as $field_name) {
						// process field
						FlexicontentFields::getFieldDisplay($item, $field_name);
						if (isset($item -> fieldvalues[$item -> fields[$field_name] -> id])) {
							$item_fields .= '<div class="field field_' . $field_name . ' ' . $params -> get('fields_class') . '">';
							if ($params -> get('fields_label')) {
								$item_fields .= '<div class="label">' . $item -> fields[$field_name] -> label . '</div>';
							}
							$item_fields .= $item -> fields[$field_name] -> display . '</div>';
						}
					}
					
					$display .= $item_fields;
					
					break;
				
				case 'readmore':
					// Read more link
					$display .= '<a class="readmore ' . $params -> get('readmore_class') . '" href="' . $item_link . '">' . $params -> get('readmore_label', 'Read More') . '</a>';
					
					break;
				
			}
			
		}

		$display .= $params -> get('itempostxt') . '</div>';
		echo $display;
	}
	echo $params -> get('modpostxt');
	echo '</div>';
}
