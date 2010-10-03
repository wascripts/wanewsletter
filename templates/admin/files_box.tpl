<table id="files-box" class="content">
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
		<td class="row1">{file_info.S_SHOW} {file_info.FILENAME}</td>
		<td class="row1">{file_info.FILESIZE}</td>
		<!-- BEGIN delete_options -->
		<td class="row2"><input type="checkbox" name="file_ids[]" value="{file_info.delete_options.FILE_ID}" /></td>
		<!-- END delete_options -->
	</tr>
	<!-- END file_info -->
	<tr>
		<td class="row2" colspan="{S_ROWSPAN}">{L_TOTAL_LOG_SIZE}&nbsp;: {TOTAL_LOG_SIZE}</td>
	</tr>
</table>
