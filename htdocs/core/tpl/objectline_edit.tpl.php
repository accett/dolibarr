<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * Need to have following variables defined:
 * $conf
 * $langs
 * $dateSelector
 * $this (invoice, order, ...)
 * $line defined
 */
?>

<!-- BEGIN PHP TEMPLATE objectline_edit.tpl.php -->
<form action="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'#'.$line->id; ?>" method="POST">
<input type="hidden" name="token" value="<?php  echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="updateligne">
<input type="hidden" name="id" value="<?php echo $this->id; ?>">
<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">
<input type="hidden" id="product_type" name="type" value="<?php echo $line->product_type; ?>">
<input type="hidden" id="product_id" name="productid" value="<?php echo (! empty($line->fk_product)?$line->fk_product:''); ?>" />

<tr <?php echo $bc[$var]; ?>>
	<td<?php echo (! empty($conf->global->MAIN_VIEW_LINE_NUMBER) ? ' colspan="2"' : ''); ?>>
	<div id="<?php echo $line->id; ?>"></div>

	<?php if ($conf->global->MAIN_FEATURES_LEVEL > 1) { ?>

	<?php if ($line->fk_product > 0) { ?>
	<?php echo $text . ' - '; ?>
	<?php } else { ?>
	<?php echo $form->select_type_of_lines($line->product_type, 'type', 1, 1); ?>
	<?php } ?>

	<input id="product_label" name="product_label" size="40" value="<?php echo $label; ?>"<?php echo $placeholder . ((! empty($line->fk_product) && empty($line->label)) ? ' disabled="disabled"' : ''); ?>>
	<input type="hidden" id="origin_label_cache" name="origin_label_cache" value="<?php echo $line->product_label; ?>" />
	<span id="update_label_area" class="hideobject"><input type="checkbox" id="update_label_checkbox" name="update_label" value="1" />
		<?php echo $form->textwithtooltip($langs->trans('UpdateOriginalProductLabel'), $langs->trans('HelpUpdateOriginalProductLabel'),1,0,'','',3); ?>
	</span>
	<span id="price_base_type" class="hideobject"></span>

	<?php } else if ($line->fk_product > 0) { ?>

	<a href="<?php echo DOL_URL_ROOT.'/product/fiche.php?id='.$line->fk_product; ?>">
	<?php
	if ($line->product_type==1) echo img_object($langs->trans('ShowService'),'service');
	else print img_object($langs->trans('ShowProduct'),'product');
	echo ' '.$line->ref;
	?>
	</a>
	<?php echo ' - '.nl2br($line->product_label); ?>

	<?php } ?>

	<br>

	<?php
	if (is_object($hookmanager))
	{
		$fk_parent_line = (GETPOST('fk_parent_line') ? GETPOST('fk_parent_line') : $line->fk_parent_line);
	    $parameters=array('line'=>$line,'fk_parent_line'=>$fk_parent_line,'var'=>$var,'dateSelector'=>$dateSelector,'seller'=>$seller,'buyer'=>$buyer);
	    $reshook=$hookmanager->executeHooks('formEditProductOptions',$parameters,$this,$action);
	}

	// editeur wysiwyg
    $nbrows=ROWS_2;
    if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('product_desc',$line->description,'',164,'dolibarr_details','',false,true,$conf->global->FCKEDITOR_ENABLE_DETAILS,$nbrows,70);
	$doleditor->Create();
	?>
	</td>

	<td align="right"><?php echo $form->load_tva('tva_tx',$line->tva_tx,$seller,$buyer,0,$line->info_bits,$line->product_type); ?></td>

	<td align="right"><input type="text" class="flat" size="8" id="price_ht" name="price_ht" value="<?php echo price($line->subprice,0,'',0); ?>"></td>
	<?php if ($conf->global->MAIN_FEATURES_LEVEL > 1) { ?>
	<td align="right"><input type="text" class="flat" size="8" id="price_ttc" name="price_ttc" value="<?php echo price($pu_ttc,0,'',0); ?>"></td>
	<?php } ?>

	<td align="right">
	<?php if (($line->info_bits & 2) != 2) { ?>
		<input size="3" type="text" class="flat" name="qty" value="<?php echo $line->qty; ?>">
	<?php } else { ?>
		&nbsp;
	<?php } ?>
	</td>

	<td align="right" nowrap>
	<?php if (($line->info_bits & 2) != 2) { ?>
		<input size="1" type="text" class="flat" name="remise_percent" value="<?php echo $line->remise_percent; ?>">%
	<?php } else { ?>
		&nbsp;
	<?php } ?>
	</td>

	<?php if (! empty($conf->margin->enabled)) { ?>
	<td align="right">
		<select id="fournprice" name="fournprice" class="hideobject"></select>
		<input type="text" size="5" id="buying_price" name="buying_price" class="hideobject" value="<?php echo price($line->pa_ht,0,'',0); ?>">
	</td>
	<?php } ?>

	<td align="center" colspan="5" valign="middle">
		<input type="submit" class="button" id="savelinebutton" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
		<input type="submit" class="button" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
	</td>
</tr>

<?php if ($conf->service->enabled && $dateSelector)	{ ?>
<tr id="service_duration_area" <?php echo $bc[$var]; ?>>
	<td colspan="11"><?php echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' '; ?>
	<?php
	echo $form->select_date($line->date_start,'date_start',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$line->date_start?0:1,"updateligne");
	echo ' '.$langs->trans('to').' ';
	echo $form->select_date($line->date_end,'date_end',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$line->date_end?0:1,"updateligne");
	?>
	</td>
</tr>
<?php } ?>

