<p id="explain">{L_EXPLAIN}</p>

<form id="logs" method="post" action="{U_FORM}">
<div id="nav-top">
	<div class="left"><p>{PAGEOF} {NUM_LOGS}</p></div>
	
	<div class="right">{S_HIDDEN_FIELDS}
		<p>{L_CLASSEMENT}&nbsp;:</p>
		<select name="type">
			<option value="log_subject"{SELECTED_TYPE_SUBJECT}>{L_BY_SUBJECT}</option>
			<option value="log_date"{SELECTED_TYPE_DATE}>{L_BY_DATE}</option>
		</select>&nbsp;&nbsp;<select name="order">
			<option value="ASC"{SELECTED_ORDER_ASC}>{L_BY_ASC}</option>
			<option value="DESC"{SELECTED_ORDER_DESC}>{L_BY_DESC}</option>
		</select>&nbsp;&nbsp;<input type="submit" name="tri" value="{L_CLASSER_BUTTON}" class="button" />
	</div>
</div>

<div class="bloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<th>{L_SUBJECT}</th>
			<th>{L_DATE}</th>
			<!-- BEGIN delete_option -->
			<th>&nbsp;</th>
			<!-- END delete_option -->
		</tr>
		<!-- BEGIN logrow -->
		<tr>
			<td class="{logrow.TD_CLASS}">{logrow.ITEM_CLIP}&nbsp;<a href="{logrow.U_VIEW}#view">{logrow.LOG_SUBJECT}</a></td>
			<td class="{logrow.TD_CLASS}">{logrow.LOG_DATE}</td>
			<!-- BEGIN delete -->
			<td class="{logrow.TD_CLASS}"><input type="checkbox" name="log_id[]" value="{logrow.delete.LOG_ID}" /></td>
			<!-- END delete -->
		</tr>
		<!-- END logrow -->
		<!-- BEGIN empty -->
		<tr>
			<td class="row-full" colspan="3">{empty.L_EMPTY}</td>
		</tr>
		<!-- END empty -->
	</table>
</div>

<div id="nav-bottom">
	<div class="left"><p>{PAGINATION}</p></div>
	
	<!-- BEGIN delete_option -->
	<div class="right"><input type="submit" name="delete" value="{delete_option.L_DELETE}" class="button" /></div>
	<!-- END delete_option -->
</div>

{IFRAME}
</form>

{LISTBOX}
