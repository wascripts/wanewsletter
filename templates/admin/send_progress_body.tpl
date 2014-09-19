
<ul class="links">
	<li><a href="./envoi.php">{L_CREATE_LOG}</a></li>
	<li><a href="./envoi.php?mode=load">{L_LOAD_LOG}</a></li>
</ul>

<div class="block">
	<h2>{L_TITLE}</h2>
	
	<table id="progress-list" class="listing">
		<tr>
			<th>{L_SUBJECT}</th>
			<th>{L_DONE}</th>
			<th colspan="2">&nbsp;</th>
		</tr>
		<!-- BEGIN logrow -->
		<tr>
			<td>{logrow.LOG_SUBJECT}</td>
			<td><progress value="{logrow.SEND_PERCENT}" max="100" title="{logrow.SEND_PERCENT}&nbsp;%">{logrow.SEND_PERCENT}&nbsp;%</progress></td>
			<td><a href="./envoi.php?mode=progress&amp;id={logrow.LOG_ID}">{L_DO_SEND}</a></td>
			<td><a href="./envoi.php?mode=cancel&amp;id={logrow.LOG_ID}">{L_CANCEL_SEND}</a></td>
		</tr>
		<!-- END logrow -->
	</table>
</div>

