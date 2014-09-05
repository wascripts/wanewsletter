/**
 * Copyright (c) 2002-2014 Aurélien Maille
 * 
 * This file is part of Wanewsletter.
 * 
 * Wanewsletter is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * Wanewsletter is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Wanewsletter; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package Wanewsletter
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wanewsletter/
 * @license http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

function make_editor()
{
	var make_button = function(bloc) {
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
	};
	
	var editForm = document.forms['send-form'];
	var DOMRangeIE = (typeof(editForm.elements['subject'].selectionStart) == 'undefined'
		&& typeof(editForm.elements['subject'].createTextRange) != 'undefined');
	
	if( document.getElementById('textarea1') != null ) {
		if( DOMRangeIE ) {
			var bloc_text = editForm.elements['body_text'];
			
			bloc_text.onclick  = storeCaret;
			bloc_text.onselect = storeCaret;
			bloc_text.onkeyup  = storeCaret;
		}
		
		make_button(document.getElementById('textarea1'));
	}
	
	if( document.getElementById('textarea2') != null ) {
		if( DOMRangeIE ) {
			var bloc_html = editForm.elements['body_html'];
			
			bloc_html.onclick  = storeCaret;
			bloc_html.onselect = storeCaret;
			bloc_html.onkeyup  = storeCaret;
		}
		
		make_button(document.getElementById('textarea2'));
	}
}

/*
 * Fenêtre de prévisualisation des newsletters
 */
function preview(evt)
{
	var subject	 = document.forms['send-form'].elements['subject'].value;
	var preview	 = window.open('','apercu','width=' + width + ',height=' + height + ',marginleft=2,topmargin=2,left=' + left + ',top=' + top + ',toolbar=0,location=0,directories=0,status=0,scrollbars=1,copyhistory=0,menuBar=0');
	
	if( evt.target.id == 'preview1' ) {
		
		var texte = document.forms['send-form'].elements['body_text'].value;
		var CRLF  = new RegExp("\r?\n", "g");
		var lines = texte.split(CRLF);
		
		//
		// WordWrap
		//
		var temp = line = '';
		var maxlen   = 78;
		var spacePos = -1;
		
		for( var i = 0, j = 0, m = lines.length; i < m; i++ ) {
			if( lines[i].length > maxlen ) {
				temp = '';
				
				while( lines[i].length > 0 ) {
					line = lines[i].substr(0, maxlen);
					
					if( line.length >= maxlen && (spacePos = line.lastIndexOf(' ')) != -1 ) {
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
		
		texte = lines.join("\r\n");
		texte = texte.replace("{LINKS}", "http://www.example.org");
		subject = subject.replace('&', '&amp;');
		subject = subject.replace('<', '&lt;');
		texte   = texte.replace('&', '&amp;');
		texte   = texte.replace('<', '&lt;');
		
		var boldSpan = new RegExp("(^|\\s)(\\*[^\\r\\n]+?\\*)(?=\\s|$)", "g");
		var italicSpan = new RegExp("(^|\\s)(/[^\\r\\n]+?/)(?=\\s|$)", "g");
		var underlineSpan = new RegExp("(^|\\s)(_[^\\r\\n]+?_)(?=\\s|$)", "g");
		texte = texte.replace(boldSpan, "$1<strong>$2</strong>");
		texte = texte.replace(italicSpan, "$1<em>$2</em>");
		texte = texte.replace(underlineSpan, "$1<u>$2</u>");
		
		preview.document.writeln('<!DOCTYPE html>');
		preview.document.writeln('<html><head><title>' + subject + '<\/title><\/head>');
		preview.document.writeln('<body><pre style="font-size: 13px;">' + texte + '<\/pre><\/body><\/html>');
	}
	else {
		var texte     = document.forms['send-form'].elements['body_html'].value;
		var rex_img   = new RegExp("<([^<]+)\"cid:([^\\:*/?<\">|]+)\"([^>]*)?>", "gi");
		var rex_title = new RegExp("<title>.*</title>", "i");
		var sessid    = '';
		
		for( var i = 0, m = document.forms.length; i < m; i++ ) {
			if( typeof(document.forms[i].elements['sessid']) != 'undefined' ) {
				sessid = document.forms[i].elements['sessid'].value;
				break;
			}
		}
		
		texte = texte.replace("{LINKS}", '<a href="http://www.example.org/">Example</a>');
		texte = texte.replace(rex_img, "<$1\"../options/show.php?file=$2&amp;sessid=" + sessid + "\"$3>");
		texte = texte.replace(rex_title, '<title>' + subject + '</title>');
		
		preview.document.write(texte);
	}
	
	preview.document.close();
	preview.focus();
}

function addLinks(evt)
{
	var texte, scrollTop = 0;
	if( evt.target.id == 'addLinks1' ) {
		texte = document.forms['send-form'].elements['body_text'];
	}
	else {
		texte = document.forms['send-form'].elements['body_html'];
	}
	
	if( typeof(texte.scrollTop) != 'undefined' ) {
		scrollTop = texte.scrollTop;
	}
	
	if( typeof(texte.selectionStart) != 'undefined' ) {
		var caretPos = (texte.selectionEnd + 7);// 7 = longueur de la chaîne {LINKS}
		var before   = (texte.value).substring(0, texte.selectionStart);
		var after    = (texte.value).substring(texte.selectionStart, texte.textLength);
		texte.value  = before + '{LINKS}' + after;
		texte.selectionStart = caretPos;
		texte.selectionEnd   = caretPos;
	}
	else if( typeof(texte.createTextRange) != 'undefined' && texte.caretPos ) {
		texte.caretPos.text = '{LINKS}';
	}
	else {
		texte.value += '{LINKS}\n';
	}
	
	if( scrollTop > 0 ) {
		texte.scrollTop = scrollTop;
	}
	
	texte.focus();
}

function storeCaret(evt)
{
	var textEl = evt.target;
	
	if( typeof(textEl.createTextRange) != 'undefined' ) {
		textEl.caretPos = document.selection.createRange().duplicate();
	}
}

var width  = (window.screen.width - 200);
var height = (window.screen.height - 200);
var top    = 50;
var left   = ((window.screen.width - width)/2);

// Need to work in IE < 9, so we don't use W3C DOM Events model here.
window.onload = function() { make_editor(); };

