<form class="compact" method="post" action="./tools.php?mode=generator">
<div class="block">
	<h2>{L_TITLE_GENERATOR}</h2>
	
	<p class="explain">{L_EXPLAIN_GENERATOR}</p>
	
	<table class="dataset">
		<tr>
			<td><label for="url_form">{L_TARGET_FORM}&nbsp;:</label></td>
			<td><input type="text" id="url_form" name="url_form" size="30" /></td>
		</tr>
	</table>
	
	<div class="bottom"> {S_HIDDEN_FIELDS}
		<button type="submit" name="generate" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>
	 