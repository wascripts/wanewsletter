<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{CONTENT_LANG}" lang="{CONTENT_LANG}" dir="{CONTENT_DIR}">
<head>
	<!-- 
		This program is free software; you can redistribute it and/or 
		modify it under the terms of the GNU General Public License as 
		published by the Free Software Foundation; either version 2 of 
		the License, or (at your option) any later version.
		
		http://www.gnu.org/copyleft/gpl.html (english)
		http://www.april.org/gnu/gpl_french.html (french)
	-->
	
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
	
	<script type="text/javascript" src="../templates/compatible.js"></script>
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