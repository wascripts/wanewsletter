<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./config.php">
<div class="bloc">
	<h2>{TITLE_CONFIG_LANGUAGE}</h2>
	
	<table class="content">
		<tr>
			<td class="row1"><label for="language">{L_DEFAULT_LANG}&#160;:</label></td>
			<td class="row2">{LANG_BOX}</td>
		</tr>
	</table>
	
	<h2>{TITLE_CONFIG_PERSO}</h2>
	
	<table class="content">
		<tr>
			<td class="row1"><label for="sitename">{L_SITENAME}&#160;:</label></td>
			<td class="row2"><input type="text" id="sitename" name="sitename" value="{SITENAME}" size="40" maxlength="100" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="urlsite">{L_URLSITE}&#160;:</label> <span class="m-texte">{L_URLSITE_NOTE}</span></td>
			<td class="row2"><input type="text" id="urlsite" name="urlsite" value="{URLSITE}" size="40" maxlength="100" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="path">{L_URLSCRIPT}&#160;:</label> <span class="m-texte">{L_URLSCRIPT_NOTE}</span></td>
			<td class="row2"><input type="text" id="path" name="path" value="{URLSCRIPT}" size="40" maxlength="100" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="date_format">{L_DATE_FORMAT}&#160;:</label><br /><span class="m-texte">{L_NOTE_DATE}</span></td>
			<td class="row2"><input type="text" id="date_format" name="date_format" maxlength="20" size="15" value="{DATE_FORMAT}" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label>{L_ENABLE_PROFIL_CP}&#160;:</label></td>
			<td class="row2">
				<input type="radio" id="enable_profil_cp_yes" name="enable_profil_cp" value="1" {CHECKED_PROFIL_CP_ON}/>
				<label for="enable_profil_cp_yes" class="m-texte">{L_YES}</label>
				<input type="radio" id="enable_profil_cp_no" name="enable_profil_cp" value="0" {CHECKED_PROFIL_CP_OFF}/>
				<label for="enable_profil_cp_no" class="m-texte">{L_NO}</label>
			</td>
		</tr>
	</table>
	
	<h2>{TITLE_CONFIG_COOKIES}</h2>
	
	<table class="content">
		<tr>
			<td colspan="2" class="explain">{L_EXPLAIN_COOKIES}</td>
		</tr>
		<tr>
			<td class="row1"><label for="cookie_name">{L_COOKIE_NAME}&#160;:</label></td>
			<td class="row2"><input type="text" id="cookie_name" name="cookie_name" value="{COOKIE_NAME}" size="30" maxlength="100" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="cookie_path">{L_COOKIE_PATH}&#160;:</label></td>
			<td class="row2"><input type="text" id="cookie_path" name="cookie_path" value="{COOKIE_PATH}" size="30" maxlength="100" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="session_length">{L_LENGTH_SESSION}&#160;:</label></td>
			<td class="row2"><input type="text" id="session_length" name="session_length" value="{LENGTH_SESSION}" size="5" maxlength="5" class="text" /> <span class="m-texte">{L_SECONDS}</span></td>
		</tr>
	</table>
	
	<h2>{TITLE_CONFIG_JOINED_FILES}</h2>
	
	<table class="content">
		<tr>
			<td colspan="2" class="explain">{L_EXPLAIN_JOINED_FILES}</td>
		</tr>
		<tr>
			<td class="row1"><label for="upload_path">{L_UPLOAD_PATH}&#160;:</label></td>
			<td class="row2"><input type="text" id="upload_path" name="upload_path" value="{UPLOAD_PATH}" size="40" maxlength="100" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="max_filesize">{L_MAX_FILESIZE}&#160;:</label><br /><span class="m-texte">{L_MAX_FILESIZE_NOTE}</span></td>
			<td class="row2"><input type="text" id="max_filesize" name="max_filesize" value="{MAX_FILESIZE}" size="7" maxlength="8" class="text" /> <span class="m-texte">{L_OCTETS}</span></td>
		</tr>
		<!-- BEGIN extension_ftp -->
		<tr>
			<td class="row1"><label>{extension_ftp.L_USE_FTP}&#160;:</label></td>
			<td class="row2">
				<input type="radio" id="use_ftp_yes" name="use_ftp" value="1" {extension_ftp.CHECKED_USE_FTP_ON}/>
				<label for="use_ftp_yes" class="m-texte">{L_YES}</label>
				<input type="radio" id="use_ftp_no" name="use_ftp" value="0" {extension_ftp.CHECKED_USE_FTP_OFF}/>
				<label for="use_ftp_no" class="m-texte">{L_NO}</label>
			</td>
		</tr>
		<tr>
			<td class="row1"><label for="ftp_server">{extension_ftp.L_FTP_SERVER}&#160;:</label><br /><span class="m-texte">{L_FTP_SERVER_NOTE}</span></td>
			<td class="row2"><input type="text" id="ftp_server" name="ftp_server" value="{extension_ftp.FTP_SERVER}" size="30" maxlength="50" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="ftp_port">{extension_ftp.L_FTP_PORT}&#160;:</label><br /><span class="m-texte">{L_FTP_PORT_NOTE}</span></td>
			<td class="row2"><input type="text" id="ftp_port" name="ftp_port" value="{extension_ftp.FTP_PORT}" maxlength="5" class="text" style="width: 40px" /></td>
		</tr>
		<tr>
			<td class="row1"><label>{extension_ftp.L_FTP_PASV}&#160;:</label><br /><span class="m-texte">{extension_ftp.L_FTP_PASV_NOTE}</span></td>
			<td class="row2">
				<input type="radio" id="ftp_pasv_on" name="ftp_pasv" value="1" {extension_ftp.CHECKED_FTP_PASV_ON}/>
				<label for="ftp_pasv_on" class="m-texte">{L_YES}</label>
				<input type="radio" id="ftp_pasv_off" name="ftp_pasv" value="0" {extension_ftp.CHECKED_FTP_PASV_OFF}/>
				<label for="ftp_pasv_off" class="m-texte">{L_NO}</label>
			</td>
		</tr>
		<tr>
			<td class="row1"><label for="ftp_path">{extension_ftp.L_FTP_PATH}&#160;:</label></td>
			<td class="row2"><input type="text" id="ftp_path" name="ftp_path" value="{extension_ftp.FTP_PATH}" size="30" maxlength="100" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="ftp_user">{extension_ftp.L_FTP_USER}&#160;:</label><br /><span class="m-texte">{extension_ftp.L_FTP_USER_NOTE}</span></td>
			<td class="row2"><input type="text" id="ftp_user" name="ftp_user" value="{extension_ftp.FTP_USER}" size="30" maxlength="30" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="ftp_pass">{extension_ftp.L_FTP_PASS}&#160;:</label><br /><span class="m-texte">{extension_ftp.L_FTP_PASS_NOTE}</span></td>
			<td class="row2"><input type="password" id="ftp_pass" name="ftp_pass" value="{extension_ftp.FTP_PASS}" size="30" maxlength="30" class="text" /></td>
		</tr>
		<!-- END extension_ftp -->
	</table>
	
	<h2>{TITLE_CONFIG_EMAIL}</h2>
	
	<table class="content">
		<tr>
			<td colspan="2" class="explain">{L_EXPLAIN_EMAIL}</td>
		</tr>
		<tr style="display: none;">
			<td class="row1"><label>{L_CHECK_EMAIL}&#160;:</label><br /><span class="m-texte">{L_CHECK_EMAIL_NOTE}</span></td>
			<td class="row2">
				<input type="radio" id="check_email_mx_on" name="check_email_mx" value="1"{CHECKED_CHECK_EMAIL_ON} />
				<label for="check_email_mx_on" class="m-texte">{L_YES}</label>
				<input type="radio" id="check_email_mx_off" name="check_email_mx" value="0"{CHECKED_CHECK_EMAIL_OFF} />
				<label for="check_email_mx_off" class="m-texte">{L_NO}</label>
			</td>
		</tr>
		<!-- BEGIN choice_engine_send -->
		<tr>
			<td class="row1"><label>{choice_engine_send.L_ENGINE_SEND}&#160;:</label></td>
			<td class="row2">
				<input type="radio" id="engine_send_bcc" name="engine_send" value="1"{choice_engine_send.CHECKED_ENGINE_BCC} />
				<label for="engine_send_bcc" class="m-texte">{choice_engine_send.L_ENGINE_BCC}</label><br />
				<input type="radio" id="engine_send_uniq" name="engine_send" value="2"{choice_engine_send.CHECKED_ENGINE_UNIQ} />
				<label for="engine_send_uniq" class="m-texte">{choice_engine_send.L_ENGINE_UNIQ}</label>
			</td>
		</tr>
		<!-- END choice_engine_send -->
		<tr>
			<td class="row1"><label for="emails_sended">{L_EMAILS_SENDED}&#160;:</label><br /><span class="m-texte">{L_EMAILS_SENDED_NOTE}</span></td>
			<td class="row2"><input type="text" id="emails_sended" name="emails_sended" value="{EMAILS_SENDED}" size="5" maxlength="5" class="text" style="width:30px" /></td>
		</tr>
		<tr>
			<td class="row1"><label>{L_USE_SMTP}&#160;:{WARNING_SMTP}</label><br /><span class="m-texte">{L_USE_SMTP_NOTE}</span></td>
			<td class="row2">
				<input type="radio" id="use_smtp_on" name="use_smtp" value="1"{CHECKED_USE_SMTP_ON}{DISABLED_SMTP} />
				<label for="use_smtp_on" class="m-texte">{L_YES}</label>
				<input type="radio" id="use_smtp_off" name="use_smtp" value="0"{CHECKED_USE_SMTP_OFF} />
				<label for="use_smtp_off" class="m-texte">{L_NO}</label>
			</td>
		</tr>
		<tr>
			<td class="row1"><label for="smtp_host">{L_SMTP_SERVER}&#160;:</label></td>
			<td class="row2"><input type="text" id="smtp_host" name="smtp_host" value="{SMTP_HOST}" size="30" maxlength="100" class="text"{DISABLED_SMTP} /></td>
		</tr>
		<tr>
			<td class="row1"><label for="smtp_port">{L_SMTP_PORT}&#160;:</label><br /><span class="m-texte">{L_SMTP_PORT_NOTE}</span></td>
			<td class="row2"><input type="text" id="smtp_port" name="smtp_port" maxlength="5" value="{SMTP_PORT}" class="text" style="width: 40px"{DISABLED_SMTP} /></td>
		</tr>
		<tr>
			<td class="row1"><label for="smtp_user">{L_SMTP_USER}&#160;:</label><br /><span class="m-texte">{L_AUTH_SMTP_NOTE}</span></td>
			<td class="row2"><input type="text" id="smtp_user" name="smtp_user" value="{SMTP_USER}" size="30" maxlength="50" class="text"{DISABLED_SMTP} /></td>
		</tr>
		<tr>
			<td class="row1"><label for="smtp_pass">{L_SMTP_PASS}&#160;:</label><br /><span class="m-texte">{L_AUTH_SMTP_NOTE}</span></td>
			<td class="row2"><input type="password" id="smtp_pass" name="smtp_pass" value="{SMTP_PASS}" size="30" maxlength="50" class="text"{DISABLED_SMTP} /></td>
		</tr>
	</table>
	
	<!-- BEGIN extension_gd -->
	<h2>{extension_gd.TITLE_CONFIG_STATS}</h2>
	
	<table class="content">
		<tr>
			<td colspan="2" class="explain">{extension_gd.L_EXPLAIN_STATS}</td>
		</tr>
		<tr>
			<td class="row1"><label>{extension_gd.L_DISABLE_STATS}&#160;:</label></td>
			<td class="row2">
				<input type="radio" id="disable_stats_off" name="disable_stats" value="0" {extension_gd.CHECKED_DISABLE_STATS_OFF}/>
				<label for="disable_stats_off" class="m-texte">{L_NO}</label>
				<input type="radio" id="disable_stats_on" name="disable_stats" value="1" {extension_gd.CHECKED_DISABLE_STATS_ON}/>
				<label for="disable_stats_on" class="m-texte">{L_YES}</label>
			</td>
		</tr>
		<tr>
			<td class="row1"><label for="gd_img_type">{extension_gd.L_GD_VERSION}&#160;:</label></td>
			<td class="row2">
				<select id="gd_img_type" name="gd_img_type">
					<option value="png"{extension_gd.SELECTED_GD_PNG}> - Lib gd &gt;= 1.6 - </option>
					<option value="gif"{extension_gd.SELECTED_GD_GIF}> - Lib gd &lt; 1.6 - </option>
				</select>
			</td>
		</tr>
	</table>
	<!-- END extension_gd -->
	
	<div class="bottom">{S_HIDDEN_FIELDS}
		<input type="submit" name="submit" class="pbutton" value="{L_VALID_BUTTON}" />
		<input type="reset" value="{L_RESET_BUTTON}" class="button" />
	</div>
</div>
</form>
