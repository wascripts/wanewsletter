
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
			<td><progress value="{logrow.TOTAL_SENT}" max="{logrow.TOTAL}" title="{logrow.SENT_PERCENT}&nbsp;%">{logrow.SENT_PERCENT}&nbsp;%</progress></td>
			<td><a href="./envoi.php?mode=send&amp;id={logrow.LOG_ID}">{logrow.L_DO_SEND}</a></td>
			<td><a href="./envoi.php?mode=cancel&amp;id={logrow.LOG_ID}">{logrow.L_CANCEL_SEND}</a></td>
		</tr>
		<!-- END logrow -->
		<!-- BEGIN logrow2 -->
		<tr>
			<td>{logrow2.LOG_SUBJECT}</td>
			<td><strong>{logrow2.NO_SUBSCRIBERS}</strong></td>
			<td><del>{logrow2.L_DO_SEND}</del></td>
			<td><a href="./envoi.php?mode=cancel&amp;id={logrow2.LOG_ID}">{logrow2.L_CANCEL_SEND}</a></td>
		</tr>
		<!-- END logrow2 -->
	</table>
</div>

