<!--[if gte IE 9]><!-->
<script type="text/javascript">
<!--
DOM_Events.addListener('load', function() {
	document._glueBox_ = document.getElementById('glue').parentNode.parentNode;
	
	DOM_Events.addListener('change', function() {
		if( this.checked == true ) {
			document._glueBox_.style.display = 'table-row';
		}
	}, false, document.forms['export-form'].elements['export-format-text']);
	
	DOM_Events.addListener('change', function() {
		if( this.checked == true ) {
			document._glueBox_.style.display = 'none';
		}
	}, false, document.forms['export-form'].elements['export-format-xml']);
}, false, document);
//-->
</script>
<!--<![endif]-->

<form id="export-form" method="post" action="./tools.php?mode=export">
<div class="smallbloc">
	<h2>{L_TITLE_EXPORT}</h2>
	
	<table class="content">
		<tr>
			<td class="explain" colspan="2">{L_EXPLAIN_EXPORT}</td>
		</tr>
		<tr>
			<td class="row1"><label>{L_EXPORT_FORMAT}&nbsp;:</label></td>
			<td class="row2">
				<input type="radio" id="export-format-text" name="eformat" value="text" checked="checked" />
				<label for="export-format-text" class="m-texte">{L_PLAIN_TEXT}</label>
				<input type="radio" id="export-format-xml" name="eformat" value="xml" />
				<label for="export-format-xml" class="m-texte"><abbr title="eXtensible Markup Language" xml:lang="en" lang="en">XML</abbr></label>
			</td>
		</tr>
		<tr>
			<td class="row1"><label for="glue">{L_GLUE}&nbsp;:</label></td>
			<td class="row2"><input type="text" id="glue" name="glue" size="3" maxlength="3" class="text" /></td>
		</tr>
		<!-- BEGIN format_box -->
		<tr>
			<td class="row1"><label for="format">{format_box.L_FORMAT}&nbsp;:</label></td>
			<td class="row2">{format_box.FORMAT_BOX}</td>
		</tr>
		<!-- END format_box -->
		<tr>
			<td class="row1"><label>{L_ACTION}&nbsp;:</label></td>
			<td class="row2">
				<input type="radio" id="action_dl" name="action" value="download" checked="checked" />
				<label for="action_dl" class="m-texte">{L_DOWNLOAD}</label>
				<input type="radio" id="action_store" name="action" value="store" />
				<label for="action_store" class="m-texte">{L_STORE_ON_SERVER}</label>
			</td>
		</tr>
		<!-- BEGIN compress_option -->
		<tr>
			<td class="row1"><label>{compress_option.L_COMPRESS}&nbsp;:</label></td>
			<td class="row2">
				<input type="radio" id="compress_none" name="compress" value="none" checked="checked" />
				<label for="compress_none" class="m-texte">{compress_option.L_NO}</label>
				<!-- BEGIN gzip_compress -->
				<input type="radio" id="compress_zip" name="compress" value="zip" />
				<label for="compress_zip" class="m-texte">Zip</label>
				<input type="radio" id="compress_gzip" name="compress" value="gzip" />
				<label for="compress_gzip" class="m-texte">Gzip</label>
				<!-- END gzip_compress -->
				<!-- BEGIN bz2_compress -->
				<input type="radio" id="compress_bz2" name="compress" value="bz2" />
				<label for="compress_bz2" class="m-texte">Bz2</label>
				<!-- END bz2_compress -->
			</td>
		</tr>
		<!-- END compress_option -->
	</table>
	
	<div class="bottom">{S_HIDDEN_FIELDS}
		<input type="submit" name="submit" class="pbutton" value="{L_VALID_BUTTON}" />
		<input type="reset" value="{L_RESET_BUTTON}" class="button" />
	</div>
</div>
</form>
