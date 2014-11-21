<!DOCTYPE html>
<html lang="{CONTENT_LANG}" dir="{CONTENT_DIR}">
<head>
	<meta charset="UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="Robots" content="noindex, nofollow, none" />
	{META}

	<title>{PAGE_TITLE}</title>

	<link rel="stylesheet" href="{BASEDIR}/templates/wanewsletter.css" />

	{S_NAV_LINKS}

</head>
<body>

<div id="header">
	<div id="logo">
		<a href="./profil_cp.php">
			<img src="{BASEDIR}/images/logo-wa.png" width="160" height="60" alt="{PAGE_TITLE}" title="{PAGE_TITLE}" />
		</a>
	</div>

	<h1>{PAGE_TITLE}</h1>
</div>

<ul id="menu">
	<li><a href="./profil_cp.php?mode=logout">{L_LOGOUT}</a></li>
	<li><a href="./profil_cp.php?mode=editprofile">{L_EDITPROFILE}</a></li>
	<li><a href="./profil_cp.php?mode=archives">{L_LOG}</a></li>
</ul>

<div id="global">

{ERROR_BOX}
