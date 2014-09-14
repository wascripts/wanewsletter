<form method="post" action="./tools.php?mode=import" enctype="{S_ENCTYPE}">
<div class="block compact">
	<h2>{L_TITLE_IMPORT}</h2>
	
	<div class="explain">{L_EXPLAIN_IMPORT}</div>
	
	<table class="dataset">
		<tr>
			<td><label for="glue">{L_GLUE}&nbsp;:</label></td>
			<td><input type="text" id="glue" name="glue" maxlength="3" class="number" /></td>
		</tr>
		<!-- BEGIN format_box -->
		<tr>
			<td><label for="format">{format_box.L_FORMAT}&nbsp;:</label></td>
			<td>{format_box.FORMAT_BOX}</td>
		</tr>
		<!-- END format_box -->
		<tr>
			<td colspan="2"><textarea name="list_email" rows="8" cols="60"></textarea></td> 
		</tr>
		<!-- BEGIN upload_file -->
		<tr>
			<td>
				<label for="file_upload">{upload_file.L_FILE_UPLOAD}&nbsp;:</label><br />
				<span class="notice">({upload_file.L_MAXIMUM_SIZE})</span>
			</td>
			<td>
				<input type="hidden" name="MAX_FILE_SIZE" value="{upload_file.MAX_FILE_SIZE}" />
				<input type="file" id="file_upload" name="file_upload" data-button-label="{upload_file.L_BROWSE_BUTTON}" />
			</td>
		</tr>
		<!-- END upload_file -->
		<tr>
			<td><label for="file_local">{L_FILE_LOCAL}&nbsp;:</label></td>
			<td><input type="text" id="file_local" name="file_local" size="25" /></td>
		</tr>
	</table>
	
	<div class="bottom"> {S_HIDDEN_FIELDS}
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
		<button type="reset">{L_RESET_BUTTON}</button>
	</div>
</div>
</form>
