<form method="post" action="./tools.php?mode=backup">
<div class="bloc">
	<h2>{L_TITLE_BACKUP}</h2>
	
	<table class="content">
		<tr>
			<td class="explain" colspan="2"> {L_EXPLAIN_BACKUP} </td>
		</tr>
		<tr>
			<td class="row1"> <label>{L_BACKUP_TYPE}&nbsp;:</label> </td>
			<td class="row2"> <input type="radio" id="backup_type_full" name="backup_type" value="0" checked="checked" /> <label for="backup_type_full" class="m-texte">{L_FULL}</label> &nbsp; <input type="radio" id="backup_type_structure" name="backup_type" value="1" /> <label for="backup_type_structure" class="m-texte">{L_STRUCTURE}</label> &nbsp; <input type="radio" id="backup_type_data" name="backup_type" value="2" /> <label for="backup_type_data" class="m-texte">{L_DATA}</label> </td>
		</tr>				
		<tr>
			<td class="row1"> <label>{L_DROP_OPTION}&nbsp;:</label> </td>
			<td class="row2"> <input type="radio" id="drop_option_yes" name="drop_option" value="1" checked="checked" /> <label for="drop_option_yes" class="m-texte">{L_YES}</label> &nbsp; <input type="radio" id="drop_option_no" name="drop_option" value="0" /> <label for="drop_option_no" class="m-texte">{L_NO}</label> </td>
		</tr>
		<tr>
			<td class="row1"> <label>{L_ACTION}&nbsp;:</label> </td>
			<td class="row2"> <input type="radio" id="action_dl" name="action" value="download" checked="checked" /> <label for="action_dl" class="m-texte">{L_DOWNLOAD}</label> &nbsp; <input type="radio" id="action_store" name="action" value="store" /> <label for="action_store" class="m-texte">{L_STORE_ON_SERVER}</label> </td>
		</tr>
		<!-- BEGIN tables_box -->
		<tr>
			<td class="row1"> <label for="tables_plus">{tables_box.L_ADDITIONAL_TABLES}&nbsp;:</label> </td>
			<td class="row2"> {tables_box.S_TABLES_BOX} </td>
		</tr>
		<!-- END tables_box -->
		<!-- BEGIN compress_option -->
		<tr>
			<td class="row1"> <label>{compress_option.L_COMPRESS}&nbsp;:</label> </td>
			<td class="row2"> 
				<input type="radio" id="compress_none" name="compress" value="none" checked="checked" /> <label for="compress_none" class="m-texte">{L_NO}</label> &nbsp; 
				<!-- BEGIN gzip_compress -->
				<input type="radio" id="compress_zip" name="compress" value="zip" /> <label for="compress_zip" class="m-texte">Zip</label> &nbsp; 
				<input type="radio" id="compress_gzip" name="compress" value="gzip" /> <label for="compress_gzip" class="m-texte">Gzip</label> &nbsp; 
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
