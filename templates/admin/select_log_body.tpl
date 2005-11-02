<form method="post" action="{U_FORM}">
<div class="bloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="row1"><label>{L_SELECT_LOG}&#160;:</label></td>
			<td class="row2">{LOG_BOX}</td>
		</tr>
		<!-- BEGIN load_text_by_url -->
		<tr>
			<td class="row1"><label>{load_text_by_url.L_LOAD_BY_URL}&#160;:</label></td>
			<td class="row2">
				<input type="text" name="body_text_url" value="{load_text_by_url.BODY_TEXT_URL}" size="35" class="text" />
				<span class="m-texte">({load_text_by_url.L_FORMAT_TEXT})</span>
			</td>
		</tr>
		<!-- END load_text_by_url -->
		<!-- BEGIN load_html_by_url -->
		<tr>
			<td class="row1"><label>{load_html_by_url.L_LOAD_BY_URL}&#160;:</label></td>
			<td class="row2">
				<input type="text" name="body_html_url" value="{load_html_by_url.BODY_HTML_URL}" size="35" class="text" />
				<span class="m-texte">({load_html_by_url.L_FORMAT_HTML})</span>
			</td>
		</tr>
		<!-- END load_html_by_url -->
		<!-- BEGIN load_multi_by_url -->
		<tr>
			<td class="row1" rowspan="2"><label>{load_multi_by_url.L_LOAD_BY_URL}&#160;:</label></td>
			<td class="row2">
				<input type="text" name="body_text_url" value="{load_multi_by_url.BODY_TEXT_URL}" size="35" class="text" />
				<span class="m-texte">({load_multi_by_url.L_FORMAT_TEXT})</span>
			</td>
		</tr>
		<tr>
			<td class="row2">
				<input type="text" name="body_html_url" value="{load_multi_by_url.BODY_HTML_URL}" size="35" class="text" />
				<span class="m-texte">({load_multi_by_url.L_FORMAT_HTML})</span>
			</td>
		</tr>
		<!-- END load_multi_by_url -->
	</table>
	
	<div class="bottom"> {S_HIDDEN_FIELDS}
		<input type="submit" value="{L_VALID_BUTTON}" class="pbutton" />
	</div>
</div>
</form> 