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

function resize_popup()
{
    var obj = document.getElementById('picture');
    
    var img_width  = parseInt(obj.getAttribute('width'));
    var img_height = parseInt(obj.getAttribute('height'));
    var max_width  = window.screen.width;
    var max_height = window.screen.height;
    var correctW   = (window.outerWidth - window.innerWidth);
    var correctH   = (window.outerHeight - window.innerHeight);
    
    var popup_width  = Math.min((img_width + 10), max_width);
    var popup_height = Math.min((img_height + 60), max_height);
    
    window.resizeTo(popup_width, popup_height);
}

if( supportDOM() )
{
    DOM_Events.addListener('load', resize_popup, false, document);
}
