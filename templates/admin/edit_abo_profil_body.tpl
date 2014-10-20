<script>
<!--
function checkForm_editAboProfil()
{
	var inputEmail = document.forms[0].elements['email'];

	if (inputEmail.defaultValue.toLowerCase() != inputEmail.value.toLowerCase()) {
		if (!window.confirm('{L_WARNING_EMAIL_DIFF}')) {
			return false;
		}
	}

	return true;
}
//-->
</script>

<p id="explain">{L_EXPLAIN}</p>

<form class="compact" method="post" action="./view.php?mode=abonnes" onsubmit="return checkForm_editAboProfil();">
<ul class="links">
	<li><a href="{U_GOTO_LIST}">{L_GOTO_LIST}</a></li>
	<li><a href="./view.php?mode=abonnes&amp;action=view&amp;id={S_ABO_ID}">{L_VIEW_ACCOUNT}</a></li>
	<li><a href="./view.php?mode=abonnes&amp;action=delete&amp;id={S_ABO_ID}">{L_DELETE_ACCOUNT}</a></li>
</ul>

<div class="block">
	<h2>{L_TITLE}</h2>

	<table class="dataset">
		<tr>
			<td><label for="pseudo">{L_PSEUDO}&nbsp;:</label></td>
			<td><input type="text" id="pseudo" name="pseudo" value="{S_ABO_PSEUDO}" size="30" maxlength="30" /></td>
		</tr>
		<tr>
			<td><label for="email">{L_EMAIL}&nbsp;:</label></td>
			<td><input type="text" id="email" name="email" value="{S_ABO_EMAIL}" size="30" maxlength="254" /></td>
		</tr>
	</table>

	<div class="explain">{L_LISTE_TO_REGISTER}</div>

	<table class="dataset">
		<!-- BEGIN listerow -->
		<tr>
			<td>&ndash;&nbsp;<a href="view.php?mode=abonnes&amp;liste={listerow.LISTE_ID}">{listerow.LISTE_NAME}</a></td>
			<td>{listerow.FORMAT_BOX}</td>
		</tr>
		<!-- END listerow -->
	</table>

	<!-- BEGIN tags -->
	<h2>{tags.L_TITLE}</h2>

	<table class="dataset">
		<!-- BEGIN row -->
		<tr>
			<td><label for="pseudo">{tags.row.NAME}&nbsp;:</label></td>
			<td><textarea name="tags[{tags.row.FIELDNAME}]" cols="35" rows="2">{tags.row.VALUE}</textarea></td>
		</tr>
		<!-- END row -->
	</table>
	<!-- END tags -->

	<div class="bottom">{S_HIDDEN_FIELDS}
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>

{LISTBOX}
