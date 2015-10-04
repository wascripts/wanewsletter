<form class="compact" method="post" action="{S_SCRIPT_NAME}?k={S_RESETKEY}">
<div class="block">
	<h2>{TITLE}</h2>

	<table class="dataset">
		<tr>
			<td><label for="new_passwd">{L_NEW_PASSWD}&nbsp;:</label></td>
			<td><input type="password" id="new_passwd" name="new_passwd" size="30" autocomplete="off" /></td>
		</tr>
		<tr>
			<td><label for="confirm_passwd">{L_CONFIRM_PASSWD}&nbsp;:</label></td>
			<td><input type="password" id="confirm_passwd" name="confirm_passwd" size="30" autocomplete="off" /></td>
		</tr>
	</table>

	<div class="bottom">
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>
