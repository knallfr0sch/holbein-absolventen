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

$document = JFactory::getDocument();
$document->addStyleSheet(VBO_SITE_URI.'resources/vikfxgallery.css');
JHtml::_('script', VBO_SITE_URI.'resources/vikfxgallery.js');

$vat_included = VikBooking::ivaInclusa();
$tax_summary = !$vat_included && VikBooking::showTaxOnSummaryOnly() ? true : false;

$currencysymb = VikBooking::getCurrencySymb();

// compose array of rooms available for the requested parties
$rooms_res = [];
$rooms_units = [];
$rooms_parties = [];
$gallery_data = [];
foreach ($this->res as $indroom => $rooms) {
	foreach ($rooms as $roomrates) {
		$idroom = $roomrates[0]['idroom'];
		if (!isset($rooms_res[$idroom])) {
			$rooms_res[$idroom] = [];
			$rooms_units[$idroom] = $roomrates[0]['unitsavail'];
		}
		$rooms_res[$idroom][$indroom] = $roomrates;
	}
}
?>

<div class="vbo-searchresults-compact-wrap">
<?php
foreach ($rooms_res as $idroom => $room_res) {
	$rooms_parties[$idroom] = array_keys($room_res);
	foreach ($room_res as $indroom => $roomrates) {
		$roominfo = $roomrates[0];
		// just one loop
		break;
	}
	$has_promotion = isset($roominfo['promotion']);
	$carats = VikBooking::getRoomCaratOriz($roominfo['idcarat'], $this->vbo_tn);

	// prepare CMS contents depending on platform
	$roominfo = VBORoomHelper::getInstance()->prepareCMSContents($roominfo, ['smalldesc']);

	$saylastavail = false;
	$showlastavail = (int)VikBooking::getRoomParam('lastavail', $roominfo['params']);
	if (!empty($showlastavail) && $showlastavail > 0) {
		if ($roominfo['unitsavail'] <= $showlastavail) {
			$saylastavail = true;
		}
	}
	// get the max units this room can have in the drop down
	$maxunits = 1;
	if (count($room_res) > 1) {
		// room is available for multiple room parties. Count how many parties have the same adults/children
		$parties = $rooms_parties[$idroom];
		$party_count = [];
		foreach ($parties as $party) {
			$party_key = "adults{$this->arrpeople[$party]['adults']}children{$this->arrpeople[$party]['children']}";
			if (!isset($party_count[$party_key])) {
				$party_count[$party_key] = 0;
			}
			$party_count[$party_key]++;
		}
		$maxunits = max($party_count);
		$maxunits = $maxunits > $roominfo['unitsavail'] ? $roominfo['unitsavail'] : $maxunits;
	}
	// gallery
	if (!empty($roominfo['moreimgs'])) {
		$gallery_data[$idroom] = [];
		$moreimages = explode(';;', $roominfo['moreimgs']);
		$imgcaptions = json_decode($roominfo['imgcaptions'], true);
		$usecaptions = is_array($imgcaptions);
		foreach ($moreimages as $iind => $mimg) {
			if (empty($mimg)) {
				continue;
			}
			$img_alt = $usecaptions && !empty($imgcaptions[$iind]) ? $imgcaptions[$iind] : substr($mimg, 0, strpos($mimg, '.'));
			array_push($gallery_data[$idroom], [
				'big' => VBO_SITE_URI . 'resources/uploads/big_' . $mimg,
				'thumb' => VBO_SITE_URI . 'resources/uploads/thumb_' . $mimg,
				'alt' => $img_alt,
				'caption' => $usecaptions && !empty($imgcaptions[$iind]) ? $imgcaptions[$iind] : "",
			]);
		}
	}
	//
	?>
	<div class="vbo-room-result-wrap<?php echo $has_promotion ? ' vbo-promotion-price' : ''; ?>">
		<div class="vbo-room-result-inner">
			<div class="vbo-room-result-head">
				<div class="vbo-room-result-head-img">
					<span><?php echo JText::_('VBSEARCHRESROOM'); ?></span>
				</div>
				<div class="vbo-room-result-head-details">
					<span><?php echo JText::_('VBSEARCHRESDETAILS'); ?></span>
				</div>
				<div class="vbo-room-result-head-party">
					<span><?php echo JText::_('VBOINVTOTGUESTS'); ?></span>
				</div>
				<div class="vbo-room-result-head-price">
					<span><?php echo JText::_('VBPRICE'); ?></span>
				</div>
				<div class="vbo-room-result-head-select">
					<span><?php echo JText::_('VBORESERVE'); ?></span>
				</div>
				<div class="vbo-room-result-head-bookstatus">
					<span></span>
				</div>
			</div>
			<div class="vbo-room-result-body">
				<div class="vbo-room-result-body-img">
					<div class="vikfx-gallery-container vikfx-compact-gallery-container">
						<div class="vikfx-gallery-fade-container">
							<img class="vblistimg" alt="<?php echo htmlspecialchars($roominfo['name']); ?>" id="vbroomimg<?php echo $idroom; ?>" src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $roominfo['img']; ?>"/>
						<?php
						if (isset($gallery_data[$idroom]) && count($gallery_data[$idroom])) {
							?>
							<div class="vikfx-gallery-navigation-controls">
								<div class="vikfx-gallery-navigation-controls-prevnext">
									<a href="javascript: void(0);" class="vikfx-gallery-previous-image" id="vikfx-gallery-previous-image<?php echo $idroom; ?>"><?php VikBookingIcons::e('chevron-left'); ?></a>
									<a href="javascript: void(0);" class="vikfx-gallery-next-image" id="vikfx-gallery-next-image<?php echo $idroom; ?>"><?php VikBookingIcons::e('chevron-right'); ?></a>
								</div>
							</div>
							<?php
						}
						?>
						</div>
					<?php
					if (isset($gallery_data[$idroom]) && count($gallery_data[$idroom])) {
						?>
						<div class="vikfx-gallery" id="vikfx-gallery<?php echo $idroom; ?>">
						<?php
						foreach ($gallery_data[$idroom] as $mimg) {
							?>
							<a href="<?php echo $mimg['big']; ?>">
								<img src="<?php echo $mimg['thumb']; ?>" alt="<?php echo $this->escape($mimg['alt']); ?>" title="<?php echo $this->escape($mimg['caption']); ?>"/>
							</a>
							<?php
						}
						?>
						</div>
						<?php
					}
					?>
					</div>
				</div>
				<div class="vbo-room-result-body-details">
					<div class="vbo-room-result-body-rname">
						<h4><?php echo $roominfo['name']; ?></h4>
					<?php
					if ($saylastavail === true) {
						?>
						<span class="vbo-room-result-body-lastavail"><?php echo JText::sprintf('VBLASTUNITSAVAIL', $roominfo['unitsavail']); ?></span>
						<?php
					}
					?>
					</div>
					<div class="vbrowcdescr"><?php echo $roominfo['smalldesc']; ?></div>
					<?php
				if (!empty($carats)) {
					?>
					<div class="roomlist_carats">
						<?php echo $carats; ?>
					</div>
					<?php
				}
				if ($has_promotion && !empty($roominfo['promotion']['promotxt'])) {
					?>
					<div class="vbo-promotion-block">
						<div class="vbo-promotion-icon"><?php VikBookingIcons::e('percentage'); ?></div>
						<div class="vbo-promotion-description">
							<?php echo $roominfo['promotion']['promotxt']; ?>
						</div>
					</div>
					<?php
				}
				?>
				</div>
				<div class="vbo-room-result-body-bookingsolutions">
				<?php
				$room_parties_displayed = [];
				foreach ($room_res as $indroom => $roomrates) {
					$party_key = "adults{$this->arrpeople[$indroom]['adults']}children{$this->arrpeople[$indroom]['children']}";
					if (in_array($party_key, $room_parties_displayed)) {
						// skip this room because the drop down will already display the possibility of booking multiple units of this room
						continue;
					}
					// push the party displayed
					array_push($room_parties_displayed, $party_key);
					?>
					<div class="vbo-room-result-body-bookingsolution">
						<div class="vbo-room-result-body-price-party">
						<?php
						$totguests = $this->arrpeople[$indroom]['adults'] + $this->arrpeople[$indroom]['children'];
						if ($this->arrpeople[$indroom]['adults'] > 0) {
							?>
							<div class="vbo-room-result-body-price-party-adults">
							<?php
							if ($totguests > 4) {
								?>
								<span><?php VikBookingIcons::e('male'); ?> &times;<?php echo $this->arrpeople[$indroom]['adults']; ?></span>
								<?php
							} else {
								for ($i = 0; $i < $this->arrpeople[$indroom]['adults']; $i++) {
									VikBookingIcons::e('male');
								}
							}
							?>
							</div>
							<?php
						}
						if ($this->arrpeople[$indroom]['children'] > 0) {
							?>
							<div class="vbo-room-result-body-price-party-children">
							<?php
							if ($totguests > 4) {
								?>
								<span><?php VikBookingIcons::e('child'); ?> &times;<?php echo $this->arrpeople[$indroom]['children']; ?></span>
								<?php
							} else {
								for ($i = 0; $i < $this->arrpeople[$indroom]['children']; $i++) {
									VikBookingIcons::e('child');
								}
							}
							?>
							</div>
							<?php
						}
						$raw_roomcost = $tax_summary ? $roomrates[0]['cost'] : VikBooking::sayCostPlusIva($roomrates[0]['cost'], $roomrates[0]['idprice']);
						?>
						</div>
						<div class="vbo-room-result-body-price-amount">
							<span class="room_cost">
								<span class="vbo_currency"><?php echo $currencysymb; ?></span> 
								<span class="vbo_price"><?php echo VikBooking::numberFormat($raw_roomcost); ?></span>
							</span>
					<?php
					if (isset($roominfo['promotion']) && isset($roominfo['promotion']['discount'])) {
						if ($roominfo['promotion']['discount']['pcent']) {
							/**
							 * Do not make an upper-cent operation, but rather calculate the original price proportionally:
							 * final price : (100 - discount amount) = x : 100
							 * 
							 * @since 	1.13.5
							 */
							$prev_amount = $raw_roomcost * 100 / (100 - $roominfo['promotion']['discount']['amount']);
						} else {
							$prev_amount = $raw_roomcost + $roominfo['promotion']['discount']['amount'];
						}
						if ($prev_amount > 0) {
							?>
							<div class="vbo-room-result-price-before-discount">
								<span class="room_cost">
									<span class="vbo_currency"><?php echo $currencysymb; ?></span> 
									<span class="vbo_price"><?php echo VikBooking::numberFormat($prev_amount); ?></span>
								</span>
							</div>
							<?php
							if ($roominfo['promotion']['discount']['pcent']) {
								// hide by default the DIV containing the percent of discount
								?>
							<div class="vbo-room-result-price-before-discount-percent" style="display: none;">
								<span class="room_cost">
									<span><?php echo '-' . (float)$roominfo['promotion']['discount']['amount'] . ' %'; ?></span>
								</span>
							</div>
								<?php
							}
						}
					}
					?>
						</div>
						<div class="vbo-room-result-body-price-selection">
							<select id="vbo-selroom-<?php echo "{$indroom}-{$roomrates[0]['idroom']}"; ?>" class="vbo-selroom-ddown vbo-selroom-room<?php echo $roomrates[0]['idroom']; ?> vbo-selroom-party<?php echo $indroom; ?>" onchange="vboCompactSelRoom(<?php echo $indroom; ?>, <?php echo $roomrates[0]['idroom']; ?>, this.value);">
								<option value="0">0</option>
							<?php
							for ($i = 1; $i <= $maxunits; $i++) { 
								?>
								<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
								<?php
							}
							?>
							</select>
						</div>
						<div class="vbo-room-result-body-bookstatus">
							<span class="vbo-room-result-body-bookmsg" style="display: none;"></span>
							<div class="vbo-room-result-body-bookbtn" style="display: none;">
								<button type="button" class="btn" onclick="vboCompactProceed();"><?php echo JText::_('VBBOOKNOW'); ?></button>
							</div>
						</div>
					</div>
					<?php
				}
				?>
				</div>
			</div>
		</div>
	</div>
	<?php
}
?>
</div>

