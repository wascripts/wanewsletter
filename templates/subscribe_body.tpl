<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="ISO-8859-1" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />

	<title>{PAGE_TITLE}</title>

	<style>
	body { font: .8em "DejaVu Sans", "Bitstream Vera Sans", Verdana, Geneva, sans-serif; }

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

	<script>
	<!--
	var submitted = false;

	function check_form()
	{
		var emailAddr   = document.forms['subscribe-form'].elements['email'].value;
		var cancelEvent = null;

		if( emailAddr.indexOf('@', 1) == -1 ) {// Test très basique pour éviter un traitement superflu du formulaire
			window.alert('{L_INVALID_EMAIL}');
			cancelEvent = true;
		}
		else if( submitted ) {
			window.alert('{L_PAGE_LOADING}');
			cancelEvent = true;
		}
		else {
			submitted = true;
		}

		return !cancelEvent;
	}
	//-->
	</script>
</head>
<body>

<form id="subscribe-form" method="post" action="./subscribe.php" onsubmit="return check_form();">
<fieldset>
	<legend lang="en">Mailing liste</legend>

	<div class="bloc">
		<label for="email">{L_EMAIL}&nbsp;:</label>
		<input type="text" id="email" name="email" size="25" maxlength="254" />
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
phpCodeur</a> &copy; 2002&ndash;2014 | Wanewsletter<br />
Ce script est distribué librement sous <a href="http://phpcodeur.net/wascripts/GPL" hreflang="fr">
licence <abbr title="General Public Licence" lang="en">GPL</abbr></a>
</address>

</body>
</html>
