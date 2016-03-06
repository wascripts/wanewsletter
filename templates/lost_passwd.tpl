<form class="compact" method="post" action="{S_SCRIPT_NAME}?mode={S_MODE}">
<ul class="links">
	<li><a href="{S_SCRIPT_NAME}?mode=login">{L_LOG_IN}</a></li>
</ul>

<div class="block">
	<h2>{TITLE}</h2>

	<div class="explain">{L_EXPLAIN}</div>

	<table class="dataset">
		<tr>
			<td><label for="login">{L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="login" name="login" autocomplete="off"
				maxlength="254" size="25" autofocus />
			</td>
		</tr>
	</table>

	<div class="bottom">
		<button type="submit" name="submit" class="primary">{L_VALID_BUTTON}</button>
	</div>
</div>
</form>
