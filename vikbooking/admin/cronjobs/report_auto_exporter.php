<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2023 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Cron Job driver that automatically executes and exports a specific PMS Report.
 * 
 * @since 	1.16.1 (J) - 1.6.1 (WP)
 */
class VikBookingCronJobReportAutoExporter extends VBOCronJob
{
	// do not need to track the elements
	use VBOCronTrackerUnused;

	/**
	 * This method should return all the form fields required to collect the information
	 * needed for the execution of the cron job.
	 * 
	 * @return  array  An associative array of form fields.
	 */
	public function getForm()
	{
		// the list of eligible reports
		$eligible_reports = [];

		// associative list of report filters
		$report_filters = [];

		// load all the available PMS Reports
		list($report_objs, $country_objs) = VBOReportLoader::getInstance()->getDrivers();

		// get countries involved
		$countries = VBOReportLoader::getInstance()->getInvolvedCountries();

		// global reports
		foreach ($report_objs as $obj) {
			if (!method_exists($obj, 'customExport') && (!property_exists($obj, 'exportAllowed') || !$obj->exportAllowed)) {
				// this report has not implemented any export functionality
				continue;
			}

			// report file name
			$report_fname = $obj->getFileName();

			// push eligible report
			$eligible_reports[$report_fname] = $obj->getName();

			// get the filters to build the example JSON payload
			foreach ($obj->getFilters() as $filter) {
				if (!is_array($filter) || empty($filter['name'])) {
					continue;
				}

				// push report filter
				if (!isset($report_filters[$report_fname])) {
					$report_filters[$report_fname] = [];
				}

				// set default/suggested value
				$report_filters[$report_fname][$filter['name']] = $this->getFilterDefaultValue($filter);
			}
		}

		// country reports
		foreach ($country_objs as $ccode => $cobj) {
			// parse reports of this country code
			foreach ($cobj as $obj) {
				if (!method_exists($obj, 'customExport') && (!property_exists($obj, 'exportAllowed') || !$obj->exportAllowed)) {
					// this report has not implemented any export functionality
					continue;
				}

				// report file name
				$report_fname = $obj->getFileName();

				// push eligible report
				$eligible_reports[$report_fname] = $countries[$ccode] . ' - ' . $obj->getName();

				// get the filters to build the example JSON payload
				foreach ($obj->getFilters() as $filter) {
					if (!is_array($filter) || empty($filter['name'])) {
						continue;
					}

					// push report filter
					if (!isset($report_filters[$report_fname])) {
						$report_filters[$report_fname] = [];
					}

					// set default/suggested value
					$report_filters[$report_fname][$filter['name']] = $this->getFilterDefaultValue($filter);
				}
			}
		}

		// default payload
		$def_payload = new stdClass;
		$def_payload->fromdate = '{Y-m-d}';
		$def_payload->todate = '{Y-m-d +1 month}';
		$def_payload_pretty = json_encode($def_payload, JSON_PRETTY_PRINT);

		// build the helper script
		$payload_examples = json_encode($report_filters);
		$payload_helper_js = <<<JS
var vbo_cron_report_auto_exporter_def_payloads = $payload_examples;

function vboCronReportAutoExportSetExamplePayload(report) {
	if (!vbo_cron_report_auto_exporter_def_payloads.hasOwnProperty(report)) {
		return;
	}
	document.getElementsByClassName('vbo-report-auto-exporter-example')[0].innerText = JSON.stringify(vbo_cron_report_auto_exporter_def_payloads[report], null, 4);
}

jQuery(function() {
	jQuery('select[name*="report"]').first().trigger('change');
});
JS;

		// inline style for CodeMirror
		JFactory::getDocument()->addStyleDeclaration('.CodeMirror { height: auto !important; }');

		// return the whole Cron Job parameters
		return [
			'cron_lbl' => [
				'type'  => 'custom',
				'label' => '',
				'html'  => '<h4><i class="' . VikBookingIcons::i('cash-register') . '"></i>&nbsp;<i class="' . VikBookingIcons::i('download') . '"></i>&nbsp;' . $this->getTitle() . '</h4>',
			],
			'report' => [
				'type'    => 'select',
				'label'   => trim(preg_replace("/[^A-Z0-9 ]/i", '', JText::_('VBOREPORTSELECT'))),
				'options' => $eligible_reports,
				'attributes' => [
					'onchange' => 'vboCronReportAutoExportSetExamplePayload(this.value);',
				],
			],
			'payload_example' => [
				'type'  => 'custom',
				'label' => 'Payload Example',
				'html'  => '<div><pre><code class="json vbo-report-auto-exporter-example">' . $def_payload_pretty . '</code></pre></div>',
			],
			'payload' => [
				'type'    => 'codemirror',
				'label'   => 'JSON Payload',
				'default' => $def_payload_pretty,
				'options' => [
					'width'   => '100%',
					'height'  => 200,
					'col' 	  => 70,
					'row' 	  => 10,
					'buttons' => false,
					'id' 	  => null,
					'params'  => [
						'syntax' => 'json',
					],
				],
			],
			'payload_helper' => [
				'type'  => 'custom',
				'label' => '',
				'html'  => "<script>$payload_helper_js</script>",
			],
			'help' => [
				'type'  => 'custom',
				'label' => '',
				'html'  => '<p class="vbo-cronparam-suggestion"><i class="vboicn-lifebuoy"></i> ' . JText::_('VBO_AUTO_EXPORT_JSON_HELP') . '</p>',
			],
			'format' => [
				'type'    => 'select',
				'label'   => JText::_('VBO_EXPORT_AS'),
				'default' => 'auto',
				'options' => [
					'csv' 	=> 'CSV',
					'excel' => 'Excel',
					'auto' 	=> JText::_('VBCONFIGSEARCHPSMARTSEARCHAUTO'),
				],
			],
			'recipient_email' => [
				'type'    => 'text',
				'label'   => JText::_('VBCUSTOMEREMAIL'),
				'help'    => JText::_('VBO_CONDTEXT_RULE_ATTFILES_DESCR'),
				'default' => VikBooking::getAdminMail(),
			],
			'save_path' => [
				'type'    => 'text',
				'label'   => JText::_('VBSAVE'),
				'help'    => JText::_('VBOINIPATH') . ' (Base: ' . (VBOPlatformDetection::isWordPress() ? ABSPATH : JPATH_SITE) . ')',
				'default' => rtrim(VBOFactory::getConfig()->get('backupfolder', ''), DIRECTORY_SEPARATOR),
			],
			'rm_file' => [
				'type'    => 'select',
				'label'   => JText::_('VBO_REMOVE_LOCAL_FILE'),
				'help'    => JText::_('VBO_AUTO_EXPORT_RMFILE_HELP'),
				'default' => 0,
				'options' => [
					1 => JText::_('VBYES'),
					0 => JText::_('VBNO'),
				],
			],
		];
	}

