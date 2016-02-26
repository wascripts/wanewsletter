<script>
<!--
function toggleView(evt)
{
	if (this.checked) {
		document.getElementById(this.name + '_choice').className =
			(this.value == 1) ? '' : 'inactive';
	}
}

document.addEventListener('DOMContentLoaded', function() {
	document.styleSheets[0].insertRule(
		'table.dataset tr.inactive ~ tr { display: none; }',
		document.styleSheets[0].cssRules.length-1
	);

	var configForm = document.forms['config-form'];

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
			<td><input type="text" id="date_format" name="date_format"
				value="{DATE_FORMAT}" size="15" maxlength="20" data-default="{DEFAULT_DATE_FORMAT}"
			/></td>
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
			<td><input type="number" id="session_length" name="session_length"
				value="{LENGTH_SESSION}" min="300" size="5"
			/> <span class="notice">{L_SECONDS}</span>
			</td>
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
			<td><input type="number" id="max_filesize" name="max_filesize"
				value="{MAX_FILESIZE}" size="8" maxlength="8"
			/> <span class="notice">{L_OCTETS}</span></td>
		</tr>
	</table>

	<h2>{TITLE_CONFIG_EMAIL}</h2>

	<div class="explain">{L_EXPLAIN_EMAIL}</div>

	<table class="dataset">
		<tr>
			<td><label>{L_ENGINE_SEND}&nbsp;:</label></td>
			<td>
				<input type="radio" id="engine_send_uniq" name="engine_send" value="2"{CHECKED_ENGINE_UNIQ} />
				<label for="engine_send_uniq" class="notice">{L_ENGINE_UNIQ}</label><br />
				<input type="radio" id="engine_send_bcc" name="engine_send" value="1"{CHECKED_ENGINE_BCC} />
				<label for="engine_send_bcc" class="notice">{L_ENGINE_BCC}</label>
			</td>
		</tr>
		<tr>
			<td><label for="sending_limit">{L_SENDING_LIMIT}&nbsp;:</label><br /><span class="notice">{L_SENDING_LIMIT_NOTE}</span></td>
			<td><input type="number" id="sending_limit" name="sending_limit"
				value="{SENDING_LIMIT}" min="0" size="5"
			/></td>
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
			<td><label for="smtp_port">{L_SMTP_PORT}&nbsp;:</label></td>
			<td><input type="number" id="smtp_port" name="smtp_port"
			value="{SMTP_PORT}" min="1" max="65535" size="5" {DISABLED_SMTP}
			/></td>
		</tr>
		<tr>
			<td><label for="smtp_user">{L_SMTP_USER}&nbsp;:</label></td>
			<td><input type="text" id="smtp_user" name="smtp_user" value="{SMTP_USER}" size="30" maxlength="100"{DISABLED_SMTP} /></td>
		</tr>
		<tr>
			<td><label for="smtp_pass">{L_SMTP_PASS}&nbsp;:</label><br /><span class="notice">{L_SMTP_PASS_NOTE}</span></td>
			<td><input type="password" id="smtp_pass" name="smtp_pass" size="30" maxlength="100"{DISABLED_SMTP} autocomplete="off" /></td>
		</tr>
		<!-- BEGIN tls_support -->
		<tr>
			<td><label for="smtp_tls">{tls_support.L_SECURITY}&nbsp;:</label></td>
			<td><select name="smtp_tls">
				<option value="0">{tls_support.L_NONE}</option>
				<option value="1"{tls_support.STARTTLS_SELECTED}>STARTTLS</option>
				<option value="2"{tls_support.SSL_TLS_SELECTED}>SSL/TLS</option>
			</select></td>
		</tr>
		<!-- END tls_support -->
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

	<h2>{TITLE_DEBUG_MODE}</h2>

	<div class="explain">{L_EXPLAIN_DEBUG_MODE}</div>

	<table class="dataset">
		<tr>
			<td><label>{L_DEBUG_LEVEL}&nbsp;:</label></td>
			<td>{DEBUG_BOX}</td>
		</tr>
	</table>

	<div class="bottom">{S_HIDDEN_FIELDS}
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
		<button type="reset">{L_RESET_BUTTON}</button>
	</div>
</div>
</form>
