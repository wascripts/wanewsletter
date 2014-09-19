<form method="post" action="./login.php?mode=sendpass">
<div class="block compact">
	<h2>{TITLE}</h2>
	
	<table class="dataset">
		<tr>
			<td><label for="login">{L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="login" name="login" value="{S_LOGIN}" maxlength="30" size="25" /></td>
		</tr>
		<tr>
			<td><label for="email">{L_EMAIL}&nbsp;:</label></td>
			<td><input type="text" id="email" name="email" value="{S_EMAIL}" maxlength="254" size="25" /></td>
		</tr>
	</table>
	
	<div class="bottom">
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>
