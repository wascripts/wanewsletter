<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./profil_cp.php">
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
			<td><input type="text" id="new_email" name="new_email" size="30" maxlength="250" /></td>
		</tr>
		<tr>
			<td><label for="confirm_email">{L_CONFIRM_EMAIL}&nbsp;:</label></td>
			<td><input type="text" id="confirm_email" name="confirm_email" size="30" maxlength="250" /></td>
		</tr>
		<tr>
			<td><label for="pseudo">{L_PSEUDO}&nbsp;:</label></td>
			<td><input type="text" id="pseudo" name="pseudo" value="{PSEUDO}" size="30" maxlength="30" /></td>
		</tr>
		<tr>
			<td><label for="language">{L_LANG}&nbsp;:</label></td>
			<td>{LANG_BOX}</td>
		</tr>
		<!-- BEGIN password -->
		<tr>
			<td><label for="current_pass">{password.L_PASS}&nbsp;:</label></td>
			<td><input type="password" id="current_pass" name="current_pass" size="30" maxlength="32" /></td>
		</tr>
		<!-- END password -->
		<tr>
			<td><label for="new_pass">{L_NEW_PASS}&nbsp;:</label></td>
			<td><input type="password" id="new_pass" name="new_pass" size="30" maxlength="30" /></td>
		</tr>
		<tr>
			<td><label for="confirm_pass">{L_CONFIRM_PASS}&nbsp;:</label></td>
			<td><input type="password" id="confirm_pass" name="confirm_pass" size="30" maxlength="30" /></td>
		</tr>
	</table>
	
	<div class="bottom"> <input type="hidden" name="mode" value="editprofile" />
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>
