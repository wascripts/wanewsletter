<form class="compact" method="post" action="./profil_cp.php?mode=sendkey">
<div class="block">
	<h2>{TITLE}</h2>

	<div class="explain">{L_EXPLAIN}</div>

	<table class="dataset">
		<tr>
			<td><label for="email">{L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="email" name="email" maxlength="254" size="30" /></td>
		</tr>
	</table>

	<div class="bottom">
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>