	/**
	 * Returns the title of the cron job.
	 * 
	 * @return  string
	 */
	public function getTitle()
	{
		return JText::_('VBMENUPMSREPORTS') . ' - ' . JText::_('VBO_AUTO_EXPORT');
	}

	/**
	 * Executes the cron job.
	 * 
	 * @return  boolean  True on success, false otherwise.
	 */
	protected function execute()
	{
		// build the request payload
		$json_payload 	= $this->params->get('payload', '{}');
		$report_payload = $this->buildReportPayload($json_payload);

		if (!is_array($report_payload) || !$report_payload) {
			$this->output('<p>Could not build JSON Report Payload: ' . $json_payload . '</p>');
			return false;
		}

		// execution output
		$this->output('<p>JSON Report Payload built:</p>');
		$this->output('<pre>' . json_encode($report_payload, JSON_PRETTY_PRINT) . '</pre>');

		// invoke report loader to load the dependencies
		$report_loader = VBOReportLoader::getInstance();

		// inject report vars before invoking the report's constructor
		VikBookingReport::setRequestVars($report_payload);

		// get an instance of the report
		$report_obj = $report_loader->getDriver($this->params->get('report', ''));
		$csvlines 	= [];

		if (!$report_obj) {
			$this->output('<p>Could not instantiate the Report object.</p>');
			return false;
		}

		if (!method_exists($report_obj, 'customExport')) {
			// set report format
			$report_obj->setExportCSVFormat($this->params->get('format', ''));

			// let the report build the lines to export
			$csvlines = $report_obj->getExportCSVLines();
			if (!$csvlines) {
				// no data to export
				$report_error = $report_obj->getError();
				$this->output('<p>No data to export.' . (!empty($report_error) ? " {$report_error}" : '') . '</p>');
				$this->appendLog('No data to export.' . (!empty($report_error) ? " {$report_error}" : '') . '');
				return false;
			}
		}

		// the name of the exported file
		$export_fname = $report_obj->getExportCSVFileName();
		$export_fname_pretty = $report_obj->getExportCSVFileName($cut_suffix = true, $suffix = '', $pretty = true);

		// build the resource file pointer/handler
		$export_fpath = $this->params->get('save_path', '');
		if (!$export_fpath) {
			// default to the backups directory
			$export_fpath = VBOFactory::getConfig()->get('backupfolder', '');
		}
		$export_fpath = JPath::clean($export_fpath . DIRECTORY_SEPARATOR . $export_fname);
		$export_fp = fopen($export_fpath, 'w');
		if (!$export_fp) {
			// could not proceed
			$this->output('<p>Could not create the resource file pointer containing the exported report: ' . $export_fpath . '</p>');
			$this->appendLog('Could not create the resource file pointer containing the exported report: ' . $export_fpath);
			return false;
		}

		// set the resource file pointer that the report will be using
		$report_obj->setExportCSVHandler($export_fp);

		// write the exported data on file
		$data_exported = true;
		if (!method_exists($report_obj, 'customExport')) {
			// regular CSV export method
			if (!$report_obj->outputCSV($csvlines)) {
				$data_exported = false;
			}
		} else {
			// custom export method declared within the report
			if (!$report_obj->customExport()) {
				$data_exported = false;
			}
		}

		if (!$data_exported) {
			$this->output('<p>Could not write report data onto: ' . $export_fpath . '</p>');
			$this->appendLog('Could not write report data onto: ' . $export_fpath);
			return false;
		}

		// export completed
		$export_fsize = @filesize($export_fpath);
		$export_fsize = JHtml::_('number.bytes', (int)$export_fsize, 'auto', 0);
		$this->output('<p>Report successfully exported onto: ' . $export_fpath . ' (' . $export_fsize . ')</p>');
		$this->appendLog('Report successfully exported onto: ' . $export_fpath . ' (' . $export_fsize . ')');

		// check if the exported file should be sent via email
		$email_addr = $this->params->get('recipient_email', '');
		if ($email_addr) {
			$sender = VikBooking::getSenderMail();
			$subject = VikBooking::getFrontTitle() . ' - ' . $report_obj->getName();
			$content = $this->getTitle() . ":\n" . $export_fname_pretty;
			$vbo_app = VikBooking::getVboApplication();
			if ($vbo_app->sendMail($sender, $sender, $email_addr, $sender, $subject, $content, $is_html = false, 'base64', $export_fpath)) {
				// success
				$this->output('<p>Exported file successfully sent via email to: ' . $email_addr . '</p>');
				$this->appendLog('Exported file successfully sent via email to: ' . $email_addr);
			} else {
				// log the error
				$this->output('<p>Could not send the exported file via email to: ' . $email_addr . '</p>');
				$this->appendLog('Could not send the exported file via email to: ' . $email_addr);
			}
		}

		return true;
	}

