<form method="post" action="./tools.php?mode=import" enctype="{S_ENCTYPE}">
<div class="smallbloc">
	<h2>{L_TITLE_IMPORT}</h2>
	
	<table class="content">
		<tr>
			<td class="explain" colspan="2"> {L_EXPLAIN_IMPORT} </td>
		</tr>
		<tr>
			<td class="row1"> <label for="glue">{L_GLUE}&#160;:</label> </td>
			<td class="row2"> <input type="text" id="glue" name="glue" maxlength="3" style="width:20px;" class="text" /> </td>
		</tr>
		<!-- BEGIN format_box -->
		<tr>
			<td class="row1"> <label for="format">{format_box.L_FORMAT}&#160;:</label> </td>
			<td class="row2"> {format_box.FORMAT_BOX} </td>
		</tr>
		<!-- END format_box -->
		<tr>
			<td class="row-full" colspan="2"> <textarea name="list_email" rows="6" cols="60"></textarea> </td> 
		</tr>
		<!-- BEGIN upload_file -->
		<tr>
			<td class="row1"> <label for="file_upload">{upload_file.L_FILE_UPLOAD}&#160;:</label> </td>
			<td class="row2"> <input type="file" id="file_upload" name="file_upload" size="25" /> </td>
		</tr>
		<!-- END upload_file -->
		<tr>
			<td class="row1"> <label for="file_local">{L_FILE_LOCAL}&#160;:</label> </td>
			<td class="row2"> <input type="text" id="file_local" name="file_local" size="25" class="text" /> </td>
		</tr>
	</table>
	
	<div class="bottom"> {S_HIDDEN_FIELDS}
		<input type="submit" name="submit" value="{L_VALID_BUTTON}" class="pbutton" />
		<input type="reset" value="{L_RESET_BUTTON}" class="button" />
	</div>
</div>
</form>
