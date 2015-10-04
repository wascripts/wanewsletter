<form class="compact" method="post" action="upgrade.php">
<div class="block">
	<h2>{L_TITLE_UPGRADE}</h2>

	<p>{L_EXPLAIN}</p>

	<!-- BEGIN login_form -->
	<table class="dataset">
		<tr>
			<td><label for="login">{login_form.L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="login" name="login" maxlength="30" size="25" autofocus /></td>
		</tr>
		<tr>
			<td><label for="passwd">{login_form.L_PASSWD}&nbsp;:</label></td>
			<td><input type="password" id="passwd" name="passwd" size="25" /></td>
		</tr>
	</table>
	<!-- END login_form -->

	<div class="bottom">
		<button type="submit" name="start" class="primary">{L_START_BUTTON}</button>
	</div>
</div>
</form>
