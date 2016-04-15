<?php // no direct access
defined('_JEXEC') or die('Restricted access');

// are there any items to show?
if (count($items)) {
	
	$html = '';
	$json = array();
	$i = 0;

	// load required FLEXIcontent libraries
	require_once (JPATH_ADMINISTRATOR.DS.'components'.DS.'com_flexicontent'.DS.'defineconstants.php');
	JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_flexicontent'.DS.'tables');
	require_once (JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'helpers'.DS.'route.php');
	require_once (JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'classes'.DS.'flexicontent.fields.php');
	require_once (JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'classes'.DS.'flexicontent.helper.php');
	require_once (JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'helpers'.DS.'permission.php');
	require_once (JPATH_SITE.DS.'components'.DS.'com_flexicontent'.DS.'models'.DS.FLEXI_ITEMVIEW.'.php');

	$html .= '<div class="module mod_lyquix_items' . $params -> get('moduleclass_sfx') . '">';
	// module pre-text
	$html .= $params -> get('modpretxt');

	// get layout order
	$layout_order = explode(',', $params -> get('layout_order', 'image,title,date,author,intro,fields,readmore'));

	// cycle through items
	foreach ($items as $id) {

		// initialize some working variables
		list($item_title, $item_date, $item_author, $item_titledateauthor, $item_introtext, $item_image, $item_fields) = '';

		// create a new FC object for the item
		$itemmodel = new FlexicontentModelItem();
		$item = $itemmodel -> getItem($id, false);
		$itemslist = array($item);
		FlexicontentFields::getFields($itemslist, 'module');

		// generate item url
		$item_link = JRoute::_(FlexicontentHelperRoute::getItemRoute($item -> slug, $item -> categoryslug));

		// get custom CSS from user function
		$css = array();
		eval($params -> get('item_css_func'));

		// item open tag
		$html .= '<li class="item ' . implode(' ', $css) . '">';

		// item pre-text
		$html .= $params -> get('itempretxt');
		
		// add item to json array
		$json[$i] = array(
			'id' => $id,
			'url' => rtrim(JURI::base(), '/') . $item_link
		);
		
		// cycle through layout order
		foreach ($layout_order as $section) {

			switch($section) {

				case 'image' :
					// Image
					if ($params -> get('image_field') != '') {

						// if there is image content
						if (isset($item -> fieldvalues[$item -> fields[$params -> get('image_field')] -> id])) {

							// Unserialize value's properties and check for empty original name property
							$value = unserialize($item -> fieldvalues[$item -> fields[$params -> get('image_field')] -> id][0]);
							$image_name = trim(@$value['originalname']);

							if (strlen($image_name)) {

								$field = $item -> fields[$params -> get('image_field')];
								$field -> parameters = json_decode($field -> attribs, true);
								$image_source = $field -> parameters['image_source'];
								$dir_url = str_replace('\\', '/', $field -> parameters['dir']);
								$multiple_image_usages = !$image_source && $field -> parameters['list_all_media_files'] && $field -> parameters['unique_thumb_method'] == 0;
								$extra_prefix = $multiple_image_usages ? 'fld' . $field -> id . '_' : '';
								$of_usage = $field -> untranslatable ? 1 : $field -> parameters['of_usage'];
								$u_item_id = ($of_usage && $item -> lang_parent_id && $item -> lang_parent_id != $item -> id) ? $item -> lang_parent_id : $item -> id;
								$extra_folder = '/item_' . $u_item_id . '_field_' . $field -> id;
										
								if ($params -> get('image_size') == 'custom') {
									
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
									
									// custom size image
									$image_file .= '/' .  $image_name;
									
									$conf = '&amp;w=' . $params -> get('image_width', 960) . '&amp;h=' . $params -> get('image_height', 540) . '&amp;aoe=1&amp;q=95';
									$conf .= $params -> get('image_resize', 1) ? '&amp;zc=1' : '';
									$ext = strtolower(pathinfo($image_file, PATHINFO_EXTENSION));
									$conf .= in_array($ext, array('png', 'ico', 'gif')) ? '&amp;f=' . $ext : '';
									
									$src = JURI::root(true) . '/components/com_flexicontent/librairies/phpthumb/phpThumb.php?src=' . urlencode($image_file) . $conf;
									
								}
								
								else {
									
									// Create thumbs URL path
									$src = JURI::root(true) . '/' . $dir_url;
									
									// Extra thumbnails sub-folder
									if ($image_source == 1) {
										// item+field specific folder
										$src .= $extra_folder;
									}
	
									$src .= '/' . $params -> get('image_size', 's') . '_' . $extra_prefix . $image_name;
									
								}

								$html .= '<div class="image ' . ($params -> get('image_align') != 'none' ? $params -> get('image_align') : '') . ' ' . $params -> get('image_class') . '">';

								// make image clickable?
								if ($params -> get('image_link', 1)) {
									$html .= '<a href="' . $item_link . '">';
								}

								$html .= '<img src="' . $src . '" alt="' . htmlspecialchars(@$value['alt'], ENT_COMPAT, 'UTF-8') . '" />';

								if ($params -> get('image_link', 1)) {
									$html .= '</a>';
								}

								$html .= '</div>';
								$json[$i]['image'] = $src;

							}
						}
					}

					break;

				case 'title' :
					// Title

					// first get the item title (just in case)
					$item_title = $item -> title;

					if ($params -> get('title_field', 'title') != 'title' && array_key_exists($item -> fields[$params -> get('title_field')] -> id, $item -> fieldvalues)) {
						// check what kind of field we are using
						switch($item->fields[$params->get('title_field')]->field_type) {
							// for item relations, get the title of the related item
							case 'relateditems' :
							case 'relateditems_advanced' :
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
					
					$json[$i]['title'] = $item_title;
					
					// title clickable?
					if ($params -> get('title_link', 1)) {
						$item_title = '<a href="' . $item_link . '">' . $item_title . '</a>';
					}

					$html .= '<h' . $params -> get('title_heading_level', 3) . ' class="' . $params -> get('title_class') . '">' . $item_title . '</h' . $params -> get('title_heading_level', 3) . '>';
					
					break;

				case 'date' :
					// Date
					$item_date = $params -> get('date_label') . JHTML::_('date', $params -> get('date_fields') == 'created' ? $item -> created : $item -> modified, $params -> get('date_format', 'DATE_FORMAT_LC3') != 'custom' ? $params -> get('date_format', 'DATE_FORMAT_LC3') : $params -> get('date_custom'));
					$html .= '<div class="date ' . $params -> get('date_class') . '">' . $item_date . '</div>';
					$json[$i]['date'] = $item_date;
					
					break;

				case 'author' :
					// Author
					$item_author = $params -> get('author_label') . ($item -> created_by_alias ? $item -> created_by_alias : $item -> creator);
					$html .= '<div class="author ' . $params -> get('author_class') . '">' . $item_author . '</div>';
					$json[$i]['author'] = $item_author;

					break;

				case 'intro' :
					// Intro text

					// get intro text from item, just in case
					$item_introtext = strip_tags($item -> text);

					// not default text?
					if ($params -> get('introtext_field', 'text') != 'text') {
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
						$html .= '<div class="field_' . $params -> get('introtext_field', 'description') . ' ' . $params -> get('intro_class') . '">' . $item_introtext . '</div>';
						$json[$i]['intro'] = $item_introtext;
					}

					break;

				case 'fields' :
					// Additional fields

					$field_names = $params -> get('fields');
					$fields = explode(",", $field_names);
					// cycle through fields
					foreach ($fields as $field_name) {
						$field_name = trim($field_name);
						// process field
						FlexicontentFields::getFieldDisplay($item, $field_name);
						if (isset($item -> fields[$field_name])) {
							
							$field = $item -> fields[$field_name];
							
							$field_html = '';
							
							// render using custom field function
							eval($params -> get('field_render_func'));
							
							if (!$field_html && !(empty($item -> fields[$field_name] -> display))) {
								$field_html .= '<div class="field field_' . $field_name . ' ' . $params -> get('fields_class') . '">';
								if ($params -> get('fields_label')) {
									$field_html .= '<div class="label">' . $item -> fields[$field_name] -> label . '</div>';
								}
								$field_html .= $item -> fields[$field_name] -> display . '</div>';

								//PDW added code to add id, label, values and display to json field
								$json[$i][$field_name] = array();
								$json[$i][$field_name]['id'] = $field -> id;
								$json[$i][$field_name]['label'] = $field -> label;
								$json[$i][$field_name]['value'] = $item -> fields[$field -> name] -> iscore ? $item -> {$field -> name} : $item -> fieldvalues [$field -> id];
								$json[$i][$field_name]['display'] = $field -> display;
								//PDW added code end
							}
							
							$html .= $field_html;
							
						}
					}

					break;

				case 'readmore' :
					// Read more link
					$html .= '<a class="readmore ' . $params -> get('readmore_class') . '" href="' . $item_link . '">' . $params -> get('readmore_label', 'Read More') . '</a>';

					break;
			}

		}

		$html .= $params -> get('itempostxt') . '</li>';
		$i++;
	}
	$html .= $params -> get('modpostxt');
	$html .= '</div>';
	
	// print output
	$html_json = $params -> get('html_json', 'html');
	if($html_json != 'json') {
		echo $html;
	}
	if($html_json != 'html') {
		echo '<script>var myItems' . $module -> id . ' = ' . json_encode($json) . ';</script>';
	}
}