<script type="text/javascript">
var vboNeededBooked = <?php echo $this->roomsnum; ?>;
var vboRoomsUnits = <?php echo json_encode($rooms_units); ?>;
var vboRoomsParties = <?php echo json_encode($rooms_parties); ?>;
var vboPartiesRequested = {};
var vboRoomsUnitsPool = {};
var vboRoomsAssigned = {};
var vboCompactMsg = {
	missingRoomsPl: "<?php echo addslashes(JText::_('VBONROOMSMISSINGPL')); ?>",
	missingRoomsSi: "<?php echo addslashes(JText::_('VBONROOMSMISSINGSI')); ?>",
};
function vboCompactCountSelected(idroom) {
	var totselected = 0;
	var ddowns = document.getElementsByClassName((idroom ? 'vbo-selroom-room' + idroom : 'vbo-selroom-ddown'));
	for (var i = 0; i < ddowns.length; i++) {
		totselected += parseInt(ddowns.item(i).value);
	}
	return totselected;
}
/**
 * Checks whether a slot is occupied by another room. Returns false or an array with the occupier information.
 */
function vboCompactIsSlotOccupied(indroom, idroom) {
	var current_val = document.getElementById('roomopt' + indroom).value;
	if (current_val && current_val.length && (current_val + '') != (idroom + '')) {
		return [parseInt(indroom), parseInt(current_val)];
	}
	return false;
}
/**
 * Frees up one slot by updating the necessary objects and input field.
 */
