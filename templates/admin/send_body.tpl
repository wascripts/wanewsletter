<script src="{BASEDIR}/vendor/tinymce/tinymce/tinymce.min.js"></script>
<script>
<!--
tinyMCE.init({
	selector: "textarea[name='body_html']",
	theme: "modern",
	skin: "lightgray",
	menubar: false,
<!-- BEGIN tinymce_lang -->
	language_url:"{BASEDIR}/languages/{tinymce_lang.CODE}/tinymce.js",
<!-- END tinymce_lang -->
	plugins: [
		"advlist autolink lists link image charmap print preview hr anchor pagebreak",
		"searchreplace wordcount visualblocks visualchars code",
		"insertdatetime media nonbreaking table contextmenu directionality",
		"paste textcolor colorpicker textpattern"
	],
	toolbar1: "bold italic underline strikethrough bullist numlist blockquote hr alignleft aligncenter alignright link unlink image spellchecker",
	toolbar2: "forecolor pastetext removeformat charmap outdent indent code undo redo",

	entity_encoding: "raw",
	relative_urls: false,
	setup: function(ed) {
		ed.on('BeforeSetContent', function(e) {
			e.content = e.content.replace(/<([^>]+)=\s*("|\')cid:/g,'<$1=$2show.php?file=');
		});
		ed.on('GetContent', function(e) {
			e.content = e.content.replace(/<([^>]+)=\s*("|\').*?show\.php\?file=/g,'<$1=$2cid:');
		});
	}
});

window._old_addLinks = window.addLinks;
window.addLinks = function(evt) {
	if( evt.currentTarget.id == 'addLinks2' ) {
		tinyMCE.execCommand('mceInsertContent', false, '&#123;LINKS&#125;');
	}
	else {
		window._old_addLinks(evt);
	}
};
//-->
</script>
<script>
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

<div class="block">
	<table class="dataset compact">
		<tr>
			<td>{L_DEST}&nbsp;:</td>
			<td><strong>{S_DEST}</strong></td>
		</tr>
		<tr>
			<td><label for="subject">{L_SUBJECT}&nbsp;:</label></td>
			<td><input type="text" id="subject" name="subject" value="{S_SUBJECT}" size="40" maxlength="100" /></td>
		</tr>
		<tr>
			<td><label for="log_status">{L_STATUS}&nbsp;:</label></td>
			<td>
				<select id="log_status" name="log_status">
					<option value="0"{SELECTED_STATUS_WRITING}>{L_STATUS_WRITING}</option>
					<option value="3"{SELECTED_STATUS_MODEL}>{L_STATUS_MODEL}</option>
				</select>
			</td>
		</tr>
		<tr title="{L_CC_ADMIN_TITLE}">
			<td><label>{L_CC_ADMIN}&nbsp;:</label></td>
			<td>
				<input type="radio" id="cc_admin_yes" name="cc_admin" value="1" {CHECKED_CC_ADMIN_ON}/>
				<label for="cc_admin_yes" class="notice">{L_YES}</label>
				<input type="radio" id="cc_admin_no" name="cc_admin" value="0" {CHECKED_CC_ADMIN_OFF}/>
				<label for="cc_admin_no" class="notice">{L_NO}</label>
			</td>
		</tr>
	</table>

	<!-- BEGIN test_send -->
	<div class="explain">{test_send.L_TEST_SEND_NOTE}</div>

	<table class="dataset compact">
		<tr>
			<td><label for="test_address">{test_send.L_TEST_SEND}&nbsp;:</label></td>
			<td>
				<input type="text" id="test_address" name="test_address" size="40" />
				<button type="submit" name="test">{test_send.L_SEND_BUTTON}</button>
			</td>
		</tr>
	</table>
	<!-- END test_send -->
</div>
<!-- BEGIN last_modified -->
<div id="aside-bottom" class="aside last-modified notice">{last_modified.S_LAST_MODIFIED}</div>
<!-- END last_modified -->

<!-- BEGIN nl_text_textarea -->
<div class="block" id="textarea1">
	<h2>{nl_text_textarea.L_TITLE}</h2>

	<div class="explain">{nl_text_textarea.L_EXPLAIN}</div>

	<div class="textinput">
		<textarea name="body_text" cols="90" rows="20">{nl_text_textarea.S_BODY}</textarea>
	</div>
</div>
<!-- END nl_text_textarea -->

<!-- BEGIN nl_html_textarea -->
<div class="block" id="textarea2">
	<h2>{nl_html_textarea.L_TITLE}</h2>

	<div class="explain">{nl_html_textarea.L_EXPLAIN}</div>

	<div class="textinput">
		<textarea name="body_html" cols="90" rows="20">{nl_html_textarea.S_BODY}</textarea>
	</div>
</div>
<!-- END nl_html_textarea -->

<!-- BEGIN joined_files -->
<div class="block">
	<h2>{joined_files.L_TITLE_ADD_FILE}</h2>

	<div class="explain">{joined_files.L_EXPLAIN_ADD_FILE}</div>

	<table class="dataset compact">
		<tr>
			<td rowspan="{joined_files.S_ROWSPAN}"><label for="local_file">{joined_files.L_ADD_FILE}&nbsp;:</label></td>
			<td><input type="text" id="local_file" name="local_file" size="40" /></td>
		</tr>
		<!-- BEGIN upload_input -->
		<tr>
			<td>
				<input type="hidden" name="MAX_FILE_SIZE" value="{joined_files.upload_input.MAX_FILE_SIZE}" />
				<input type="file" name="join_file" data-button-label="{joined_files.upload_input.L_BROWSE_BUTTON}" />
				<span class="notice">({joined_files.upload_input.L_MAXIMUM_SIZE})</span>
			</td>
		</tr>
		<!-- END upload_input -->
		<!-- BEGIN select_box -->
		<tr>
			<td>{joined_files.select_box.SELECT_BOX}</td>
		</tr>
		<!-- END select_box -->
		<tr>
			<td><button type="submit" name="attach">{joined_files.L_ADD_FILE_BUTTON}</button></td>
		</tr>
	</table>

	{JOINED_FILES_BOX}
</div>

<!-- BEGIN files_box -->
<div id="aside-bottom" class="aside">
	<div>&nbsp;</div>
	<div><button type="submit" name="unattach">{joined_files.files_box.L_DEL_FILE_BUTTON}</button></div>
</div>
<!-- END files_box -->
<!-- END joined_files -->

<div class="block">
	<div class="bottom">{S_HIDDEN_FIELDS}
		<button type="submit" name="send">{L_SEND_BUTTON}</button>
		<button type="submit" name="save" class="primary" tabindex="1">{L_SAVE_BUTTON}</button>
		<button type="submit" name="delete" {S_DELETE_BUTTON_DISABLED}>{L_DELETE_BUTTON}</button>
	</div>
</div>
</form>

{LISTBOX}