</form>

<script type="text/javascript">
$(document).ready(function() {

	<?php if ($conf->global->MAIN_FEATURES_LEVEL > 1) { ?>

	if ($('#product_type').val() == 0) {
		$('#service_duration_area').hide();
	} else if ($('#product_type').val() == 1) {
		$('#service_duration_area').show();
	}

	if ($('#product_label').attr('disabled')) {
		$('#update_label_area').show();
	}

	$('#update_label_checkbox').change(function() {
		if ($(this).attr('checked')) {
			$('#product_label').removeAttr('disabled').focus();
		} else {
			$('#product_label')
				.attr('disabled','disabled')
				.val($('#origin_label_cache').val());
		}
	});

	$('#select_type').change(function() {
		var type = $(this).val();
		if (type >= 0) {
			if (type == 0) {
				$('#service_duration_area').hide();
				$('#date_start').val('').trigger('change');
				$('#date_end').val('').trigger('change');
			} else if (type == 1) {
				$('#service_duration_area').show();
			}
			if (($('#price_ht').val().length > 0) || ($('#price_ttc').val().length > 0)) {
				$('#savelinebutton').removeAttr('disabled');
			} else {
				$('#savelinebutton').attr('disabled','disabled');
			}
		} else {
			$('#savelinebutton').attr('disabled','disabled');
			$('#service_duration_area').hide();
			$('#date_start').val('').trigger('change');
			$('#date_end').val('').trigger('change');
		}
	});

	$('#price_ht').focusin(function() {
		$('#price_base_type').html('HT');
	});

	$('#price_ht').onDelayedKeyup({
		handler: function() {
			$('#price_base_type').html('HT');
			$.post('<?php echo DOL_URL_ROOT; ?>/core/ajax/price.php', {
				'action': 'get_ttc',
				'pu_ht': $(this).val(),
				'tva_tx': $('#tva_tx').val()
			},
			function(data) {
				if (data && data.price.length > 0) {
					$('#price_ttc').val(data.price);
					if (($('#product_id').val().length > 0) || ($('#select_type').val() >= 0)) {
						$('#savelinebutton').removeAttr('disabled');
					} else {
						$('#savelinebutton').attr('disabled','disabled');
					}
				} else {
					$('#price_ttc').val('');
					$('#savelinebutton').attr('disabled','disabled');
				}
			}, 'json');
		}
	});

	$('#price_ttc').focusin(function() {
		$('#price_base_type').html('TTC');
	});

	$('#price_ttc').onDelayedKeyup({
		handler: function() {
			$.post('<?php echo DOL_URL_ROOT; ?>/core/ajax/price.php', {
				'action': 'get_ht',
				'pu_ttc': $(this).val(),
				'tva_tx': $('#tva_tx').val()
			},
			function(data) {
				if (data && data.price.length > 0) {
					$('#price_ht').val(data.price);
					if (($('#product_id').val().length > 0) || ($('#select_type').val() >= 0)) {
						$('#savelinebutton').removeAttr('disabled');
					} else {
						$('#savelinebutton').attr('disabled','disabled');
					}
				} else {
					$('#price_ht').val('');
					$('#savelinebutton').attr('disabled','disabled');
				}
			}, 'json');
		}
	});

	$('#tva_tx').change(function() {
		if ($('#price_base_type').html() == 'HT') {
			$.post('<?php echo DOL_URL_ROOT; ?>/core/ajax/price.php', {
				'action': 'get_ttc',
				'pu_ht': $('#price_ht').val(),
				'tva_tx': $(this).val()
			},
			function(data) {
				if (data && data.price.length > 0) {
					$('#price_ttc').val(data.price);
				}
			}, 'json');
		} else {
			$.post('<?php echo DOL_URL_ROOT; ?>/core/ajax/price.php', {
				'action': 'get_ht',
				'pu_ttc': $('#price_ttc').val(),
				'tva_tx': $(this).val()
			},
			function(data) {
				if (data && data.price.length > 0) {
					$('#price_ht').val(data.price);
				}
			}, 'json');
		}
	});
	<?php } ?>

	<?php if (! empty($conf->margin->enabled)) { ?>
	$.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php', {'idprod': <?php echo $line->fk_product; ?>}, function(data) {
		if (data.length > 0) {
			var options = '';
			var trouve=false;
			$(data).each(function() {
				options += '<option value="'+this.id+'" price="'+this.price+'"';
				<?php if ($line->fk_fournprice > 0) { ?>
				if (this.id == <?php echo $line->fk_fournprice; ?>) {
					options += ' selected';
					$("#buying_price").val(this.price);
					trouve = true;
				}
				<?php } ?>
				options += '>'+this.label+'</option>';
			});
			options += '<option value=null'+(trouve?'':' selected')+'><?php echo $langs->trans("InputPrice"); ?></option>';
			$("#fournprice").html(options);
			if (trouve) {
				$("#buying_price").hide();
				$("#fournprice").show();
			} else {
				$("#buying_price").show();
			}
			$("#fournprice").change(function() {
				var selval = $(this).find('option:selected').attr("price");
				if (selval)
					$("#buying_price").val(selval).hide();
				else
					$('#buying_price').show();
			});
		} else {
			$("#fournprice").hide();
			$('#buying_price').show();
		}
	}, 'json');
	<?php } ?>
});
</script>
<!-- END PHP TEMPLATE objectline_edit.tpl.php -->