function vboCompactMakeSlotFree(indroom, idroom) {
	var current_room = document.getElementById('roomopt' + indroom).value;
	if (!current_room || !current_room.length) {
		return;
	}
	current_room = parseInt(current_room);
	document.getElementById('roomopt' + indroom).value = '';
	if (vboPartiesRequested.hasOwnProperty(indroom)) {
		delete vboPartiesRequested[indroom];
	}
	if (vboRoomsAssigned.hasOwnProperty(current_room) && vboRoomsAssigned[current_room].hasOwnProperty(indroom)) {
		for (var i in vboRoomsAssigned[current_room][indroom]) {
			if (vboRoomsAssigned[current_room][indroom].hasOwnProperty(i) && vboRoomsAssigned[current_room][indroom][i] == indroom) {
				vboRoomsAssigned[current_room][indroom].splice(i, 1);
				break;
			}
		}
	}
	jQuery('.vbo-selroom-room' + current_room).each(function(k, v) {
		var elem = jQuery(v);
		var current_val = parseInt(elem.val());
		if (current_val > 0 && (elem.hasClass('vbo-selroom-party' + indroom) || (Array.isArray(vboRoomsParties[current_room]) && vboRoomsParties[current_room].includes(indroom)))) {
			elem.val((current_val - 1));
			// if some drop downs were previously disabled, turn them back on
			var roomtotsel = vboCompactCountSelected(current_room);
			if (roomtotsel < vboRoomsUnits[current_room]) {
				// other units available for this room-type
				var container = elem.closest('.vbo-room-result-body-bookingsolutions');
				container.find('.vbo-room-result-body-bookingsolution').removeClass('vbo-room-result-soldout');
				container.find('select.vbo-selroom-ddown').prop('disabled', false);
			}
			// break loop
			return false;
		}
	});
}
/**
 * Disables or enables the parties that can be booked.
 */
