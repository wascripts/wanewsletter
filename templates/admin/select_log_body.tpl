<!-- BEGIN script_load_by_url -->
<script>
<!--
document.addEventListener('DOMContentLoaded', function() {

	document.styleSheets[0].insertRule('div#loadByURL { display: none; }', 0);

	document.forms[0].elements['id'].addEventListener('change', function() {
		var displayVal = null;

		if (this.selectedIndex == (this.options.length - 1)) {
			displayVal = 'block';
		}
		else {
			displayVal = 'none';
		}

		document.getElementById('loadByURL').style.display = displayVal;
	}, false);

	var newOption = document.createElement('option');
	newOption.appendChild(document.createTextNode('\u2013 {script_load_by_url.L_FROM_AN_URL}\u2026'));

	document.forms[0].elements['id'].appendChild(newOption);
}, false);
//-->
</script>
<!-- END script_load_by_url -->

<form method="post" action="envoi.php">
<div class="block">
	<h2>{L_TITLE}</h2>

	<!-- BEGIN load_draft -->
	<table class="dataset compact">
		<tr>
			<td><label>{load_draft.L_SELECT_LOG}&nbsp;:</label></td>
			<td>{load_draft.LOG_BOX}</td>
		</tr>
	</table>
	<!-- END load_draft -->

	<div id="loadByURL">
	<div class="explain">{L_EXPLAIN_LOAD}</div>

	<table class="dataset compact">
		<!-- BEGIN load_text_by_url -->
		<tr>
			<td><label>{load_text_by_url.L_LOAD_BY_URL}&nbsp;:</label></td>
			<td>
				<input type="text" name="body_text_url" value="{load_text_by_url.BODY_TEXT_URL}" size="35" />
				<span class="notice">({load_text_by_url.L_FORMAT_TEXT})</span>
			</td>
		</tr>
		<!-- END load_text_by_url -->
		<!-- BEGIN load_html_by_url -->
		<tr>
			<td><label>{load_html_by_url.L_LOAD_BY_URL}&nbsp;:</label></td>
			<td>
				<input type="text" name="body_html_url" value="{load_html_by_url.BODY_HTML_URL}" size="35" />
				<span class="notice">({load_html_by_url.L_FORMAT_HTML})</span>
			</td>
		</tr>
		<!-- END load_html_by_url -->
		<!-- BEGIN load_multi_by_url -->
		<tr>
			<td rowspan="2"><label>{load_multi_by_url.L_LOAD_BY_URL}&nbsp;:</label></td>
			<td>
				<input type="text" name="body_text_url" value="{load_multi_by_url.BODY_TEXT_URL}" size="35" />
				<span class="notice">({load_multi_by_url.L_FORMAT_TEXT})</span>
			</td>
		</tr>
		<tr>
			<td>
				<input type="text" name="body_html_url" value="{load_multi_by_url.BODY_HTML_URL}" size="35" />
				<span class="notice">({load_multi_by_url.L_FORMAT_HTML})</span>
			</td>
		</tr>
		<!-- END load_multi_by_url -->
	</table>
	</div>

	<div class="bottom">{S_HIDDEN_FIELDS}
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>
