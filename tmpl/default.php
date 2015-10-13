<?php // no direct access
defined('_JEXEC') or die('Restricted access');

// load required libraries
require_once (JPATH_ADMINISTRATOR.DS.'components/com_flexicontent/defineconstants.php');
JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_flexicontent'.DS.'tables');
require_once("components/com_flexicontent/models/".FLEXI_ITEMVIEW.".php");
require_once("components/com_flexicontent/classes/flexicontent.fields.php");
require_once("components/com_flexicontent/classes/flexicontent.helper.php");


if(count($items)) {
	echo '<div class="module mod_lyquix_items'.$params->get('moduleclass_sfx').'">';
	echo $params->get('modpretxt');
	
	foreach($items as $id){
		list($item_title,$item_date,$item_author,$item_titledateauthor,$item_introtext,$item_image,$item_fields)='';
		$itemmodel = new FlexicontentModelItem();
		$item = $itemmodel->getItem($id,false);
		$items = array($item);
		FlexicontentFields::getFields($items);
		$item_link = JRoute::_(FlexicontentHelperRoute::getItemRoute($item->slug, $item->categoryslug));
		$display = $params->get('itempretxt');
		
		// Title
		if($params->get('title_display',1)){
			$item_title = $item->title;
			FlexicontentFields::getFieldDisplay($item, $params->get('title_field'));
			if($params->get('title_field','title')!='title' && $item->fieldvalues[$item->fields[$params->get('title_field')]->id]) {
				switch($item->fields[$params->get('title_field')]->field_type) {
					case 'relateditems'||'relateditems_advanced':
						$relitemmodel = new FlexicontentModelItem();
						$relitem = $relitemmodel->getItem($item->fieldvalues[$item->fields[$params->get('title_field')]->id][0],false);
						$item_title = $relitem->title;
						break;
					default:
						FlexicontentFields::getFieldDisplay($item, $params->get('title_field'));
						$item_title = $item->fields[$params->get('title_field')]->display;
						$item_title = strip_tags($item_title);
						break;
				}
			}
			if($params->get('title_length',100) < strlen($item_title && $params->get('title_length',100) > 0)) {
				$item_title = trim(substr($item_title,0,$params->get('title_length', 100)));
				$item_title = substr($item_title,0,strrpos($item_title," "))."...";
			}
			if($params->get('title_link',1)) {
				$item_title = '<a href="'.$item_link.'">'.$item_title.'</a>';
			}
			$item_title = '<h'.$params->get('title_heading_level',3).'>'.$item_title.'</h'.$params->get('title_heading_level',3).'>';
		}
		
		// Date
		if($params->get('date_display',1)) {
			$item_date = $params->get('date_label').JHTML::_('date', $params->get('date_fields')=='created' ? $item->created : $item->modified , $params->get('date_format','DATE_FORMAT_LC3')!='custom' ? $params->get('date_format','DATE_FORMAT_LC3') : $params->get('date_custom'));
			$item_date = '<div class="date">'.$item_date.'</div>';
		}
		
		// Author
		if($params->get('author_display',1)) {
			$item_author = $params->get('author_label').($item->created_by_alias ? $item->created_by_alias : $item->creator);
			$item_author = '<div class="author">'.$item_author.'</div>';
		}
		// Intro Text
		
		if($params->get('introtext_display',1)){
			$item_introtext = strip_tags($item->text);
			if($params->get('introtext_field','text')!='text') {
				FlexicontentFields::getFieldDisplay($item, $params->get('introtext_field'));
				if(isset($item->fieldvalues[$item->fields[$params->get('introtext_field')]->id])){
					FlexicontentFields::getFieldDisplay($item, $params->get('introtext_field'), $values=null, $method='display');
					//$item_introtext = $item->fieldvalues[$item->fields[$params->get('introtext_field')]->id][0];
					$item_introtext = $item->fields[$params->get('introtext_field')]->display;
					$item_introtext = strip_tags($item_introtext);
				}
				else $item_introtext = '';
			}
			if($params->get('introtext_length',200) < strlen($item_introtext) && $params->get('introtext_length',100) > 0) {
				$item_introtext = trim(substr($item_introtext,0,$params->get('introtext_length', 200)));
				$item_introtext = substr($item_introtext,0,strrpos($item_introtext," "))."...";
			}
			if($item_introtext) $item_introtext = '<div class="field_'.$params->get('introtext_field','description').'">'.$item_introtext.'</div>';
		}
		
		// Image
		if($params->get('image_display',1) && $params->get('image_field')!=''){
			FlexicontentFields::getFieldDisplay($item, $params->get('image_field'));
			if(isset($item->fieldvalues[$item->fields[$params->get('image_field')]->id])){
				if($params->get('image_size')=='custom') {
					// custom size image
					$image_field = unserialize($item->fieldvalues[$item->fields[$params->get('image_field')]->id][0]);
					$image_file = JPATH_SITE.DS.'images'.DS.'stories'.DS.'flexicontent'.DS.'l_'.$image_field['originalname'];
					$conf = '&amp;w='.$params->get('image_width').'&amp;h='.$params->get('image_height').'&amp;aoe=1&amp;q=95';
					$conf .= $params->get('image_resize') ? '&amp;zc='.$params->get('image_resize') : '';
					$ext = pathinfo($image_file,PATHINFO_EXTENSION);
					$conf .= in_array($ext, array('png', 'ico', 'gif')) ? '&amp;f='.$ext : '';
					$image_src = JURI::base().'components/com_flexicontent/librairies/phpthumb/phpThumb.php?src='.$image_file.$conf;
					$item_image = '<img src="'.$image_src.'" alt="'.$image_field['alt'].'" title="'.$image_field['title'].'" /></a>';
				}
				else {
					// standard thumbnail
					$image_field = unserialize($item->fieldvalues[$item->fields[$params->get('image_field')]->id][0]);
					$image_src = JURI::base().'images/stories/flexicontent/'.$params->get('image_size').'_'.$image_field['originalname'];
					$item_image = '<img src="'.$image_src.'" alt="'.$image_field['alt'].'" title="'.$image_field['title'].'" /></a>';
				}
				if($params->get('image_link',1)) {
					$item_image = '<a href="'.$item_link.'">'.$item_image.'</a>';
				}
				$item_image = '<div class="image '.($params->get('image_align')!='none' ? $params->get('image_align') : '').'">'.$item_image.'</div>';
			}
		}
		
	
		// Additional Fields
		if($params->get('fields')!=''){
			$fields = explode(",",$params->get('fields'));
			foreach($fields as $field_name) {
				FlexicontentFields::getFieldDisplay($item, $field_name);
				if(isset($item->fieldvalues[$item->fields[$field_name]->id])){
					$item_fields .= '<div class="field_'.$field_name.'">';
					if($params->get('fields_label')) $item_fields .= '<div class="label">'.$item->fields[$field_name]->label.'</div>';
					$item_fields .= $item->fields[$field_name]->display.'</div>';
				}
			}
		}
		
		
		// Read More link
		if($params->get('readmore_display',1)){
			$item_readmore = '<a class="readmore" href="'.$item_link.'">'.$params->get('readmore_label','Read More').'</a>';
		}
		
		// Order the title-date-author
		for($i=0;$i<=2;$i++) {
			switch(substr($params->get('position_titledateauthor','tda'),$i,1)) {
				case 't':
					$item_titledateauthor .= $item_title;
					break;
				case 'd':
					$item_titledateauthor .= $item_date;
					break;
				case 'a':
					$item_titledateauthor .= $item_author;
					break;
			}
		}
		
		// Order the parts
		if($params->get('position_image','title')=='fields') {
			switch($params->get('position_fields','readmore')) {
				case 'title':
					$display .= $item_image.$item_fields.$item_titledateauthor.$item_introtext.$item_readmore;
					break;
				case 'introtext':
					$display .= $item_titledateauthor.$item_image.$item_fields.$item_introtext.$item_readmore;
					break;
				case 'readmore':
					$display .= $item_titledateauthor.$item_introtext.$item_image.$item_fields.$item_readmore;
					break;
				case 'end':
					$display .= $item_titledateauthor.$item_introtext.$item_readmore.$item_image.$item_fields;
					break;
			}
		}
		else {
			if($params->get('position_image','title')=='title') $display .= $item_image;
			if($params->get('position_fields','readmore')=='title') $display .= $item_fields;
			$display .= $item_titledateauthor;
			if($params->get('position_image','title')=='introtext') $display .= $item_image;
			if($params->get('position_fields','readmore')=='introtext') $display .= $item_fields;
			$display .= $item_introtext;
			if($params->get('position_image','title')=='readmore') $display .= $item_image;
			if($params->get('position_fields','readmore')=='readmore') $display .= $item_fields;
			$display .= $item_readmore;
			if($params->get('position_image','title')=='end') $display .= $item_image;
			if($params->get('position_fields','readmore')=='end') $display .= $item_fields;
		}
		
		$display .= $params->get('itempostxt');
		echo $display;
	}
	echo $params->get('modpostxt');
	echo '</div>';
}
