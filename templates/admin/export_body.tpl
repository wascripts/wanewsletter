<form method="post" action="./tools.php?mode=export">
<div class="smallbloc">
	<h2>{L_TITLE_EXPORT}</h2>
	
	<table class="content">
		<tr>
			<td class="explain" colspan="2"> {L_EXPLAIN_EXPORT} </td>
		</tr>				
		<tr>
			<td class="row1"> <label for="glue">{L_GLUE}&#160;:</label> </td>
			<td class="row2"> <input type="text" id="glue" name="glue" maxlength="3" style="width: 20px;" class="text" /> </td>
		</tr>
		<!-- BEGIN format_box -->
		<tr>
			<td class="row1"> <label for="format">{format_box.L_FORMAT}&#160;:</label> </td>
			<td class="row2"> {format_box.FORMAT_BOX} </td>
		</tr>
		<!-- END format_box -->
		<tr>
			<td class="row1"> <label>{L_ACTION}&#160;:</label> </td>
			<td class="row2"> <input type="radio" id="action_dl" name="action" value="download" checked="checked" /> <label for="action_dl" class="m-texte">{L_DOWNLOAD}</label> &#160; <input type="radio" id="action_store" name="action" value="store" /> <label for="action_store" class="m-texte">{L_STORE_ON_SERVER}</label> </td>
		</tr>
		<!-- BEGIN compress_option -->
		<tr>
			<td class="row1"> <label>{compress_option.L_COMPRESS}&#160;:</label> </td>
			<td class="row2">
				<input type="radio" id="compress_none" name="compress" value="none" checked="checked" /> <label for="compress_none" class="m-texte">{compress_option.L_NO}</label> &#160; 
				<!-- BEGIN gzip_compress -->
				<input type="radio" id="compress_zip" name="compress" value="zip" /> <label for="compress_zip" class="m-texte">Zip</label> &#160; 
				<input type="radio" id="compress_gzip" name="compress" value="gzip" /> <label for="compress_gzip" class="m-texte">Gzip</label> &#160; 
				<!-- END gzip_compress -->
				<!-- BEGIN bz2_compress -->
				<input type="radio" id="compress_bz2" name="compress" value="bz2" /> <label for="compress_bz2" class="m-texte">Bz2</label> 
				<!-- END bz2_compress -->
			</td>
		</tr>
		<!-- END compress_option -->
	</table>
	
	<div class="bottom"> {S_HIDDEN_FIELDS}
		<input type="submit" name="submit" class="pbutton" value="{L_VALID_BUTTON}" /> <input type="reset" value="{L_RESET_BUTTON}" class="button" />
	</div>
</div>
</form>
