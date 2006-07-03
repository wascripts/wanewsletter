<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./view.php?mode=liste">
<div class="bloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="row1"><label for="liste_name">{L_LISTE_NAME}&#160;:</label></td>
			<td class="row2"><input type="text" id="liste_name" name="liste_name" value="{LISTE_NAME}" size="40" maxlength="100" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label>{L_LISTE_PUBLIC}&#160;:</label></td>
			<td class="row2">
				<input type="radio" id="liste_public_yes" name="liste_public" value="1"{CHECK_PUBLIC_YES} />
				<label for="liste_public_yes" class="m-texte">{L_YES}</label>
				<input type="radio" id="liste_public_no" name="liste_public" value="0"{CHECK_PUBLIC_NO} />
				<label for="liste_public_no" class="m-texte">{L_NO}</label>
			</td>					   
		</tr>
		<tr>
			<td class="row1"><label for="liste_format">{L_AUTH_FORMAT}&#160;:</label></td>
			<td class="row2">{FORMAT_BOX}</td>					   
		</tr>
		<tr>
			<td class="row1"><label for="sender_email">{L_SENDER_EMAIL}&#160;:</label></td>
			<td class="row2"><input type="text" id="sender_email" name="sender_email" value="{SENDER_EMAIL}" size="40" maxlength="200" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="return_email">{L_RETURN_EMAIL}&#160;:</label></td>
			<td class="row2"><input type="text" id="return_email" name="return_email" value="{RETURN_EMAIL}" size="40" maxlength="200" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="form_url">{L_FORM_URL}&#160;:</label></td>
			<td class="row2"><input type="text" id="form_url" name="form_url" value="{FORM_URL}" size="40" maxlength="250" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label>{L_CONFIRM_SUBSCRIBE}&#160;:</label></td>
			<td class="row2">
				<input type="radio" id="confirm_always" name="confirm_subscribe" value="2"{CHECK_CONFIRM_ALWAYS} />
				<label for="confirm_always" class="m-texte">{L_CONFIRM_ALWAYS}</label>
				<input type="radio" id="confirm_once" name="confirm_subscribe" value="1"{CHECK_CONFIRM_ONCE} />
				<label for="confirm_once" class="m-texte">{L_CONFIRM_ONCE}</label>
				<input type="radio" id="confirm_no" name="confirm_subscribe" value="0"{CHECK_CONFIRM_NO} />
				<label for="confirm_no" class="m-texte">{L_NO}</label>
			</td>
		</tr>
		<tr>
			<td class="row1"><label for="limitevalidate">{L_LIMITEVALIDATE}&#160;:</label><br /><span class="m-texte">{L_NOTE_VALIDATE}</span></td>
			<td class="row2"><input type="text" id="limitevalidate" name="limitevalidate" value="{LIMITEVALIDATE}" size="5" maxlength="3" class="text" style="width: 30px;" /> <span class="m-texte">{L_DAYS}</span></td>
		</tr>
		<tr>
			<td class="row1"><label for="liste_sig">{L_SIG_EMAIL}&#160;:</label><br /><span class="m-texte">{L_SIG_EMAIL_NOTE}</span></td>
			<td class="row2"><textarea id="liste_sig" name="liste_sig" rows="3" cols="35">{SIG_EMAIL}</textarea></td>
		</tr>
	</table>
	
	<h2>{L_TITLE_PURGE}</h2>
	
	<table class="content">
		<tr>
			<td class="explain" colspan="2">{L_EXPLAIN_PURGE}</td>
		</tr>
		<tr>
			<td class="row1"><label>{L_ENABLE_PURGE}&#160;:</label></td>
			<td class="row2">
				<input type="radio" id="auto_purge_on" name="auto_purge" value="1"{CHECKED_PURGE_ON} />
				<label for="auto_purge_on" class="m-texte">{L_YES}</label>
				<input type="radio" id="auto_purge_off" name="auto_purge" value="0"{CHECKED_PURGE_OFF} />
				<label for="auto_purge_off" class="m-texte">{L_NO}</label>
			</td>
		</tr>
		<tr>
			<td class="row1"><label for="purge_freq">{L_PURGE_FREQ}&#160;:</label></td>
			<td class="row2"><input type="text" id="purge_freq" name="purge_freq" value="{PURGE_FREQ}" size="5" maxlength="3" class="text" style="width: 30px;" /> <span class="m-texte">{L_DAYS}</span></td>
		</tr>
	</table>
	
	<h2>{L_TITLE_CRON}</h2>
	
	<table class="content">
		<tr>
			<td class="explain" colspan="2">{L_EXPLAIN_CRON}</td>
		</tr>
		<tr>
			<td class="row1"><label>{L_USE_CRON}&#160;:{WARNING_CRON}</label></td>
			<td class="row2">
				<input type="radio" id="use_cron_on" name="use_cron" value="1"{CHECKED_USE_CRON_ON}{DISABLED_CRON} />
				<label for="use_cron_on" class="m-texte">{L_YES}</label>
				<input type="radio" id="use_cron_off" name="use_cron" value="0"{CHECKED_USE_CRON_OFF} />
				<label for="use_cron_off" class="m-texte">{L_NO}</label>
			</td>
		</tr>
		<tr>
			<td class="row1"><label for="pop_host">{L_POP_SERVER}&#160;:</label></td>
			<td class="row2"><input type="text" id="pop_host" name="pop_host" value="{POP_HOST}" size="30" maxlength="100" class="text"{DISABLED_CRON} /></td>
		</tr>
		<tr>
			<td class="row1"><label for="pop_port">{L_POP_PORT}&#160;:</label><br /><span class="m-texte">{L_POP_PORT_NOTE}</span></td>
			<td class="row2"><input type="text" id="pop_port" name="pop_port" maxlength="5" value="{POP_PORT}" class="text" style="width: 40px"{DISABLED_CRON} /></td>
		</tr>
		<tr>
			<td class="row1"><label for="pop_user">{L_POP_USER}&#160;:</label></td>
			<td class="row2"><input type="text" id="pop_user" name="pop_user" value="{POP_USER}" size="30" maxlength="50" class="text"{DISABLED_CRON} /></td>
		</tr>
		<tr>
			<td class="row1"><label for="pop_pass">{L_POP_PASS}&#160;:</label></td>
			<td class="row2"><input type="password" id="pop_pass" name="pop_pass" size="30" maxlength="50" class="text"{DISABLED_CRON} /></td>
		</tr>
		<tr>
			<td class="row1"><label for="liste_alias">{L_LISTE_ALIAS}&#160;:</label></td>
			<td class="row2"><input type="text" id="liste_alias" name="liste_alias" value="{LISTE_ALIAS}" size="40" maxlength="200" class="text"{DISABLED_CRON} /></td>
		</tr>
	</table>
	
	<div class="bottom">{S_HIDDEN_FIELDS}
		<input type="submit" name="submit" class="pbutton" value="{L_VALID_BUTTON}" />
		<input type="submit" name="cancel" class="button" value="{L_CANCEL_BUTTON}" />
	</div>
</div>
</form>
