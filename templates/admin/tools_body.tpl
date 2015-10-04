<p id="explain">{L_EXPLAIN}</p>

<form method="get" action="./tools.php">
<div class="block">
	<h2>{L_TITLE}</h2>

	<table class="dataset">
		<tr>
			<td><label for="mode">{L_SELECT_TOOL}&nbsp;:</label></td>
			<td>{S_TOOLS_BOX}</td>
		</tr>
	</table>

	<div class="bottom"> {S_TOOLS_HIDDEN_FIELDS}
		<button type="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>

{TOOL_BODY}

{LISTBOX}
