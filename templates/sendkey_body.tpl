<form method="post" action="./profil_cp.php">
<div class="smallbloc">
	<h2>{TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="explain" colspan="2">{L_EXPLAIN}</td>
		</tr>
		<tr>
			<td class="row1"> <label for="email">{L_LOGIN}&#160;:</label> </td>
			<td class="row1"> <input type="text" id="email" name="email" maxlength="250" size="30" class="text" /> </td>
		</tr>
	</table>
	
	<div class="bottom">
		<input type="hidden" name="mode" value="sendkey" />
		<input type="submit" name="submit" value="{L_VALID_BUTTON}" class="pbutton" />
	</div>
</div>
</form>
