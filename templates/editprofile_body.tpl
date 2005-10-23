<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./profil_cp.php">
<div class="bloc">
	<h2>{TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="row1"> <label>{L_EMAIL}&#160;:</label> </td>
			<td class="row2"> <span class="texte">{EMAIL}</span> </td>
		</tr>
		<tr>
			<td class="row1"> <label for="pseudo">{L_PSEUDO}&#160;:</label> </td>
			<td class="row2"> <input type="text" id="pseudo" name="pseudo" value="{PSEUDO}" size="30" maxlength="30" class="text" /> </td>
		</tr>
		<tr>
			<td class="row1"> <label for="language">{L_LANG}&#160;:</label> </td>
			<td class="row2"> {LANG_BOX} </td>
		</tr>
		<!-- BEGIN password -->
		<tr>
			<td class="row1"> <label for="current_pass">{password.L_PASS}&#160;:</label> </td>
			<td class="row2"> <input type="password" id="current_pass" name="current_pass" size="30" maxlength="32" class="text" /> </td>
		</tr>
		<!-- END password -->
		<tr>
			<td class="row1"> <label for="new_pass">{L_NEW_PASS}&#160;:</label> </td>
			<td class="row2"> <input type="password" id="new_pass" name="new_pass" size="30" maxlength="30" class="text" /> </td>
		</tr>
		<tr>
			<td class="row1"> <label for="confirm_pass">{L_CONFIRM_PASS}&#160;:</label> </td>
			<td class="row2"> <input type="password" id="confirm_pass" name="confirm_pass" size="30" maxlength="30" class="text" /> </td>
		</tr>
	</table>
	
	<div class="bottom"> <input type="hidden" name="mode" value="editprofile" />
		<input type="submit" name="submit" value="{L_VALID_BUTTON}" class="pbutton" />
	</div>
</div>
</form>
