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

function make_admin()
{
	//
	// Boite de sélection de liste
	//
	var smallbox = document.forms['smallbox'];
	
	if( smallbox )
	{
		smallbox.getElementsByTagName('select')[0].addEventListener('change', jump, false);
	}

	//
	// Loupe d'affichage des images jointes aux newsletters
	//
	var aList = document.getElementsByTagName('a');
	for( var i = 0, m = aList.length; i < m; i++ )
	{
		if( aList[i].getAttribute('rel') == 'show' )
		{
			aList[i].addEventListener('click', show, false);
		}
	}

	//
	// Lien "switch" pour cocher/décocher toutes les checkbox dans un listing
	//
	var deleteButton = document.querySelector('div#aside-bottom button[name="delete"]');

	if( deleteButton != null )
	{
		var divNode = deleteButton.parentNode;
		
		var switchLink = document.createElement('a');
		switchLink.appendChild(document.createTextNode('switch'));
		switchLink.setAttribute('href', '#switch/checkbox');
		switchLink.setAttribute('class', 'notice');
		switchLink.style.marginRight = '6px';
		switchLink.addEventListener('click', switch_checkbox, false);

		divNode.insertBefore(switchLink, divNode.lastElementChild);
		divNode.insertBefore(document.createTextNode(' '), divNode.lastElementChild);
	}

	window.checkboxStatus = false;
}

function jump(evt)
{
	var selectbox = evt.target;
	
	if( selectbox.options[selectbox.selectedIndex].value != 0
		&& selectbox.options[selectbox.selectedIndex].defaultSelected == false )
	{
		selectbox.form.submit();
	}
}

function switch_checkbox(evt)
{
	var checkbox_ary = null;
	
	if( typeof(document.forms['logs']) != 'undefined' )
	{
		checkbox_ary = document.forms['logs'].elements['log_id[]'];
	}
	else if( typeof(document.forms['abo']) != 'undefined' )
	{
		checkbox_ary = document.forms['abo'].elements['id[]'];
	}
	else
	{
		return;
	}
	
	if( checkbox_ary != null )
	{
		window.checkboxStatus = !window.checkboxStatus;

		if( checkbox_ary.length )
		{
			for( var i = 0, m = checkbox_ary.length; i < m; i++ )
			{
				checkbox_ary[i].checked = window.checkboxStatus;
			}
		}
		else
		{
			checkbox_ary.checked = window.checkboxStatus;
		}

		evt.preventDefault();
	}
}

function show(evt)
{
	var sessid = '';
	
	for( var i = 0, m = document.forms.length; i < m; i++ )
	{
		if( document.forms[i].elements['sessid'] )
		{
			sessid = document.forms[i].elements['sessid'].value;
			break;
		}
	}
	
	var sURL = evt.currentTarget.href + '&sessid=' + sessid;
	var imgBox = document.getElementById('image-box');

	if( imgBox == null )
	{
		imgBox = document.createElement('div');
		imgBox.setAttribute('id', 'image-box');
		document.body.appendChild(imgBox);
	}

	imgBox.innerHTML = '<object type="'+evt.currentTarget.type+'"'
		+ ' data="'+sURL+'"></object>';
	imgBox.style.display = 'block';

	var clickListener = function(evt) {
		if( evt.button == 0 ) {
			imgBox.style.display = 'none';
			document.removeEventListener('click', clickListener, true);
			evt.stopPropagation();
			evt.preventDefault();
		}
	};
	document.addEventListener('click', clickListener, true);

	evt.stopPropagation();
	evt.preventDefault();
}

document.addEventListener('DOMContentLoaded', make_admin, false);

