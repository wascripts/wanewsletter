<div class="bloc">
	<h2 id="view">{L_SUBJECT}&#160;: &#160; {SUBJECT}</h2>
	
	<table class="content">
		<tr>
			<td class="row-full">
				<object id="iframe" codebase="{S_CODEBASE}" data="{U_FRAME}" type="text/html" standby="Loading... Please wait."></object>
			</td>
		</tr>
		<tr>
			<td class="row1">
				<span class="m-texte">{L_NUMDEST}&#160;: {S_NUMDEST}</span>
			</td>
		</tr>
	</table>
	
	<!-- BEGIN files_box -->
	<h2>{files_box.L_TITLE_JOINED_FILES}</h2>
	
	{JOINED_FILES_BOX}
	<!-- END joined_files -->
	
	<div class="bottom">
		<!-- BEGIN format_box -->
		<span class="m-texte">{format_box.L_FORMAT}&#160;:</span> {format_box.FORMAT_BOX} <input type="submit" value="{format_box.L_GO_BUTTON}" class="button" />
		<!-- END format_box -->
	</div>
</div>
