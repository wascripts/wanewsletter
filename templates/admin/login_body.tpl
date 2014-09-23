<form class="compact" id="login-form" method="post" action="login.php">
<div class="block">
	<h2>{TITLE}</h2>
	
	<table class="dataset">
		<tr>
			<td><label for="login">{L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="login" name="login" maxlength="30" size="25" autofocus /></td>
		</tr>
		<tr>
			<td><label for="passwd">{L_PASS}&nbsp;:</label></td>
			<td><input type="password" id="passwd" name="passwd" maxlength="30" size="25" /></td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="checkbox" id="autologin" name="autologin" value="1" />
				<label for="autologin">{L_AUTOLOGIN}</label><br />
				<span class="notice"><a href="login.php?mode=sendpass">{L_LOST_PASSWORD}</a></span>
			</td>
		</tr>
	</table>
	
	<div class="bottom">{S_HIDDEN_FIELDS}
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>

<!-- BEGIN cookie_notice -->
<p id="cookie-notice" class="warning">{cookie_notice.L_TEXT}</p>
<script>
if( navigator.cookieEnabled ) {
	document.getElementById('cookie-notice').style.display = 'none';
}
</script>
<!-- END cookie_notice -->
</form>
