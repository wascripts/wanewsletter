<p id="explain">{L_EXPLAIN}</p>

<form method="get" action="view.php">
<div id="aside-top" class="aside">
	<div>{PAGEOF} {NUM_LOGS}</div>

	<div>
		<input type="hidden" name="mode" value="log" />
		<span>{L_CLASSEMENT}&nbsp;:</span>
		<select name="type">
			<option value="log_subject"{SELECTED_TYPE_SUBJECT}>{L_BY_SUBJECT}</option>
			<option value="log_date"{SELECTED_TYPE_DATE}>{L_BY_DATE}</option>
		</select>&nbsp;&nbsp;<select name="order">
			<option value="ASC"{SELECTED_ORDER_ASC}>{L_BY_ASC}</option>
			<option value="DESC"{SELECTED_ORDER_DESC}>{L_BY_DESC}</option>
		</select>&nbsp;&nbsp;<button type="submit">{L_CLASSER_BUTTON}</button>
	</div>
</div>
</form>

<form id="logs" method="post" action="view.php?mode=log{PAGING}">
<div class="block">
	<h2>{L_TITLE}</h2>

	<table class="listing">
		<tr>
			<th>{L_SUBJECT}</th>
			<th>{L_DATE}</th>
			<!-- BEGIN delete_option -->
			<th>&nbsp;</th>
			<!-- END delete_option -->
		</tr>
		<!-- BEGIN logrow -->
		<tr>
			<td>{logrow.ITEM_CLIP}&nbsp;<a href="{logrow.U_VIEW}#view">{logrow.LOG_SUBJECT}</a></td>
			<td>{logrow.LOG_DATE}</td>
			<!-- BEGIN delete -->
			<td><input type="checkbox" name="log_id[]" value="{logrow.delete.LOG_ID}" /></td>
			<!-- END delete -->
		</tr>
		<!-- END logrow -->
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
	<div><button type="submit" name="delete">{delete_option.L_DELETE}</button></div>
	<!-- END delete_option -->
</div>
</form>

{IFRAME}

{LISTBOX}
