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

if (empty($rows)) {
	?>
	<p class="warn"><?php echo JText::_('VBNOIVAFOUND'); ?></p>
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
			<th class="title left" width="150"><?php echo JText::_( 'VBPVIEWIVAONE' ); ?></th>
			<th class="title left" width="150"><?php echo JText::_( 'VBPVIEWIVATWO' ); ?></th>
			<th class="title center" width="150"><?php echo JText::_( 'VBOTAXBKDWNCOUNT' ); ?></th>
		</tr>
		</thead>
	<?php
	$k = 0;
	$i = 0;
	for ($i = 0, $n = count($rows); $i < $n; $i++) {
		$row = $rows[$i];
		$breakdown_str = '-----';
		if (!empty($row['breakdown'])) {
			$breakdown = json_decode($row['breakdown'], true);
			if (is_array($breakdown) && count($breakdown) > 0) {
				$breakdown_aliq = array();
				foreach ($breakdown as $key => $subtax) {
					$breakdown_aliq[] = $subtax['aliq'].'%';
				}
				$breakdown_str = implode(', ', $breakdown_aliq);
			}
		}
		$breakdown_str .= $row['taxcap'] > 0 ? ' (' . JText::_('VBNEWIVATAXCAP') . ')' : '';
		?>
		<tr class="row<?php echo $k; ?>">
			<td><input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row['id']; ?>" onclick="Joomla.isChecked(this.checked);"></td>
			<td class="vbo-highlighted-td"><a href="index.php?option=com_vikbooking&amp;task=editiva&amp;cid[]=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a></td>
			<td><?php echo $row['aliq']; ?></td>
			<td style="text-align: center;"><?php echo $breakdown_str; ?></td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	?>
	</table>
</div>
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="iva" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHtml::_('form.token'); ?>
	<?php echo $navbut; ?>
</form>
<?php
}