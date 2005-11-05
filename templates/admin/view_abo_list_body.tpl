<p id="explain">{L_EXPLAIN}</p>

<form id="abo" method="post" action="{U_FORM}">
<div class="bloc">
	<table class="content">
		<tr>
			<td rowspan="2" class="row1">
				<label for="keyword">{L_SEARCH}&#160;: </label><br />
				<span class="m-texte">{L_SEARCH_NOTE}</span>
			</td>
			<td class="row2">
				<input type="text" id="keyword" name="keyword" value="{KEYWORD}" size="35" maxlength="60" class="text" />
				<input type="submit" name="search" value="{L_SEARCH_BUTTON}" class="pbutton" />
			</td>
		</tr>
		<tr>
			<td class="row2">{SEARCH_DAYS_BOX}</td>
		</tr>
	</table>
</div>

<!-- BEGIN delete_option -->
<div class="bloc">
	<table class="content">
		<tr>
			<td rowspan="2" class="row1">
				<label for="email_list">{delete_option.L_FAST_DELETION}&#160;:</label><br />
				<span class="m-texte">{delete_option.L_FAST_DELETION_NOTE}</span>
			</td>
			<td class="row2"><textarea id="email_list" name="email_list" rows="2" cols="40"></textarea></td>
		</tr>
		<tr>
			<td class="row2"><input type="submit" name="delete" value="{delete_option.L_DELETE_BUTTON}" class="button" /></td>
		</tr>
	</table>
</div>
<!-- END delete_option -->

<div id="nav-top">
	<div class="left"><p>{PAGEOF} {NUM_SUBSCRIBERS}</p></div>
	
	<div class="right">{S_HIDDEN_FIELDS}
		<p>{L_CLASSEMENT}&#160;:</p>
		<select name="type">
			<option value="abo_email"{SELECTED_TYPE_EMAIL}>{L_BY_EMAIL}</option>
			<option value="abo_register_date"{SELECTED_TYPE_DATE}>{L_BY_DATE}</option>
			<option value="format"{SELECTED_TYPE_FORMAT}>{L_BY_FORMAT}</option>
		</select>&#160;&#160;<select name="order">
			<option value="ASC"{SELECTED_ORDER_ASC}>{L_BY_ASC}</option>
			<option value="DESC"{SELECTED_ORDER_DESC}>{L_BY_DESC}</option>
		</select>&#160;&#160;<input type="submit" name="tri" value="{L_CLASSER_BUTTON}" class="button" />
	</div>
</div>

<div class="bloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<th>{L_EMAIL}</th>
			<th>{L_DATE}</th>
			<!-- BEGIN view_format -->
			<th>{view_format.L_FORMAT}</th>
			<!-- END view_format -->
			<!-- BEGIN delete_option -->
			<th>&#160;</th>
			<!-- END delete_option -->
		</tr>
		<!-- BEGIN aborow -->
		<tr>
			<td class="{aborow.TD_CLASS}"><span class="texte"><a href="{aborow.U_VIEW}">{aborow.ABO_EMAIL}</a></span></td>
			<td class="{aborow.TD_CLASS}"><span class="texte">{aborow.ABO_REGISTER_DATE}</span></td>
			<!-- BEGIN format -->
			<td class="{aborow.TD_CLASS}"><span class="texte">{aborow.format.ABO_FORMAT}</span></td>
			<!-- END format -->
			<!-- BEGIN delete -->
			<td class="{aborow.TD_CLASS}"><input type="checkbox" name="id[]" value="{aborow.delete.ABO_ID}" /></td>
			<!-- END delete -->
		</tr>
		<!-- END aborow -->
		<!-- BEGIN empty -->
		<tr>
			<td class="row-full" colspan="3"><span class="texte">{empty.L_EMPTY}</span></td>
		</tr>
		<!-- END empty -->
	</table>
</div>

<div id="nav-bottom">
	<div class="left"><p>{PAGINATION}</p></div>
	
	<!-- BEGIN delete_option -->
	<div class="right"><input type="submit" name="delete" value="{delete_option.L_DELETE_ABO_BUTTON}" class="button" /></div>
	<!-- END delete_option -->
</div>
</form>

{LISTBOX}
