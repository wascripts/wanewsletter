<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./admin.php">
<!-- BEGIN admin_options -->
<ul class="links">
	<li><a href="./admin.php?mode=adduser">{admin_options.L_ADD_ADMIN}</a></li>
</ul>
<!-- END admin_options -->

<div class="block">
	<h2>{L_TITLE}</h2>
	
	<table class="dataset">
		<tr>
			<td><label for="language">{L_DEFAULT_LANG}&nbsp;:</label></td>
			<td>{LANG_BOX}</td>
		</tr>
		<tr>
			<td><label for="email">{L_EMAIL}&nbsp;:</label></td>
			<td><input type="text" id="email" name="email" value="{EMAIL}" size="30" maxlength="254" /></td>
		</tr>
		<tr>
			<td><label for="dateformat">{L_DATEFORMAT}&nbsp;:</label><br /><span class="notice">{L_NOTE_DATE}</span></td>
			<td><input type="text" id="dateformat" name="dateformat" value="{DATEFORMAT}" size="15" maxlength="20" /></td>
		</tr>
		<tr>
			<td><label>{L_EMAIL_NEW_SUBSCRIBE}&nbsp;:</label></td>
			<td>
				<input type="radio" id="email_new_subscribe_yes" name="email_new_subscribe" value="1" {EMAIL_NEW_SUBSCRIBE_YES}/>
				<label for="email_new_subscribe_yes" class="notice">{L_YES}</label>
				<input type="radio" id="email_new_subscribe_no" name="email_new_subscribe" value="0" {EMAIL_NEW_SUBSCRIBE_NO}/>
				<label for="email_new_subscribe_no" class="notice">{L_NO}</label>
			</td>
		</tr>
		<tr>
			<td><label>{L_EMAIL_UNSUBSCRIBE}&nbsp;:</label></td>
			<td>
				<input type="radio" id="email_unsubscribe_yes" name="email_unsubscribe" value="1" {EMAIL_UNSUBSCRIBE_YES}/>
				<label for="email_unsubscribe_yes" class="notice">{L_YES}</label>
				<input type="radio" id="email_unsubscribe_no" name="email_unsubscribe" value="0" {EMAIL_UNSUBSCRIBE_NO}/>
				<label for="email_unsubscribe_no" class="notice">{L_NO}</label>
			</td>
		</tr>
		<!-- BEGIN owner_profil -->
		<tr>
			<td><label for="current_pass">{L_PASS}&nbsp;:</label><br /><span class="notice">{L_NOTE_PASS}</span></td>
			<td><input type="password" id="current_pass" name="current_pass" size="30" maxlength="30" /></td>
		</tr>
		<!-- END owner_profil -->
		<tr>
			<td><label for="new_pass">{L_NEW_PASS}&nbsp;:</label><br /><span class="notice">{L_NOTE_PASS}</span></td>
			<td><input type="password" id="new_pass" name="new_pass" size="30" maxlength="30" /></td>
		</tr>
		<tr>
			<td><label for="confirm_pass">{L_CONFIRM_PASS}&nbsp;:</label><br /><span class="notice">{L_NOTE_PASS}</span></td>
			<td><input type="password" id="confirm_pass" name="confirm_pass" size="30" maxlength="30" /></td>
		</tr>
	</table>
	
	<!-- BEGIN admin_options -->
	<h2>{admin_options.L_TITLE_MANAGE}</h2>
	
	<script>
	<!--
	function switch_selectbox(evt)
	{
		var node = evt.target.parentNode.parentNode;
		
		var boxList = node.getElementsByTagName('select');
		var val = boxList[0].value;
		
		for( var i = 0, m = boxList.length; i < m; i++ ) {
			boxList[i].options[val].selected = true;
		}
		
		evt.preventDefault();
	}
	
	document.addEventListener('DOMContentLoaded', function() {
 		var rows = document.getElementById('admin_authlist').rows;
 		var switchLink = null;
 		
 		for( var i = 1, m = rows.length; i < m; i++ ) {
 			switchLink = document.createElement('a');
 			switchLink.appendChild(document.createTextNode('switch'));
 			switchLink.setAttribute('href', '#switch/selectbox');
 			switchLink.setAttribute('class', 'notice');
			switchLink.style.cssFloat = 'right';
			switchLink.style.marginTop = '0.18em';
			switchLink.addEventListener('click', switch_selectbox, false);
 			
 			rows[i].cells[0].appendChild(switchLink);
 		}
	}, false);
	//-->
	</script>
	
	<table id="admin_authlist" class="dataset">
		<tr>
			<th>{admin_options.L_LISTE_NAME}</th>
			<th>{admin_options.L_VIEW}</th>
			<th>{admin_options.L_EDIT}</th>
			<th>{admin_options.L_DEL}</th>
			<th>{admin_options.L_SEND}</th>
			<th>{admin_options.L_IMPORT}</th>
			<th>{admin_options.L_EXPORT}</th>
			<th>{admin_options.L_BAN}</th>
			<th>{admin_options.L_ATTACH}</th>
		</tr>
		<!-- BEGIN auth -->
		<tr>
			<td>{admin_options.auth.LISTE_NAME} <input type="hidden" name="liste_id[]" value="{admin_options.auth.LISTE_ID}" /></td>
			<td>{admin_options.auth.BOX_AUTH_VIEW}</td>
			<td>{admin_options.auth.BOX_AUTH_EDIT}</td>
			<td>{admin_options.auth.BOX_AUTH_DEL}</td>
			<td>{admin_options.auth.BOX_AUTH_SEND}</td>
			<td>{admin_options.auth.BOX_AUTH_IMPORT}</td>
			<td>{admin_options.auth.BOX_AUTH_EXPORT}</td>
			<td>{admin_options.auth.BOX_AUTH_BACKUP}</td>
			<td>{admin_options.auth.BOX_AUTH_ATTACH}</td>
		</tr>
		<!-- END auth -->
	</table>
	
	<h2>{admin_options.L_TITLE_OPTIONS}</h2>
	
	<table class="dataset">
		<tr>
			<td><label for="admin_level">{admin_options.L_ADMIN_LEVEL}&nbsp;:</label></td>
			<td><select id="admin_level" name="admin_level"><option value="2"{admin_options.SELECTED_ADMIN}>{admin_options.L_ADMIN}</option><option value="1"{admin_options.SELECTED_USER}>{admin_options.L_USER}</option></select></td>
		</tr>
		<tr>
			<td><label for="delete_user">{admin_options.L_DELETE_ADMIN}&nbsp;:</label></td>
			<td><input type="checkbox" id="delete_user" name="delete_user" value="1" /> <span class="notice">{admin_options.L_NOTE_DELETE}</span></td>
		</tr>
	</table>
	<!-- END admin_options -->
	
	<div class="bottom">{S_HIDDEN_FIELDS}
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
		<button type="reset">{L_RESET_BUTTON}</button>
	</div>
</div>
</form>

<!-- BEGIN admin_box -->
<form id="smallbox" method="post" action="./admin.php">
<div>
	<label for="admin_id">{admin_box.L_VIEW_PROFILE}&nbsp;:</label>
	{admin_box.ADMIN_BOX} <button type="submit">{admin_box.L_BUTTON_GO}</button>
</div>
</form>
<!-- END admin_box -->