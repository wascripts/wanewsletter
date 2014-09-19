<form method="post" action="./profil_cp.php">
<ul class="links special">
	<li><a href="profil_cp.php?mode=sendkey">{L_SENDKEY}</a></li>
</ul>

<div class="block compact">
	<h2>{TITLE}</h2>
	
	<table class="dataset">
		<tr>
			<td><label for="email">{L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="email" name="email" value="{S_LOGIN}" maxlength="254" size="25" autofocus /></td>
		</tr>
		<tr>
			<td><label for="passwd">{L_PASS}&nbsp;:</label></td>
			<td><input type="password" id="passwd" name="passwd" maxlength="30" size="25" /></td>
		</tr>
	</table>
	
	<div class="bottom">
		<input type="hidden" name="mode" value="login" />
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>