	/**
	 * Helper method to suggest the default value in the JSON Payload for a specific report filter.
	 * 
	 * @param 	array 	$filter 	the whole filter associative array set by the PMS report.
	 * 
	 * @return 	string|array|int 	the suggested default value to be JSON encoded.
	 */
	protected function getFilterDefaultValue(array $filter)
	{
		$def_value = isset($filter['type']) && $filter['type'] == 'select' && isset($filter['multiple']) && $filter['multiple'] ? [] : '';

		if (is_string($def_value)) {
			if (preg_match("/^(id)/i", $filter['name']) || preg_match("/(id)$/i", $filter['name'])) {
				$def_value = 0;
			}
		}

		if (preg_match("/(checkout|todate|dateto)/i", $filter['name'])) {
			$def_value = '{Y-m-d +1 day}';
		} elseif (preg_match("/(date|checkin)/i", $filter['name'])) {
			$def_value = '{Y-m-d}';
		}

		return $def_value;
	}

	/**
	 * Given the Cron Job payload, builds an associative array of data to inject to the report.
	 * 
	 * @param 	string 	$json_payload 	the raw Cron Job parameter provided.
	 * 
	 * @return 	array
	 */
	protected function buildReportPayload($json_payload)
	{
		$payload = (array)json_decode($json_payload, true);

		if (!$payload) {
			return [];
		}

		foreach ($payload as $prop => &$val) {
			if (!is_string($val) || !preg_match("/^\{.+\}$/i", $val)) {
				// we parse only special strings within curly brackets
				continue;
			}
			if (isset($matches)) {
				// reset any previous regex
				unset($matches);
			}
			// look for current date or datetime (Y-m-d or similar format)
			if (preg_match("/^\{(y|m|d|\-|\_|\/| |h|i|s|\:|\.)+\}$/i", $val)) {
				// set requested value
				$val = date(str_replace(['{', '}'], '', trim($val)));
				continue;
			} elseif (preg_match("/^\{(y|m|d|\-|\_|\/| |h|i|s|\:|\.)+([\+\-]\d days?|weeks?|months?|years?)\}$/i", $val, $matches) && isset($matches[2])) {
				// matched a dynamic date
				$dt_format = trim(str_replace(['{', '}', $matches[2]], '', $val));
				$val = date($dt_format, strtotime($matches[2], strtotime(date($dt_format))));
			}
		}

		// unset last reference
		unset($val);

		// return the payload just built
		return $payload;
	}
}
