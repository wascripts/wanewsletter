<form class="compact" method="post" action="./tools.php?mode=restore" enctype="{S_ENCTYPE}">
<div class="block">
	<h2>{L_TITLE_RESTORE}</h2>

	<p class="explain">{L_EXPLAIN_RESTORE}</p>

	<table class="dataset">
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
	
	<div class="bottom">{S_HIDDEN_FIELDS}
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
		<button type="reset">{L_RESET_BUTTON}</button>
	</div>
</div>
</form>
