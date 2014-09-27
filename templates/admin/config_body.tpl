<script>
<!--
function toggleView(evt)
{
	if( this.checked == true ) {
		document.getElementById(this.name + '_choice').className =
			( this.value == 1 ) ? '' : 'inactive';
	}
}

document.addEventListener('DOMContentLoaded', function() {
	document.styleSheets[0].insertRule(
		'table.dataset tr.inactive ~ tr { display: none; }',
		document.styleSheets[0].cssRules.length-1
	);
	
	var configForm = document.forms['config-form'];
	
	configForm.elements['use_ftp'][0].addEventListener('change', toggleView, false);
	configForm.elements['use_ftp'][1].addEventListener('change', toggleView, false);
	
	configForm.elements['use_smtp'][0].addEventListener('change', toggleView, false);
	configForm.elements['use_smtp'][1].addEventListener('change', toggleView, false);
}, false);
//-->
</script>

<p id="explain">{L_EXPLAIN}</p>

<form id="config-form" method="post" action="./config.php">
<div class="block">
	<h2>{TITLE_CONFIG_LANGUAGE}</h2>
	
	<table class="dataset">
		<tr>
			<td><label for="language">{L_DEFAULT_LANG}&nbsp;:</label></td>
			<td>{LANG_BOX}</td>
		</tr>
	</table>
	
	<h2>{TITLE_CONFIG_PERSO}</h2>
	
	<table class="dataset">
		<tr>
			<td><label for="sitename">{L_SITENAME}&nbsp;:</label></td>
			<td><input type="text" id="sitename" name="sitename" value="{SITENAME}" size="40" maxlength="100" /></td>
		</tr>
		<tr>
			<td><label for="urlsite">{L_URLSITE}&nbsp;:</label> <span class="notice">{L_URLSITE_NOTE}</span></td>
			<td><input type="text" id="urlsite" name="urlsite" value="{URLSITE}" size="40" maxlength="100" /></td>
		</tr>
		<tr>
			<td><label for="path">{L_URLSCRIPT}&nbsp;:</label> <span class="notice">{L_URLSCRIPT_NOTE}</span></td>
			<td><input type="text" id="path" name="path" value="{URLSCRIPT}" size="40" maxlength="100" /></td>
		</tr>
		<tr>
			<td><label for="date_format">{L_DATE_FORMAT}&nbsp;:</label><br /><span class="notice">{L_NOTE_DATE}</span></td>
			<td><input type="text" id="date_format" name="date_format" maxlength="20" size="15" value="{DATE_FORMAT}" /></td>
		</tr>
		<tr>
			<td><label>{L_ENABLE_PROFIL_CP}&nbsp;:</label></td>
			<td>
				<input type="radio" id="enable_profil_cp_yes" name="enable_profil_cp" value="1" {CHECKED_PROFIL_CP_ON}/>
				<label for="enable_profil_cp_yes" class="notice">{L_YES}</label>
				<input type="radio" id="enable_profil_cp_no" name="enable_profil_cp" value="0" {CHECKED_PROFIL_CP_OFF}/>
				<label for="enable_profil_cp_no" class="notice">{L_NO}</label>
			</td>
		</tr>
	</table>
	
	<h2>{TITLE_CONFIG_COOKIES}</h2>
	
	<div class="explain">{L_EXPLAIN_COOKIES}</div>
	
	<table class="dataset">
		<tr>
			<td><label for="cookie_name">{L_COOKIE_NAME}&nbsp;:</label></td>
			<td><input type="text" id="cookie_name" name="cookie_name" value="{COOKIE_NAME}" size="30" maxlength="100" /></td>
		</tr>
		<tr>
			<td><label for="cookie_path">{L_COOKIE_PATH}&nbsp;:</label></td>
			<td><input type="text" id="cookie_path" name="cookie_path" value="{COOKIE_PATH}" size="30" maxlength="100" /></td>
		</tr>
		<tr>
			<td><label for="session_length">{L_LENGTH_SESSION}&nbsp;:</label></td>
			<td><input type="text" id="session_length" name="session_length" value="{LENGTH_SESSION}" size="5" maxlength="5" /> <span class="notice">{L_SECONDS}</span></td>
		</tr>
	</table>
	
	<h2>{TITLE_CONFIG_JOINED_FILES}</h2>
	
	<div class="explain">{L_EXPLAIN_JOINED_FILES}</div>
	
	<table class="dataset">
		<tr>
			<td><label for="upload_path">{L_UPLOAD_PATH}&nbsp;:</label></td>
			<td><input type="text" id="upload_path" name="upload_path" value="{UPLOAD_PATH}" size="40" maxlength="100" /></td>
		</tr>
		<tr>
			<td><label for="max_filesize">{L_MAX_FILESIZE}&nbsp;:</label><br /><span class="notice">{L_MAX_FILESIZE_NOTE}</span></td>
			<td><input type="text" id="max_filesize" name="max_filesize" value="{MAX_FILESIZE}" size="7" maxlength="8" /> <span class="notice">{L_OCTETS}</span></td>
		</tr>
		<!-- BEGIN extension_ftp -->
		<tr id="use_ftp_choice" class="{extension_ftp.FTP_ROW_CLASS}">
			<td><label>{extension_ftp.L_USE_FTP}&nbsp;:</label></td>
			<td>
				<input type="radio" id="use_ftp_yes" name="use_ftp" value="1" {extension_ftp.CHECKED_USE_FTP_ON}/>
				<label for="use_ftp_yes" class="notice">{L_YES}</label>
				<input type="radio" id="use_ftp_no" name="use_ftp" value="0" {extension_ftp.CHECKED_USE_FTP_OFF}/>
				<label for="use_ftp_no" class="notice">{L_NO}</label>
			</td>
		</tr>
		<tr>
			<td><label for="ftp_server">{extension_ftp.L_FTP_SERVER}&nbsp;:</label><br /><span class="notice">{L_FTP_SERVER_NOTE}</span></td>
			<td><input type="text" id="ftp_server" name="ftp_server" value="{extension_ftp.FTP_SERVER}" size="30" maxlength="100" /></td>
		</tr>
		<tr>
			<td><label for="ftp_port">{extension_ftp.L_FTP_PORT}&nbsp;:</label><br /><span class="notice">{L_FTP_PORT_NOTE}</span></td>
			<td><input type="text" id="ftp_port" name="ftp_port" value="{extension_ftp.FTP_PORT}" maxlength="5" class="number" /></td>
		</tr>
		<tr>
			<td><label>{extension_ftp.L_FTP_PASV}&nbsp;:</label><br /><span class="notice">{extension_ftp.L_FTP_PASV_NOTE}</span></td>
			<td>
				<input type="radio" id="ftp_pasv_on" name="ftp_pasv" value="1" {extension_ftp.CHECKED_FTP_PASV_ON}/>
				<label for="ftp_pasv_on" class="notice">{L_YES}</label>
				<input type="radio" id="ftp_pasv_off" name="ftp_pasv" value="0" {extension_ftp.CHECKED_FTP_PASV_OFF}/>
				<label for="ftp_pasv_off" class="notice">{L_NO}</label>
			</td>
		</tr>
		<tr>
			<td><label for="ftp_path">{extension_ftp.L_FTP_PATH}&nbsp;:</label></td>
			<td><input type="text" id="ftp_path" name="ftp_path" value="{extension_ftp.FTP_PATH}" size="30" maxlength="100" /></td>
		</tr>
		<tr>
			<td><label for="ftp_user">{extension_ftp.L_FTP_USER}&nbsp;:</label><br /><span class="notice">{extension_ftp.L_FTP_USER_NOTE}</span></td>
			<td><input type="text" id="ftp_user" name="ftp_user" value="{extension_ftp.FTP_USER}" size="30" maxlength="100" /></td>
		</tr>
		<tr>
			<td><label for="ftp_pass">{extension_ftp.L_FTP_PASS}&nbsp;:</label><br /><span class="notice">{extension_ftp.L_FTP_PASS_NOTE}</span></td>
			<td><input type="password" id="ftp_pass" name="ftp_pass" size="30" maxlength="100" autocomplete="off" /></td>
		</tr>
		<!-- END extension_ftp -->
	</table>
	
	<h2>{TITLE_CONFIG_EMAIL}</h2>
	
	<div class="explain">{L_EXPLAIN_EMAIL}</div>
	
	<table class="dataset">
		<tr>
			<td><label>{L_CHECK_EMAIL}&nbsp;:</label><br /><span class="notice">{L_CHECK_EMAIL_NOTE}</span></td>
			<td>
				<input type="radio" id="check_email_mx_on" name="check_email_mx" value="1"{CHECKED_CHECK_EMAIL_ON} />
				<label for="check_email_mx_on" class="notice">{L_YES}</label>
				<input type="radio" id="check_email_mx_off" name="check_email_mx" value="0"{CHECKED_CHECK_EMAIL_OFF} />
				<label for="check_email_mx_off" class="notice">{L_NO}</label>
			</td>
		</tr>
		<!-- BEGIN choice_engine_send -->
		<tr>
			<td><label>{choice_engine_send.L_ENGINE_SEND}&nbsp;:</label></td>
			<td>
				<input type="radio" id="engine_send_uniq" name="engine_send" value="2"{choice_engine_send.CHECKED_ENGINE_UNIQ} />
				<label for="engine_send_uniq" class="notice">{choice_engine_send.L_ENGINE_UNIQ}</label><br />
				<input type="radio" id="engine_send_bcc" name="engine_send" value="1"{choice_engine_send.CHECKED_ENGINE_BCC} />
				<label for="engine_send_bcc" class="notice">{choice_engine_send.L_ENGINE_BCC}</label>
			</td>
		</tr>
		<!-- END choice_engine_send -->
		<tr>
			<td><label for="emails_sended">{L_EMAILS_SENDED}&nbsp;:</label><br /><span class="notice">{L_EMAILS_SENDED_NOTE}</span></td>
			<td><input type="text" id="emails_sended" name="emails_sended" value="{EMAILS_SENDED}" size="5" maxlength="5" class="number" /></td>
		</tr>
		<tr id="use_smtp_choice" class="{SMTP_ROW_CLASS}">
			<td><label>{L_USE_SMTP}&nbsp;:{WARNING_SMTP}</label><br /><span class="notice">{L_USE_SMTP_NOTE}</span></td>
			<td>
				<input type="radio" id="use_smtp_on" name="use_smtp" value="1"{CHECKED_USE_SMTP_ON}{DISABLED_SMTP} />
				<label for="use_smtp_on" class="notice">{L_YES}</label>
				<input type="radio" id="use_smtp_off" name="use_smtp" value="0"{CHECKED_USE_SMTP_OFF} />
				<label for="use_smtp_off" class="notice">{L_NO}</label>
			</td>
		</tr>
		<tr>
			<td><label for="smtp_host">{L_SMTP_SERVER}&nbsp;:</label></td>
			<td><input type="text" id="smtp_host" name="smtp_host" value="{SMTP_HOST}" size="30" maxlength="100"{DISABLED_SMTP} /></td>
		</tr>
		<tr>
			<td><label for="smtp_port">{L_SMTP_PORT}&nbsp;:</label><br /><span class="notice">{L_SMTP_PORT_NOTE}</span></td>
			<td><input type="text" id="smtp_port" name="smtp_port" maxlength="5" value="{SMTP_PORT}" class="number"{DISABLED_SMTP} /></td>
		</tr>
		<tr>
			<td><label for="smtp_user">{L_SMTP_USER}&nbsp;:</label><br /><span class="notice">{L_AUTH_SMTP_NOTE}</span></td>
			<td><input type="text" id="smtp_user" name="smtp_user" value="{SMTP_USER}" size="30" maxlength="100"{DISABLED_SMTP} /></td>
		</tr>
		<tr>
			<td><label for="smtp_pass">{L_SMTP_PASS}&nbsp;:</label><br /><span class="notice">{L_AUTH_SMTP_NOTE}</span></td>
			<td><input type="password" id="smtp_pass" name="smtp_pass" size="30" maxlength="100"{DISABLED_SMTP} autocomplete="off" /></td>
		</tr>
	</table>
	
	<!-- BEGIN extension_gd -->
	<h2>{extension_gd.TITLE_CONFIG_STATS}</h2>
	
	<div class="explain">{extension_gd.L_EXPLAIN_STATS}</div>
	
	<table class="dataset">
		<tr>
			<td><label>{extension_gd.L_DISABLE_STATS}&nbsp;:</label></td>
			<td>
				<input type="radio" id="disable_stats_off" name="disable_stats" value="0" {extension_gd.CHECKED_DISABLE_STATS_OFF}/>
				<label for="disable_stats_off" class="notice">{L_NO}</label>
				<input type="radio" id="disable_stats_on" name="disable_stats" value="1" {extension_gd.CHECKED_DISABLE_STATS_ON}/>
				<label for="disable_stats_on" class="notice">{L_YES}</label>
			</td>
		</tr>
	</table>
	<!-- END extension_gd -->
	
	<div class="bottom">{S_HIDDEN_FIELDS}
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
		<button type="reset">{L_RESET_BUTTON}</button>
	</div>
</div>
</form>
