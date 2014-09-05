<div class="block">
	<h2 id="view">{L_SUBJECT}&nbsp;: {SUBJECT}</h2>
	
	<div class="textinput">
		<object id="iframe" codebase="{S_CODEBASE}" data="{U_FRAME}" type="text/html" standby="Loading... Please wait."></object>
	</div>
	<div id="log-details">
		<span><em>{L_NUMDEST}&nbsp;:</em> {S_NUMDEST}</span>
		<span>
			<a href="{U_EXPORT}" title="{L_EXPORT_T}">
				<img src="../templates/images/archive.png" alt="{L_EXPORT}"
					onmouseover="this.src = '../templates/images/archive-hover.png';"
					onmouseout="this.src = '../templates/images/archive.png';"
				/>
			</a>
		</span>
	</div>
	
	<!-- BEGIN files_box -->
	<h2>{files_box.L_TITLE_JOINED_FILES}</h2>
	
	{JOINED_FILES_BOX}
	<!-- END joined_files -->
	
	<!-- BEGIN format_box -->
	<div class="bottom">
		<span class="notice">{format_box.L_FORMAT}&nbsp;:</span> {format_box.FORMAT_BOX}
		<button type="submit">{format_box.L_GO_BUTTON}</button>
	</div>
	<!-- END format_box -->
</div>
