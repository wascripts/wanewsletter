<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!--
	Copyright (c) 2002-2006 Aurélien Maille
	
	Wanewsletter is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License 
	as published by the Free Software Foundation; either version 2 
	of the License, or (at your option) any later version.
	
	Wanewsletter is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with Wanewsletter; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
-->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{CONTENT_LANG}" lang="{CONTENT_LANG}" dir="{CONTENT_DIR}">
<head>
	<title>{PAGE_TITLE}</title>
	
	<meta name="Author" content="Bobe" />
	<meta name="Editor" content="jEdit" />
	<meta name="Copyright" content="phpCodeur (c) 2002-2005" />
	<meta name="Robots" content="noindex, nofollow, none" />
	
	<link rel="stylesheet" type="text/css" href="../templates/wanewsletter.css" media="screen" title="Wanewsletter thème" />
</head>
<body>

<div id="header">
	<p><img src="../images/logo-wa.png" width="160" height="60" alt="{PAGE_TITLE}" title="{PAGE_TITLE}" /></p>
	
	<h1>{PAGE_TITLE}</h1>
</div>

{ERROR_BOX}

<form method="post" action="./install.php">
<div id="global">
	
	<!-- BEGIN welcome -->
	<div class="bloc"><p>{welcome.L_WELCOME}</p></div>
	
	<div class="bloc">
	<h2>{welcome.TITLE_DATABASE}</h2>
	
	<table class="content">
		<tr>
			<td class="medrow1"> <label for="dbtype">{welcome.L_DBTYPE}&#160;:</label> </td>
			<td class="medrow2"> {welcome.DB_BOX} </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="dbhost">{welcome.L_DBHOST}&#160;:</label> </td>
			<td class="medrow2"> <input type="text" id="dbhost" name="dbhost" size="30" value="{welcome.DBHOST}" class="text" /> </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="dbname">{welcome.L_DBNAME}&#160;:</label> </td>
			<td class="medrow2"> <input type="text" id="dbname" name="dbname" size="30" value="{welcome.DBNAME}" class="text" /> </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="dbuser">{welcome.L_DBUSER}&#160;:</label> </td>
			<td class="medrow2"> <input type="text" id="dbuser" name="dbuser" size="30" value="{welcome.DBUSER}" class="text" /> </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="dbpassword">{welcome.L_DBPWD}&#160;:</label> </td>
			<td class="medrow2"> <input type="password" id="dbpassword" name="dbpassword" size="30" class="text" /> </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="prefixe">{welcome.L_PREFIXE}&#160;:</label> </td>
			<td class="medrow2"> <input type="text" id="prefixe" name="prefixe" size="10" value="{welcome.PREFIXE}" class="text" /> </td>
		</tr>
	</table>
	
	<h2>{welcome.TITLE_ADMIN}</h2>
	
	<table class="content">
		<tr>
			<td class="medrow1"> <label for="language">{welcome.L_DEFAULT_LANG}&#160;:</label> </td>
			<td class="medrow2"> {welcome.LANG_BOX} </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="admin_login">{welcome.L_LOGIN}&#160;:</label> </td>
			<td class="medrow2"> <input type="text" id="admin_login" name="admin_login" size="30" value="{welcome.LOGIN}" maxlength="20" class="text" /> </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="admin_pass">{welcome.L_PASS}&#160;:</label> </td>
			<td class="medrow2"> <input type="password" id="admin_pass" name="admin_pass" size="25" maxlength="25" class="text" /> </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="confirm_pass">{welcome.L_PASS_CONF}&#160;:</label> </td>
			<td class="medrow2"> <input type="password" id="confirm_pass" name="confirm_pass" size="25" maxlength="25" class="text" /> </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="admin_email">{welcome.L_EMAIL}&#160;:</label> </td>
			<td class="medrow2"> <input type="text" id="admin_email" name="admin_email" size="30" value="{welcome.EMAIL}" maxlength="100" class="text" /> </td>
		</tr>
	</table>
	
	<h2>{welcome.TITLE_DIVERS}</h2>
	
	<table class="content">
		<tr>
			<td class="medrow1"> <label for="urlsite">{welcome.L_URLSITE}&#160;:</label><br /><span class="m-texte">{L_URLSITE_NOTE}</span> </td>
			<td class="medrow2"> <input type="text" id="urlsite" name="urlsite" size="30" value="{welcome.URLSITE}" maxlength="100" class="text" /> </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="urlscript">{welcome.L_URLSCRIPT}&#160;:</label><br /><span class="m-texte">{L_URLSCRIPT_NOTE}</span> </td>
			<td class="medrow2"> <input type="text" id="urlscript" name="urlscript" size="30" value="{welcome.URLSCRIPT}" maxlength="100" class="text" /> </td>
		</tr>
	</table>
	
	<div class="bottom"> {welcome.S_HIDDEN_FIELD}
		<input type="submit" name="start" value="{welcome.L_BUTTON_START}" class="pbutton" />
	</div>
	
	</div>
	<!-- END welcome -->
	
	<!-- BEGIN reinstall -->
	<div class="bloc"><p>{reinstall.L_EXPLAIN_REINSTALL}</p></div>
	
	<div class="bloc">
	<h2>{PAGE_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="medrow1"> <label for="admin_login">{reinstall.L_LOGIN}&#160;:</label> </td>
			<td class="medrow2"> <input type="text" id="admin_login" name="admin_login" maxlength="25" size="25" class="text" /> </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="admin_pass">{reinstall.L_PASS}&#160;:</label> </td>
			<td class="medrow2"> <input type="password" id="admin_pass" name="admin_pass" maxlength="25" size="25" class="text" /> </td>
		</tr>
		<tr>
			<td class="medrow1"> <label for="type">{reinstall.L_SELECT_TYPE}&#160;:</label> </td>
			<td class="medrow2"> <select id="type" name="type"><option value="reinstall"> - {reinstall.L_TYPE_REINSTALL} - </option><option value="update" selected="selected"> - {reinstall.L_TYPE_UPDATE} - </option></select> </td>
		</tr>
	</table>
	
	<div class="bottom"> {welcome.S_HIDDEN_FIELD}
		<input type="submit" name="confirm" value="{reinstall.L_CONF_BUTTON}" class="pbutton" />
	</div>
	</div>
	<!-- END reinstall -->
	
	<!-- BEGIN result -->
	<div class="bloc">
	<h2>{result.L_TITLE}</h2>
	
	<p>{result.MSG_RESULT}</p>
	</div>
	<!-- END result -->
	
	<!-- BEGIN download_file -->
	<div class="bloc">
	<h2>{download_file.L_TITLE}</h2>
	
	<p>{download_file.MSG_RESULT}</p>
	
	<div class="bottom"> {download_file.S_HIDDEN_FIELDS}
		<input type="submit" name="send_file" value="{download_file.L_DL_BUTTON}" class="pbutton" />
	</div>
	</div>
	<!-- END download_file -->
	
</div>
</form>

<hr />

<address id="footer">
Powered by <a href="http://phpcodeur.net/" hreflang="fr" title="Site officiel de WAnewsletter">
phpCodeur</a> &copy; 2002-2005 | WAnewsletter {NEW_VERSION} {TRANSLATE}<br />
Ce script est distribué librement sous <a href="http://phpcodeur.net/wascripts/GPL" hreflang="fr">
licence <acronym title="General Public Licence" xml:lang="en" lang="en">GPL</acronym></a>
</address>

</body>
</html>
