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
 * VikBooking pricing controller.
 *
 * @since 1.5.11
 */
class VikBookingControllerPricing extends JControllerAdmin
{
	public function setnewrates()
	{
		$dbo = JFactory::getDBO();
		$session = JFactory::getSession();
		$updforvcm = $session->get('vbVcmRatesUpd', '');
		$updforvcm = empty($updforvcm) || !is_array($updforvcm) ? array() : $updforvcm;
		$currencysymb = VikBooking::getCurrencySymb();
		$vbo_df = VikBooking::getDateFormat();
		$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y/m/d');
		$pcheckinh = 0;
		$pcheckinm = 0;
		$pcheckouth = 0;
		$pcheckoutm = 0;
		$timeopst = VikBooking::getTimeOpenStore();
		if (is_array($timeopst)) {
			$opent = VikBooking::getHoursMinutes($timeopst[0]);
			$closet = VikBooking::getHoursMinutes($timeopst[1]);
			$pcheckinh = $opent[0];
			$pcheckinm = $opent[1];
			$pcheckouth = $closet[0];
			$pcheckoutm = $closet[1];
		}
		$pid_room = VikRequest::getInt('id_room', '', 'request');
		$pid_price = VikRequest::getInt('id_price', '', 'request');
		$prate = VikRequest::getFloat('rate', 0, 'request');
		$pvcm = VikRequest::getInt('vcm', '', 'request');
		$pminlos = VikRequest::getInt('minlos', 0, 'request');
		$prateclosed = VikRequest::getInt('rateclosed', 0, 'request');
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		if (empty($pid_room) || empty($pid_price) || empty($prate) || !($prate > 0) || empty($pfromdate) || empty($ptodate)) {
			echo 'e4j.error.'.addslashes(JText::_('VBRATESOVWERRNEWRATE'));
			exit;
		}

		//read the rates for the lowest number of nights
		//the old query below used to cause an error #1055 when sql_mode=only_full_group_by
		//$q = "SELECT `r`.*,`p`.`name` FROM `#__vikbooking_dispcost` AS `r` INNER JOIN (SELECT MIN(`days`) AS `min_days` FROM `#__vikbooking_dispcost` WHERE `idroom`=".(int)$pid_room." AND `idprice`=".(int)$pid_price." GROUP BY `idroom`) AS `r2` ON `r`.`days`=`r2`.`min_days` LEFT JOIN `#__vikbooking_prices` `p` ON `p`.`id`=`r`.`idprice` AND `p`.`id`=".(int)$pid_price." WHERE `r`.`idroom`=".(int)$pid_room." AND `r`.`idprice`=".(int)$pid_price." GROUP BY `r`.`idprice` ORDER BY `r`.`days` ASC, `r`.`cost` ASC;";
		$q = "SELECT `r`.`id`,`r`.`idroom`,`r`.`days`,`r`.`idprice`,`r`.`cost`,`p`.`name` FROM `#__vikbooking_dispcost` AS `r` INNER JOIN (SELECT MIN(`days`) AS `min_days` FROM `#__vikbooking_dispcost` WHERE `idroom`=".(int)$pid_room." AND `idprice`=".(int)$pid_price." GROUP BY `idroom`) AS `r2` ON `r`.`days`=`r2`.`min_days` LEFT JOIN `#__vikbooking_prices` `p` ON `p`.`id`=`r`.`idprice` AND `p`.`id`=".(int)$pid_price." WHERE `r`.`idroom`=".(int)$pid_room." AND `r`.`idprice`=".(int)$pid_price." GROUP BY `r`.`id`,`r`.`idroom`,`r`.`days`,`r`.`idprice`,`r`.`cost`,`p`.`name` ORDER BY `r`.`days` ASC, `r`.`cost` ASC;";
		$dbo->setQuery($q);
		$roomrates = $dbo->loadAssocList();
		if ($roomrates) {
			foreach ($roomrates as $rrk => $rrv) {
				$roomrates[$rrk]['cost'] = round(($rrv['cost'] / $rrv['days']), 2);
				$roomrates[$rrk]['days'] = 1;
			}
		}

		if (!$roomrates) {
			echo 'e4j.error.'.addslashes(JText::_('VBRATESOVWERRNORATES'));
			exit;
		}

		/**
		 * Disable season records caching because new rates will have to be re-calculated
		 * for the response by checking the same exact dates.
		 * 
		 * @since 	1.16.5 (J) - 1.6.5 (WP)
		 */
		VikBooking::setSeasonsCache(false);

		$roomrates = $roomrates[0];
		$current_rates = array();
		$start_ts = strtotime($pfromdate);
		$end_ts = strtotime($ptodate);
		$infostart = getdate($start_ts);
		while ($infostart[0] > 0 && $infostart[0] <= $end_ts) {
			$today_tsin = VikBooking::getDateTimestamp(date($df, $infostart[0]), $pcheckinh, $pcheckinm);
			$today_tsout = VikBooking::getDateTimestamp(date($df, mktime(0, 0, 0, $infostart['mon'], ($infostart['mday'] + 1), $infostart['year'])), $pcheckouth, $pcheckoutm);

			$tars = VikBooking::applySeasonsRoom([$roomrates], $today_tsin, $today_tsout);
			$current_rates[(date('Y-m-d', $infostart[0]))] = $tars[0];

			$infostart = getdate(mktime(0, 0, 0, $infostart['mon'], ($infostart['mday'] + 1), $infostart['year']));
		}
		if (!$current_rates) {
			echo 'e4j.error.'.addslashes(JText::_('VBRATESOVWERRNORATES').'.');
			exit;
		}
		$all_days = array_keys($current_rates);
		$season_intervals = array();
		$firstind = 0;
		$firstdaycost = $current_rates[$all_days[0]]['cost'];
		$nextdaycost = false;
		for ($i = 1; $i < count($all_days); $i++) {
			$ind = $all_days[$i];
			$nextdaycost = $current_rates[$ind]['cost'];
			if ($firstdaycost != $nextdaycost) {
				$interval = array(
					'from' => $all_days[$firstind],
					'to' => $all_days[($i - 1)],
					'cost' => $firstdaycost
				);
				$season_intervals[] = $interval;
				$firstdaycost = $nextdaycost;
				$firstind = $i;
			}
		}
		if ($nextdaycost === false) {
			$interval = array(
				'from' => $all_days[$firstind],
				'to' => $all_days[$firstind],
				'cost' => $firstdaycost
			);
			$season_intervals[] = $interval;
		} elseif ($firstdaycost == $nextdaycost) {
			$interval = array(
				'from' => $all_days[$firstind],
				'to' => $all_days[($i - 1)],
				'cost' => $firstdaycost
			);
			$season_intervals[] = $interval;
		}
		foreach ($season_intervals as $sik => $siv) {
			if ((float)$siv['cost'] == $prate) {
				unset($season_intervals[$sik]);
			}
		}
		if (!$season_intervals) {
			// VBO 1.11 - do not raise this error if it was requested to set the restriction or to close a rate plan
			if ((!($pminlos > 0) && !($prateclosed > 0)) || !($pvcm > 0)) {
				echo 'e4j.error.'.addslashes(JText::_('VBRATESOVWERRNORATESMOD'));
				exit;
			}
		}
		foreach ($season_intervals as $sik => $siv) {
			$first = strtotime($siv['from']);
			$second = strtotime($siv['to']);
			if ($second > 0 && $second == $first) {
				$second += 86399;
			}
			if (!($second > $first)) {
				unset($season_intervals[$sik]);
				continue;
			}
			$baseone = getdate($first);
			$basets = mktime(0, 0, 0, 1, 1, $baseone['year']);
			$sfrom = $baseone[0] - $basets;
			$basetwo = getdate($second);
			$basets = mktime(0, 0, 0, 1, 1, $basetwo['year']);
			$sto = $basetwo[0] - $basets;
			//check leap year
			if ($baseone['year'] % 4 == 0 && ($baseone['year'] % 100 != 0 || $baseone['year'] % 400 == 0)) {
				$leapts = mktime(0, 0, 0, 2, 29, $baseone['year']);
				if ($baseone[0] > $leapts) {
					$sfrom -= 86400;
					/**
					 * To avoid issue with leap years and dates near Feb 29th, we only reduce the seconds if these were reduced
					 * for the from-date of the seasons. Doing it just for the to-date in 2019 for 2020 (leap) produced invalid results.
					 * 
					 * @since 	July 2nd 2019
					 */
					if ($basetwo['year'] % 4 == 0 && ($basetwo['year'] % 100 != 0 || $basetwo['year'] % 400 == 0)) {
						$leapts = mktime(0, 0, 0, 2, 29, $basetwo['year']);
						if ($basetwo[0] > $leapts) {
							$sto -= 86400;
						}
					}
				}
			}
			//end leap year
			$tieyear = $baseone['year'];
			$ptype = (float)$siv['cost'] > $prate ? "2" : "1";
			$pdiffcost = $ptype == "1" ? ($prate - (float)$siv['cost']) : ((float)$siv['cost'] - $prate);
			$roomstr = "-".$pid_room."-,";
			$pspname = date('Y-m-d H:i').' - '.substr($baseone['month'], 0, 3).' '.$baseone['mday'].($siv['from'] != $siv['to'] ? '/'.($baseone['month'] != $basetwo['month'] ? substr($basetwo['month'], 0, 3).' ' : '').$basetwo['mday'] : '');
			$pval_pcent = 1;
			$pricestr = "-".$pid_price."-,";
			$q = "INSERT INTO `#__vikbooking_seasons` (`type`,`from`,`to`,`diffcost`,`idrooms`,`spname`,`wdays`,`checkinincl`,`val_pcent`,`losoverride`,`roundmode`,`year`,`idprices`,`promo`,`promodaysadv`,`promotxt`,`promominlos`,`occupancy_ovr`) VALUES('".($ptype == "1" ? "1" : "2")."', ".$dbo->quote($sfrom).", ".$dbo->quote($sto).", ".$dbo->quote($pdiffcost).", ".$dbo->quote($roomstr).", ".$dbo->quote($pspname).", '', '0', '".$pval_pcent."', '', NULL, ".$tieyear.", ".$dbo->quote($pricestr).", 0, NULL, '', 0, NULL);";
			$dbo->setQuery($q);
			$dbo->execute();
		}
		
		$start_ts = strtotime($pfromdate);
		$end_ts = strtotime($ptodate);
		$infostart = getdate($start_ts);
		$infoend = getdate($end_ts);
		// VBO 1.11 - Restrictions can be set only if VCM is enabled because we use the Connector Class
		$current_minlos = 0;
		// always require the VCM libraries if requested
		if ($pvcm > 0) {
			if (!class_exists('VikChannelManager')) {
				require_once(VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "lib.vikchannelmanager.php");
				require_once(VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "vcm_config.php");
			}
			// invoke the Connector for any update request
			$vboConnector = VikChannelManager::getVikBookingConnectorInstance();
			//set the caller to 'VBO' to reduce the sleep time between the requests
			$vboConnector->caller = 'VBO';
		}
		if ($pminlos > 0 && $pvcm > 0) {
			// set the end date to the last second
			$end_ts = mktime(23, 59, 59, $infoend['mon'], $infoend['mday'], $infoend['year']);

			// create the restriction in VBO
			$restr_res = $vboConnector->createRestriction(date('Y-m-d H:i:s', $start_ts), date('Y-m-d H:i:s', $end_ts), array($pid_room), array($pminlos, ''));

			// update value for the ajax response
			if ($restr_res) {
				$current_minlos = $pminlos;
			} else {
				$current_minlos = 'e4j.error.'.$vboConnector->getError();
			}
		}

		//prepare output by re-calculating the rates in real-time
		$current_rates = array();
		while ($infostart[0] > 0 && $infostart[0] <= $end_ts) {
			$today_tsin = VikBooking::getDateTimestamp(date($df, $infostart[0]), $pcheckinh, $pcheckinm);
			$today_tsout = VikBooking::getDateTimestamp(date($df, mktime(0, 0, 0, $infostart['mon'], ($infostart['mday'] + 1), $infostart['year'])), $pcheckouth, $pcheckoutm);

			$tars = VikBooking::applySeasonsRoom([$roomrates], $today_tsin, $today_tsout);
			$indkey = $infostart['mday'].'-'.$infostart['mon'].'-'.$infostart['year'].'-'.$pid_price;
			$current_rates[$indkey] = $tars[0];
			if (is_int($current_minlos) && $current_minlos > 0) {
				// VBO 1.11
				$current_rates[$indkey]['newminlos'] = $current_minlos;
			}

			$infostart = getdate(mktime(0, 0, 0, $infostart['mon'], ($infostart['mday'] + 1), $infostart['year']));
		}

		/**
		 * Store a record in the rates flow for this rate modification on VBO.
		 * 
		 * @requires 	VCM >= 1.8.3
		 * 
		 * @since 		1.15.0 (J) - 1.5.0 (WP)
		 */
		$rflow_handler = VikBooking::getRatesFlowInstance();
		if ($rflow_handler !== null) {
			$rflow_record  = $rflow_handler->getRecord()
				->setCreatedBy('VBO')
				->setDates($pfromdate, $ptodate)
				->setVBORoomID($pid_room)
				->setVBORatePlanID($pid_price)
				->setNightlyFee($prate);
			if (is_int($current_minlos) && $current_minlos > 0) {
				// push restriction extra data
				$rflow_record->setRestrictions(array('minLOS' => $current_minlos));
			}
			if (method_exists($rflow_record, 'setBaseFee')) {
				/**
				 * @requires 	VCM >= 1.8.4
				 */
				$rflow_record->setBaseFee($roomrates['cost']);
			}
			// push rates flow record
			$rflow_handler->pushRecord($rflow_record);
			// store rates flow record
			$rflow_handler->storeRecords();
		}

		//launch channel manager or update session values for VCM
		if ($pvcm > 0) {
			//launch channel manager (from VBO, unlikely through the App, we update one rate plan per request)
			$vcm_logos = VikBooking::getVcmChannelsLogo('', true);
			$channels_updated = array();
			$channels_bkdown = array();
			$channels_success = array();
			$channels_warnings = array();
			$channels_errors = array();
			//load room details
			$q = "SELECT `id`,`name`,`units` FROM `#__vikbooking_rooms` WHERE `id`=".(int)$pid_room.";";
			$dbo->setQuery($q);
			$row = $dbo->loadAssoc();
			if ($row) {
				$row['channels'] = array();
				//Get the mapped channels for this room
				$q = "SELECT * FROM `#__vikchannelmanager_roomsxref` WHERE `idroomvb`=".(int)$pid_room.";";
				$dbo->setQuery($q);
				$channels_data = $dbo->loadAssocList();
				if ($channels_data) {
					foreach ($channels_data as $ch_data) {
						$row['channels'][$ch_data['idchannel']] = $ch_data;
					}
				}
			}
			if ($row && isset($row['channels']) && $row['channels']) {
				//this room is actually mapped to some channels supporting AV requests
				//load the 'Bulk Action - Rates Upload' cache
				$bulk_rates_cache = VikChannelManager::getBulkRatesCache();
				//we update one rate plan per time, even though we could update all of them with a similar request
				$rates_data = array(
					array(
						'rate_id' => $pid_price,
						'cost' => $prate
					)
				);
				//build the array with the update details
				$update_rows = array();
				foreach ($rates_data as $rk => $rd) {
					$node = $row;
					$setminlos = '';
					$setmaxlos = '';
					// VBO 1.11 - pass the restrictions to the channels if specified
					if (is_int($current_minlos) && $current_minlos > 0) {
						$setminlos = $current_minlos;
						// max los must have a length > 0 or min los won't be set
						$setmaxlos = '0';
					}
					// VBO 1.11 - close rate plan (min or max los do not need to be set)
					if ($prateclosed > 0) {
						// VikBookingConnector class in VCM requires the closure to be concatenated to maxlos
						$setmaxlos .= 'closed';
					}
					//
					//check bulk rates cache to see if the exact rate should be increased for the channels (the exact rate has already been set in VBO at this point of the code)
					if (isset($bulk_rates_cache[$pid_room]) && isset($bulk_rates_cache[$pid_room][$rd['rate_id']])) {
						if ((int)$bulk_rates_cache[$pid_room][$rd['rate_id']]['rmod'] > 0 && (float)$bulk_rates_cache[$pid_room][$rd['rate_id']]['rmodamount'] > 0) {
							if ((int)$bulk_rates_cache[$pid_room][$rd['rate_id']]['rmodop'] > 0) {
								//Increase rates
								if ((int)$bulk_rates_cache[$pid_room][$rd['rate_id']]['rmodval'] > 0) {
									//Percentage charge
									$rd['cost'] = $rd['cost'] * (100 + (float)$bulk_rates_cache[$pid_room][$rd['rate_id']]['rmodamount']) / 100;
								} else {
									//Fixed charge
									$rd['cost'] += (float)$bulk_rates_cache[$pid_room][$rd['rate_id']]['rmodamount'];
								}
							} else {
								//Lower rates
								if ((int)$bulk_rates_cache[$pid_room][$rd['rate_id']]['rmodval'] > 0) {
									//Percentage discount
									$disc_op = $rd['cost'] * (float)$bulk_rates_cache[$pid_room][$rd['rate_id']]['rmodamount'] / 100;
									$rd['cost'] -= $disc_op;
								} else {
									//Fixed discount
									$rd['cost'] -= (float)$bulk_rates_cache[$pid_room][$rd['rate_id']]['rmodamount'];
								}
							}
						}
					}
					//
					$node['ratesinventory'] = array(
						$pfromdate.'_'.$ptodate.'_'.$setminlos.'_'.$setmaxlos.'_1_2_'.$rd['cost'].'_0'
					);
					$node['pushdata'] = array(
						'pricetype' => $rd['rate_id'],
						'defrate' => $roomrates['cost'],
						'rplans' => array(),
						'cur_rplans' => array(),
						'rplanarimode' => array()
					);
					//build push data for each channel rate plan according to the Bulk Rates Cache or to the OTA Pricing
					if (isset($bulk_rates_cache[$pid_room]) && isset($bulk_rates_cache[$pid_room][$rd['rate_id']])) {
						//Bulk Rates Cache available for this room_id and rate_id
						$node['pushdata']['rplans'] = $bulk_rates_cache[$pid_room][$rd['rate_id']]['rplans'];
						$node['pushdata']['cur_rplans'] = $bulk_rates_cache[$pid_room][$rd['rate_id']]['cur_rplans'];
						$node['pushdata']['rplanarimode'] = $bulk_rates_cache[$pid_room][$rd['rate_id']]['rplanarimode'];
					}
					//check the channels mapped for this room and add what was not found in the Bulk Rates Cache, if anything
					foreach ($node['channels'] as $idchannel => $ch_data) {
						if (!isset($node['pushdata']['rplans'][$idchannel])) {
							//this channel was not found in the Bulk Rates Cache. Read data from OTA Pricing
							$otapricing = json_decode($ch_data['otapricing'], true);
							$ch_rplan_id = '';
							if (is_array($otapricing) && isset($otapricing['RatePlan'])) {
								foreach ($otapricing['RatePlan'] as $rpkey => $rpv) {
									//get the first key (rate plan ID) of the RatePlan array from OTA Pricing
									$ch_rplan_id = $rpkey;
									break;
								}
							}
							if (empty($ch_rplan_id)) {
								unset($node['channels'][$idchannel]);
								continue;
							}
							//set channel rate plan data
							$node['pushdata']['rplans'][$idchannel] = $ch_rplan_id;
							if ($idchannel == (int)VikChannelManagerConfig::BOOKING) {
								//Default Pricing is used by default, when no data available
								$node['pushdata']['rplanarimode'][$idchannel] = 'person';
							}
						}
					}
					//add update node
					array_push($update_rows, $node);
				}
				//Update rates on the various channels
				$channels_map = array();
				foreach ($update_rows as $update_row) {
					if (!$update_row['channels']) {
						continue;
					}
					if (!$channels_updated) {
						foreach ($update_row['channels'] as $ch) {
							$channels_map[$ch['idchannel']] = ucfirst($ch['channel']);
							$ota_logo_url = is_object($vcm_logos) ? $vcm_logos->setProvenience($ch['channel'])->getLogoURL() : false;
							$channel_logo = $ota_logo_url !== false ? $ota_logo_url : '';
							$channels_updated[$ch['idchannel']] = array(
								'id' 	=> $ch['idchannel'],
								'name' 	=> ucfirst($ch['channel']),
								'logo' 	=> $channel_logo
							);
						}
					}
					//prepare request data
					$channels_ids = array_keys($update_row['channels']);
					$channels_rplans = array();
					foreach ($channels_ids as $ch_id) {
						$ch_rplan = isset($update_row['pushdata']['rplans'][$ch_id]) ? $update_row['pushdata']['rplans'][$ch_id] : '';
						$ch_rplan .= isset($update_row['pushdata']['rplanarimode'][$ch_id]) ? '='.$update_row['pushdata']['rplanarimode'][$ch_id] : '';
						$ch_rplan .= isset($update_row['pushdata']['cur_rplans'][$ch_id]) && !empty($update_row['pushdata']['cur_rplans'][$ch_id]) ? ':'.$update_row['pushdata']['cur_rplans'][$ch_id] : '';
						$channels_rplans[] = $ch_rplan;
					}
					$channels = array(
						implode(',', $channels_ids)
					);
					$chrplans = array(
						implode(',', $channels_rplans)
					);
					$nodes = array(
						implode(';', $update_row['ratesinventory'])
					);
					$rooms = array($pid_room);
					$pushvars = array(
						implode(';', array($update_row['pushdata']['pricetype'], $update_row['pushdata']['defrate']))
					);
					//send the request
					$result = $vboConnector->channelsRatesPush($channels, $chrplans, $nodes, $rooms, $pushvars);
					if ($vc_error = $vboConnector->getError(true)) {
						$channels_errors[] = $vc_error;
						continue;
					}
					//parse the channels update result and compose success, warnings, errors
					$result_pool = json_decode($result, true);
					foreach ($result_pool as $rid => $ch_responses) {
						foreach ($ch_responses as $ch_id => $ch_res) {
							if ($ch_id == 'breakdown' || !is_numeric($ch_id)) {
								//get the rates/dates breakdown of the update request
								$bkdown = $ch_res;
								if (is_array($ch_res)) {
									$bkdown = '';
									foreach ($ch_res as $bk => $bv) {
										$bkparts = explode('-', $bk);
										if (count($bkparts) == 6) {
											//breakdown key is usually composed of two dates in Y-m-d concatenated with another "-".
											$bkdown .= ucwords(JText::_('VBDAYSFROM')).' '.implode('-', array_slice($bkparts, 0, 3)).' - '.ucwords(JText::_('VBDAYSTO')).' '.implode('-', array_slice($bkparts, 3, 3)).': '.$bv."\n";
										} else {
											$bkdown .= $bk.': '.$bv."\n";
										}
									}
									// VBO 1.11 - since the Connector does not return breakdown info about the restrictions, we concatenate the response here for the Ajax request
									if ((int)$setminlos > 0) {
										$bkdown = rtrim($bkdown, "\n");
										$bkdown .= ' - '.JText::_('VBOMINIMUMSTAY').': '.$setminlos."\n";
									}
									//
									$bkdown = rtrim($bkdown, "\n");
								}
								if (!isset($channels_bkdown[$ch_id])) {
									$channels_bkdown[$ch_id] = $bkdown;
								} else {
									$channels_bkdown[$ch_id] .= "\n".$bkdown;
								}
								continue;
							}
							$ch_id = (int)$ch_id;
							if (substr($ch_res, 0, 6) == 'e4j.OK') {
								//success
								if (!isset($channels_success[$ch_id])) {
									$channels_success[$ch_id] = $channels_map[$ch_id];
								}
							} elseif (substr($ch_res, 0, 11) == 'e4j.warning') {
								//warning
								if (!isset($channels_warnings[$ch_id])) {
									$channels_warnings[$ch_id] = $channels_map[$ch_id].': '.str_replace('e4j.warning.', '', $ch_res);
								} else {
									$channels_warnings[$ch_id] .= "\n".str_replace('e4j.warning.', '', $ch_res);
								}
								//add the channel also to the successful list in case of Warning
								if (!isset($channels_success[$ch_id])) {
									$channels_success[$ch_id] = $channels_map[$ch_id];
								}
							} elseif (substr($ch_res, 0, 9) == 'e4j.error') {
								//error
								if (!isset($channels_errors[$ch_id])) {
									$channels_errors[$ch_id] = $channels_map[$ch_id].': '.str_replace('e4j.error.', '', $ch_res);
								} else {
									$channels_errors[$ch_id] .= "\n".str_replace('e4j.error.', '', $ch_res);
								}
							}
						}
					}
				}
			}
			if (count($channels_updated)) {
				$current_rates['vcm'] = array(
					'channels_updated' => $channels_updated
				);
				//set these property only if not empty
				if (count($channels_bkdown)) {
					$current_rates['vcm']['channels_bkdown'] = $channels_bkdown['breakdown'];
				}
				if (count($channels_success)) {
					$current_rates['vcm']['channels_success'] = $channels_success;
				}
				if (count($channels_warnings)) {
					$current_rates['vcm']['channels_warnings'] = $channels_warnings;
				}
				if (count($channels_errors)) {
					$current_rates['vcm']['channels_errors'] = $channels_errors;
				}
			}
		} else {
			//update session values
			$updforvcm['count'] = array_key_exists('count', $updforvcm) && !empty($updforvcm['count']) ? ($updforvcm['count'] + 1) : 1;
			if (array_key_exists('dfrom', $updforvcm) && !empty($updforvcm['dfrom'])) {
				$updforvcm['dfrom'] = $updforvcm['dfrom'] > $start_ts ? $start_ts : $updforvcm['dfrom'];
			} else {
				$updforvcm['dfrom'] = $start_ts;
			}
			if (array_key_exists('dto', $updforvcm) && !empty($updforvcm['dto'])) {
				$updforvcm['dto'] = $updforvcm['dto'] < $end_ts ? $end_ts : $updforvcm['dto'];
			} else {
				$updforvcm['dto'] = $end_ts;
			}
			if (array_key_exists('rooms', $updforvcm) && is_array($updforvcm['rooms'])) {
				if (!in_array($pid_room, $updforvcm['rooms'])) {
					$updforvcm['rooms'][] = $pid_room;
				}
			} else {
				$updforvcm['rooms'] = array($pid_room);
			}
			if (array_key_exists('rplans', $updforvcm) && is_array($updforvcm['rplans'])) {
				if (array_key_exists($pid_room, $updforvcm['rplans'])) {
					if (!in_array($pid_price, $updforvcm['rplans'][$pid_room])) {
						$updforvcm['rplans'][$pid_room][] = $pid_price;
					}
				} else {
					$updforvcm['rplans'][$pid_room] = array($pid_price);
				}
			} else {
				$updforvcm['rplans'] = array($pid_room => array($pid_price));
			}
		}
		//update session in any case
		$session->set('vbVcmRatesUpd', $updforvcm);
		//
		$pdebug = VikRequest::getInt('e4j_debug', '', 'request');
		if ($pdebug == 1) {
			echo "e4j.error.\n".print_r($roomrates, true)."\n";
			echo print_r($current_rates, true)."\n\n";
			echo print_r($season_intervals, true)."\n";
			echo $pid_room.' - '.$pid_price.' - '.$prate.' - '.$pfromdate.' - '.$ptodate."\n";
		}
		echo json_encode($current_rates);
		exit;
	}
}
