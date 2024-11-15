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

JLoader::import('adapter.payment.payment');

/**
 * This class is used to collect the credit card details
 * of the customers within VikBooking plugin booking process.
 *
 * @since 1.0.5
 */
class VikBookingOfflineCreditCardPayment extends JPayment
{
	private $validation = 0;
	private $vbo_app;

	/**
	 * @override
	 * Class constructor.
	 *
	 * @param 	string 	$alias 	 The name of the plugin that requested the payment.
	 * @param 	mixed 	$order 	 The order details to start the transaction.
	 * @param 	mixed 	$params  The configuration of the payment.
	 */
	public function __construct($alias, $order, $params = array())
	{
		parent::__construct($alias, $order, $params);
		
		$this->setParam('askcvv', $this->getParam('askcvv') == 'ON' ? 1 : 0);
		$this->setParam('sslvalidation', $this->getParam('sslvalidation') == 'ON' ? 1 : 0);
		$this->setParam('sslcapturepage', $this->getParam('sslcapturepage') == 'ON' ? 1 : 0);
		
		if (!class_exists('VboApplication'))
		{
			require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "jv_helper.php";
		}

		$this->vbo_app = new VboApplication;

		$oid = $this->get('id');

		if (empty($oid))
		{
			$details = $this->get('details', []);
			$this->set('oid', (isset($details['id']) ? $details['id'] : 0));
		}
		else
		{
			$this->set('oid', $this->get('id'));
		}
	}

	/**
	 * @override
	 * Method used to build the associative array 
	 * to allow the plugins to construct a configuration form.
	 *
	 * In case the payment needs an API Key, the array should
	 * be built as follows:
	 *
	 * {"apikey": {"type": "text", "label": "API Key"}}
	 *
	 * @return 	array 	The associative array.
	 */
	protected function buildAdminParameters()
	{
		return array(
			'newstatus' => array(
				'type' 		=> 'select',
				'label' 	=> 'Set Reservation status to://if you want to manually verify that the credit card details are valid, set this to Pending',
				'options' 	=> array('CONFIRMED', 'PENDING'),
			),
			'askcvv' => array(
				'type' 		=> 'select',
				'label' 	=> 'Request CVV Code://If enabled the validation page should be in HTTPS for PCI-compliance',
				'options' 	=> array('OFF', 'ON'),
			),
			'cardtypes' => array(
				'type' 	=> 'text',
				'label' => 'Credit Card Types://separate the choices with a comma, ex. Visa,Mastercard,Amex',
			),
			'sslvalidation' => array(
				'type' 		=> 'select',
				'label' 	=> 'Force HTTPS Validation://If enabled the validation page will be in HTTPS',
				'options' 	=> array('OFF', 'ON'),
			),
			'sslcapturepage' => array(
				'type' 		=> 'select',
				'label' 	=> 'Force HTTPS Capture://If enabled the page where the credit card information are captured will be in HTTPS',
				'options' 	=> array('OFF', 'ON'),
			),
		);
	}

	/**
	 * @override
	 * Method used to begin a payment transaction.
	 * This method usually generates the HTML form of the payment.
	 * The HTML contents can be echoed directly because this method
	 * is executed always within a buffer.
	 *
	 * @return 	void
	 */
	protected function beginTransaction()
	{
		//load jQuery
		if (VikBooking::loadJquery())
		{
			JHtml::_('jquery.framework', true, true);
		}

		$pitemid = VikRequest::getString('Itemid', '', 'request');
		$depositmess = "";
		$actionurl = $this->get('notify_url');
		//enable ssl in the payment validation page
		if ($this->getParam('sslvalidation')) {
			$actionurl = str_replace('http:', 'https:', $actionurl);
		}
		//enable ssl in the credit card info capture page
		if ($this->getParam('sslcapturepage')) {
			if ($_SERVER['HTTPS'] != "on") {
				$url = $this->get('return_url');
				$mainframe = JFactory::getApplication();
				$mainframe->redirect(str_replace('http:', 'https:', $url));
				exit;
			}
		}
		//
		$cardtypes = array();
		$param_ctypes = $this->getParam('cardtypes');
		if(!empty($param_ctypes)) {
			$all_cardtypes = explode(',', $this->getParam('cardtypes'));
			foreach ($all_cardtypes as $cardtype) {
				$cardtypes[] = trim($cardtype);
			}
		}
		
		//check previous submissions
		$data_previously_sent = false;
		$dbo = JFactory::getDBO();
		$q = "SELECT `paymentlog` FROM `#__vikbooking_orders` WHERE `id`=".(int)$this->get('oid').";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cur_log = $dbo->loadResult();
			$data_previously_sent = !empty($cur_log) ? true : $data_previously_sent;
		}
		//

		$info = $this->get('payment_info');

