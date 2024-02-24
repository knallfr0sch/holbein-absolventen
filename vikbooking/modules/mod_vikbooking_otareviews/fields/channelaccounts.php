<?php
/**
 * @package     VikBooking
 * @subpackage  mod_vikbooking_otareviews
 * @author      Alessio Gaggii - E4J s.r.l
 * @copyright   Copyright (C) 2018 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */
 
// no direct access
defined('ABSPATH') or die('No script kiddies please!');

jimport('joomla.form.formfield.list');

class JFormFieldChannelaccounts extends JFormFieldList
{
	protected $type = 'channelaccounts';

	/**
	 * @inheritDoc
	 *
	 * @since 1.14.4
	 */
	public function getOptions()
	{
		$dbo = JFactory::getDbo();
		// container of unique channel-property combinations
		$channelaccounts = [];

		// load pairs of channel-property from the reviews downloaded
		$q = "SELECT * FROM `#__vikchannelmanager_otareviews` ORDER BY `channel` ASC, `prop_name` ASC;";
		$dbo->setQuery($q);
		
		foreach ($dbo->loadAssocList() as $v) {
			if (!empty($v['channel'])) {
				// OTA review
				$chidentifier = $v['channel'] . '_' . $v['prop_name'];
				if (!isset($channelaccounts[$chidentifier])) {
					$channelaccounts[$chidentifier] = $v['channel'] . (!empty($v['prop_name']) ? ' ('.$v['prop_name'].')' : '');
				}
			} elseif (!empty($v['prop_name'])) {
				// Website review for multi account
				$chidentifier = 'ibe_' . $v['prop_name'];
				if (!isset($channelaccounts[$chidentifier])) {
					$channelaccounts[$chidentifier] = JText::_('VBOMODOTAREVWEBSITE') . (!empty($v['prop_name']) ? ' ('.$v['prop_name'].')' : '');
				}
			} else {
				// Website review for single account
				if (!isset($channelaccounts['ibe'])) {
					$channelaccounts['ibe'] = JText::_('VBOMODOTAREVWEBSITE');
				}
			}
		}

		// load pairs of channel-property from the scores downloaded
		$q = "SELECT * FROM `#__vikchannelmanager_otascores` ORDER BY `channel` ASC, `prop_name` ASC;";
		$dbo->setQuery($q);
		
		foreach ($dbo->loadAssocList() as $v) {
			$chidentifier = $v['channel'] . '_' . $v['prop_name'];
			if (!isset($channelaccounts[$chidentifier])) {
				$channelaccounts[$chidentifier] = $v['channel'] . (!empty($v['prop_name']) ? ' ('.$v['prop_name'].')' : '');
			}
		}

		// if (!$channelaccounts) {
		// 	// return an error string
		// 	return '<div style="width: 100%; font-weight: bold; border-bottom: 2px solid #12728b; color: #ff0000; padding: 5px 0; text-align: left; margin-bottom:10px;">No reviews available.</div>';
		// }
		
		// build the list of options
		// $opt_str = '';
		// foreach ($channelaccounts as $k => $v) {
		// 	$opt_str .= '<option value="'.$k.'"'.($this->value == $k ? " selected=\"selected\"" : "").'>'.htmlspecialchars(ucwords($v)).'</option>';
		// }

		// $html = '<select class="inputbox" name="' . $this->name . '" >';
		// $html .= '<option value=""></option>';
		// $html .= $opt_str;
		// $html .='</select>';

		// load existing options from parent first
		$options = parent::getOptions();

		if (!$options && !$channelaccounts)
		{
			$options[] = JHtml::_('select.option', '', '- no reviews available -');
		}

		foreach ($channelaccounts as $value => $text) {
			$options[] = JHtml::_('select.option', $value, $text);
		}

		return $options;
    }
}
