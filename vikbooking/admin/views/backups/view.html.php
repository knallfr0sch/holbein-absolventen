<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking backups view.
 *
 * @since 1.5
 */
class VikBookingViewBackups extends JViewVikBooking
{
	/**
	 * VikBooking view display method.
	 *
	 * @return 	void
	 */
	function display($tpl = null)
	{
		$app   = JFactory::getApplication();
		$dbo   = JFactory::getDbo();

		$model = new VBOModelBackup();

		// set the toolbar
		$this->addToolBar();

		$this->ordering = $app->getUserStateFromRequest('com_vikbooking.backups.ordering', 'filter_order', 'createdon', 'string');
		$this->orderDir = $app->getUserStateFromRequest('com_vikbooking.backups.orderdir', 'filter_order_Dir', 'DESC', 'string');

		// db object
		$lim 	= $app->getUserStateFromRequest('com_vikbooking.limit', 'limit', $app->get('list_limit'), 'uint');
		$lim0 	= $app->input->getUint('limitstart', 0);
		$navbut	= '';

		// load all the export types
		$this->exportTypes = $model->getExportTypes();

		$rows = array();

		// fetch folder in which the backup are stored
		$folder = VBOFactory::getConfig()->get('backupfolder');

		if (!$folder)
		{
			// use temporary folder if not specified
			$folder = JFactory::getApplication()->get('tmp_path');
		}

		if ($folder && JFolder::exists($folder))
		{
			// load all backup archives
			$rows = JFolder::files($folder, 'backup_', $recurse = false, $fullpath = true);
		}

		// fetch backup details
		$rows = array_map(function($file) use ($model)
		{
			return $model->getItem($file);
		}, $rows);

		$ordering  = $this->ordering;
		$direction = $this->orderDir;

		// fetch the type of ordering
		usort($rows, function($a, $b) use ($ordering, $direction)
		{
			switch ($ordering)
			{
				case 'filesize':
					// sort by file size
					$factor = $a->size - $b->size;
					break;

				default:
					// sort by creation date
					$factor = $a->timestamp - $b->timestamp;
			}

			// in case of descending direction, reverse the ordering factor
			if (preg_match("/desc/i", $direction))
			{
				$factor *= -1;
			}

			return $factor;
		});

		$tot_count = count($rows);

		if ($tot_count > $lim)
		{
			if ($lim0 >= $tot_count)
			{
				// We exceeded the pagination, probably because we deleted all the records of the last page.
				// For this reason, we need to go back to the previous one.
				$lim0 = max(array(0, $lim0 - $lim));
			}

			$rows = array_slice($rows, $lim0, $lim);

			jimport('joomla.html.pagination');
			$pageNav = new JPagination($tot_count, $lim0, $lim);
			$navbut = "<table align=\"center\"><tr><td>" . $pageNav->getListFooter() . "</td></tr></table>";
		}

		$this->rows   = $rows;
		$this->navbut = $navbut;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Setting the toolbar.
	 *
	 * @return 	void
	 */
	protected function addToolBar()
	{
		// add menu title and some buttons to the page
		JToolBarHelper::title(JText::_('VBMAINBACKUPSTITLE'), 'vikbooking');

		$user = JFactory::getUser();

		if ($user->authorise('core.create', 'com_vikbooking'))
		{
			JToolBarHelper::addNew('backup.add');
		}

		if ($user->authorise('core.delete', 'com_vikbooking'))
		{
			JToolBarHelper::deleteList(JText::_('VBDELCONFIRM'), 'backup.delete');
		}
	}
}
