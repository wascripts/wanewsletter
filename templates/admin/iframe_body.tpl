<div class="bloc">
	<h2 id="view">{L_SUBJECT}&#160;: {SUBJECT}</h2>
	
	<table class="content">
		<tr>
			<td class="row-full">
				<object id="iframe" codebase="{S_CODEBASE}" data="{U_FRAME}" type="text/html" standby="Loading... Please wait."></object>
			</td>
		</tr>
		<tr>
			<td class="row1" style="padding-top: 5px;">
				<span class="m-texte" style="float: left;">{L_NUMDEST}&#160;: {S_NUMDEST}</span>
				<span class="m-texte" style="display: block;text-align: right;">
					<a href="{U_EXPORT}" title="{L_EXPORT_T}">
						<img src="../templates/images/archive.png" alt="{L_EXPORT}"
							onmouseover="this.src = '../templates/images/archive-hover.png';"
							onmouseout="this.src = '../templates/images/archive.png';"
						/>
					</a>
				</span>
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
