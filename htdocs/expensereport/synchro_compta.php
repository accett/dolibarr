<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

require '../main.inc.php';
require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');
dol_include_once("/expensereport/class/expensereport.class.php");

$langs->load("companies");
$langs->load("users");
$langs->load("trips");
$langs->load("banks");

$idAccount=isset($_GET["account"])?$_GET["account"]:$_POST["account"];

if ($_GET["action"] == 'confirm_ndf_to_account' && $_GET["confirm"] == "yes"):

	$idTrip 	= $_GET['idTrip'];

	$expensereport = new ExpenseReport($db);
	$expensereport->fetch($idTrip,$user);

	$datePaiement = explode("-",$expensereport->date_paiement);

	$dateop 	= dol_mktime(12,0,0,$datePaiement[1],$datePaiement[2],$datePaiement[0]);
	$operation	= $expensereport->code_paiement;
	$label		= "Règlement ".$expensereport->ref;
	$amount 	= - price2num($expensereport->total_ttc);
	$num_chq	= '';
	$cat1		= '';

	$user = new User($db);
	$user->fetch($expensereport->fk_user_paid);

	$acct=new Account($db,$idAccount);
	$insertid = $acct->addline($dateop, $operation, $label, $amount, $num_chq, $cat1, $user);

	if ($insertid > 0):
		$sql = " UPDATE ".MAIN_DB_PREFIX."expensereport d";
		$sql.= " SET integration_compta = 1, fk_bank_account = $idAccount";
		$sql.= " WHERE rowid = $idTrip";
		$resql=$db->query($sql);
		if($result):
			Header("Location: synchro_compta.php?account=".$idAccount);
			exit;
		else:
			dol_print_error($db);
		endif;
	else:
		dol_print_error($db,$acct->error);
	endif;
endif;

if ($_GET["action"] == 'confirm_account_to_ndf' && $_GET["confirm"] == "yes"):

	$idTrip 	= $_GET['idTrip'];

	$expensereport = new ExpenseReport($db);
	$expensereport->fetch($idTrip,$user);

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank";
	$sql.= " WHERE label LIKE '%".$expensereport->ref."%'";
	$resql=$db->query($sql);
	if ($resql > 0):
		$sql = " UPDATE ".MAIN_DB_PREFIX."expensereport d";
		$sql.= " SET integration_compta = 0, fk_bank_account = 0";
		$sql.= " WHERE rowid = $idTrip";
		$resql=$db->query($sql);
		if($result):
			Header("Location: synchro_compta.php?account=".$idAccount);
			exit;
		else:
			dol_print_error($db);
		endif;
	else:
		dol_print_error($db);
	endif;
endif;


/*
 * Actions
 */

llxHeader();

$html = new Form($db);

$submit = isset($_POST['submit'])?true:false;
$idAccount=isset($_GET["account"])?$_GET["account"]:$_POST["account"];

print_fiche_titre($langs->trans("TripSynch"));


dol_fiche_head('');


if ($_GET["action"] == 'ndfTOaccount'):
	$idTrip = $_GET['idTrip'];
	$ret=$html->form_confirm("synchro_compta.php?idTrip=".$idTrip."&account=".$idAccount,$langs->trans("ndfToAccount"),$langs->trans("ConfirmNdfToAccount"),"confirm_ndf_to_account","","",1);
	if ($ret == 'html') print '<br />';
endif;

if ($_GET["action"] == 'accountTOndf'):
	$idTrip = $_GET['idTrip'];
	$ret=$html->form_confirm("synchro_compta.php?idTrip=".$idTrip."&account=".$idAccount,$langs->trans("AccountToNdf"),$langs->trans("ConfirmAccountToNdf"),"confirm_account_to_ndf","","",1);
	if ($ret == 'html') print '<br />';
endif;

if(empty($submit) && empty($idAccount)):

	print "<form name='add' method=\"post\" action=\"synchro_compta.php\">";
	print 'Choix du compte&nbsp;&nbsp;';
	print $html->select_comptes($_POST['account'],'account',0,'',1);
	print '&nbsp;<input type="submit" name="submit" class="button" value="'.$langs->trans("ViewAccountSynch").'">';
	print "</form>";

else:

	print "<form name='add' method=\"post\" action=\"synchro_compta.php\">";
	print 'Choix du compte&nbsp;&nbsp;';
	print $html->select_comptes($idAccount,'account',0,'',1);
	print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("ViewAccountSynch").'">';
	print "</form>";

	$sql = "SELECT d.fk_bank_account, d.ref, d.rowid, d.date_valid, d.fk_user_author, d.total_ttc, d.integration_compta, d.fk_c_expensereport_statuts";
	$sql.= " ,CONCAT(u.firstname,' ',u.lastname) as declarant_NDF";
	$sql.= " FROM ".MAIN_DB_PREFIX."expensereport d";
	$sql.= " INNER JOIN ".MAIN_DB_PREFIX."user u ON d.fk_user_author = u.rowid";
	$sql.= " WHERE d.fk_c_expensereport_statuts = 6";
	$sql.= " ORDER BY d.date_valid DESC";

	$resql=$db->query($sql);
	if ($resql):
	  	$num = $db->num_rows($resql); $i = 0;
		if($num>0):

			$account=new Account($db);
			$account->fetch($idAccount);

			print '<br>';

			print "<table class='noborder' width='80%'>";
				print '<tr class="liste_titre">';
					print '<td>'.$langs->trans("Ref").'</td>';
					print '<td>'.$langs->trans("DateValidation").'</td>';
					print '<td>'.$langs->trans("USER_AUTHOR").'</td>';
					print '<td align="center">'.$langs->trans("TotalTTC").'</td>';
					print '<td align="center">Actions</td>';
					print '<td>Compte</td>';
					print '<td align="center">Int.</td>';
				print '</tr>';

				while($i<$num):
					$objp = $db->fetch_object($resql);
					$var=!$var;
						print "<tr $bc[$var]>";
							print '<td>'.$objp->ref.'</td>';
							print '<td>'.dol_print_date($db->jdate($objp->date_valid),'day').'</td>';
							print '<td><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$objp->fk_user_author.'">'.img_object($langs->trans("ShowUser"),"user").' '.$objp->declarant_NDF.'</a></td>';
							print '<td align="center">'.$objp->total_ttc.' '.$langs->trans("EURO").'</td>';

							if($objp->integration_compta)
							{
								print '<td align="center"><a href="synchro_compta.php?action=accountTOndf&idTrip='.$objp->rowid.'&account='.$idAccount.'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1leftarrow.png" style="border:0px;" alt="Compte vers NDF" title="Compte vers NDF"/></a></td>';
							}
							else
							{
								print '<td align="center"><a href="synchro_compta.php?action=ndfTOaccount&idTrip='.$objp->rowid.'&account='.$idAccount.'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow.png" style="border:0px;" alt="NDF vers Compte" title="NDF vers Compte"/></a></td>';
							}

							print '<td>'.$account->label.'</td>';

							if($objp->integration_compta)
							{
								print '<td align="center"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" style="border:0px;" alt="Intégration OK" /></td>';
							}
							else
							{
								print '<td align="center"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/off.png" style="border:0px;" alt="Intégration Non OK" /></td>';
							}

						print "</tr>";
					$i++;
				endwhile;

			print "</table>";

		else:
			print '<div class="error">'.$langs->trans("AucuneTripToSynch").'</div>';
		endif;

		$db->free($resql);
	else:
		dol_print_error($db);
	endif;

endif;

dol_fiche_end();

llxFooter();

$db->close();