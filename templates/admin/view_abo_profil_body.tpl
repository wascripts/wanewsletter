<p id="explain">{L_EXPLAIN}</p>

<div class="compact">
<ul class="links">
	<li><a href="{U_GOTO_LIST}">{L_GOTO_LIST}</a></li>
	<!-- BEGIN actions -->
	<li><a href="./view.php?mode=abonnes&amp;action=edit&amp;id={actions.S_ABO_ID}">{actions.L_EDIT_ACCOUNT}</a></li>
	<li><a href="./view.php?mode=abonnes&amp;action=delete&amp;id={actions.S_ABO_ID}">{actions.L_DELETE_ACCOUNT}</a></li>
	<!-- END actions -->
</ul>

<div class="block">
	<h2>{L_TITLE}</h2>

	<table class="dataset">
		<tr>
			<td>{L_PSEUDO}&nbsp;:</td>
			<td>{S_ABO_PSEUDO}</td>
		</tr>
		<tr>
			<td>{L_EMAIL}&nbsp;:</td>
			<td><a href="mailto:{S_ABO_EMAIL}">{S_ABO_EMAIL}</a></td>
		</tr>
		<tr>
			<td>{L_REGISTER_DATE}&nbsp;:</td>
			<td>{S_REGISTER_DATE}</td>
		</tr>
		<tr>
			<td>{L_STATUS}&nbsp;:</td>
			<td>{S_STATUS}</td>
		</tr>
	</table>

	<div class="explain">{L_LISTE_TO_REGISTER}&nbsp;:</div>

	<table class="listing">
		<!-- BEGIN listerow -->
		<tr>
			<td>
				&ndash;&nbsp;<a href="view.php?mode=abonnes&amp;liste={listerow.LISTE_ID}">{listerow.LISTE_NAME}</a> {listerow.CHOICE_FORMAT}
			</td>
		</tr>
		<!-- END listerow -->
	</table>

	<!-- BEGIN tags -->
	<h2>{tags.L_CAPTION}</h2>

	<table class="dataset">
		<tr>
			<th>{tags.L_NAME}</th>
			<th>{tags.L_VALUE}</th>
		</tr>
		<!-- BEGIN row -->
		<tr>
			<td>{tags.row.NAME}</td>
			<td>{tags.row.VALUE}</td>
		</tr>
		<!-- END row -->
	</table>
	<!-- END tags -->
</div>
</div>

{LISTBOX}
