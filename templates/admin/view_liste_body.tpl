<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./view.php?mode=liste">
<!-- BEGIN admin_options -->
<ul class="links">
	<!-- BEGIN auth_add -->
	<li><a href="./view.php?mode=liste&amp;action=add">{admin_options.auth_add.L_ADD_LISTE}</a></li>
	<!-- END auth_add -->
	<!-- BEGIN auth_edit -->
	<li><a href="./view.php?mode=liste&amp;action=edit">{admin_options.auth_edit.L_EDIT_LISTE}</a></li>
	<!-- END auth_edit -->
	<!-- BEGIN auth_del -->
	<li><a href="./view.php?mode=liste&amp;action=delete">{admin_options.auth_del.L_DELETE_LISTE}</a></li>
	<!-- END auth_del -->
</ul>
<!-- END admin_options -->

<div class="bloc">
	<h2>{L_TITLE}</h2>
	
	<table width="100%" cellspacing="1" cellpadding="3" align="center" class="content">
		<tr>
			<td class="row1">{L_LISTE_ID}&nbsp;:</td>
			<td class="row2">{LISTE_ID}</td>
		</tr>
		<tr>
			<td class="row1">{L_LISTE_NAME}&nbsp;:</td>
			<td class="row2">{LISTE_NAME}</td>
		</tr>
		<tr>
			<td class="row1">{L_LISTE_PUBLIC}&nbsp;:</td>
			<td class="row2">{LISTE_PUBLIC}</td>
		</tr>
		<tr>
			<td class="row1">{L_AUTH_FORMAT}&nbsp;:</td>
			<td class="row2">{AUTH_FORMAT}</td>
		</tr>
		<tr>
			<td class="row1">{L_SENDER_EMAIL}&nbsp;:</td>
			<td class="row2">{SENDER_EMAIL}</td>
		</tr>
		<tr>
			<td class="row1">{L_RETURN_EMAIL}&nbsp;:</td>
			<td class="row2">{RETURN_EMAIL}</td>
		</tr>
		<tr>
			<td class="row1">{L_CONFIRM_SUBSCRIBE}&nbsp;:</td>
			<td class="row2">{CONFIRM_SUBSCRIBE}</td>
		</tr>
		<!-- BEGIN liste_confirm -->
		<tr>
			<td class="row1">{liste_confirm.L_LIMITEVALIDATE}&nbsp;:</td>
			<td class="row2">{liste_confirm.LIMITEVALIDATE}&nbsp;{liste_confirm.L_DAYS}</td>
		</tr>
		<!-- END liste_confirm -->
		<tr>
			<td class="row1">{L_NUM_SUBSCRIBERS}&nbsp;:</td>
			<td class="row2">{NUM_SUBSCRIBERS}</td>
		</tr>
		<!-- BEGIN liste_confirm -->
		<tr>
			<td class="row1">{liste_confirm.L_NUM_TEMP}&nbsp;:</td>
			<td class="row2">{liste_confirm.NUM_TEMP}</td>
		</tr>
		<!-- END liste_confirm -->
		<tr>
			<td class="row1">{L_NUM_LOGS}&nbsp;:</td>
			<td class="row2">{NUM_LOGS}</td>
		</tr>
		<!-- BEGIN date_last_log -->
		<tr>
			<td class="row1">{date_last_log.L_LAST_LOG}&nbsp;:</td>
			<td class="row2">{date_last_log.LAST_LOG}</td>
		</tr>
		<!-- END date_last_log -->
		<tr>
			<td class="row1">{L_FORM_URL}&nbsp;:</td>
			<td class="row2">{FORM_URL}</td>
		</tr>
		<tr>
			<td class="row1">{L_STARTDATE}&nbsp;:</td>
			<td class="row2">{STARTDATE}</td>
		</tr>
	</table>
	
	<!-- BEGIN purge_option -->
	<div class="bottom">{purge_option.S_HIDDEN_FIELDS}
		<input type="submit" name="purge" value="{purge_option.L_PURGE_BUTTON}" class="pbutton" />
	</div>
	<!-- END purge_option -->
</div>
</form>

{LISTBOX}
