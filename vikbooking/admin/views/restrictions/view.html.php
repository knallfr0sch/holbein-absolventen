<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

// import Joomla view library
jimport('joomla.application.component.view');

class VikBookingViewRestrictions extends JViewVikBooking {
	
	function display($tpl = null) {
		// Set the toolbar
		$this->addToolBar();

		$dbo = JFactory::getDBO();
		$session = JFactory::getSession();
		$mainframe = JFactory::getApplication();
		$lim = $mainframe->getUserStateFromRequest("com_vikbooking.limit", 'limit', $mainframe->get('list_limit'), 'int');
		$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
		$q = "SELECT `id`,`name` FROM `#__vikbooking_rooms` ORDER BY `#__vikbooking_rooms`.`name` ASC;";
		$dbo->setQuery($q);
		$get_rooms = $dbo->loadAssocList();
		$all_rooms = array();
		if (count($get_rooms)) {
			foreach ($get_rooms as $rk => $rv) {
				$all_rooms[$rv['id']] = $rv['name'];
			}
		}
		$pvborderby = VikRequest::getString('vborderby', '', 'request');
		$pvbordersort = VikRequest::getString('vbordersort', '', 'request');
		$validorderby = array('id', 'name', 'dfrom', 'minlos', 'maxlos', 'ctad', 'ctdd');
		$orderby = $session->get('vbViewRestrictionsOrderby', 'id');
		$ordersort = $session->get('vbViewRestrictionsOrdersort', 'DESC');
		if (!empty($pvborderby) && in_array($pvborderby, $validorderby)) {
			$orderby = $pvborderby;
			$session->set('vbViewRestrictionsOrderby', $orderby);
			if (!empty($pvbordersort) && in_array($pvbordersort, array('ASC', 'DESC'))) {
				$ordersort = $pvbordersort;
				$session->set('vbViewRestrictionsOrdersort', $ordersort);
			}
		}

		$navbut = "";
		$q = "SELECT SQL_CALC_FOUND_ROWS * FROM `#__vikbooking_restrictions` ORDER BY `".$orderby."` ".$ordersort;
		$dbo->setQuery($q, $lim0, $lim);
		$rows = $dbo->loadAssocList();
		if ($rows) {
			$dbo->setQuery('SELECT FOUND_ROWS();');
			jimport('joomla.html.pagination');
			$pageNav = new JPagination( $dbo->loadResult(), $lim0, $lim );
			$navbut = "<table align=\"center\"><tr><td>".$pageNav->getListFooter()."</td></tr></table>";
		}
		
		$this->rows = $rows;
		$this->all_rooms = $all_rooms;
		$this->lim0 = $lim0;
		$this->navbut = $navbut;
		$this->orderby = $orderby;
		$this->ordersort = $ordersort;
		
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Sets the toolbar
	 */
	protected function addToolBar() {
		JToolBarHelper::title(JText::_('VBMAINRESTRICTIONSTITLE'), 'vikbooking');
		if (JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
			JToolBarHelper::addNew('newrestriction', JText::_('VBMAINRESTRICTIONNEW'));
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
			JToolBarHelper::editList('editrestriction', JText::_('VBMAINRESTRICTIONEDIT'));
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.delete', 'com_vikbooking')) {
			JToolBarHelper::deleteList(JText::_('VBDELCONFIRM'), 'removerestrictions', JText::_('VBMAINRESTRICTIONDEL'));
			JToolBarHelper::spacer();
		}
	}

}
