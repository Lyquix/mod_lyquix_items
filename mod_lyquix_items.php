<?php
/**
 * @license GNU/GPL v2
 * @copyright  Copyright (c) Lyquix. All rights reserved.
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

// Include the syndicate functions only once
require_once (dirname(__FILE__).DS.'helper.php');
$items = modLyquixItemsHelper::getList($params);
require(JModuleHelper::getLayoutPath('mod_lyquix_items'));
