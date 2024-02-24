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

$report_objs = $this->report_objs;
$country_objs = $this->country_objs;
$countries = $this->countries;

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();
$vbo_app->loadContextMenuAssets();

$preport = VikRequest::getString('report', '', 'request');
$pkrsort = VikRequest::getString('krsort', '', 'request');
$pkrorder = VikRequest::getString('krorder', '', 'request');
$pexportreport = VikRequest::getInt('exportreport', 0, 'request');
$pexport_format = VikRequest::getString('export_format', '', 'request');
$execreport = VikRequest::getString('execreport', '', 'request');
$execreport = !empty($execreport);

$report_obj = null;

// JS lang defs
JText::script('VBOPRINT');
JText::script('VBOREPORTSELECT');

if ($pexportreport > 0 && $execreport) {
	// if report requested, call the exportCSV() method before outputting any HMTL code
	foreach ($report_objs as $obj) {
		if ($obj->getFileName() != $preport) {
			continue;
		}
		if (method_exists($obj, 'customExport')) {
			// custom export method implemented
			$obj->customExport($pexportreport);
		} else {
			// default export method
			$obj->setExportCSVFormat($pexport_format)->exportCSV();
		}
		// execute just the requested report
		break;
	}

	foreach ($country_objs as $cobj) {
		foreach ($cobj as $obj) {
			if ($obj->getFileName() != $preport) {
				continue;
			}
			if (method_exists($obj, 'customExport')) {
				// custom export method implemented
				$obj->customExport($pexportreport);
			} else {
				// default export method
				$obj->setExportCSVFormat($pexport_format)->exportCSV();
			}
			// execute just the requested report
			break;
		}
	}
}
?>

<div class="vbo-info-overlay-block">
	<a class="vbo-info-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-info-overlay-content vbo-info-overlay-report"></div>
</div>

