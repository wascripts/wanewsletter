<form class="compact" method="post" action="upgrade.php">
<div class="block">
	<h2>{L_TITLE_UPGRADE}</h2>

	<p>{MESSAGE}</p>
	<!-- BEGIN moved_dirs -->
	<p>{moved_dirs.MOVED_DIRS_NOTICE}</p>
	<!-- END moved_dirs -->
	<!-- BEGIN unknown_files -->
	<div class="message"><p>{unknown_files.NOTICE}</p>
	<pre>{unknown_files.LISTING}</pre></div>
	<!-- END unknown_files -->
	<!-- BEGIN download_file -->
	<div class="bottom">
		<button type="submit" name="sendfile" class="primary">{download_file.L_DL_BUTTON}</button>
	</div>
	<!-- END download_file -->
</div>
</form>
