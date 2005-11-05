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
	{META}
	<meta http-equiv="Content-Type" content="text/html; charset={CHARSET}" />
	<meta http-equiv="Content-Script-Type" content="text/javascript" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	
	<title>{PAGE_TITLE}</title>
	
	<meta name="Author" content="Bobe" />
	<meta name="Editor" content="jEdit" />
	<meta name="Copyright" content="phpCodeur (c) 2002-2005" />
	<meta name="Robots" content="noindex, nofollow, none" />
	
	<link rel="stylesheet" type="text/css" href="../templates/wanewsletter.css" media="screen" title="Wanewsletter thème" />
	
	{S_NAV_LINKS}
	
	{S_SCRIPTS}
</head>
<body id="top">

<div id="header">
	<div id="logo">
		<a href="./index.php">
			<img src="../images/logo-wa.png" width="160" height="60" alt="{L_INDEX}" title="{L_INDEX}" />
		</a>
	</div>
	
	<h1>{SITENAME}
	<!-- BEGIN display_liste -->
	<br />{display_liste.LISTE_NAME}
	<!-- END display_liste -->
	</h1>
</div>

<ul id="menu">
	<li><a href="./login.php?mode=logout">{L_LOGOUT}</a></li>
	<li><a href="./config.php">{L_CONFIG}</a></li>
	<li><a href="./envoi.php">{L_SEND}</a></li>
	<li><a href="./view.php?mode=abonnes">{L_SUBSCRIBERS}</a></li>
	<li><a href="./view.php?mode=liste">{L_LIST}</a></li>
	<li><a href="./view.php?mode=log">{L_LOG}</a></li>
	<li><a href="./tools.php">{L_TOOLS}</a></li>
	<li><a href="./admin.php">{L_USERS}</a></li>
	<li><a href="./stats.php">{L_STATS}</a></li>
</ul>

{ERROR_BOX}

<div id="global">
