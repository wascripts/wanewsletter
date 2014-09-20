<script>
<!--
document.addEventListener('DOMContentLoaded', function() {
	document._glueBox_ = document.getElementById('glue').parentNode.parentNode;
	var form = document.forms['export-form'];
	
	form.elements['export-format-text'].addEventListener('change', function() {
		if( this.checked == true ) {
			document._glueBox_.style.display = 'table-row';
		}
	}, false);
	
	form.elements['export-format-xml'].addEventListener('change', function() {
		if( this.checked == true ) {
			document._glueBox_.style.display = 'none';
		}
	}, false);
}, false);
//-->
</script>

<form id="export-form" method="post" action="./tools.php?mode=export">
<div class="block compact">
	<h2>{L_TITLE_EXPORT}</h2>
	
	<p class="explain">{L_EXPLAIN_EXPORT}</p>
	
	<table class="dataset">
		<tr>
			<td><label>{L_EXPORT_FORMAT}&nbsp;:</label></td>
			<td>
				<input type="radio" id="export-format-text" name="eformat" value="text" checked="checked" />
				<label for="export-format-text" class="notice">{L_PLAIN_TEXT}</label>
				<input type="radio" id="export-format-xml" name="eformat" value="xml" />
				<label for="export-format-xml" class="notice"><abbr title="eXtensible Markup Language" lang="en">XML</abbr></label>
			</td>
		</tr>
		<tr>
			<td><label for="glue">{L_GLUE}&nbsp;:</label></td>
			<td><input type="text" id="glue" name="glue" size="3" maxlength="3" /></td>
		</tr>
		<!-- BEGIN format_box -->
		<tr>
			<td><label for="format">{format_box.L_FORMAT}&nbsp;:</label></td>
			<td>{format_box.FORMAT_BOX}</td>
		</tr>
		<!-- END format_box -->
		<tr>
			<td><label>{L_ACTION}&nbsp;:</label></td>
			<td>
				<input type="radio" id="action_dl" name="action" value="download" checked="checked" />
				<label for="action_dl" class="notice">{L_DOWNLOAD}</label>
				<input type="radio" id="action_store" name="action" value="store" />
				<label for="action_store" class="notice">{L_STORE_ON_SERVER}</label>
			</td>
		</tr>
		<!-- BEGIN compress_option -->
		<tr>
			<td><label>{compress_option.L_COMPRESS}&nbsp;:</label></td>
			<td>
				<input type="radio" id="compress_none" name="compress" value="none" checked="checked" />
				<label for="compress_none" class="notice">{compress_option.L_NO}</label>
				<!-- BEGIN zip_compress -->
				<input type="radio" id="compress_zip" name="compress" value="zip" />
				<label for="compress_zip" class="notice">Zip</label>
				<!-- END zip_compress -->
				<!-- BEGIN gzip_compress -->
				<input type="radio" id="compress_gzip" name="compress" value="gzip" />
				<label for="compress_gzip" class="notice">Gzip</label>
				<!-- END gzip_compress -->
				<!-- BEGIN bz2_compress -->
				<input type="radio" id="compress_bz2" name="compress" value="bz2" />
				<label for="compress_bz2" class="notice">Bz2</label>
				<!-- END bz2_compress -->
			</td>
		</tr>
		<!-- END compress_option -->
	</table>
	
	<div class="bottom">{S_HIDDEN_FIELDS}
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
		<button type="reset">{L_RESET_BUTTON}</button>
	</div>
</div>
</form>
