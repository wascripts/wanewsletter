<form method="post" action="./profil_cp.php">
<ul class="links special">
	<li> <a href="profil_cp.php?mode=sendkey">{L_SENDKEY}</a> </li>
</ul>

<div class="smallbloc">
	<h2>{TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="row1"> <label for="email">{L_LOGIN}&#160;:</label> </td>
			<td class="row1"> <input type="text" id="email" name="email" value="{S_LOGIN}" maxlength="250" size="30" class="text" /> </td>
		</tr>
		<tr>
			<td class="row1"> <label for="passwd">{L_PASS}&#160;:</label> </td>
			<td class="row1"> <input type="password" id="passwd" name="passwd" maxlength="32" size="30" class="text" /> </td>
		</tr>
	</table>
	
	<div class="bottom">
		<input type="hidden" name="mode" value="login" />
		<input type="submit" name="submit" value="{L_VALID_BUTTON}" class="pbutton" />
	</div>
</div>
</form>