<div class="vbo-reports-container">
	<form name="adminForm" action="index.php?option=com_vikbooking&task=pmsreports" method="post" enctype="multipart/form-data" id="adminForm">
		<div class="vbo-reports-filters-outer vbo-btn-toolbar">
			<div class="vbo-reports-filters-main">
				<select id="choose-report" name="report" onchange="document.adminForm.submit();">
					<option value=""></option>
				<?php
				foreach ($report_objs as $obj) {
					$opt_active = false;
					if ($obj->getFileName() == $preport) {
						//get current report object
						$report_obj = $obj;
						//
						$opt_active = true;
					}
					?>
					<option value="<?php echo $obj->getFileName(); ?>"<?php echo $opt_active ? ' selected="selected"' : ''; ?>><?php echo $obj->getName(); ?></option>
					<?php
				}
				foreach ($country_objs as $ccode => $cobj) {
					?>
					<optgroup label="<?php echo $countries[$ccode]; ?>">
					<?php
					foreach ($cobj as $obj) {
						$opt_active = false;
						if ($obj->getFileName() == $preport) {
							//get current report object
							$report_obj = $obj;
							//
							$opt_active = true;
						}
						?>
						<option value="<?php echo $obj->getFileName(); ?>"<?php echo $opt_active ? ' selected="selected"' : ''; ?>><?php echo $obj->getName(); ?></option>
						<?php
					}
					?>
					</optgroup>
					<?php
				}
				?>
				</select>
			</div>
		<?php
		$report_filters = $report_obj !== null ? $report_obj->getFilters() : array();
		if (count($report_filters)) {
			?>
			<div class="vbo-reports-filters-report">
			<?php
			foreach ($report_filters as $filt) {
				?>
				<div class="vbo-report-filter-wrap">
				<?php
				if (!empty($filt['label'])) {
					?>
					<div class="vbo-report-filter-lbl">
						<span><?php echo $filt['label']; ?></span>
					</div>
					<?php
				}
				if (!empty($filt['html'])) {
					?>
					<div class="vbo-report-filter-val">
						<?php echo $filt['html']; ?>
					</div>
					<?php
				}
				?>
				</div>
				<?php
			}
			?>
			</div>
			<?php
		}
		if ($report_obj !== null) {
			?>
			<div class="vbo-reports-filters-launch">
				<input type="submit" class="btn" name="execreport" value="<?php echo JText::_('VBOREPORTLOAD'); ?>" />
			</div>
			<?php
			if ($execreport && property_exists($report_obj, 'exportAllowed') && $report_obj->exportAllowed) {
				// get the pretty version of the report file name
				$export_fname = $report_obj->getExportCSVFileName($cut_suffix = true, $suffix = '.csv', $pretty = true);
				?>
			<div style="display: none;">
				<span class="vbo-report-compactname"><?php echo preg_replace("/[^a-z0-9]+/i", '-', strtolower($report_obj->getName())); ?></span>
				<span class="vbo-report-prettyname"><?php echo $export_fname; ?></span>
			</div>

			<div class="vbo-reports-filters-export">
				<button type="button" class="btn btn-success vbo-context-menu-btn vbo-context-menu-exportcsvtype">
					<span class="vbo-context-menu-lbl">
						<?php VikBookingIcons::e('table'); ?> <?php echo JText::_('VBO_EXPORT_AS'); ?>
					</span><span class="vbo-context-menu-ico">
						<?php VikBookingIcons::e('sort-down'); ?>
					</span>
				</button>
			</div>

			<script type="text/javascript">
				jQuery(function() {
					jQuery('.vbo-context-menu-exportcsvtype').vboContextMenu({
						placement: 'bottom-right',
						buttons: [
							{
								icon: '<?php echo VikBookingIcons::i('file-csv'); ?>',
								text: 'CSV',
								separator: false,
								action: (root, config) => {
									vboDoExport('csv');
								},
							},
							{
								icon: '<?php echo VikBookingIcons::i('file-excel'); ?>',
								text: 'Excel',
								separator: true,
								action: (root, config) => {
									vboDoExport('excel');
								},
							},
							{
								icon: '<?php echo VikBookingIcons::i('print'); ?>',
								text: Joomla.JText._('VBOPRINT'),
								separator: false,
								action: (root, config) => {
									// get the element we want to print (report output)
									let print_element = document.getElementsByClassName('vbo-reports-output')[0];
									print_element.classList.add('vbo-report-output-printing');

									// get the title and compact name of the report
									let report_title = document.getElementsByClassName('vbo-report-prettyname')[0].innerText;
									let report_cname = document.getElementsByClassName('vbo-report-compactname')[0].innerText;

									// clone the whole and current HTML page source
									let clone_source = document.documentElement.cloneNode(true);
									clone_body = clone_source.getElementsByTagName('body')[0];
									// add a class to the body and set the only content to what we want to print
									clone_body.classList.add('vbo-is-printing', 'vbo-report-printing-' + report_cname);
									clone_body.innerHTML = '<h4 class="vbo-report-title-print">' + report_title + '</h4>' + "\n" + print_element.outerHTML;

									// write the proper HTML source onto a new window
									let print_window = window.open('', '_blank');
									print_window.document.write(clone_source.outerHTML);
									print_window.document.close();

									// delay the print command to avoid printing a blank page
									setTimeout(() => {
										print_window.focus();
										print_window.print();
										print_window.close();
									}, 500);
								},
							},
						],
					});
				});
			</script>
			<?php
			} elseif ($execreport && property_exists($report_obj, 'customExport')) {
			?>
			<div class="vbo-reports-filters-export">
				<?php echo $report_obj->customExport; ?>
			</div>
			<?php
			}
		}
		?>
		</div>
		<div id="vbo_hidden_fields"></div>
		<input type="hidden" name="krsort" value="<?php echo $pkrsort; ?>" />
		<input type="hidden" name="krorder" value="<?php echo $pkrorder; ?>" />
		<input type="hidden" name="e4j_debug" value="<?php echo VikRequest::getInt('e4j_debug', 0, 'request'); ?>" />
		<input type="hidden" name="task" value="pmsreports" />
		<input type="hidden" name="option" value="com_vikbooking" />
	</form>
