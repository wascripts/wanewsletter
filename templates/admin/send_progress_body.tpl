
<ul class="links">
	<li><a href="./envoi.php">{L_CREATE_LOG}</a></li>
	<li><a href="./envoi.php?mode=load">{L_LOAD_LOG}</a></li>
</ul>

<div class="bloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<th>{L_SUBJECT}</th>
			<th>{L_DONE}</th>
			<th colspan="2">&nbsp;</th>
		</tr>
		<!-- BEGIN logrow -->
		<tr>
			<td class="row1">{logrow.LOG_SUBJECT}</td>
			<td class="row1" align="right">{logrow.SEND_PERCENT}&nbsp;%</td>
			<td class="row1" nowrap><a href="./envoi.php?mode=progress&amp;id={logrow.LOG_ID}">{L_DO_SEND}</a></td>
			<td class="row1" nowrap><a href="./envoi.php?mode=cancel&amp;id={logrow.LOG_ID}">{L_CANCEL_SEND}</a></td>
		</tr>
		<!-- END logrow -->
	</table>
</div>

