<table id="files-box" class="listing">
	<tr>
		<th>#</th>
		<th>{L_FILENAME}</th>
		<th>{L_FILESIZE}</th>
		<!-- BEGIN del_column -->
		<th></th>
		<!-- END del_column -->
	</tr>
	<!-- BEGIN file_info -->
	<tr>
		<th scope="row">{file_info.OFFSET}</th>
		<td>{file_info.S_SHOW} {file_info.FILENAME}</td>
		<td>{file_info.FILESIZE}</td>
		<!-- BEGIN delete_options -->
		<td><input type="checkbox" name="file_ids[]" value="{file_info.delete_options.FILE_ID}" /></td>
		<!-- END delete_options -->
	</tr>
	<!-- END file_info -->
	<tr>
		<td colspan="{S_ROWSPAN}"><em>{L_TOTAL_LOG_SIZE}&nbsp;:</em> {TOTAL_LOG_SIZE}</td>
	</tr>
</table>
