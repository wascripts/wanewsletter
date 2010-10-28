<p id="explain">{L_EXPLAIN}</p>

<ul class="links special">
	<li><a href="{U_GOTO_LIST}">{L_GOTO_LIST}</a></li>
	<li><a href="./view.php?mode=abonnes&amp;action=edit&amp;id={S_ABO_ID}">{L_EDIT_ACCOUNT}</a></li>
	<li><a href="./view.php?mode=abonnes&amp;action=delete&amp;id={S_ABO_ID}">{L_DELETE_ACCOUNT}</a></li>
</ul>

<div class="smallbloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="row1">{L_PSEUDO}&nbsp;:</td>
			<td class="row2">{S_ABO_PSEUDO}</td>
		</tr>
		<tr>
			<td class="row1">{L_EMAIL}&nbsp;:</td>
			<td class="row2"><a href="mailto:{S_ABO_EMAIL}">{S_ABO_EMAIL}</a></td>
		</tr>
		<tr>
			<td class="row1">{L_REGISTER_DATE}&nbsp;:</td>
			<td class="row2">{S_REGISTER_DATE}</td>
		</tr>
		<tr>
			<td class="explain" colspan="2">{L_LISTE_TO_REGISTER}&nbsp;:</td>
		</tr>
		<!-- BEGIN listerow -->
		<tr>
			<td class="row1" colspan="2">
				&ndash;&nbsp;<a href="{listerow.U_VIEW_LISTE}">{listerow.LISTE_NAME}</a> {listerow.CHOICE_FORMAT}
			</td>
		</tr>
		<!-- END listerow -->
	</table>
</div>

<!-- BEGIN tags -->
<table id="tagsList">
	<caption>{tags.L_CAPTION}</caption>
	<col span="2" width="50%" />
	<tr>
		<th>{tags.L_NAME}</th>
		<th>{tags.L_VALUE}</th>
	</tr>
	<!-- BEGIN row -->
	<tr>
		<td class="row1">{tags.row.NAME}</td>
		<td class="row2">{tags.row.VALUE}</td>
	</tr>
	<!-- END row -->
</table>
<!-- END tags -->

{LISTBOX}
