<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!--
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License 
	as published by the Free Software Foundation; either version 2 
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
-->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{CONTENT_LANG}" lang="{CONTENT_LANG}" dir="{CONTENT_DIR}">
<head>
	<title>{POPUP_TITLE}</title>
	
	<meta name="Author" content="Bobe" />
	<meta name="Editor" content="jEdit" />
	<meta name="Copyright" content="phpCodeur (c) 2002-2005" />
	<meta name="Robots" content="noindex, nofollow, none" />
	
	<link rel="stylesheet" type="text/css" href="../templates/admin/popup.css" media="screen" />
	<!--[if lt IE 7]>
	<style type="text/css">
	body { behavior: url("../templates/admin/fix_object.htc"); }
	</style>
	<![endif]-->
	
	<script type="text/javascript" src="../templates/DOM-Compat/DOM-Compat.js"></script>
	<script type="text/javascript" src="../templates/admin/showPopup.js"></script>
</head>
<body>

<div>
	<object id="picture" type="{MIME_TYPE}" data="{U_SHOW_IMG}" width="{WIDTH_IMG}" height="{HEIGHT_IMG}">
		{FILENAME}
	</object>
</div>

</body>
</html>
