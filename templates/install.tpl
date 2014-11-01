<!DOCTYPE html>
<html lang="{CONTENT_LANG}" dir="{CONTENT_DIR}">
<head>
	<meta charset="UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="Robots" content="noindex, nofollow, none" />

	<title>{PAGE_TITLE}</title>

	<link rel="stylesheet" href="templates/wanewsletter.css" />

	<style>
	div#global form tr.only-sqlite {
		display: none;
	}
	div#global form.is-sqlite tr.only-server {
		display: none;
	}
	div#global form.is-sqlite tr.only-sqlite {
		display: table-row;
	}
	</style>

	<script>
	function specialSQLite(engineBox)
	{
		if (engineBox.value == 'sqlite') {
			engineBox.form.className = 'is-sqlite';
		}
		else {
			engineBox.form.className = null;
		}
	}

	window.onload = function() {
		var engineBox;
		if ((engineBox = document.getElementById('engine')) != null) {
			specialSQLite(engineBox);
		}
	};
	</script>
</head>
<body>

<div id="header">
	<div id="logo">
		<img src="images/logo-wa.png" width="160" height="60" alt="{PAGE_TITLE}" title="{PAGE_TITLE}" />
	</div>

	<h1>{PAGE_TITLE}</h1>
</div>

<div id="global">
<form method="post" action="install.php" class="{IS_SQLITE}">

	{ERROR_BOX}

	<!-- BEGIN install -->
	<div class="block"><p>{install.L_EXPLAIN}</p></div>

	<div class="block">
	<h2>{install.TITLE_DATABASE}</h2>

	<table class="dataset compact">
		<tr>
			<td><label for="engine">{install.L_DBTYPE}&nbsp;:</label></td>
			<td><select id="engine" name="engine" onchange="specialSQLite(this);">{install.DB_BOX}</select></td>
		</tr>
		<tr class="only-sqlite">
			<td><label for="path">{install.L_DBPATH}&nbsp;:</label><br /><span class="notice">{install.L_DBPATH_NOTE}</span></td>
			<td><input type="text" id="path" name="path" size="40" value="{install.DBPATH}" /></td>
		</tr>
		<tr class="only-server">
			<td><label for="host">{install.L_DBHOST}&nbsp;:</label></td>
			<td><input type="text" id="host" name="host" size="30" value="{install.DBHOST}" /> (syntaxe&nbsp;: <em>host[:port]</em>)</td>
		</tr>
		<tr class="only-server">
			<td><label for="dbname">{install.L_DBNAME}&nbsp;:</label></td>
			<td><input type="text" id="dbname" name="dbname" size="30" value="{install.DBNAME}" /></td>
		</tr>
		<tr class="only-server">
			<td><label for="user">{install.L_DBUSER}&nbsp;:</label></td>
			<td><input type="text" id="user" name="user" size="30" value="{install.DBUSER}" /></td>
		</tr>
		<tr class="only-server">
			<td><label for="pass">{install.L_DBPWD}&nbsp;:</label></td>
			<td><input type="password" id="pass" name="pass" size="30" /></td>
		</tr>
		<tr>
			<td><label for="prefixe">{install.L_PREFIXE}&nbsp;:</label></td>
			<td><input type="text" id="prefixe" name="prefixe" size="10" value="{install.PREFIXE}" /></td>
		</tr>
	</table>

	<h2>{install.TITLE_ADMIN}</h2>

	<table class="dataset compact">
		<tr>
			<td><label for="language">{install.L_DEFAULT_LANG}&nbsp;:</label></td>
			<td>{install.LANG_BOX}</td>
		</tr>
		<tr>
			<td><label for="admin_login">{install.L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="admin_login" name="admin_login" value="{install.LOGIN}" size="30" maxlength="30" /></td>
		</tr>
		<tr>
			<td><label for="admin_pass">{install.L_PASS}&nbsp;:</label></td>
			<td><input type="password" id="admin_pass" name="admin_pass" size="30" autocomplete="off" /></td>
		</tr>
		<tr>
			<td><label for="confirm_pass">{install.L_PASS_CONF}&nbsp;:</label></td>
			<td><input type="password" id="confirm_pass" name="confirm_pass" size="30" autocomplete="off" /></td>
		</tr>
		<tr>
			<td><label for="admin_email">{install.L_EMAIL}&nbsp;:</label></td>
			<td><input type="text" id="admin_email" name="admin_email" value="{install.EMAIL}" size="30" maxlength="254" /></td>
		</tr>
	</table>

	<div class="bottom">
		<button type="submit" name="start" class="primary">{install.L_START_BUTTON}</button>
	</div>

	</div>
	<!-- END welcome -->

	<!-- BEGIN reinstall -->
	<div class="block"><p>{reinstall.L_EXPLAIN}</p></div>

	<div class="block">
	<h2>{PAGE_TITLE}</h2>

	<table class="dataset compact">
		<tr>
			<td><label for="admin_login">{reinstall.L_LOGIN}&nbsp;:</label></td>
			<td><input type="text" id="admin_login" name="admin_login" value="{reinstall.LOGIN}" maxlength="30" size="30" /></td>
		</tr>
		<tr>
			<td><label for="admin_pass">{reinstall.L_PASS}&nbsp;:</label> </td>
			<td><input type="password" id="admin_pass" name="admin_pass" size="30" /></td>
		</tr>
	</table>

	<div class="bottom">
		<button type="submit" name="start" class="primary">{reinstall.L_START_BUTTON}</button>
	</div>
	</div>
	<!-- END reinstall -->

	<!-- BEGIN download_file -->
	<div class="block">
	<h2>{download_file.L_TITLE}</h2>

	<p>{download_file.MSG_RESULT}</p>

	<div class="bottom"> {download_file.S_HIDDEN_FIELDS}
		<button type="submit" name="sendfile" class="primary">{download_file.L_DL_BUTTON}</button>
	</div>
	</div>
	<!-- END download_file -->

	<!-- BEGIN result -->
	<div class="block">
	<h2>{result.L_TITLE}</h2>

	<p>{result.MSG_RESULT}</p>
	</div>
	<!-- END result -->

	<input type="hidden" name="prev_language" value="{S_PREV_LANGUAGE}" />
</form>
