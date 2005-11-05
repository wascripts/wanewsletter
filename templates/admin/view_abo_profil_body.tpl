<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./view.php?mode=abonnes">
<ul class="links special">
	<li><a href="{U_GOTO_LIST}">{L_GOTO_LIST}</a></li>
	<li><a href="./view.php?mode=abonnes&amp;action=edit&amp;id={S_ABO_ID}">{L_EDIT_ACCOUNT}</a></li>
	<li><a href="./view.php?mode=abonnes&amp;action=delete&amp;id={S_ABO_ID}">{L_DELETE_ACCOUNT}</a></li>
</ul>

<div class="smallbloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="row1"><span class="texte">{L_PSEUDO}&#160;:</span></td>
			<td class="row2"><span class="texte">{S_ABO_PSEUDO}</span></td>
		</tr>
		<tr>
			<td class="row1"><span class="texte">{L_EMAIL}&#160;:</span></td>
			<td class="row2"><span class="texte"><a href="mailto:{S_ABO_EMAIL}">{S_ABO_EMAIL}</a></span></td>
		</tr>
		<tr>
			<td class="row1"><span class="texte">{L_REGISTER_DATE}&#160;:</span></td>
			<td class="row2"><span class="texte">{S_REGISTER_DATE}</span></td>
		</tr>
		<tr>
			<td class="explain" colspan="2">{L_LISTE_TO_REGISTER}&#160;:</td>
		</tr>
		<!-- BEGIN listerow -->
		<tr>
			<td class="row1" colspan="2">
				<span class="texte">&#8211;&#160;<a href="{listerow.U_VIEW_LISTE}">{listerow.LISTE_NAME}</a> {listerow.CHOICE_FORMAT}</span>
			</td>
		</tr>
		<!-- END listerow -->
	</table>
</div>
</form>

{LISTBOX}
