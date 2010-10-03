<form method="post" action="./login.php?mode=sendpass">
<div class="smallbloc">
	<h2>{TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="row1"><label for="login">{L_LOGIN}&nbsp;:</label></td>
			<td class="row1"><input type="text" id="login" name="login" value="{S_LOGIN}" maxlength="30" size="30" class="text" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="email">{L_EMAIL}&nbsp;:</label></td>
			<td class="row1"><input type="text" id="email" name="email" value="{S_EMAIL}" maxlength="200" size="30" class="text" /></td>
		</tr>
	</table>
	
	<div class="bottom">
		<input type="submit" name="submit" value="{L_VALID_BUTTON}" class="pbutton" />
	</div>
</div>
</form>
