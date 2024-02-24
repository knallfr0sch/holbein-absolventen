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

class VikBookingViewCustomers extends JViewVikBooking {
	
	function display($tpl = null) {
		// Set the toolbar
		$this->addToolBar();

		$dbo = JFactory::getDbo();
		$mainframe = JFactory::getApplication();
		$lim = $mainframe->getUserStateFromRequest("com_vikbooking.limit", 'limit', $mainframe->get('list_limit'), 'int');
		$lim0 = VikRequest::getVar('limitstart', 0, '', 'int');
		$session = JFactory::getSession();
		$pvborderby = VikRequest::getString('vborderby', '', 'request');
		$pvbordersort = VikRequest::getString('vbordersort', '', 'request');
		$validorderby = array('id','first_name', 'last_name', 'email', 'phone', 'country', 'pin', 'tot_bookings');
		$orderby = $session->get('vbViewCustomersOrderby', 'last_name');
		$ordersort = $session->get('vbViewCustomersOrdersort', 'ASC');
		if (!empty($pvborderby) && in_array($pvborderby, $validorderby)) {
			$orderby = $pvborderby;
			$session->set('vbViewCustomersOrderby', $orderby);
			if (!empty($pvbordersort) && in_array($pvbordersort, array('ASC', 'DESC'))) {
				$ordersort = $pvbordersort;
				$session->set('vbViewCustomersOrdersort', $ordersort);
			}
		}
		$rows = "";
		$navbut = '';
		$pfiltercustomer = $mainframe->getUserStateFromRequest("vbo.customers.filtercustomer", 'filtercustomer', '', 'string');
		$whereclause = '';
		if (!empty($pfiltercustomer)) {
			$whereclause = " WHERE CONCAT_WS(' ', `first_name`, `last_name`) LIKE ".$dbo->quote("%".$pfiltercustomer."%")." OR `email` LIKE ".$dbo->quote("%".$pfiltercustomer."%")." OR `pin` LIKE ".$dbo->quote("%".$pfiltercustomer."%")."";
		}
		//this query below is safe with the error #1055 when sql_mode=only_full_group_by because there is no GROUP BY clause
		$q = "SELECT SQL_CALC_FOUND_ROWS *,(SELECT COUNT(*) FROM `#__vikbooking_customers_orders` WHERE `#__vikbooking_customers_orders`.`idcustomer`=`#__vikbooking_customers`.`id`) AS `tot_bookings`,(SELECT `country_name` FROM `#__vikbooking_countries` WHERE `#__vikbooking_countries`.`country_3_code`=`#__vikbooking_customers`.`country`) AS `country_full_name` FROM `#__vikbooking_customers`".$whereclause." ORDER BY `".$orderby."` ".$ordersort;
		$dbo->setQuery($q, $lim0, $lim);
		$dbo->execute();

		/**
		 * Call assertListQuery() from the View class to make sure the filters set
		 * do not produce an empty result. This would reset the page in this case.
		 */
		$this->assertListQuery($lim0, $lim);
		//

		if ($dbo->getNumRows() > 0) {
			$rows = $dbo->loadAssocList();
			$dbo->setQuery('SELECT FOUND_ROWS();');
			jimport('joomla.html.pagination');
			$pageNav = new JPagination( $dbo->loadResult(), $lim0, $lim );
			$navbut = "<table align=\"center\"><tr><td>".$pageNav->getListFooter()."</td></tr></table>";
		}
		
		$this->rows = $rows;
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
		JToolBarHelper::title(JText::_('VBMAINCUSTOMERSTITLE'), 'vikbooking');
		if (JFactory::getUser()->authorise('core.create', 'com_vikbooking')) {
			JToolBarHelper::addNew('newcustomer', JText::_('VBMAINCUSTOMERNEW'));
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.edit', 'com_vikbooking')) {
			JToolBarHelper::editList('editcustomer', JText::_('VBMAINCUSTOMEREDIT'));
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.vbo.management', 'com_vikbooking')) {
			JToolBarHelper::custom( 'exportcustomers', 'file-2', 'file-2', JText::_('VBOCSVEXPCUSTOMERS'), false);
			JToolBarHelper::spacer();
		}
		if (JFactory::getUser()->authorise('core.delete', 'com_vikbooking')) {
			JToolBarHelper::deleteList(JText::_('VBDELCONFIRM'), 'removecustomers', JText::_('VBMAINCUSTOMERDEL'));
			JToolBarHelper::spacer();
		}
	}

}
