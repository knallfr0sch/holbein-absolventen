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

$operator = $this->operator;

$vbo_app = VikBooking::getVboApplication();
?>
<script type="text/Javascript">
function vboGetRandomCode(len) {
	var codechars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	var code = '';
	for (var i = 0; i < len; i++) {
		code += codechars.charAt(Math.floor(Math.random() * codechars.length));
	}
	return code;
}
function vboGenerateCode() {
	var code = vboGetRandomCode(10);
	document.getElementById('inpcode').value = code;
}
</script>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<div class="vbo-admin-container">
		<div class="vbo-config-maintab-left">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::_('VBOADMINLEGENDDETAILS'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::_('VBCUSTOMERFIRSTNAME'); ?> <sup>*</sup></div>
							<div class="vbo-param-setting"><input type="text" name="first_name" value="<?php echo count($operator) ? $operator['first_name'] : ''; ?>" size="30"/></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::_('VBCUSTOMERLASTNAME'); ?> <sup>*</sup></div>
							<div class="vbo-param-setting"><input type="text" name="last_name" value="<?php echo count($operator) ? $operator['last_name'] : ''; ?>" size="30"/></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::_('VBCUSTOMEREMAIL'); ?> <sup>*</sup></div>
							<div class="vbo-param-setting"><input type="text" name="email" value="<?php echo count($operator) ? $operator['email'] : ''; ?>" size="30"/></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::_('VBCUSTOMERPHONE'); ?></div>
							<div class="vbo-param-setting"><input type="text" name="phone" value="<?php echo count($operator) ? $operator['phone'] : ''; ?>" size="30"/></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::_('VBOCODEOPERATOR'); ?> <?php echo $vbo_app->createPopover(array('title' => JText::_('VBOCODEOPERATOR'), 'content' => JText::_('VBOCODEOPERATORSHELP'))); ?></div>
							<div class="vbo-param-setting"><input type="text" name="code" id="inpcode" value="<?php echo count($operator) ? $operator['code'] : ''; ?>" size="16" placeholder="ABCDE12345" /> &nbsp;&nbsp; <button type="button" class="btn" onclick="vboGenerateCode();" style="vertical-align: top;"><?php echo JText::_('VBOCODEOPERATORGEN'); ?></button></div>
						</div>
						<!-- @wponly the user label is called statically 'Website User' -->
						<div class="vbo-param-container">
							<div class="vbo-param-label">Website User</div>
							<div class="vbo-param-setting"><?php echo JHtml::_('list.users', 'ujid', (count($operator) ? $operator['ujid'] : ''), 1); ?></div>
						</div>
					</div>
				</div>
			</fieldset>
		</div>
	<?php
	$operator_perms = array();
	if (count($operator) && !empty($operator['perms'])) {
		$operator_perms = json_decode($operator['perms'], true);
		$operator_perms = is_array($operator_perms) ? $operator_perms : array();
	}
	?>
		<div class="vbo-config-maintab-right">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::_('VBOADMINLEGENDDETAILS'); ?></legend>
					<div class="vbo-params-container">
					<?php
					foreach ($operator_perms as $perm) {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::_('VBOOPERPERM'.strtoupper($perm['type'])); ?></div>
							<div class="vbo-param-setting">&nbsp;</div>
						</div>
						<?php
					}
					?>
					</div>
				</div>
			</fieldset>
		</div>
	</div>
	<?php
if (count($operator)) {
	?>
	<input type="hidden" name="where" value="<?php echo $operator['id']; ?>">
	<?php
}
?>
	<input type="hidden" name="task" value="<?php echo count($operator) ? 'updateoperator' : 'saveoperator'; ?>">
	<input type="hidden" name="option" value="com_vikbooking">
	<?php echo JHtml::_('form.token'); ?>
</form>
