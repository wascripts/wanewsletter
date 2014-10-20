<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./profil_cp.php">
<div class="block">
	<h2>{TITLE}</h2>

	<table class="dataset compact">
		<!-- BEGIN listerow -->
		<tr>
			<td><label for="liste_{listerow.LISTE_ID}">{listerow.LISTE_NAME}&nbsp;:</label></td>
			<td>{listerow.SELECT_LOG}</td>
		</tr>
		<!-- END listerow -->
	</table>

	<div class="bottom"> {S_HIDDEN_FIELDS}
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>
