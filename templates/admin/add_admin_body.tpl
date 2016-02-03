<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./admin.php?mode=adduser">
<div class="block">
	<h2>{L_TITLE}</h2>

	<table class="dataset compact">
		<tr>
			<td><label for="new_login">{L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="new_login" name="new_login" value="{LOGIN}" size="30" maxlength="30" /></td>
		</tr>
		<tr>
			<td><label for="new_email">{L_EMAIL}&nbsp;:</label></td>
			<td><input type="text" id="new_email" name="new_email" value="{EMAIL}" size="30" maxlength="254" /></td>
		</tr>
	</table>

	<div class="bottom">
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
		<button type="submit" name="cancel">{L_CANCEL_BUTTON}</button>
	</div>
</div>
</form>
