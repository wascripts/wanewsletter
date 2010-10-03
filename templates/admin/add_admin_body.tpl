<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./admin.php">
<div class="bloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="row1"> <label for="new_login">{L_LOGIN}&nbsp;:</label> </td>
			<td class="row2"> <input type="text" id="new_login" name="new_login" value="{LOGIN}" size="30" maxlength="30" class="text" /> </td>
		</tr>
		<tr>
			<td class="row1"> <label for="new_email">{L_EMAIL}&nbsp;:</label> <span class="m-texte">{L_EMAIL_NOTE}</span> </td>
			<td class="row2"> <input type="text" id="new_email" name="new_email" value="{EMAIL}" size="30" maxlength="200" class="text" /> </td>
		</tr>
	</table>
	
	<div class="bottom"> {S_HIDDEN_FIELDS}
		<input type="submit" name="submit" class="pbutton" value="{L_VALID_BUTTON}" /> <input type="submit" name="cancel" value="{L_CANCEL_BUTTON}" class="button" />
	</div>
</div>
</form>
