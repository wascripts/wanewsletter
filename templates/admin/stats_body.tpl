<form method="get" action="./stats.php">
<div class="bloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="explain">{L_EXPLAIN_STATS}</td>
		</tr>
	</table>
	
	<div class="bottom">
		<select name="year">{YEAR_LIST}</select>
		<select name="month">{MONTH_LIST}</select>
		{S_HIDDEN_FIELDS} <input type="submit" value="{L_GO_BUTTON}" class="pbutton" />
	</div>
</div>
</form>

<!-- BEGIN statsdir_error -->
<p class="warning"><strong>{statsdir_error.MESSAGE}</strong></p>
<!-- END statsdir_error -->

<div class="stats">
	<img src="{U_IMG_GRAPH}" alt="" title="{L_IMG_GRAPH}" />
</div>

<div class="stats">
	<img src="{U_IMG_CAMEMBERT}" alt="" title="{L_IMG_CAMEMBERT}" />
</div>

{LISTBOX}
