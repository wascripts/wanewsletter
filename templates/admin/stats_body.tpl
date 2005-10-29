<form method="get" action="./stats.php">
<div class="bloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="explain">{L_EXPLAIN_STATS}</td>
		</tr>
	</table>
	
	<div class="bottom"> {S_HIDDEN_FIELDS} {DATE_BOX} &#160;
		<input type="submit" value="{L_GO_BUTTON}" class="pbutton" />
	</div>
</div>
</form>

<div class="stats">
	<img src="{U_IMG_GRAPH}" alt="" title="{L_IMG_GRAPH}" />
</div>

<div class="stats">
	<img src="{U_IMG_CAMENBERT}" alt="" title="{L_IMG_CAMENBERT}" />
</div>

{LISTBOX}
