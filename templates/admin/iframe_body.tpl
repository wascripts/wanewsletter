<form id="view-log" method="get" action="view.php#view">
<div class="block">
	<h2 id="view">{L_SUBJECT}&nbsp;: <q>{SUBJECT}</q></h2>

	<div class="textinput">
		<object id="iframe" data="view.php?mode=iframe&amp;id={LOG_ID}&amp;format={FORMAT}"
			type="text/html" width="600" height="500"><p>Unknown error</p></object>
	</div>
	<div id="log-details">
		<span><em>{L_NUMDEST}&nbsp;:</em> {NUMDEST}</span>
		<!-- BEGIN export -->
		<span>
			<a href="view.php?mode=export&amp;id={LOG_ID}" title="{export.L_EXPORT_T}">
				<img src="../templates/images/archive.png" alt="{export.L_EXPORT}"
					onmouseover="this.src = '../templates/images/archive-hover.png';"
					onmouseout="this.src = '../templates/images/archive.png';"
				/>
			</a>
		</span>
		<!-- END export -->
	</div>

	{JOINED_FILES_BOX}

	<div class="bottom"> {S_HIDDEN_FIELDS}
		<label for="format" class="notice">{L_FORMAT}&nbsp;:</label> {FORMAT_BOX}
		<button type="submit">{L_GO_BUTTON}</button>
	</div>
	<script>
	<!--
	document.addEventListener('DOMContentLoaded', function () {
		document.forms['view-log'].elements['format'].addEventListener('change', function () {
			this.form.submit();
		}, false);
	}, false);
	//-->
	</script>
</div>
</form>
