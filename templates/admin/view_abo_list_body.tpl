<p id="explain">{L_EXPLAIN}</p>

<form id="abo" method="post" action="{U_FORM}">
<div class="block">
	<table class="dataset">
		<tr>
			<td rowspan="2">
				<label for="keyword">{L_SEARCH}&nbsp;: </label><br />
				<span class="notice">{L_SEARCH_NOTE}</span>
			</td>
			<td>
				<input type="text" id="keyword" name="keyword" value="{KEYWORD}" size="35" maxlength="60" />
				<button type="submit" name="search" class="primary">{L_SEARCH_BUTTON}</button>
			</td>
		</tr>
		<tr>
			<td>{SEARCH_DAYS_BOX}</td>
		</tr>
	</table>
</div>

<!-- BEGIN delete_option -->
<div class="block">
	<table class="dataset">
		<tr>
			<td rowspan="2">
				<label for="email_list">{delete_option.L_FAST_DELETION}&nbsp;:</label><br />
				<span class="notice">{delete_option.L_FAST_DELETION_NOTE}</span>
			</td>
			<td><textarea id="email_list" name="email_list" rows="2" cols="40"></textarea></td>
		</tr>
		<tr>
			<td><button type="submit" name="delete">{delete_option.L_DELETE_BUTTON}</button></td>
		</tr>
	</table>
</div>
<!-- END delete_option -->

<div id="aside-top" class="aside">
	<div>{PAGEOF} {NUM_SUBSCRIBERS}</div>
	
	<div>{S_HIDDEN_FIELDS}
		<span>{L_CLASSEMENT}&nbsp;:</span>
		<select name="type">
			<option value="abo_email"{SELECTED_TYPE_EMAIL}>{L_BY_EMAIL}</option>
			<option value="abo_register_date"{SELECTED_TYPE_DATE}>{L_BY_DATE}</option>
			<option value="format"{SELECTED_TYPE_FORMAT}>{L_BY_FORMAT}</option>
		</select>&nbsp;&nbsp;<select name="order">
			<option value="ASC"{SELECTED_ORDER_ASC}>{L_BY_ASC}</option>
			<option value="DESC"{SELECTED_ORDER_DESC}>{L_BY_DESC}</option>
		</select>&nbsp;&nbsp;<button type="submit" name="tri">{L_CLASSER_BUTTON}</button>
	</div>
</div>

<div class="block">
	<h2>{L_TITLE}</h2>
	
	<table class="listing">
		<tr>
			<th>{L_EMAIL}</th>
			<th>{L_DATE}</th>
			<!-- BEGIN view_format -->
			<th>{view_format.L_FORMAT}</th>
			<!-- END view_format -->
			<!-- BEGIN delete_option -->
			<th>&nbsp;</th>
			<!-- END delete_option -->
		</tr>
		<!-- BEGIN aborow -->
		<tr>
			<td><a href="{aborow.U_VIEW}">{aborow.ABO_EMAIL}</a></td>
			<td>{aborow.ABO_REGISTER_DATE}</td>
			<!-- BEGIN format -->
			<td>{aborow.format.ABO_FORMAT}</td>
			<!-- END format -->
			<!-- BEGIN delete -->
			<td><input type="checkbox" name="id[]" value="{aborow.delete.ABO_ID}" /></td>
			<!-- END delete -->
		</tr>
		<!-- END aborow -->
		<!-- BEGIN empty -->
		<tr>
			<td colspan="3">{empty.L_EMPTY}</td>
		</tr>
		<!-- END empty -->
	</table>
</div>

<div id="aside-bottom" class="aside">
	<div>{PAGINATION}</div>
	
	<!-- BEGIN delete_option -->
	<div><button type="submit" name="delete">{delete_option.L_DELETE_ABO_BUTTON}</button></div>
	<!-- END delete_option -->
</div>
</form>

{LISTBOX}
