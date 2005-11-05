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
			<td class="row1"><span class="texte">{L_LISTE_ID}&#160;:</span></td>
			<td class="row2"><span class="texte">{LISTE_ID}</span></td>
		</tr>
		<tr>
			<td class="row1"><span class="texte">{L_LISTE_NAME}&#160;:</span></td>
			<td class="row2"><span class="texte">{LISTE_NAME}</span></td>
		</tr>
		<tr>
			<td class="row1"><span class="texte">{L_AUTH_FORMAT}&#160;:</span></td>
			<td class="row2"><span class="texte">{AUTH_FORMAT}</span></td>
		</tr>
		<tr>
			<td class="row1"><span class="texte">{L_SENDER_EMAIL}&#160;:</span></td>
			<td class="row2"><span class="texte">{SENDER_EMAIL}</span></td>
		</tr>
		<tr>
			<td class="row1"><span class="texte">{L_RETURN_EMAIL}&#160;:</span></td>
			<td class="row2"><span class="texte">{RETURN_EMAIL}</span></td>
		</tr>
		<tr>
			<td class="row1"><span class="texte">{L_CONFIRM_SUBSCRIBE}&#160;:</span></td>
			<td class="row2"><span class="texte">{CONFIRM_SUBSCRIBE}</span></td>
		</tr>
		<!-- BEGIN liste_confirm -->
		<tr>
			<td class="row1"><span class="texte">{liste_confirm.L_LIMITEVALIDATE}&#160;:</span></td>
			<td class="row2"><span class="texte">{liste_confirm.LIMITEVALIDATE} {liste_confirm.L_DAYS}</span></td>
		</tr>
		<!-- END liste_confirm -->
		<tr>
			<td class="row1"><span class="texte">{L_NUM_SUBSCRIBERS}&#160;:</span></td>
			<td class="row2"><span class="texte">{NUM_SUBSCRIBERS}</span></td>
		</tr>
		<!-- BEGIN liste_confirm -->
		<tr>
			<td class="row1"><span class="texte">{liste_confirm.L_NUM_TEMP}&#160;:</span></td>
			<td class="row2"><span class="texte">{liste_confirm.NUM_TEMP}</span></td>
		</tr>
		<!-- END liste_confirm -->
		<tr>
			<td class="row1"><span class="texte">{L_NUM_LOGS}&#160;:</span></td>
			<td class="row2"><span class="texte">{NUM_LOGS}</span></td>
		</tr>
		<!-- BEGIN date_last_log -->
		<tr>
			<td class="row1"><span class="texte">{date_last_log.L_LAST_LOG}&#160;:</span></td>
			<td class="row2"><span class="texte">{date_last_log.LAST_LOG}</span></td>
		</tr>
		<!-- END date_last_log -->
		<tr>
			<td class="row1"><span class="texte">{L_FORM_URL}&#160;:</span></td>
			<td class="row2"><span class="texte">{FORM_URL}</span></td>
		</tr>
		<tr>
			<td class="row1"><span class="texte">{L_STARTDATE}&#160;:</span></td>
			<td class="row2"><span class="texte">{STARTDATE}</span></td>
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