function vboCompactUpdateParties() {
	// always free up the already chosen elements for the now empty parties
	for (var i = 1; i <= vboNeededBooked; i++) {
		var inpslot = document.getElementById('roomopt' + i).value;
		if (!inpslot.length) {
			// no room for this party, make sure the drop downs are enabled
			jQuery('.vbo-room-result-alreadychosen-' + i).prop('disabled', false).removeClass('vbo-room-result-alreadychosen-' + i);
		}
	}
	//
	for (var indparty in vboPartiesRequested) {
		if (!vboPartiesRequested.hasOwnProperty(indparty) || !vboPartiesRequested[indparty]) {
			continue;
		}
		for (var room in vboRoomsParties) {
			if (!vboRoomsParties.hasOwnProperty(room) || !vboRoomsParties[room].length) {
				continue;
			}
			var check_indparty = parseInt(indparty);
			if (vboRoomsParties[room].includes(check_indparty)) {
				// this room is suitable for this party already chosen
				var elem = jQuery('#vbo-selroom-' + indparty + '-' + room);
				if (!elem.length) {
					continue;
				}
				if (elem.val() < 1) {
					// we can disable this drop down so that no units will be selected
					elem.prop('disabled', true).addClass('vbo-room-result-alreadychosen-' + indparty);
				} else if (!elem.closest('.vbo-room-result-body-bookingsolution').hasClass('vbo-room-result-soldout')) {
					// we can enable this drop down
					elem.prop('disabled', false);
				}
			}
		}
	}
}

