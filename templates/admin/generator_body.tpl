<form method="post" action="./tools.php?mode=generator">
<div class="smallbloc">
	<h2>{L_TITLE_GENERATOR}</h2>
	
	<table class="content">
		<tr>
			<td class="explain" colspan="2"> {L_EXPLAIN_GENERATOR} </td>
		</tr>
		<tr>
			<td class="row1"> <label for="url_form">{L_TARGET_FORM}&nbsp;:</label> </td>
			<td class="row2"> <input type="text" id="url_form" name="url_form" size="30" class="text" /> </td>
		</tr>
	</table>
	
	<div class="bottom"> {S_HIDDEN_FIELDS}
		<input type="submit" name="generate" class="pbutton" value="{L_VALID_BUTTON}" />
	</div>
</div>
</form>
	 