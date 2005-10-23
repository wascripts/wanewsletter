/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package Wanewsletter
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wanewsletter/
 * @license http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * @version $Id$
 */

function make_editor()
{
	if( document.getElementById('textarea1') != null )
	{
		var bloc_text = document.forms['send-form'].elements['body_text'];
		
		DOM_Events.addListener('click', storeCaret, false, bloc_text);
		DOM_Events.addListener('select', storeCaret, false, bloc_text);
		DOM_Events.addListener('keyup', storeCaret, false, bloc_text);
		DOM_Events.addListener('keydown', storeCaret, false, bloc_text);
		
		make_button(document.getElementById('textarea1'));
	}
	
	if( document.getElementById('textarea2') != null )
	{
		var bloc_html = document.forms['send-form'].elements['body_html'];
		
		DOM_Events.addListener('click', storeCaret, false, bloc_html);
		DOM_Events.addListener('select', storeCaret, false, bloc_html);
		DOM_Events.addListener('keyup', storeCaret, false, bloc_html);
		DOM_Events.addListener('keydown', storeCaret, false, bloc_html);
		
		make_button(document.getElementById('textarea2'));
	}
}

function make_button(bloc)
{
	var format = bloc.id.substr((bloc.id.length - 1), 1);
	
	var conteneur = document.createElement('div');
	conteneur.setAttribute('class', 'bottom');
	
	var bouton = document.createElement('input');
	bouton.setAttribute('id', 'preview' + format);
	bouton.setAttribute('type', 'button');
	bouton.setAttribute('value', lang['preview']);
	bouton.setAttribute('class', 'button');
	DOM_Events.addListener('click', preview, false, bouton);
	conteneur.appendChild(bouton);
	conteneur.appendChild(document.createTextNode('\u00A0'));
	
	bouton = bouton.cloneNode(false);
	bouton.listeners = [];
	bouton.setAttribute('id', 'addLinks' + format);
	bouton.setAttribute('type', 'button');
	bouton.setAttribute('value', lang['addlink']);
	bouton.setAttribute('class', 'button');
	DOM_Events.addListener('click', addLinks, false, bouton);
	conteneur.appendChild(bouton);
	
	bloc.appendChild(conteneur);
}

/*
 * Fenêtre de prévisualisation des newsletters
 */
function preview(evt)
{
	var subject	 = document.forms['send-form'].elements['subject'].value;
	var preview	 = window.open('about:blank','apercu','width=' + width + ',height=' + height + ',marginleft=2,topmargin=2,left=' + left + ',top=' + top + ',toolbar=0,location=0,directories=0,status=0,scrollbars=1,resizable=0,copyhistory=0,menuBar=0');
	var rex_link = new RegExp("{LINKS}", "gi");
	
	if( evt.target.id == 'preview1' )
	{
		var texte = document.forms['send-form'].elements['body_text'].value;
		texte = texte.replace(rex_link, "http://www.example.org");
		var boldSpan = new RegExp("(\\*\\w+\\*)", "g");
		var italicSpan = new RegExp("(/\\w+/)", "g");
		var underlineSpan = new RegExp("(_\\w+_)", "g");
		texte = texte.replace(boldSpan, "<strong>$1</strong>");
		texte = texte.replace(italicSpan, "<em>$1</em>");
		texte = texte.replace(underlineSpan, "<u>$1</u>");
		
		preview.document.writeln('<!DOCTYPE HTML PUBLIC "-\/\/W3C\/\/DTD HTML 4.01\/\/EN" "http:\/\/www.w3.org\/TR\/html4\/strict.dtd">');
		preview.document.writeln('<html><head><title>' + subject + '<\/title><\/head>');
		preview.document.writeln('<body><pre>' + texte + '<\/pre><\/body><\/html>');
	}
	else
	{
		var texte     = document.forms['send-form'].elements['body_html'].value;
		var rex_img   = new RegExp("<([^<]+)\"cid:([^\\:*/?<\">|]+)\"([^>]*)?>", "gi");
		var rex_title = new RegExp("<title>.*</title>", "i");
		var sessid    = '';
		
		for( var i = 0, m = document.forms.length; i < m; i++ )
		{
			if( document.forms[i].elements['sessid'] )
			{
				sessid = document.forms[i].elements['sessid'].value;
				break;
			}
		}
		
		texte = texte.replace(rex_link, '<a href="http://www.example.org/">Example</a>');
		texte = texte.replace(rex_img, "<$1\"../options/show.php?file=$2&amp;sessid=" + sessid + "\"$3>");
		texte = texte.replace(rex_title, '<title>' + subject + '</title>');
		
		preview.document.write(texte);
	}
	
	preview.document.close();
	preview.focus();
}

function addLinks(evt)
{
	if( evt.target.id == 'addLinks1' )
	{
		var texte = document.forms['send-form'].elements['body_text'];
	}
	else
	{
		var texte = document.forms['send-form'].elements['body_html'];
	}
	
	if( typeof(texte.selectionStart) != 'undefined' )
	{
		var caretPos = (texte.selectionEnd + 7);// 7 = longueur de la chaîne {LINKS}
		var before   = (texte.value).substring(0, texte.selectionStart);
		var after    = (texte.value).substring(texte.selectionStart, texte.textLength);
		texte.value  = before + '{LINKS}' + after;
		texte.setSelectionRange(caretPos, caretPos);
	}
	else if( texte.createTextRange && texte.caretPos )
	{
		texte.caretPos.text = '{LINKS}';
	}
	else
	{
		texte.value += '{LINKS}\n';
	}
	
	texte.focus();
}

function storeCaret(evt) {
	var textEl = evt.target;
	
	if( typeof(textEl.createTextRange) != 'undefined' )
	{
		textEl.caretPos = document.selection.createRange().duplicate();
	}
}

if( supportDOM() )
{
	var width  = (window.screen.width - 200);
	var height = (window.screen.height - 200);
	var top    = 50;
	var left   = ((window.screen.width - width)/2);
	
	DOM_Events.addListener('load', make_editor, false, document);
}

