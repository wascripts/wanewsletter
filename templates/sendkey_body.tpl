<form method="post" action="./profil_cp.php">
<div class="block compact">
	<h2>{TITLE}</h2>
	
	<div class="explain">{L_EXPLAIN}</div>
	
	<table class="dataset">
		<tr>
			<td><label for="email">{L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="email" name="email" maxlength="250" size="30" /></td>
		</tr>
	</table>
	
	<div class="bottom">
		<input type="hidden" name="mode" value="sendkey" />
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>
