<form method="post" action="./tools.php?mode=restore" enctype="{S_ENCTYPE}">
<div class="smallbloc">
	<h2>{L_TITLE_RESTORE}</h2>
	
	<table class="content">
		<tr>
			<td class="explain" colspan="2">{L_EXPLAIN_RESTORE}</td>
		</tr>
		<!-- BEGIN upload_file -->
		<tr>
			<td class="row1">
				<label for="file_upload">{upload_file.L_FILE_UPLOAD}&nbsp;:</label><br />
				<span class="m-texte">({upload_file.L_MAXIMUM_SIZE})</span>
			</td>
			<td class="row2">
				<input type="hidden" name="MAX_FILE_SIZE" value="{upload_file.MAX_FILE_SIZE}" />
				<input type="file" id="file_upload" name="file_upload" size="25" />
			</td>
		</tr>
		<!-- END upload_file -->
		<tr>
			<td class="row1"><label for="file_local">{L_FILE_LOCAL}&nbsp;:</label></td>
			<td class="row2"><input type="text" id="file_local" name="file_local" size="25" class="text" /></td>
		</tr>
	</table>
	
	<div class="bottom">{S_HIDDEN_FIELDS}
		<input type="submit" name="submit" value="{L_VALID_BUTTON}" class="pbutton" />
		<input type="reset" value="{L_RESET_BUTTON}" class="button" />
	</div>
</div>
</form>
