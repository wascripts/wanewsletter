<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./profil_cp.php?mode=editprofile">
<div class="block">
	<h2>{TITLE}</h2>

	<div class="explain">{L_EXPLAIN_EMAIL}</div>

	<table class="dataset">
		<tr>
			<td><label>{L_EMAIL}&nbsp;:</label></td>
			<td>{EMAIL}</td>
		</tr>
		<tr>
			<td><label for="new_email">{L_NEW_EMAIL}&nbsp;:</label></td>
			<td><input type="text" id="new_email" name="new_email" size="30" maxlength="254" autocomplete="off" /></td>
		</tr>
		<tr>
			<td><label for="confirm_email">{L_CONFIRM_EMAIL}&nbsp;:</label></td>
			<td><input type="text" id="confirm_email" name="confirm_email" size="30" maxlength="254" autocomplete="off" /></td>
		</tr>
		<tr>
			<td><label for="username">{L_USERNAME}&nbsp;:</label></td>
			<td><input type="text" id="username" name="username" value="{USERNAME}" size="30" maxlength="30" /></td>
		</tr>
		<tr>
			<td><label for="language">{L_LANG}&nbsp;:</label></td>
			<td>{LANG_BOX}</td>
		</tr>
		<tr>
			<td><label for="current_passwd">{L_PASSWD}&nbsp;:</label></td>
			<td><input type="password" id="current_passwd" name="current_passwd" size="30" autocomplete="off" /></td>
		</tr>
		<tr>
			<td><label for="new_passwd">{L_NEW_PASSWD}&nbsp;:</label></td>
			<td><input type="password" id="new_passwd" name="new_passwd" size="30" autocomplete="off" /></td>
		</tr>
		<tr>
			<td><label for="confirm_pass">{L_CONFIRM_PASSWD}&nbsp;:</label></td>
			<td><input type="password" id="confirm_passwd" name="confirm_passwd" size="30" autocomplete="off" /></td>
		</tr>
	</table>

	<div class="bottom">
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>
