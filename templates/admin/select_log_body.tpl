<!-- BEGIN script_load_by_url -->
<script type="text/javascript">
<!--
if( typeof(document.styleSheets) != 'undefined' ) {
	if( typeof(document.styleSheets[0].cssRules) != 'undefined' ) {
		document.styleSheets[0].insertRule('tr#loadByURL, tr#loadByURL-bis { display: none; }', 0);
	} else {
		document.styleSheets[0].addRule('tr#loadByURL, tr#loadByURL-bis', 'display: none');
	}
	
	DOM_Events.addListener('load', function() {
		DOM_Events.addListener('change', function() {
			var displayVal = (this.selectedIndex == (this.options.length - 1)) ? 'table-row' : 'none';
			
			//
			// La détection de navigateur est à éviter mais il arrive que l'on ait pas trop le choix...
			//
			if( navigator.userAgent.indexOf('MSIE') != -1 ) {
				displayVal = 'block';
			}
			
			document.getElementById('loadByURL').style.display = displayVal;
			if( document.getElementById('loadByURL-bis') != null ) {
				document.getElementById('loadByURL-bis').style.display = displayVal;
			}
		}, false, document.forms[0].elements['id']);
		
		var newOption = document.createElement('option');
		newOption.appendChild(document.createTextNode('{script_load_by_url.L_FROM_AN_URL}\u2026'));
		
		document.forms[0].elements['id'].appendChild(newOption);
	}, false, document);
}
//-->
</script>
<!-- END script_load_by_url -->

<form method="post" action="{U_FORM}">
<div class="bloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="row1"><label>{L_SELECT_LOG}&#160;:</label></td>
			<td class="row2">{LOG_BOX}</td>
		</tr>
		<!-- BEGIN load_text_by_url -->
		<tr id="loadByURL">
			<td class="row1"><label>{load_text_by_url.L_LOAD_BY_URL}&#160;:</label></td>
			<td class="row2">
				<input type="text" name="body_text_url" value="{load_text_by_url.BODY_TEXT_URL}" size="35" class="text" />
				<span class="m-texte">({load_text_by_url.L_FORMAT_TEXT})</span>
			</td>
		</tr>
		<!-- END load_text_by_url -->
		<!-- BEGIN load_html_by_url -->
		<tr id="loadByURL">
			<td class="row1"><label>{load_html_by_url.L_LOAD_BY_URL}&#160;:</label></td>
			<td class="row2">
				<input type="text" name="body_html_url" value="{load_html_by_url.BODY_HTML_URL}" size="35" class="text" />
				<span class="m-texte">({load_html_by_url.L_FORMAT_HTML})</span>
			</td>
		</tr>
		<!-- END load_html_by_url -->
		<!-- BEGIN load_multi_by_url -->
		<tr id="loadByURL">
			<td class="row1" rowspan="2"><label>{load_multi_by_url.L_LOAD_BY_URL}&#160;:</label></td>
			<td class="row2">
				<input type="text" name="body_text_url" value="{load_multi_by_url.BODY_TEXT_URL}" size="35" class="text" />
				<span class="m-texte">({load_multi_by_url.L_FORMAT_TEXT})</span>
			</td>
		</tr>
		<tr id="loadByURL-bis">
			<td class="row2">
				<input type="text" name="body_html_url" value="{load_multi_by_url.BODY_HTML_URL}" size="35" class="text" />
				<span class="m-texte">({load_multi_by_url.L_FORMAT_HTML})</span>
			</td>
		</tr>
		<!-- END load_multi_by_url -->
	</table>
	
	<div class="bottom">{S_HIDDEN_FIELDS}
		<input type="submit" value="{L_VALID_BUTTON}" class="pbutton" />
	</div>
</div>
</form> 