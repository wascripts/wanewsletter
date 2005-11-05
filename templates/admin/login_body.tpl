<form id="login-form" method="post" action="./login.php?mode=login">
<div class="smallbloc">
	<h2>{TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="row1"><label for="login">{L_LOGIN}&#160;:</label></td>
			<td class="row1"><input type="text" id="login" name="login" maxlength="30" size="30" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="passwd">{L_PASS}&#160;:</label></td>
			<td class="row1"><input type="password" id="passwd" name="passwd" maxlength="20" size="30" class="text" /></td>
		</tr>
		<tr>
			<td colspan="2" class="row-full">
				<input type="checkbox" id="autologin" name="autologin" value="1" />
				<label for="autologin">{L_AUTOLOGIN}</label>
			</td>
		</tr>
	</table>
	
	<div class="bottom">{S_HIDDEN_FIELDS}
		<input type="submit" name="submit" value="{L_VALID_BUTTON}" class="pbutton" />
	</div>
</div>
</form>