function vboCompactSelRoom(indroom, idroom, units) {
    var selectedRoomCapacity = vboCompactCountSelected();

	if (selectedRoomCapacity == vboNeededBooked) {
        document.getElementById('vbsearchmainsbmt').style.display = 'block';
		jQuery('.vbo-room-result-body-bookmsg, .vbo-room-result-body-bookbtn').fadeIn();
    } else {
        document.getElementById('vbsearchmainsbmt').style.display = 'none';
		jQuery('.vbo-room-result-body-bookmsg, .vbo-room-result-body-bookbtn').hide();
    }
}


function vboCompactProceed() {
    var selectedRoomCapacity = vboCompactCountSelected();

	if (selectedRoomCapacity != vboNeededBooked) {
        window.alert('Fehler beim Buchen.')
        return undefined;
    }


    var dropdowns = document.getElementsByTagName('select');

    // The guest currently being assigned to a room.
    var guestIndex = 0;

    for (var roomNumbe = 0; roomNumbe < dropdowns.length; roomNumbe++) {
		var guestsSelectedForThisRoom = parseInt(dropdowns.item(i).value);
        for (var guestIndexPerRoom = 0; guestIndexPerRoom < guestsSelectedForThisRoom; guestIndexPerRoom++) {
            document.getElementById('roomopt' + guestIndex).value = guestIndex;
            guestIndex++;
        }
	}


	document.getElementById('vbselectroomform').submit();
}

jQuery(function() {
<?php
foreach ($gallery_data as $idr => $gallery) {
	?>
	window["vikfxgallery<?php echo $idr; ?>"] = jQuery("#vikfx-gallery<?php echo $idr; ?> a").vikFxGallery();
	jQuery("#vikfx-gallery-previous-image<?php echo $idr; ?>, #vikfx-gallery-next-image<?php echo $idr; ?>, #vbroomimg<?php echo $idr; ?>").click(function() {
		if (typeof window["vikfxgallery<?php echo $idr; ?>"] !== "undefined") {
			window["vikfxgallery<?php echo $idr; ?>"].open();
		}
	});
	<?php
}
?>
});
</script>
