<form class="compact" id="login-form" method="post" action="{S_SCRIPT_NAME}?mode=login">
<ul class="links">
	<li><a href="{S_SCRIPT_NAME}?mode=reset_passwd">{L_RESET_PASSWD}</a></li>
</ul>

<div class="block">
	<h2>{TITLE}</h2>

	<div class="explain">{L_EXPLAIN}</div>

	<table class="dataset">
		<tr>
			<td><label for="login">{L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="login" name="login" maxlength="254" size="25" autofocus /></td>
		</tr>
		<tr>
			<td><label for="passwd">{L_PASSWD}&nbsp;:</label></td>
			<td><input type="password" id="passwd" name="passwd" size="25" /></td>
		</tr>
		<!-- tr>
			<td colspan="2">
				<input type="checkbox" id="autologin" name="autologin" value="1" />
				<label for="autologin">{L_AUTOLOGIN}</label>
			</td>
		</tr -->
	</table>

	<div class="bottom">
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>

<!-- BEGIN cookie_notice -->
<p id="cookie-notice" class="warning">{cookie_notice.L_TEXT}</p>
<script>
if (navigator.cookieEnabled) {
	document.getElementById('cookie-notice').style.display = 'none';
}
</script>
<!-- END cookie_notice -->
</form>
