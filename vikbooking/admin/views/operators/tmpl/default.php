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

$rows = $this->rows;
$lim0 = $this->lim0;
$navbut = $this->navbut;
$orderby = $this->orderby;
$ordersort = $this->ordersort;

$pfiltercustomer = VikRequest::getString('filtercustomer', '', 'request');
?>
<div class="vbo-list-form-filters vbo-btn-toolbar">
	<form action="index.php?option=com_vikbooking&amp;task=operators" method="post" name="operatorsform">
		<div style="width: 100%; display: inline-block;" class="btn-toolbar" id="filter-bar">
			<div class="btn-group pull-left input-append">
				<input type="text" name="filtercustomer" id="filtercustomer" value="<?php echo $pfiltercustomer; ?>" size="40" placeholder="<?php echo JText::_( 'VBCUSTOMERFIRSTNAME' ).', '.JText::_( 'VBCUSTOMERLASTNAME' ).', '.JText::_( 'VBCUSTOMEREMAIL' ).', '.JText::_( 'VBOCODEOPERATOR' ); ?>"/>
				<button type="button" class="btn btn-secondary" onclick="document.operatorsform.submit();"><i class="icon-search"></i></button>
			</div>
			<div class="btn-group pull-left">
				<button type="button" class="btn btn-secondary" onclick="document.getElementById('filtercustomer').value='';document.operatorsform.submit();"><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
			</div>
			
		</div>
		<input type="hidden" name="task" value="operators" />
		<input type="hidden" name="option" value="com_vikbooking" />
	</form>
</div>
<?php
if (empty($rows)) {
	?>
	<p class="warn"><?php echo JText::_('VBNOOPERATORS'); ?></p>
	<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="option" value="com_vikbooking" />
	</form>
	<?php
} else {
?>
<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm" class="vbo-list-form">
<div class="table-responsive">
	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="table table-striped vbo-list-table">
		<thead>
		<tr>
			<th width="20">
				<input type="checkbox" onclick="Joomla.checkAll(this)" value="" name="checkall-toggle">
			</th>
			<th class="title left" width="75">
				<a href="index.php?option=com_vikbooking&amp;task=operators&amp;vborderby=id&amp;vbordersort=<?php echo ($orderby == "id" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "id" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "id" ? "vbo-list-activesort" : "")); ?>">
					ID<?php echo ($orderby == "id" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "id" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title left" width="75">
				<a href="index.php?option=com_vikbooking&amp;task=operators&amp;vborderby=first_name&amp;vbordersort=<?php echo ($orderby == "first_name" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "first_name" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "first_name" ? "vbo-list-activesort" : "")); ?>">
					<?php echo JText::_('VBCUSTOMERFIRSTNAME').($orderby == "first_name" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "first_name" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title left" width="75">
				<a href="index.php?option=com_vikbooking&amp;task=operators&amp;vborderby=last_name&amp;vbordersort=<?php echo ($orderby == "last_name" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "last_name" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "last_name" ? "vbo-list-activesort" : "")); ?>">
					<?php echo JText::_('VBCUSTOMERLASTNAME').($orderby == "last_name" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "last_name" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title left" width="75">
				<a href="index.php?option=com_vikbooking&amp;task=operators&amp;vborderby=email&amp;vbordersort=<?php echo ($orderby == "email" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "email" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "email" ? "vbo-list-activesort" : "")); ?>">
					<?php echo JText::_('VBCUSTOMEREMAIL').($orderby == "email" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "email" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title left" width="75">
				<a href="index.php?option=com_vikbooking&amp;task=operators&amp;vborderby=phone&amp;vbordersort=<?php echo ($orderby == "phone" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "phone" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "phone" ? "vbo-list-activesort" : "")); ?>">
					<?php echo JText::_('VBCUSTOMERPHONE').($orderby == "phone" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "phone" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
			<th class="title center" width="75">
				<a href="index.php?option=com_vikbooking&amp;task=operators&amp;vborderby=code&amp;vbordersort=<?php echo ($orderby == "code" && $ordersort == "ASC" ? "DESC" : "ASC"); ?>" class="<?php echo ($orderby == "code" && $ordersort == "ASC" ? "vbo-list-activesort" : ($orderby == "code" ? "vbo-list-activesort" : "")); ?>">
					<?php echo JText::_('VBOCODEOPERATOR').($orderby == "code" && $ordersort == "ASC" ? '<i class="'.VikBookingIcons::i('sort-asc').'"></i>' : ($orderby == "code" ? '<i class="'.VikBookingIcons::i('sort-desc').'"></i>' : '<i class="'.VikBookingIcons::i('sort').'"></i>')); ?>
				</a>
			</th>
		</tr>
		</thead>
	<?php
	$kk = 0;
	$i = 0;
	for ($i = 0, $n = count($rows); $i < $n; $i++) {
		$row = $rows[$i];
		?>
		<tr class="row<?php echo $kk; ?>">
			<td><input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row['id']; ?>" onclick="Joomla.isChecked(this.checked);"></td>
			<td><a href="index.php?option=com_vikbooking&amp;task=editoperator&amp;cid[]=<?php echo $row['id']; ?>"><?php echo $row['id']; ?></a></td>
			<td class="vbo-highlighted-td"><a href="index.php?option=com_vikbooking&amp;task=editoperator&amp;cid[]=<?php echo $row['id']; ?>"><?php echo $row['first_name']; ?></a></td>
			<td><?php echo $row['last_name']; ?></td>
			<td><?php echo $row['email']; ?></td>
			<td><?php echo $row['phone']; ?></td>
			<td class="center"><?php echo !empty($row['code']) ? $row['code'] : '-----'; ?></td>
		 </tr>
		  <?php
		$kk = 1 - $kk;
	}
	?>
	</table>
</div>
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="operators" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHtml::_('form.token'); ?>
	<?php echo $navbut; ?>
</form>
<?php
}
