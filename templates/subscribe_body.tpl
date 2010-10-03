<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!--
	Copyright (c) 2002-2010 Aurélien Maille
	
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
<!-- $Id$ -->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
	<meta http-equiv="Content-Script-Type" content="text/javascript" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	
	<title>{PAGE_TITLE}</title>
	
	<meta name="Author" content="Bobe" />
	<meta name="Editor" content="jEdit" />
	
	<style type="text/css" media="screen">
	body { font: .8em "Bitstream Vera Sans", Verdana, Arial, sans-serif; }
	
	form#subscribe-form { width: 60%; margin: 30px auto 15px; }
	form#subscribe-form fieldset	   { border: 1px dashed #79C; padding: 10px; }
	form#subscribe-form legend		   { background-color: white; padding: 1px 4px; color: black; }
	form#subscribe-form div			   { padding: 5px 8px; }
	form#subscribe-form div.bloc label { display: block; float: left; width: 30%; margin-top: .2em; cursor: pointer; }
	form#subscribe-form div label	   { cursor: pointer; }
	form#subscribe-form div.center	   { text-align: center; }
	form#subscribe-form p.message	   { text-align: center; }
	
	form#subscribe-form select,
	form#subscribe-form input[type="text"]  { border: 1px inset silver; }
	
	abbr[title] { cursor: help; }
	address#footer {
		margin: 15px auto;
		text-align: center;
		font-style: normal;
		font-size: 11px;
	}
	</style>
	
	<script type="text/javascript">
	<!--
	var submitted = false;
	
	function check_form(evt)
	{
		var emailAddr   = document.forms['subscribe-form'].elements['email'].value;
		var cancelEvent = null;
		
		if( emailAddr.indexOf('@', 1) == -1 || emailAddr.indexOf('.', 1) == -1 ) {
			window.alert('{L_INVALID_EMAIL}');
			cancelEvent = true;
		}
		else if( submitted == true ) {
			window.alert('{L_PAGE_LOADING}');
			cancelEvent = true;
		}
		else {
			submitted = true;
		}
		
		if( cancelEvent == true ) {
			if( evt && typeof(evt.preventDefault) != 'undefined' ) { // standard
				evt.preventDefault();
			}
			else { // MS
				window.event.returnValue = false;
			}
		}
	}
	
	window.onload = function() {
		document.forms['subscribe-form'].onsubmit = check_form;
	}
	//-->
	</script>
</head>
<body>

<form id="subscribe-form" method="post" action="./subscribe.php">
<fieldset>
	<legend xml:lang="en" lang="en">Mailing liste</legend>
	
	<div class="bloc">
		<label for="email">{L_EMAIL}&nbsp;:</label>
		<input type="text" id="email" name="email" size="25" maxlength="250" />
	</div>
	
	<div class="bloc">
		<label for="format">{L_FORMAT}&nbsp;:</label>
		<select id="format" name="format"><option value="1">TXT</option><option value="2">HTML</option></select>
	</div>
	
	<div class="bloc">
		<label for="liste">{L_DIFF_LIST}&nbsp;:</label>
		{LIST_BOX}
	</div>
	
	<div class="center">
		<label><input type="radio" name="action" value="inscription" checked="checked" /> {L_SUBSCRIBE}</label>
		<label><input type="radio" name="action" value="setformat" /> {L_SETFORMAT}</label>
		<label><input type="radio" name="action" value="desinscription" /> {L_UNSUBSCRIBE}</label>
	</div>
	
	<p class="message">{MESSAGE}</p>
	
	<div class="center"><input type="submit" name="wanewsletter" value="{L_VALID_BUTTON}" /></div>
</fieldset>
</form>

<address id="footer">
Powered by <a href="http://phpcodeur.net/" hreflang="fr" title="Site officiel de Wanewsletter">
phpCodeur</a> &copy; 2002&ndash;2006 | Wanewsletter<br />
Ce script est distribué librement sous <a href="http://phpcodeur.net/wascripts/GPL" hreflang="fr">
licence <abbr title="General Public Licence" xml:lang="en" lang="en">GPL</abbr></a>
</address>

</body>
</html>