		/**
		 * @wponly 	we need to let WordPress parse the paragraphs in the message.
		 */
		if (VBOPlatformDetection::isWordPress() && is_array($info) && !empty($info['note'])) {
			$info['note'] = wpautop($info['note']);
		}

		$form = '<div class="offline-cc-overlay-outer"><div class="offline-cc-overlay-inner">'."\n";
		$form .= '<div class="offline-cc-overlay-closer"><i class="'.VikBookingIcons::i('times').'"></i></div>'."\n";
		$form .= "<p>".JText::_('VBCCOFFLINECCMESSAGE')."</p><form action=\"".$actionurl."\" method=\"post\" name=\"offlineccpaymform\">\n";
		$form .= (is_array($info) && isset($info['note']) ? $info['note'] : '') . "<div class=\"vbo-offline-cc-container\">\n";
		$form .= '<div class="vbo-offline-cc-row-group vbo-offline-cc-row-group-cardpan">';
		if (count($cardtypes)) {
			$form .= "<div class=\"vbo-offline-cc-row vbo-offline-cc-row-cardtype\"><div class=\"vbo-offline-cc-lbl\">".JText::_('VBCCCREDITCARDTYPE')." <sup>*</sup></div><div class=\"vbo-offline-cc-val\"><select name=\"credit_card_type\">\n";
			foreach ($cardtypes as $cardtype) {
				$form .= '<option value="'.$cardtype.'">'.$cardtype.'</option>'."\n";
			}
			$form .= "</select></div></div>\n";
		}
		$form .= "<div class=\"vbo-offline-cc-row vbo-offline-cc-row-cardpan\"><div class=\"vbo-offline-cc-lbl\">".JText::_('VBCCCREDITCARDNUMBER')." <sup>*</sup></div><div class=\"vbo-offline-cc-val\"><input type=\"text\" id=\"credit_card_number\" name=\"credit_card_number\" autocomplete=\"off\" size=\"20\" value=\"\"/></div></div>\n";
		$form .= "</div>\n";
		$form .= '<div class="vbo-offline-cc-row-group vbo-offline-cc-row-group-validity">';
		$form .= '<div class="vbo-offline-cc-row vbo-offline-cc-row-validity"><div class="vbo-offline-cc-lbl">'.JText::_('VBCCVALIDTHROUGH').' <sup>*</sup></div><div class="vbo-offline-cc-val"><select name="expire_month">
				<option value="01">'.JText::_('VBMONTHONE').'</option>
				<option value="02">'.JText::_('VBMONTHTWO').'</option>
				<option value="03">'.JText::_('VBMONTHTHREE').'</option>
				<option value="04">'.JText::_('VBMONTHFOUR').'</option>
				<option value="05">'.JText::_('VBMONTHFIVE').'</option>
				<option value="06">'.JText::_('VBMONTHSIX').'</option>
				<option value="07">'.JText::_('VBMONTHSEVEN').'</option>
				<option value="08">'.JText::_('VBMONTHEIGHT').'</option>
				<option value="09">'.JText::_('VBMONTHNINE').'</option>
				<option value="10">'.JText::_('VBMONTHTEN').'</option>
				<option value="11">'.JText::_('VBMONTHELEVEN').'</option>
				<option value="12">'.JText::_('VBMONTHTWELVE').'</option>
				</select> ';
		$maxyear = date("Y");
		$form .= '<select name="expire_year">';
		for ($i = $maxyear; $i <= ($maxyear + 10); $i++) {
			$form .= '<option value="'.substr($i, -2, 2).'">'.$i.'</option>';
		}
		$form .= '</select></div></div>'."\n";
		if ($this->getParam('askcvv')) {
			$form .= "<div class=\"vbo-offline-cc-row vbo-offline-cc-row-cvv\"><div class=\"vbo-offline-cc-lbl\">".JText::_('VBCCCVV')." <sup>*</sup></div><div class=\"vbo-offline-cc-val\"><input type=\"text\" id=\"credit_card_cvv\" name=\"credit_card_cvv\" autocomplete=\"off\" size=\"5\" value=\"\"/></div></div>\n";
		}
		$form .= "</div>\n";
		$form .= '<div class="vbo-offline-cc-row-group vbo-offline-cc-row-group-cardholder">';
		$form .= "<div class=\"vbo-offline-cc-row vbo-offline-cc-row-fname\"><div class=\"vbo-offline-cc-lbl\">".JText::_('VBCCFIRSTNAME')." <sup>*</sup></div><div class=\"vbo-offline-cc-val\"><input type=\"text\" id=\"business_first_name\" name=\"business_first_name\" autocomplete=\"off\" size=\"20\" value=\"\"/></div></div>\n";
		$form .= "<div class=\"vbo-offline-cc-row vbo-offline-cc-row-lname\"><div class=\"vbo-offline-cc-lbl\">".JText::_('VBCCLASTNAME')." <sup>*</sup></div><div class=\"vbo-offline-cc-val\"><input type=\"text\" id=\"business_last_name\" name=\"business_last_name\" autocomplete=\"off\" size=\"20\" value=\"\"/></div></div>\n";
		$form .= "</div>\n";
		$form .= "<div class=\"vbo-offline-cc-row vbo-offline-cc-row-submit\"><div class=\"vbo-offline-cc-val\"><input type=\"submit\" id=\"offlineccsubmit\" name=\"offlineccsubmit\" class=\"button\" value=\"".JText::_('VBOFFLINECCSEND')."\" onclick=\"javascript: event.preventDefault(); this.disabled = true; this.value = '".addslashes(JText::_('VBOFFLINECCSENT'))."'; document.offlineccpaymform.submit(); return true;\"/></div></div>\n";
		$form .= "</div>\n";
		$form .= "<input type=\"hidden\" name=\"total\" value=\"".number_format($this->get('total_to_pay'), 2)."\"/>\n";
		$form .= "<input type=\"hidden\" name=\"description\" value=\"".$this->get('rooms_name')."\"/>\n";
		if (!empty($pitemid)) {
			$form .= "<input type=\"hidden\" name=\"Itemid\" value=\"".$pitemid."\"/>\n";
		}
		$form .= "</form>\n";
		$form .= "</div></div>\n";
		$form .= "<div class=\"vbo-offline-cc-toggle-wrap\"/><button type=\"button\" class=\"btn\" id=\"offline-cc-overlay-toggler\">".JText::_('VBCCOFFLINECCTOGGLEFORM')."</button>\n</div>\n";
		
