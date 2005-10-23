<form method="post" action="./tools.php?mode=ban">
<div class="smallbloc">
	<h2>{L_TITLE_BAN}</h2>
	
	<table class="content">
		<tr>
			<td class="explain" colspan="2"> {L_EXPLAIN_BAN} </td>
		</tr>
		<tr>
			<td class="row1"> <label for="pattern">{L_BAN_EMAIL}&#160;:</label> </td>
			<td class="row2"> <input type="text" id="pattern" name="pattern" size="30" maxlength="100" class="text" /> </td>
		</tr>
		<tr>
			<td class="explain" colspan="2"> {L_EXPLAIN_UNBAN} </td>
		</tr>
		<tr>
			<td class="row1"> <label for="unban_list_id">{L_UNBAN_EMAIL}&#160;:</label> </td>
			<td class="row2"> {UNBAN_EMAIL_BOX} </td>
		</tr>
	</table>
	
	<div class="bottom"> {S_HIDDEN_FIELDS}
		<input type="submit" name="submit" class="pbutton" value="{L_VALID_BUTTON}" /> <input type="reset" value="{L_RESET_BUTTON}" class="button" />
	</div>
</div>
</form>
