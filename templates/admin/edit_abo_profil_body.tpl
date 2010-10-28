<script type="text/javascript">
<!--
function checkForm_editAboProfil(evt)
{
	var inputEmail = document.forms[0].elements['email'];
	
	if( inputEmail.defaultValue.toLowerCase() != inputEmail.value.toLowerCase() ) {
		if( !window.confirm('{L_WARNING_EMAIL_DIFF}') ) {
			evt.preventDefault();
		}
	}
}

DOM_Events.addListener('load', function() {
	DOM_Events.addListener('submit', checkForm_editAboProfil, false, document.forms[0]);
}, false, document);
//-->
</script>

<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./view.php?mode=abonnes">
<ul class="links special">
	<li><a href="{U_GOTO_LIST}">{L_GOTO_LIST}</a></li>
	<li><a href="./view.php?mode=abonnes&amp;action=view&amp;id={S_ABO_ID}">{L_VIEW_ACCOUNT}</a></li>
	<li><a href="./view.php?mode=abonnes&amp;action=delete&amp;id={S_ABO_ID}">{L_DELETE_ACCOUNT}</a></li>
</ul>

<div class="smallbloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="row1"><label for="pseudo">{L_PSEUDO}&nbsp;:</label></td>
			<td class="row2"><input type="text" id="pseudo" name="pseudo" value="{S_ABO_PSEUDO}" class="text" size="30" maxlength="30" /></td>
		</tr>
		<tr>
			<td class="row1"><label for="email">{L_EMAIL}&nbsp;:</label></td>
			<td class="row2"><input type="text" id="email" name="email" value="{S_ABO_EMAIL}" class="text" size="30" maxlength="100" /></td>
		</tr>
		<tr>
			<td class="explain" colspan="2">{L_LISTE_TO_REGISTER}&nbsp;:</td>
		</tr>
		<!-- BEGIN listerow -->
		<tr>
			<td class="row1">&ndash;&nbsp;<a href="{listerow.U_VIEW_LISTE}">{listerow.LISTE_NAME}</a></td>
			<td class="row2">{listerow.FORMAT_BOX}</td>
		</tr>
		<!-- END listerow -->
	</table>
	
	<!-- BEGIN tags -->
	<h2>{tags.L_TITLE}</h2>
	
	<table class="content">
		<!-- BEGIN row -->
		<tr>
			<td class="row1"><label for="pseudo">{tags.row.NAME}&nbsp;:</label></td>
			<td class="row2"><textarea name="tags[{tags.row.FIELDNAME}]" cols="35" rows="2">{tags.row.VALUE}</textarea></td>
		</tr>
		<!-- END row -->
	</table>
	<!-- END tags -->
	
	<div class="bottom">{S_HIDDEN_FIELDS}
		<input type="submit" name="submit" value="{L_VALID_BUTTON}" class="pbutton" />
	</div>
</div>
</form>

{LISTBOX}