<?php
if ($report_obj !== null && $execreport) {
	// execute the report
	$res = $report_obj->getReportData();
	// get the report Chart (if any)
	$report_chart = $report_obj->getChart();

	/**
	 * Attempt to register in the local storage the choice of this PMS report.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	$link_name = htmlspecialchars($report_obj->getName());
	JFactory::getDocument()->addScriptDeclaration(
<<<JS
;(function($) {
	$(function() {
		try {
			VBOCore.registerAdminMenuAction({
				name: '$link_name',
				href: (window.location.href + '&report=$preport').replace('&tmpl=component', ''),
			}, 'pms');
		} catch(e) {
			console.error(e);
		}
	});
})(jQuery);
JS
	);

	if ($res && !empty($report_chart)) {
		// display the layout type choice
		?>
	<div class="vbo-report-layout-type">
		<div class="vbo-report-layout-type-inner">
			<label for="vbo-report-layout"><?php VikBookingIcons::e('chart-line'); ?></label>
			<select id="vbo-report-layout" onchange="vboSetReportLayout(this.value);">
				<option value="sheetnchart"><?php echo JText::_('VBOSHEETNCHART'); ?></option>
				<option value="sheet"><?php echo JText::_('VBOSHEETONLY'); ?></option>
				<option value="chart"><?php echo JText::_('VBOCHARTONLY'); ?></option>
			</select>
		</div>
	</div>
		<?php
	}
	?>
	<div class="vbo-reports-output <?php echo empty($report_chart) ? 'vbo-report-sheetonly' : 'vbo-report-sheetnchart'; ?> vbo-report-output-<?php echo $report_obj->getFileName(); ?>">
	<?php
	if (!$res) {
		// error generating the report
		?>
		<p class="err"><?php echo $report_obj->getError(); ?></p>
		<?php
	} else {
		// display the report and set default ordering and sorting
		if (empty($pkrsort) && property_exists($report_obj, 'defaultKeySort')) {
			$pkrsort = $report_obj->defaultKeySort;
		}
		if (empty($pkrorder) && property_exists($report_obj, 'defaultKeyOrder')) {
			$pkrorder = $report_obj->defaultKeyOrder;
		}
		if (strlen($report_obj->getWarning())) {
			// warning message should not stop the report from rendering
			?>
		<p class="warn"><?php echo $report_obj->getWarning(); ?></p>
			<?php
		}

		// parse the classic sheet of the report
		?>
		<div class="vbo-report-sheet">
			<div class="table-responsive">
				<table class="table">
					<thead>
						<tr>
						<?php
						foreach ($report_obj->getReportCols() as $col) {
							$col_cont = $col['label'];
							if (isset($col['tip'])) {
								$tip_data = [
									'title' => $col['label'],
									'content' => $col['tip']
								];
								if (!empty($col['tip_pos'])) {
									$tip_data['placement'] = $col['tip_pos'];
								}
								$col_cont = $vbo_app->createPopover($tip_data) . ' ' . $col_cont;
							}
							?>
							<th<?php echo isset($col['attr']) && count($col['attr']) ? ' '.implode(' ', $col['attr']) : ''; ?>>
							<?php
							if (isset($col['sortable'])) {
								$krorder = $pkrsort == $col['key'] && $pkrorder == 'DESC' ? 'ASC' : 'DESC';
								?>
								<a href="JavaScript: void(0);" onclick="vboSetFilters({krsort: '<?php echo $col['key']; ?>', krorder: '<?php echo $krorder; ?>'}, true);" class="<?php echo $pkrsort == $col['key'] ? 'vbo-list-activesort' : ''; ?>">
									<span><?php echo $col_cont; ?></span>
									<i class="fa <?php echo $pkrsort == $col['key'] && $krorder == 'DESC' ? 'fa-sort-asc' : ($pkrsort == $col['key'] ? 'fa-sort-desc' : 'fa-sort'); ?>"></i>
								</a>
								<?php
							} else {
								?>
								<span><?php echo $col_cont; ?></span>
								<?php
							}
							?>
							</th>
							<?php
						}
						?>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ($report_obj->getReportRows() as $row) {
						?>
						<tr>
						<?php
						foreach ($row as $cell) {
							?>
							<td<?php echo isset($cell['attr']) && count($cell['attr']) ? ' '.implode(' ', $cell['attr']) : ''; ?>>
								<span<?php echo !empty($cell['title']) ? ' title="' . htmlspecialchars($cell['title']) . '"' : ''; ?>><?php echo isset($cell['callback']) && is_callable($cell['callback']) ? $cell['callback']($cell['value']) : $cell['value']; ?></span>
							</td>
							<?php
						}
						?>
						</tr>
						<?php
					}
					?>
					</tbody>
				<?php
				if (count($report_obj->getReportFooterRow())) {
					?>
					<tfoot>
					<?php
					foreach ($report_obj->getReportFooterRow() as $row) {
						?>
						<tr>
						<?php
						foreach ($row as $cell) {
							?>
							<td<?php echo isset($cell['attr']) && count($cell['attr']) ? ' '.implode(' ', $cell['attr']) : ''; ?>>
								<span><?php echo isset($cell['callback']) && is_callable($cell['callback']) ? $cell['callback']($cell['value']) : $cell['value']; ?></span>
							</td>
							<?php
						}
						?>
						</tr>
						<?php
					}
					?>
					</tfoot>
					<?php
				}
				?>
				</table>
			</div>
		</div>
		<?php

		// parse the Chart, if defined
		if (!empty($report_chart)) {
			$report_chart_title = $report_obj->getChartTitle();
			?>
		<div class="vbo-report-chart-wrap">
			<div class="vbo-report-chart-inner">
				<div class="vbo-report-chart-main">
				<?php
				$top_chart_metas = $report_obj->getChartMetaData('top');
				if (is_array($top_chart_metas) && count($top_chart_metas)) {
					?>
					<div class="vbo-report-chart-metas vbo-report-chart-metas-top">
					<?php
					foreach ($top_chart_metas as $chart_meta) {
						?>
						<div class="vbo-report-chart-meta<?php echo isset($chart_meta['class']) ? ' ' . $chart_meta['class'] : ''; ?>">
							<div class="vbo-report-chart-meta-inner">
								<div class="vbo-report-chart-meta-lbl"><?php echo isset($chart_meta['label']) ? $chart_meta['label'] : ''; ?></div>
								<div class="vbo-report-chart-meta-val">
									<span class="vbo-report-chart-meta-val-main"><?php echo isset($chart_meta['value']) ? $chart_meta['value'] : ''; ?></span>
								<?php
								if (isset($chart_meta['descr'])) {
									?>
									<span class="vbo-report-chart-meta-val-descr"><?php echo $chart_meta['descr']; ?></span>
									<?php
								}
								?>
									<?php echo isset($chart_meta['extra']) ? $chart_meta['extra'] : ''; ?>
								</div>
							</div>
						</div>
						<?php
					}
					?>
					</div>
					<?php
				}
				?>
					<div class="vbo-report-chart-content">
					<?php
					if (!empty($report_chart_title)) {
						?>
						<h4><?php echo $report_chart_title; ?></h4>
						<?php
					}
					?>
						<?php echo $report_chart; ?>
					</div>
				<?php
				$bottom_chart_metas = $report_obj->getChartMetaData('bottom');
				if (is_array($bottom_chart_metas) && count($bottom_chart_metas)) {
					?>
					<div class="vbo-report-chart-metas vbo-report-chart-metas-bottom">
					<?php
					foreach ($bottom_chart_metas as $chart_meta) {
						?>
						<div class="vbo-report-chart-meta<?php echo isset($chart_meta['class']) ? ' ' . $chart_meta['class'] : ''; ?>">
							<div class="vbo-report-chart-meta-inner">
								<div class="vbo-report-chart-meta-lbl"><?php echo isset($chart_meta['label']) ? $chart_meta['label'] : ''; ?></div>
								<div class="vbo-report-chart-meta-val">
									<span class="vbo-report-chart-meta-val-main"><?php echo isset($chart_meta['value']) ? $chart_meta['value'] : ''; ?></span>
								<?php
								if (isset($chart_meta['descr'])) {
									?>
									<span class="vbo-report-chart-meta-val-descr"><?php echo $chart_meta['descr']; ?></span>
									<?php
								}
								?>
									<?php echo isset($chart_meta['extra']) ? $chart_meta['extra'] : ''; ?>
								</div>
							</div>
						</div>
						<?php
					}
					?>
					</div>
					<?php
				}
				?>
				</div>
			<?php
			$right_chart_metas = $report_obj->getChartMetaData('right');
			if (is_array($right_chart_metas) && count($right_chart_metas)) {
				?>
				<div class="vbo-report-chart-right">
					<div class="vbo-report-chart-metas vbo-report-chart-metas-right">
					<?php
					foreach ($right_chart_metas as $chart_meta) {
						?>
						<div class="vbo-report-chart-meta<?php echo isset($chart_meta['class']) ? ' ' . $chart_meta['class'] : ''; ?>">
							<div class="vbo-report-chart-meta-inner">
								<div class="vbo-report-chart-meta-lbl"><?php echo isset($chart_meta['label']) ? $chart_meta['label'] : ''; ?></div>
								<div class="vbo-report-chart-meta-val">
									<span class="vbo-report-chart-meta-val-main"><?php echo isset($chart_meta['value']) ? $chart_meta['value'] : ''; ?></span>
								<?php
								if (isset($chart_meta['descr'])) {
									?>
									<span class="vbo-report-chart-meta-val-descr"><?php echo $chart_meta['descr']; ?></span>
									<?php
								}
								?>
									<?php echo isset($chart_meta['extra']) ? $chart_meta['extra'] : ''; ?>
								</div>
							</div>
						</div>
						<?php
					}
					?>
					</div>
				</div>
				<?php
			}
			?>
			</div>
		</div>
			<?php
		}
	}
	?>
	</div>
	<?php
}
?>
</div>

<script type="text/javascript">
function vboSetFilters(obj, dosubmit) {
	if (typeof obj != "object") {
		console.log("arg is not an object");
		return;
	}
	for (var p in obj) {
		if (!obj.hasOwnProperty(p)) {
			continue;
		}
		var elem = document.adminForm[p];
		if (elem) {
			document.adminForm[p].value = obj[p];
		} else {
			document.getElementById("vbo_hidden_fields").innerHTML += "<input type='hidden' name='"+p+"' value='"+obj[p]+"' />";
		}
	}
	if (!obj.hasOwnProperty('execreport')) {
		document.getElementById("vbo_hidden_fields").innerHTML += "<input type='hidden' name='execreport' value='1' />";
	}
	if (dosubmit) {
		document.adminForm.submit();
	}
}

function vboDoExport(format) {
	// set input vars and submit
	document.adminForm.target = '_blank';
	document.adminForm.action += '&tmpl=component';
	vboSetFilters({
		exportreport: '1',
		export_format: format
	}, true);

	// restore input vars
	setTimeout(function() {
		document.adminForm.target = '';
		document.adminForm.action = document.adminForm.action.replace('&tmpl=component', '');
		vboSetFilters({
			exportreport: '0',
			export_format: ''
		}, false);
	}, 1000);
}

var vbo_overlay_on = false;

function vboShowOverlay() {
	jQuery(".vbo-info-overlay-block").fadeIn(400, function() {
		if (jQuery(".vbo-info-overlay-block").is(":visible")) {
			vbo_overlay_on = true;
		} else {
			vbo_overlay_on = false;
		}
	});
}

function vboHideOverlay() {
	jQuery(".vbo-info-overlay-block").fadeOut();
	vbo_overlay_on = false;
}

function vboSetReportLayout(layout) {
	if (layout == 'sheet') {
		jQuery('.vbo-reports-output').removeClass('vbo-report-sheetnchart').removeClass('vbo-report-chartonly').addClass('vbo-report-sheetonly');
		jQuery('.vbo-report-chart-wrap').hide();
		jQuery('.vbo-report-sheet').show();
	} else if (layout == 'chart') {
		jQuery('.vbo-reports-output').removeClass('vbo-report-sheetnchart').removeClass('vbo-report-sheetonly').addClass('vbo-report-chartonly');
		jQuery('.vbo-report-sheet').hide();
		jQuery('.vbo-report-chart-wrap').show();
	} else if (layout == 'sheetnchart') {
		jQuery('.vbo-reports-output').removeClass('vbo-report-sheetonly').removeClass('vbo-report-chartonly').addClass('vbo-report-sheetnchart');
		jQuery('.vbo-report-sheet').show();
		jQuery('.vbo-report-chart-wrap').show();
	}
}

jQuery(function() {

	jQuery("#choose-report").select2({placeholder: Joomla.JText._('VBOREPORTSELECT'), width: "200px"});

	jQuery(document).mouseup(function(e) {
		if (!vbo_overlay_on) {
			return false;
		}
		var vbo_overlay_cont = jQuery(".vbo-info-overlay-content");
		if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
			// get the target element class for validation
			var target_el  = jQuery(e.target);
			if (target_el.is('option')) {
				// never close when clicking a select-option
				return false;
			}
			var target_cls = target_el.attr('class');
			if (!target_cls || !target_cls.length) {
				// nothing to check, just close the modal
				vboHideOverlay();
				return true;
			}
			// exclude the click on smart selects and datepicker fields
			if (target_cls.indexOf('select2') < 0 && target_cls.indexOf('ui-') < 0) {
				// safe to hide the modal as we probably clicked outside it
				vboHideOverlay();
			}
		}
	});

	jQuery(document).keyup(function(e) {
		if (e.keyCode == 27 && vbo_overlay_on) {
			vboHideOverlay();
		}
	});

});
<?php echo $report_obj !== null && strlen($report_obj->getScript()) ? $report_obj->getScript() : ''; ?>
</script>
