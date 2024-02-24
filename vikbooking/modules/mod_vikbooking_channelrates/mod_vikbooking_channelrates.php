<?php
/**
 * @package     VikBooking
 * @subpackage  mod_vikbooking_channelrates
 * @author      Alessio Gaggii - E4J s.r.l
 * @copyright   Copyright (C) 2018 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */
 
// no direct access
defined('ABSPATH') or die('No script kiddies please!');

if (!class_exists('VikBooking')) {
	// the widget is probably loaded in Home Page or any other page where VBO is not available. We need to load the main VBO library
	require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "lib.vikbooking.php");
}

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'helper.php');

VikBookingChannelRatesHelper::loadFontAwesome();
VikBookingChannelRatesHelper::getTracker();

if (method_exists('VikBooking', 'loadPreferredColorStyles')) {
	VikBooking::loadPreferredColorStyles();
}

// get widget id
$randid = str_replace('mod_vikbooking_channelrates-', '', $params->get('widget_id', rand(1, 999)));
// get widget base URL
$baseurl = VBO_MODULES_URI;

// module layout file
require JModuleHelper::getLayoutPath('mod_vikbooking_channelrates', $params->get('layout', 'default'));