		if ($this->get('leave_deposit')) {
			$depositmess = "<p class=\"vbo-leave-deposit\"><span>".JText::_('VBLEAVEDEPOSIT')."</span>".$this->get('currency_symb')." ".number_format($this->get('total_to_pay'), 2)."</p><br/>";
		}
		//output
		echo $depositmess;
		echo $form;
		?>
		<script type="text/javascript">
		jQuery(function() {
		<?php
		if($data_previously_sent !== true) {
		?>
			jQuery('.offline-cc-overlay-outer').fadeIn();
		<?php
		}
		?>
			jQuery('#offline-cc-overlay-toggler').click(function() {
				jQuery('.offline-cc-overlay-outer').fadeToggle();
			});
			jQuery('.offline-cc-overlay-closer').click(function() {
				jQuery('.offline-cc-overlay-outer').fadeOut();
			});
		});
		</script>
		<?php
		
		return true;
	}

	/**
	 * @override
	 * Method used to validate the payment transaction.
	 * It is usually an end-point that the providers use to POST the
	 * transaction data.
	 *
	 * @param 	JPaymentStatus 	&$status 	The status object. In case the payment was 
	 * 										successful, you should invoke: $status->verified().
	 *
	 * @return 	boolean
	 *
	 * @see 	JPaymentStatus
	 */
	protected function validateTransaction(JPaymentStatus &$status)
	{	
		//post data
		$creditcardtype = VikRequest::getString('credit_card_type', '', 'request');
		$creditcard = VikRequest::getString('credit_card_number', '', 'request');
		$creditcard = str_replace(' ', '', $creditcard);
		$expire_month = VikRequest::getString('expire_month', '', 'request');
		$expire_year = VikRequest::getString('expire_year', '', 'request');
		$cvv = VikRequest::getString('credit_card_cvv', '', 'request');
		$total = VikRequest::getString('total', '', 'request');
		$business_first_name = VikRequest::getString('business_first_name', '', 'request');
		$business_last_name = VikRequest::getString('business_last_name', '', 'request');
		//end post data
		
		//post data validation

		/**
		 * @wponly
		 */

		$itemid = $this->getItemID();

		$error_redirect_url = 'index.php?option=com_vikbooking&view=booking&sid='.$this->get('sid').'&ts='.$this->get('ts');
		if ($itemid)
		{
			$error_redirect_url .= '&Itemid=' . $itemid;
		}
		$error_redirect_url = JRoute::_($error_redirect_url, false);
		//

		$valid_data = true;
		$current_month = date("m");
		$current_year = date("y");
		if ((int)$expire_year < (int)$current_year) {
			$valid_data = false;
		} else { 
			if ((int)$expire_year == (int)$current_year) {
				if ((int)$expire_month < (int)$current_month) {
					$valid_data = false;
				}
			}
		}
		if(empty($creditcard) || strlen($creditcard) < 13 || strlen($creditcard) > 19 || (empty($cvv) && $this->getParam('askcvv')) || empty($business_first_name) || empty($business_last_name)) {
			$valid_data = false;
		}
		$param_ctypes = $this->getParam('cardtypes');
		if(!empty($param_ctypes) && empty($creditcardtype)) {
			$valid_data = false;
		}
		if(!$valid_data) {
			VikError::raiseWarning('', JText::_('VBCCCREDITCARDNUMBERINVALID'));
			$mainframe = JFactory::getApplication();
			$mainframe->redirect($error_redirect_url);
			exit;
		}
		//end post data validation
		
		//Credit Card Information Received
		
		$this->validation = 1;
		$status->setData('skip_email', 1);
		$param_newstatus = $this->getParam('newstatus');

		if (empty($param_newstatus) || $this->getParam('newstatus') == 'CONFIRMED') {
			$status->verified();
			$status->setData('skip_email', 0);
		}
		
		//Send Credit Card Info via eMail to the Administrator
		$admail = VikBooking::getAdminMail();
		$adsendermail = VikBooking::getSenderMail();
		$currencyname = VikBooking::getCurrencyName();
		
		$replacement = '';
		for ($i = 1; $i < (strlen($creditcard) - 4); $i++) {
			$replacement .= '*';
		}
		
		$log = '';
		if(!empty($creditcardtype)) {
			$log .= JText::_('VBCCCREDITCARDTYPE').": ".$creditcardtype."\n";
		}
		$log .= JText::_('VBCCCREDITCARDNUMBER').": ".substr_replace(substr_replace($creditcard, '*', 0, 1), '****', (strlen($creditcard) - 4), 4)."\n";
		$log .= JText::_('VBCCVALIDTHROUGH')." (mm/yy): ".$expire_month."/".$expire_year."\n";
		if($this->getParam('askcvv')) {
			$log .= JText::_('VBCCCVV').": *** (".JText::_('VBSENTVIAMAIL').")"."\n";
		}
		$log .= JText::_('VBCCFIRSTNAME').": ".$business_first_name."\n";
		$log .= JText::_('VBCCLASTNAME').": ".$business_last_name."\n";
		$status->setLog($log);
		
		$mess = "Booking ID: ".$this->get('id')."\n\n";
		$mess .= JText::_('VBCCCREDITCARDNUMBER').": ".substr_replace($creditcard, $replacement, 1, -4).(!empty($creditcardtype) ? " (".$creditcardtype.")" : "")."\n";
		$mess .= JText::_('VBCCVALIDTHROUGH')." (mm/yy): ".$expire_month."/".$expire_year."\n";
		if($this->getParam('askcvv')) {
			$mess .= JText::_('VBCCCVV').": ".$cvv."\n";
		}
		$mess .= JText::_('VBCCFIRSTNAME').": ".$business_first_name."\n";
		$mess .= JText::_('VBCCLASTNAME').": ".$business_last_name."\n\n";
		$mess .= JText::_('VBOFFCCTOTALTOPAY').": ".$currencyname." ".$total."\n\n\n";
		$mess .= VikBooking::externalroute('index.php?option=com_vikbooking&view=booking&sid='.$this->get('sid').'&ts='.$this->get('ts'), false);

		$this->vbo_app->setCommand('print_errors', 1)->sendMail($adsendermail, $adsendermail, $admail, $adsendermail, JText::_('VBOFFCCMAILSUBJECT'), $mess, false);
		
		return $status->isVerified();
	}

	/**
	 * @override
	 * Method used to finalise the payment.
	 * e.g. enter here the code used to redirect the
	 * customers to a specific landing page.
	 *
	 * @param 	boolean  $res 	True if the payment was successful, otherwise false.
	 *
	 * @return 	void
	 */
	protected function complete($res)
	{
		/**
		 * @wponly
		 */

		$app 	= JFactory::getApplication();

		$itemid = $this->getItemID();

		$url  = 'index.php?option=com_vikbooking&view=booking&sid='.$this->get('sid').'&ts='.$this->get('ts').(!empty($itemid) ? '&Itemid='.$itemid : '');
		$esit = $this->validation;
		
		if ($esit < 1)
		{
			$app->enqueueMessage(JText::_('VBCCPAYMENTNOTVERIFIED'), 'error');
		}
		else
		{
			$app->enqueueMessage(JText::_('VBCCINFOSENTOK'));
		}
		
		$app->redirect(JRoute::_($url, false));
		exit;
	}

	/**
	 * @wponly
	 * Returns the proper Item ID to use.
	 *
	 * @return 	integer  The item ID, if any.
	 */
	protected function getItemID()
	{
		$app 	= JFactory::getApplication();
		$input  = $app->input;

		$itemid = $input->getInt('Itemid');

		if (!$itemid)
		{
			$model 	= JModel::getInstance('vikbooking', 'shortcodes', 'admin');
			$itemid = $model->all('post_id', $full = true);
			
			if (count($itemid))
			{
				$itemid = $itemid[0]->post_id;
			}
		}

		return (int) $itemid;
	}
}
