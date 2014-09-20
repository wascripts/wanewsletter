/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
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
	var aList = document.querySelectorAll('table#files-box a.show');
	for( var i = 0, m = aList.length; i < m; i++ )
	{
		aList[i].addEventListener('click', showImage, false);
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

function showImage(evt)
{
	var imgBox = evt.currentTarget.parentNode.querySelector('div.image-box');

	if( imgBox == null )
	{
		imgBox = document.createElement('div');
		imgBox.setAttribute('class', 'image-box');
		evt.currentTarget.parentNode.appendChild(imgBox);

		var img = document.createElement('img');
		img.setAttribute('data-type', evt.currentTarget.type);
		img.setAttribute('src', evt.currentTarget.href);
		imgBox.appendChild(img);
	}

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

/**
 * Utilisée pour masquer les champs file et afficher à la place un élément
 * <button> mieux intégré graphiquement.
 */
function initUploadButton(inputFile)
{
	/*
	 * L'attribut HTML5 hidden est le meilleur choix, car il retire aussi
	 * l'élément de la navigation au clavier
	 */
	if( typeof(inputFile.hidden) != 'undefined' ) {
		inputFile.hidden = true;
	}
	else {
		inputFile.style.position = 'absolute';
		inputFile.style.left = '9999px';
		inputFile.style.width = '0';
		inputFile.style.overflow = 'hidden';
	}
	
	var filename = document.createElement('span');
	filename.style.margin = '0 1em';
	inputFile.parentNode.insertBefore(filename, inputFile.parentNode.firstElementChild);
	
	var textLabel = inputFile.getAttribute('data-button-label');
	var button = document.createElement('button');
	button.setAttribute('type', 'button');
	button.textContent = textLabel + '\u2026';
	inputFile.parentNode.insertBefore(button, inputFile.parentNode.firstElementChild);
	
	button.addEventListener('click', function() {
		inputFile.click();
	}, false);
	
	inputFile.addEventListener('change', function() {
		if( this.files.length == 0 ) {
			filename.textContent = '';
		}
		else {
			filename.textContent = this.files[0].name;
		}
	}, false);
	inputFile.form.addEventListener('reset', function() {
		filename.textContent = '';
	}, false);
}

document.addEventListener('DOMContentLoaded', make_admin, false);
document.addEventListener('DOMContentLoaded', function() {
	if( window.FileList ) {
		var inputList = document.querySelectorAll('input[type="file"][data-button-label]');
		
		for( var i = 0, m = inputList.length; i < m; i++ ) {
			initUploadButton(inputList[i]);
		}
	}
}, false);

