<script type="text/javascript">
<!--
var lang = [];
lang["preview"] = '{L_PREVIEW_BUTTON}';
lang["addlink"] = '{L_ADDLINK_BUTTON}';
//-->
</script>

<p id="explain">{L_EXPLAIN}</p>

<form id="send-form" method="post" action="./envoi.php" enctype="{S_ENCTYPE}">
<ul class="links">
	<li><a href="./envoi.php?mode=load">{L_LOAD_LOG}</a></li>
	<li><a href="./envoi.php?mode=progress">{L_LIST_SEND}</a></li>
</ul>

<div class="bloc">
	<table class="content">
		<tr>
			<td class="medrow1">{L_DEST}&nbsp;:</td>
			<td class="row2"><b>{S_DEST}</b></td>
		</tr>
		<tr>
			<td class="medrow1"><label for="subject">{L_SUBJECT}&nbsp;:</label></td>
			<td class="row2"><input type="text" id="subject" name="subject" value="{S_SUBJECT}" size="40" maxlength="100" class="text" /></td>
		</tr>
		<tr>
			<td class="medrow1"><label for="log_status">{L_STATUS}&nbsp;:</label></td>
			<td class="row2">
				<select id="log_status" name="log_status">
					<option value="0"{SELECTED_STATUS_WRITING}>{L_STATUS_WRITING}</option>
					<option value="3"{SELECTED_STATUS_MODEL}>{L_STATUS_MODEL}</option>
				</select>
			</td>
		</tr>
		<tr title="{L_CC_ADMIN_TITLE}">
			<td class="medrow1"><label for="cc_admin">{L_CC_ADMIN}&nbsp;:</label></td>
			<td class="row2">
				<select id="cc_admin" name="cc_admin">
					<option value="1"{SELECTED_CC_ADMIN_ON}>{L_YES}</option>
					<option value="0"{SELECTED_CC_ADMIN_OFF}>{L_NO}</option>
				</select>
			</td>
		</tr>
		<!-- BEGIN test_send -->
		<tr>
			<td colspan="2" class="explain">{test_send.L_TEST_SEND_NOTE}</td>
		</tr>
		<tr>
			<td class="medrow1"><label for="test_address">{test_send.L_TEST_SEND}&nbsp;:</label></td>
			<td class="row2">
				<input type="text" id="test_address" name="test_address" size="40" class="text" />
				<input type="submit" name="test" value="{test_send.L_SEND_BUTTON}" class="button" />
			</td>
		</tr>
		<!-- END test_send -->
	</table>
</div>
<!-- BEGIN last_modified -->
<div id="nav-bottom" class="last-modified m-texte">{last_modified.S_LAST_MODIFIED}</div>
<!-- END last_modified -->

<!-- BEGIN nl_text_textarea -->
<div class="bloc" id="textarea1">
	<h2>{nl_text_textarea.L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="explain">{nl_text_textarea.L_EXPLAIN}</td>
		</tr>
		<tr>
			<td class="row-full">
				<textarea name="body_text" cols="90" rows="20" class="text">{nl_text_textarea.S_BODY}</textarea>
			</td>
		</tr>
	</table>
</div>
<!-- END nl_text_textarea -->

<!-- BEGIN nl_html_textarea -->
<div class="bloc" id="textarea2">
	<h2>{nl_html_textarea.L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="explain">{nl_html_textarea.L_EXPLAIN}</td>
		</tr>
		<tr>
			<td class="row-full">
				<textarea name="body_html" cols="90" rows="20" class="text">{nl_html_textarea.S_BODY}</textarea>
			</td>
		</tr>
	</table>
</div>
<!-- END nl_html_textarea -->

<!-- BEGIN joined_files -->
<div class="bloc">
	<h2>{joined_files.L_TITLE_ADD_FILE}</h2>
	
	<table class="content">
		<tr>
			<td class="explain" colspan="2">{joined_files.L_EXPLAIN_ADD_FILE}</td>
		</tr>
		<tr>
			<td rowspan="{joined_files.S_ROWSPAN}" class="medrow1"><label for="join_file">{joined_files.L_ADD_FILE}&nbsp;:</label></td>
			<td class="row2"><input type="text" id="join_file" name="join_file" size="30" class="text" /></td>
		</tr>
		<!-- BEGIN upload_input -->
		<tr>
			<td class="row2">
				<input type="hidden" name="MAX_FILE_SIZE" value="{joined_files.upload_input.MAX_FILE_SIZE}" />
				<input type="file" name="join_file" size="30" />
				<span class="m-texte">({joined_files.upload_input.L_MAXIMUM_SIZE})</span>
			</td>
		</tr>
		<!-- END upload_input -->
		<!-- BEGIN select_box -->
		<tr>
			<td class="row2">{joined_files.select_box.SELECT_BOX}</td>
		</tr>
		<!-- END select_box -->
		<tr>
			<td class="row2"><input type="submit" name="attach" value="{joined_files.L_ADD_FILE_BUTTON}" class="button" /></td>
		</tr>
	</table>
	
	<!-- BEGIN files_box -->
	<h2>{joined_files.files_box.L_TITLE_JOINED_FILES}</h2>
	
	{JOINED_FILES_BOX}
	<!-- END files_box -->
</div>

<!-- BEGIN files_box -->
<div id="nav-bottom">
	<div class="left">&nbsp;</div>
	<div class="right"><input type="submit" name="unattach" value="{joined_files.files_box.L_DEL_FILE_BUTTON}" class="button" /></div>
</div>
<!-- END files_box -->
<!-- END joined_files -->

<div class="bloc">
	<div class="bottom">{S_HIDDEN_FIELDS}
		<input type="submit" name="send" value="{L_SEND_BUTTON}" class="button" /> 
		<input type="submit" name="save" value="{L_SAVE_BUTTON}" class="pbutton" tabindex="1" /> 
		<input type="submit" name="delete" value="{L_DELETE_BUTTON}" class="button" {S_DELETE_BUTTON_DISABLED} />
	</div>
</div>
</form>

{LISTBOX}
