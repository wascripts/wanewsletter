<form class="compact" method="post" action="./tools.php?mode=import" enctype="{S_ENCTYPE}">
<div class="block">
	<h2>{L_TITLE_IMPORT}</h2>

	<div class="explain">{L_EXPLAIN_IMPORT}</div>

	<table class="dataset">
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
			<td colspan="2"><textarea name="list_email" rows="8" cols="60"></textarea></td>
		</tr>
		<!-- BEGIN upload_file -->
		<tr>
			<td>
				<label for="upload_file">{upload_file.L_UPLOAD_FILE}&nbsp;:</label><br />
				<span class="notice">({upload_file.L_MAXIMUM_SIZE})</span>
			</td>
			<td>
				<input type="hidden" name="MAX_FILE_SIZE" value="{upload_file.MAX_FILE_SIZE}" />
				<input type="file" id="upload_file" name="upload_file" data-button-label="{upload_file.L_BROWSE_BUTTON}" />
			</td>
		</tr>
		<!-- END upload_file -->
		<tr>
			<td><label for="local_file">{L_LOCAL_FILE}&nbsp;:</label></td>
			<td><input type="text" id="local_file" name="local_file" size="25" /></td>
		</tr>
	</table>

	<div class="bottom">
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
		<button type="reset">{L_RESET_BUTTON}</button>
	</div>
</div>
</form>
