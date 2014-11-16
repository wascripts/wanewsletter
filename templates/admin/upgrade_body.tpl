<form class="compact" method="post" action="upgrade.php">
<div class="block">
	<h2>{L_TITLE_UPGRADE}</h2>

	<p>{L_EXPLAIN}</p>

	<table class="dataset">
		<tr>
			<td><label for="login">{L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="login" name="login" maxlength="30" size="25" autofocus /></td>
		</tr>
		<tr>
			<td><label for="passwd">{L_PASSWD}&nbsp;:</label></td>
			<td><input type="password" id="passwd" name="passwd" size="25" /></td>
		</tr>
	</table>

	<div class="bottom">
		<button type="submit" name="start" class="primary">{L_START_BUTTON}</button>
	</div>
</div>
</form>
