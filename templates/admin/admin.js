/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package Script for Wanewsletter
 * @author  Bobe <wascripts@phpcodeur.net>
 * @see     http://phpcodeur.net/wascripts/wanewsletter/
 */

function make_admin()
{
    var smallbox = document.forms['smallbox'];
    
    if( smallbox )
    {
        DOM_Events.addListener('change', jump, false, smallbox.getElementsByTagName('select')[0]);
    }
    
    var aList = document.getElementsByTagName('a');
    for( var i = 0, m = aList.length; i < m; i++ )
    {
        if( aList[i].rel && aList[i].rel == 'show' )
        {
            DOM_Events.addListener('click', show, false, aList[i]);
        }
    }
    
    if( document.forms['logs'] || document.forms['abo'] )
    {
        var bottomAdmin = document.getElementById('nav-bottom');
        
        if( typeof(bottomAdmin) != 'undefined' )
        {
            var divList = bottomAdmin.getElementsByTagName('div');
            
            if( divList[1] != null && divList[1].className.toLowerCase() == 'right' )
            {
                var secondDiv = divList[1];
                
                var paragraphe = document.createElement('p');
                var switchLink = document.createElement('a');
                var texte = document.createTextNode('switch');
                switchLink.appendChild(texte);
                switchLink.setAttribute('href', './switch/checkbox');
                DOM_Events.addListener('click', switch_checkbox, false, switchLink);
                paragraphe.appendChild(switchLink);
                
                secondDiv.insertBefore(paragraphe, secondDiv.getElementsByTagName('input')[0]);
                secondDiv.insertBefore(document.createTextNode(' '), secondDiv.getElementsByTagName('input')[0]);
            }
        }
    }
}

function jump(evt)
{
    var selectbox = evt.target;
    
    if( selectbox.options[selectbox.selectedIndex].value != 0 && selectbox.options[selectbox.selectedIndex].defaultSelected == false )
    {
        selectbox.form.submit();
    }
}

function switch_checkbox(evt)
{
    if( document.forms['logs'] )
    {
        var checkbox_ary = document.forms['logs'].elements['log_id[]'];
    }
    else if( document.forms['abo'] )
    {
        var checkbox_ary = document.forms['abo'].elements['abo_id[]'];
    }
    else
    {
        return;
    }
    
    if( checkbox_ary.length )
    {
        for( var i = 0, m = checkbox_ary.length; i < m; i++ )
        {
            checkbox_ary[i].checked = check;
        }
    }
    else
    {
        checkbox_ary.checked = check;
    }
    
    check = !check;
    
    evt.preventDefault();
}

function show(evt)
{
    var lien      = evt.currentTarget;
    var sessid    = '';
    
    for( var i = 0, m = document.forms.length; i < m; i++ )
    {
        if( document.forms[i].elements['sessid'] )
        {
            sessid = document.forms[i].elements['sessid'].value;
            break;
        }
    }
    
    var w = window.open(lien.href + '&mode=popup&sessid=' + sessid,'showimage','directories=0,menuBar=0,status=0,location=0,scrollbars=0,resizable=yes,toolbar=0,width=400,height=200,left=20,top=20');
    w.focus();
    
	if( w )
		evt.preventDefault();
}

if( supportDOM() )
{
    var check = true;
    DOM_Events.addListener('load', make_admin, false, document);
}
