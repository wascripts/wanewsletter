/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 */

function make_editor()
{
	['textarea1','textarea2'].forEach(function(name) {
		if (document.getElementById(name) != null) {
			var bloc   = document.getElementById(name);
			var format = bloc.id.substr((bloc.id.length - 1), 1);

			var conteneur = document.createElement('div');
			conteneur.setAttribute('class', 'bottom');

			var button = document.createElement('button');
			button.setAttribute('id', 'preview' + format);
			button.setAttribute('type', 'button');
			button.appendChild(document.createTextNode(lang['preview']));
			conteneur.appendChild(button);
			button.onclick = preview;

			conteneur.appendChild(document.createTextNode('\u00A0'));

			button = button.cloneNode(false);
			button.setAttribute('id', 'addLinks' + format);
			button.appendChild(document.createTextNode(lang['addlink']));
			conteneur.appendChild(button);
			button.onclick = addLinks;

			bloc.appendChild(conteneur);
		}
	});
}

/*
 * Fenêtre de prévisualisation des newsletters
 */
function preview()
{
	var width  = (window.screen.width - 200);
	var height = (window.screen.height - 200);
	var top    = 50;
	var left   = ((window.screen.width - width)/2);

	var subject	= document.forms['send-form'].elements['subject'].value;
	var message = '';
	var preview	= window.open('','apercu','width=' + width + ',height=' + height + ',marginleft=2,topmargin=2,left=' + left + ',top=' + top + ',toolbar=0,location=0,directories=0,status=0,scrollbars=1,copyhistory=0,menuBar=0');

	if (this.id == 'preview1') {

		message   = document.forms['send-form'].elements['body_text'].value;
		var CRLF  = new RegExp("\r?\n", "g");
		var lines = message.split(CRLF);

		//
		// WordWrap
		//
		var temp = line = '';
		var maxlen   = 78;
		var spacePos = -1;

		for (var i = 0, j = 0, m = lines.length; i < m; i++) {
			if (lines[i].length > maxlen) {
				temp = '';

				while (lines[i].length > 0) {
					line = lines[i].substr(0, maxlen);

					if (line.length >= maxlen && (spacePos = line.lastIndexOf(' ')) != -1) {
						line = line.substr(0, spacePos);
						spacePos++;
					}
					else {
						spacePos = maxlen;
					}

					temp += line;
					temp += "\r\n";

					lines[i] = lines[i].substr(spacePos, lines[i].length);
				}

				lines[i] = temp.substr(0, (temp.length - 2));
			}
		}

		message = lines.join("\r\n");
		message = message.replace("{LINKS}", "http://www.example.org");
		subject = subject.replace('&', '&amp;');
		subject = subject.replace('<', '&lt;');
		message = message.replace('&', '&amp;');
		message = message.replace('<', '&lt;');

		var boldSpan = new RegExp("(^|\\s)(\\*[^\\r\\n]+?\\*)(?=\\s|$)", "g");
		var italicSpan = new RegExp("(^|\\s)(/[^\\r\\n]+?/)(?=\\s|$)", "g");
		var underlineSpan = new RegExp("(^|\\s)(_[^\\r\\n]+?_)(?=\\s|$)", "g");
		message = message.replace(boldSpan, "$1<strong>$2</strong>");
		message = message.replace(italicSpan, "$1<em>$2</em>");
		message = message.replace(underlineSpan, "$1<u>$2</u>");

		preview.document.writeln('<!DOCTYPE html>');
		preview.document.writeln('<html><head><title>' + subject + '<\/title><\/head>');
		preview.document.writeln('<body><pre style="font-size: 13px;">' + message + '<\/pre><\/body><\/html>');
	}
	else {
		if (typeof(tinyMCE) != 'undefined') {
			message = tinyMCE.activeEditor.getContent();
		}
		else {
			message = document.forms['send-form'].elements['body_html'].value;
		}

		var rex_img   = new RegExp("<([^<]+)\"cid:([^\\:*/?<\">|]+)\"([^>]*)?>", "gi");
		var rex_title = new RegExp("<title>\\s*</title>", "gi");

		message = message.replace("{LINKS}", '<a href="http://www.example.org/">Example</a>');
		message = message.replace(rex_img, "<$1\"show.php?file=$2\"$3>");
		message = message.replace(rex_title, '<title>' + subject + '</title>');

		preview.document.write(message);
	}

	preview.document.close();
	preview.focus();
}

function addLinks()
{
	var message, scrollTop = 0;
	if (this.id == 'addLinks1') {
		message = document.forms['send-form'].elements['body_text'];
	}
	else {
		if (this.id == 'addLinks2' && typeof(tinyMCE) != 'undefined') {
			tinyMCE.execCommand('mceInsertContent', false, '&#123;LINKS&#125;');
			return true;
		}

		message = document.forms['send-form'].elements['body_html'];
	}

	if (typeof(message.scrollTop) != 'undefined') {
		scrollTop = message.scrollTop;
	}

	var caretPos = (message.selectionEnd + 7);// 7 = longueur de la chaîne {LINKS}
	var before   = (message.value).substring(0, message.selectionStart);
	var after    = (message.value).substring(message.selectionStart, message.textLength);
	message.value  = before + '{LINKS}' + after;
	message.selectionStart = caretPos;
	message.selectionEnd   = caretPos;

	if( scrollTop > 0 ) {
		message.scrollTop = scrollTop;
	}

	message.focus();
}

document.addEventListener('DOMContentLoaded', make_editor, false);

