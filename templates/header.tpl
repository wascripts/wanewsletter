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
    {META}
    <meta http-equiv="Content-Type" content="text/html; charset={CHARSET}" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    
    <title>{PAGE_TITLE}</title>
    
    <meta name="Author" content="Bobe" />
    <meta name="Editor" content="jEdit" />
    <meta name="Copyright" content="phpCodeur (c) 2002-2005" />
    <meta name="Robots" content="noindex, nofollow, none" />
    
    <link rel="stylesheet" type="text/css" href="./templates/wanewsletter.css" media="screen" title="Wanewsletter thème" />
    
    {S_NAV_LINKS}

</head>
<body>

<div id="header">
    <p><a href="./profil_cp.php"><img src="./images/logo-wa.png" width="160" height="60" alt="{PAGE_TITLE}" title="{PAGE_TITLE}" /></a></p>
    
    <h1>{PAGE_TITLE}</h1>
</div>

<div id="menu">
    <a href="./profil_cp.php?mode=logout">{L_LOGOUT}</a> &#8226; 
    <a href="./profil_cp.php?mode=editprofile">{L_EDITPROFILE}</a> &#8226; 
    <a href="./profil_cp.php?mode=archives">{L_LOG}</a>
</div>

{ERROR_BOX}

<div id="global">
