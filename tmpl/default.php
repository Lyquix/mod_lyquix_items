<?php // no direct access
defined('_JEXEC') or die('Restricted access');

// are there any items to show?
if (count($items)) {
	
	// load required FLEXIcontent libraries
	require_once (JPATH_ADMINISTRATOR.DS.'components'.DS.'com_flexicontent'.DS.'defineconstants.php');
	JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_flexicontent'.DS.'tables');
	require_once (JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'helpers'.DS.'route.php');
	require_once (JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'classes'.DS.'flexicontent.fields.php');
	require_once (JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'classes'.DS.'flexicontent.helper.php');
	require_once (JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'helpers'.DS.'permission.php');
	require_once (JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'models'.DS.FLEXI_ITEMVIEW.'.php');

	// some module params
	$banner_layout 				= $params -> get('banner_layout', 0);
	$link_type 					= $params -> get('link_type', 'item');
	$html_json 					= $params -> get('html_json', 'html');
	$items_selection_mode 		= $params -> get('items_selection_mode', 'basic');

	if($html_json != 'json') {
		$html = '';
		$item_idx = 0;
		if($banner_layout) $thumbnails = '';
	}
	if($html_json != 'html') {
		$json = array();
		$json_idx = 0;
	}

	if($html_json != 'json') {
		// module wrapper
		$html .= '<div class="module mod_lyquix_items' . $params -> get('moduleclass_sfx') . ($banner_layout ? ' banners' : '') . ($items_selection_mode == 'events' ? ' events' : '') . '">';
		// module pre-text
		$html .= $params -> get('modpretxt');
		// item list wrapper
		$html .= '<ul>';
	}

	// get layout order
	$layout_order = explode(',', $params -> get('layout_order', 'image,title,date,author,intro,fields,readmore'));

	// create FLEXIcontent objects for each disctinct item
	$itemobjects = array();
	foreach ($items as $id) {
		if (!array_key_exists($id, $itemobjects)) {
			$itemmodel = new FlexicontentModelItem();
			$item = $itemmodel -> getItem($id, false);
			$itemslist = array($item);
			FlexicontentFields::getFields($itemslist, 'module');
			$itemobjects[$id] = $item;
		}
	}
	// cycle through items
	foreach ($items as $id) {

		$item = $itemobjects[$id];

		// initialize some working variables
		list($item_title, $item_date, $item_author, $item_titledateauthor, $item_introtext, $item_image, $item_fields) = '';

		// generate item url
		if($link_type == 'item') {
			$item_link = JRoute::_(FlexicontentHelperRoute::getItemRoute($item -> slug, $item -> categoryslug));
		}
		else if($link_type == 'field') {
			FlexicontentFields::getFieldDisplay($item, $params -> get('link_field'));
			$html .= ' ' . $item -> fields[$link_field] -> display;
		}

		if($html_json != 'json') {
			// item open tag
			$html .= '<li class="item' . ($banner_layout ? ' banner' : '') . ($items_selection_mode == 'events' ? ' event' : '') . ' ';


			// get custom CSS from user function
			$css = array();
			eval($params -> get('item_css_func'));
			$html .= implode(' ', $css);

			// add class from style field
			if($params -> get('style_field')) {
				FlexicontentFields::getFieldDisplay($item, $params -> get('style_field'));
				$html .= ' ' . $item -> fields[$link_field] -> display;
			}

			$html .= '"';

			// add custom HTML attributes
			$attribs = array();
			eval($params -> get('item_attrib_func'));
			foreach($attribs as $name => $value) $html .= ' ' . $name . '="' . htmlentities($value) . '"';

			$html .= '>';

			// item pre-text
			$html .= $params -> get('itempretxt');
		}

		// add item to json array
		if($html_json != 'html') {
			$json[$json_idx] = array();
			if($params -> get('json_item_id')) $json[$json_idx]['id'] = $id;
			if($params -> get('json_link')) $json[$json_idx]['url'] = ($link_type == 'item' ? rtrim(JURI::base(), '/') : '') . $item_link;
		}
		
		// counts open/close sections
		$s = 0;

		// cycle through layout order
		foreach ($layout_order as $section) {

			switch($section) {

				case 'image' :
					// Image
					if ($params -> get('image_field')) {

						$image = modLyquixItemsHelper::getImage($item, $params -> get('image_field'), $params -> get('image_size', 'l'), $params -> get('image_width', 960), $params -> get('image_height', 540), $params -> get('image_resize', 1));
						
						// if banner and if the item has video
						$video_id = '';
						$banner_video_field = $params -> get('banner_video_field');
						if ($banner_layout && $params -> get('banner_video', 0) && is_array($item -> fieldvalues [$banner_video_field])) {
							$url = @unserialize($item -> fieldvalues [$banner_video_field][0])['url'];
							if(preg_match('%(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})%i', $url, $match)) $video_id = $match[1];
						}

						// if there is image URL
						if ($image['url']) {

							if($html_json != 'json') {

								$html .= '<div class="image ' . ($params -> get('image_align') != 'none' ? $params -> get('image_align') : '') . ' ' . $params -> get('image_class') . '">';

								// if youtube video available
								if($video_id) $html .= '<div class="video ' . $params -> get('banner_video_class') . '" ' . $params -> get('banner_video_id_attrib', 'data-video-id') . '="' . $video_id . '" ' . $params -> get('banner_video_addl_attribs') . '></div>';

								// make image clickable?
								if ($params -> get('image_link', 1)) {
									$html .= '<a href="' . $item_link . '">';
								}

								$html .= '<img' .
										 ' src="' . $image['url'] . '"' .
										 ($image['alt'] ? ' alt="' . htmlentities($image['alt']) . '"' : '') .
										 ($image['title'] ? ' title="' . htmlentities($image['title']) . '"' : '') .
										 ' />';

								if ($params -> get('image_link', 1)) {
									$html .= '</a>';
								}

								$html .= '</div>';
							}
							
							if($html_json != 'html') {
								$json[$json_idx]['image']['url'] = rtrim(JURI::base(), '/') . $image['url'];
								if($image['alt']) $json[$json_idx]['image']['alt'] = $image['alt'];
								if($image['title']) $json[$json_idx]['image']['title'] = $image['title'];
								if($video_id) $json[$json_idx]['video']['id'] = $video_id;
							}

						}
					}

					break;

				case 'title' :
					// Title

					// first get the item title (just in case)
					$item_title = $item -> title;

					$fieldname = $params -> get('title_field');

					if ($fieldname != 'title' && array_key_exists($item -> fields[$fieldname] -> id, $item -> fieldvalues)) {
						// check what kind of field we are using
						switch($item->fields[$fieldname]->field_type) {
							// for item relations, get the title of the related item
							case 'relateditems' :
							case 'relateditems_advanced' :
								$relitemmodel = new FlexicontentModelItem();
								$relitem = $relitemmodel -> getItem($item -> fieldvalues[$item -> fields[$fieldname] -> id][0], false);
								$item_title = $relitem -> title;
								break;

							// otherwise just use the regular field display and strip any html
							default :
								FlexicontentFields::getFieldDisplay($item, $fieldname);
								$item_title = $item -> fields[$fieldname] -> display;
								$item_title = strip_tags($item_title);
								break;
						}
					}
					// trim and add ellipsis at the end if needed
					if ($params -> get('title_length', 250) < strlen($item_title) && $params -> get('title_length', 100) > 0) {
						$item_title = trim(substr($item_title, 0, $params -> get('title_length', 100)));
						$item_title = substr($item_title, 0, strrpos($item_title, " ")) . "...";
					}
					
					if($html_json != 'html') $json[$json_idx]['title'] = $item_title;
					
					if($html_json != 'json') {
						// title clickable?
						if ($params -> get('title_link', 1)) {
							$item_title = '<a href="' . $item_link . '">' . $item_title . '</a>';
						}

						$html .= '<h' . $params -> get('title_heading_level', 3) . ' class="' . $params -> get('title_class') . '">' . $item_title . '</h' . $params -> get('title_heading_level', 3) . '>';
					}
					
					break;

				case 'date' :
					// Date
					$item_date = $params -> get('date_label') . JHTML::_('date', $params -> get('date_fields') == 'created' ? $item -> created : $item -> modified, $params -> get('date_format', 'DATE_FORMAT_LC3') != 'custom' ? $params -> get('date_format', 'DATE_FORMAT_LC3') : $params -> get('date_custom'));
					
					if($html_json != 'json') $html .= '<div class="date ' . $params -> get('date_class') . '">' . $item_date . '</div>';
					
					if($html_json != 'html') $json[$json_idx]['date'] = $item_date;
					
					break;

				case 'author' :
					// Author
					$item_author = $params -> get('author_label') . ($item -> created_by_alias ? $item -> created_by_alias : $item -> creator);
					
					if($html_json != 'json') $html .= '<div class="author ' . $params -> get('author_class') . '">' . $item_author . '</div>';
					
					if($html_json != 'html') $json[$json_idx]['author'] = $item_author;

					break;

				case 'intro' :
					// Intro text

					// get intro text from item, just in case
					$item_introtext = strip_tags($item -> text);

					// not default text?
					if ($params -> get('introtext_field') && $params -> get('introtext_field') != 'text') {
						if (isset($item -> fieldvalues[$item -> fields[$params -> get('introtext_field')] -> id])) {
							FlexicontentFields::getFieldDisplay($item, $params -> get('introtext_field'), $values = null, $method = 'display');
							$item_introtext = $item -> fields[$params -> get('introtext_field')] -> display;
							$item_introtext = strip_tags($item_introtext);
						} else {
							$item_introtext = '';
						}
					}
					// trim intro text and add ellipsis if needed
					if ($params -> get('introtext_length', 500) < strlen($item_introtext) && $params -> get('introtext_length', 100) > 0) {
						$item_introtext = trim(substr($item_introtext, 0, $params -> get('introtext_length', 200)));
						$item_introtext = substr($item_introtext, 0, strrpos($item_introtext, " ")) . "...";
					}
					// is there intro text?
					if ($item_introtext) {
						if($html_json != 'json') $html .= '<div class="field_' . $params -> get('introtext_field', 'description') . ' ' . $params -> get('intro_class') . '">' . $item_introtext . '</div>';
						
						if($html_json != 'html') $json[$json_idx]['intro'] = $item_introtext;
					}

					break;

				case 'fields' :
					// Additional fields
					$fields = explode(",", $params -> get('fields'));
					// cycle through fields
					foreach ($fields as $fieldname) {
						$fieldname = trim($fieldname);
						// process field
						FlexicontentFields::getFieldDisplay($item, $fieldname);
						if (isset($item -> fields[$fieldname])) {
							
							$field = $item -> fields[$fieldname];
							
							if($html_json != 'json') {
								
								// render using custom field function
								$field_html = '';
								eval($params -> get('field_render_func'));
							
								if (!$field_html && !(empty($item -> fields[$fieldname] -> display))) {
										$field_html .= '<div class="field field_' . $fieldname . ' ' . $params -> get('fields_class') . '">';
										if ($params -> get('fields_label')) {
											$field_html .= '<div class="label">' . $item -> fields[$fieldname] -> label . '</div>';
										}
										$field_html .= $item -> fields[$fieldname] -> display . '</div>';
								}
								
								$html .= $field_html;

							}
							
							if($html_json != 'html') {
								// add id, label, values and display to json field
								if($html_json != 'html') $json[$json_idx][$fieldname] = array();
								if($params -> get('json_field_id')) $json[$json_idx][$fieldname]['id'] = $field -> id;
								if($params -> get('json_field_label')) $json[$json_idx][$fieldname]['label'] = $field -> label;
								if($params -> get('json_field_value')) {
									
									$json[$json_idx][$fieldname]['value'] = $item -> fields[$field -> name] -> iscore ? $item -> {$field -> name} : $item -> fieldvalues [$field -> id];
									// process serialized data
									if(is_array($json[$json_idx][$fieldname]['value'])) {
										foreach($json[$json_idx][$fieldname]['value'] as $value_idx => $value) {
											$value = @unserialize($value);
											if($value) $json[$json_idx][$fieldname]['value'][$value_idx] = $value;
										}
									}
									else {
										$value = @unserialize($json[$json_idx][$fieldname]['value']);
										if($value) $json[$json_idx][$fieldname]['value'] = $value;
									}
								}
								if($params -> get('json_field_display')) $json[$json_idx][$fieldname]['display'] = $field -> display;
							}
						}
					}

					break;

				case 'readmore' :
					// Read more link
					if($html_json != 'json') $html .= '<a class="readmore ' . $params -> get('readmore_class') . '" href="' . $item_link . '">' . $params -> get('readmore_label', 'Read More') . '</a>';

					break;

				default :

					if($html_json != 'json') {
						if(strstr($section, 'open')) {
							$s++;
							$html .= '<div class="section-' . $s . ' ' . $this -> params -> get('css_section_' . $i) . '">';
						}

						if(strstr($section, 'close')) {
							$html .= '</div>';
						}
					}

					break;
			}

		}

		// item post text
		if($html_json != 'json') {

			$html .= $params -> get('itempostxt') . '</li>';

			// item thumbnail
			if($banner_layout) {
				
				$thumbnails .= '<div class="thumbnail">';

				// thumbnail image
				if ($params -> get('thumb_image_display')) {

					$image = modLyquixItemsHelper::getImage($item, $params -> get('thumb_image_field'), $params -> get('thumb_image_size', 's'), $params -> get('thumb_image_width', 240), $params -> get('thumb_image_height', 135), $params -> get('thumb_image_resize', 1));
					$thumbnails .= '<span class="image ' . $params -> get('thumb_image_class') . '"><img src="' . $image['url'] . '" /></span>';

				}

				// thumbnail caption
				if ($params -> get('thumb_caption_display')) {
					$item_title = $item -> title;
					$fieldname = $params -> get('thumb_caption_field');
					if ($fieldname) {
						FlexicontentFields::getFieldDisplay($item, $fieldname);
						$item_title = $item -> fields[$fieldname] -> display;
					}
					$thumbnails .= '<span class="caption ' . $params -> get('thumb_caption_class') . '">' . $item_title . '</span>';
				}

				$thumbnails .= '</div>';
				
			}

		}

		if($html_json != 'html') $json_idx++;
		if($html_json != 'json') $item_idx++;

	}

	if($html_json != 'json') {
		// item list wrapper
		$html .= '</ul>';

		// banner layout elements: arrows, thumbnails, counter
		if($banner_layout) {
			// render arrows divs
			$html .= '<div class="arrows"><div class="arrow prev ' . $params -> get('arrow_prev_class') . '"></div><div class="arrow next ' . $params -> get('arrow_next_class') . '"></div></div>';

			// render tabs
			$html .= '<div class="thumbnails">' . $thumbnails . '</div>';

			// render counter div
			$html .= '<div class="counter"><span class="current ' . $params -> get('counter_current_class') . '">1</span> of <span class="total ' . $params -> get('counter_total_class') . '">' . count($banners) . '</span></div>';
		}

		// module post test
		$html .= $params -> get('modpostxt');
		// module wrapper
		$html .= '</div>';
		echo $html;
	}
	if($html_json != 'html') {
		echo $params -> get('scriptpretxt');
		echo '<script>';
		if($params -> get('json_callback_func')) {
			if($params -> get('json_document_ready')) echo 'jQuery(document).ready(function(){';
			echo $params -> get('json_callback_func') . '(' . json_encode($json) . ');';
			if($params -> get('json_document_ready')) echo '});';
		}
		else echo 'var ' . $params -> get('json_var_prefix', 'myItems') . $module -> id . ' = ' . json_encode($json) . ';';
		echo '</script>';
		echo $params -> get('scriptpostxt');
	}
}
