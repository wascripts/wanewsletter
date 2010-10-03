<div class="bloc">
	<h2 id="view">{L_SUBJECT}&nbsp;: {SUBJECT}</h2>
	
	<table class="content">
		<tr>
			<td class="row-full">
				<object id="iframe" codebase="{S_CODEBASE}" data="{U_FRAME}" type="text/html" standby="Loading... Please wait."></object>
			</td>
		</tr>
		<tr>
			<td id="loginfos" class="row1">
				<span>{L_NUMDEST}&nbsp;: {S_NUMDEST}</span>
				<span>
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
	
	<!-- BEGIN format_box -->
	<div class="bottom">
		<span class="m-texte">{format_box.L_FORMAT}&nbsp;:</span> {format_box.FORMAT_BOX} <input type="submit" value="{format_box.L_GO_BUTTON}" class="button" />
	</div>
	<!-- END format_box -->
</div>
