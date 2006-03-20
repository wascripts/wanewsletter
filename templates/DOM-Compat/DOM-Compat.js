/**
 * Copyright (c) 2004,2005 Aurélien Maille
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package DOM-Compat
 * @author  Bobe <bobe@webnaute.net>
 * @link    http://dev.webnaute.net/Applications/DOM-Compat/ (bientôt)
 * @license http://www.gnu.org/copyleft/lesser.html
 * @version $Id: DOM-Compat.js 22 2005-10-30 23:05:18Z bobe $
 * 
 * @todo
 * - offsetX/Y sous Opera (existe nativement dans Opera mais complètement bogué)
 * - évènement 'change' sur les input de type 'radio' sur Opera
 * - évènement 'change' sur les input de type 'checkbox' sur MSIE
 * - Simuler correctement detail (voir events successifs click .. click .. dblclick) (problème avec Opera 8.0b1)
 * - offsetY foireux sous Mozilla (si div en position statique. Ok en position relative)
 * - Gérer le cas où un objet contenant une méthode handleEvent() est passé en deuxième 
 *	 argument de addEventListener() ? (https://bugzilla.mozilla.org/show_bug.cgi?id=49017)
 *	 (géré pour l’instant)
 * - Problème avec cette bouse de MSIE : http://www.dotvoid.com/view.php?id=23 (corrigé en grande partie, mais solution à la con)
 */

// Bientôt une diffusion publique et un petit mode d’emploi...
var applyPatch = false;

var DOM_Events = {
	lastEventDispatched: '',
	
	addListener: function(type, listener, useCapture) {
		var elem = ( arguments.length > 3 ) ? arguments[3] : this;
		
		if( typeof(elem.nodeType) == 'undefined' )// temporaire
		{
			alert('Don\'t use the DOM Events on the Window object. Use rather on the Document object.');
			return;
		}
		
		if( DOM_Events.getEventData(type).eventType == 'DOMEvents' && typeof(elem.DOM_addEventListener) != 'undefined' )
		{
			elem.DOM_addEventListener(type, listener, useCapture);
			return;
		}
		
		if( DOM_Events.hasListeners(elem, type) == false )
		{
			elem.listeners['_toremove'] = [];
			elem.listeners[type] = { capture: [], bubble: [], phaseProcessing: null };
			
			//
			// Les évènements 'load' et 'unload' ne sont pas gérés sur l’objet Document 
			// dans certains navigateurs.
			// Si l’on a à faire à l’un de ces évènements et que la cible est un objet 
			// Document, on se reportera sur l’objet window dont fait partie ce Document.
			//
			if( elem.nodeType == Node.DOCUMENT_NODE && ( type == 'load' || type == 'unload' ) )
			{
				elem.defaultView['on' + type] = function(evt) {
					if( !evt ) evt = this.event;
					evt = DOM_Events.normalize(evt, this.document, this.document);
					DOM_Events.handleEvent(evt);
				};
			}
			else
			{
				elem['on' + type]  = function(evt) {
					if( !evt ) evt = ( this.nodeType == Node.ELEMENT_NODE ) ? this.ownerDocument.defaultView.event : this.defaultView.event;
					evt = DOM_Events.normalize(evt, this);
					DOM_Events.handleEvent(evt);
				};
				
				if( type == 'click' )
				{
					elem['ondblclick'] = elem['onclick'];
				}
			}
		}
		
		//
		// L’enregistrement de cet EventListener n’est fait que si ce n’est pas un doublon
		//
		var listeners = DOM_Events.getListeners(elem, type, useCapture);
		for( var i = 0, m = listeners.length; i < m; i++ )
		{
			if( listeners[i] == listener )
			{
				return;
			}
		}
		listeners.push(listener);
		
		elem = null;
	},
	
	removeListener: function(type, listener, useCapture) {
		var elem = ( arguments.length > 3 ) ? arguments[3] : this;
		
		if( DOM_Events.getEventData(type).eventType == 'DOMEvents' && typeof(elem.DOM_removeEventListener) != 'undefined' )
		{
			elem.DOM_removeEventListener(type, listener, useCapture);
		}
		else if( DOM_Events.hasListeners(elem, type, useCapture) == true )
		{
			var listeners       = DOM_Events.getListeners(elem, type, useCapture);
			var removeOnStandBy = ( elem.listeners[type].phaseProcessing != null && elem.listeners[type].phaseProcessing == useCapture ) ? true : false;
			
			for( var i = 0, m = listeners.length; i < m; i++ )
			{
				if( listeners[i] == listener )
				{
					if( removeOnStandBy == true )
					{
						listeners[i] = function(evt) {};
						elem.listeners['_toremove'].push(listeners[i]);
					}
					else
					{
						listeners.splice(i, 1);
					}
					break;
				}
			}
			
			if( DOM_Events.hasListeners(elem, type) == false )
			{
				elem['on' + type] = null;
			}
		}
		
		elem = null;
	},
	
	dispatch: function(evt) {
		var elem = ( arguments.length > 1 ) ? arguments[1] : this;
		evt._isDispatched = true;
		
		if( typeof(elem.DOM_dispatchEvent) != 'undefined' && DOM_Events.getEventData(evt.type).eventType != 'Events' )
		{
			return elem.DOM_dispatchEvent(evt);
		}
		
		if( evt.type != null )
		{
			try {
				eval('elem.' + evt.type + '();');
			} catch(e) {
				var targetElem = elem;
				evt = DOM_Events.normalize(evt, elem, elem);
				DOM_Events.handleEvent(evt);
				return !evt._isDefaultPrevented;
			}
		}
		else
		{
			alert('DOMException: UNSPECIFIED_EVENT_TYPE_ERR');
		}
		
		return true;
	},
	
	handleEvent: function(evt) {
		if( evt.type == 'dblclick' ) return;
		
		//
		// On récupère la liste des cibles en remontant jusqu’à la racine du document (Document inclus)
		// Voir paragraphe 1.2 de la spec.
		//
		for( var node = evt.target.parentNode, targets = []; node != null; node = node.parentNode )
		{
			targets.push(node);
		}
		
		//
		// Phase de capture
		//
		evt.eventPhase = evt.CAPTURING_PHASE;
		
		for( var i = (targets.length - 1), m = 0; i >= m && evt._isPropagationStopped == false; i-- )
		{
			this.triggerListeners(targets[i], evt);
		}
		
		//
		// Phase "à la cible"
		//
		if( evt._isPropagationStopped == false )
		{
			evt.eventPhase = evt.AT_TARGET;
			this.triggerListeners(evt.target, evt);
		}
		
		//
		// Phase de bouillonnement
		//
		evt.eventPhase = evt.BUBBLING_PHASE;
		
		for( i = 0, m = targets.length; i < m && evt.bubbles == true && evt._isPropagationStopped == false; i++ )
		{
			this.triggerListeners(targets[i], evt);
		}
		
		evt.stopPropagation();
	},
	
	triggerListeners: function(elem, evt) {
		var useCapture = ( evt.eventPhase == evt.CAPTURING_PHASE ) ? true : false;
		
		if( this.hasListeners(elem, evt.type, useCapture) == true )
		{
			var listeners     = this.getListeners(elem, evt.type, useCapture);
			evt.currentTarget = elem;
			
			elem.listeners[evt.type].phaseProcessing = useCapture;
			for( var i = 0, m = listeners.length; i < m; i++ )
			{
				if( typeof(listeners[i]) == 'function' )
				{
					elem.__listener = listeners[i];
					elem.__listener(evt);
					elem.__listener = null;
				}
				else if( typeof(listeners[i]) == 'object' && typeof(listeners[i].handleEvent) == 'function' )
				{
					listeners[i].handleEvent(evt);
				}
			}
			elem.listeners[evt.type].phaseProcessing = null;
			
			if( elem.listeners['_toremove'].length > 0 )
			{
				for( i = 0, m = elem.listeners['_toremove'].length; i < m; i++ )
				{
					this.removeListener(evt.type, elem.listeners['_toremove'][i], useCapture, elem);
				}
				elem.listeners['_toremove'] = [];
			}
		}
	},
	
	clickPatch: {
		disableClick: false,
		status: false,
		
		initialize: function() {
			DOM_Events.addListener('mousedown', function() {
				DOM_Events.clickPatch.enable();
			}, true, document);
			
			DOM_Events.addListener('mouseup', function() {
				if( DOM_Events.clickPatch.status == true )
				{
					DOM_Events.clickPatch.disable();
				}
			}, true, document);
		},
		
		enable: function() {
			this.disableClick = false;
			this.status = true;
			DOM_Events.addListener('mouseover', this.listener, true, document);
			DOM_Events.addListener('mouseout',  this.listener, true, document);
			DOM_Events.addListener('mousemove', this.listener, true, document);
		},
		
		disable: function() {
			this.status = false;
			DOM_Events.removeListener('mouseover', this.listener, true, document);
			DOM_Events.removeListener('mouseout',  this.listener, true, document);
			DOM_Events.removeListener('mousemove', this.listener, true, document);
		},
		
		listener: function() {
			DOM_Events.clickPatch.disableClick = true;
			DOM_Events.clickPatch.disable();
		}
	},
	
	normalize: function(oldEvt, currentTarget) {
		if( typeof(oldEvt._correction) != 'undefined' && oldEvt._correction == true )
		{
			return oldEvt;
		}
		
		var evt = {
			CAPTURING_PHASE: 1,
			AT_TARGET:       2,
			BUBBLING_PHASE:  3,
			
			type           : oldEvt.type,
			target         : ( typeof(oldEvt.target) != 'undefined' ) ? oldEvt.target : oldEvt.srcElement,
			currentTarget  : currentTarget,
			eventPhase     : ( typeof(oldEvt.eventPhase) != 'undefined' ) ? oldEvt.eventPhase : 2,
			bubbles        : ( typeof(oldEvt.bubbles) != 'undefined' ) ? oldEvt.bubbles : null,
			cancelable     : ( typeof(oldEvt.cancelable) != 'undefined' ) ? oldEvt.cancelable : null,
			timeStamp      : ( typeof(oldEvt.timeStamp) != 'undefined' ) ? oldEvt.timeStamp : null,
			view           : ( typeof(oldEvt.view) != 'undefined' ) ? oldEvt.view : null,
			
			stopPropagation: function() {
				if( this.bubbles == true )
				{
					if( typeof(this._oldEvt.stopPropagation) != 'undefined' )
					{
						this._oldEvt.stopPropagation();
					}
					else
					{
						this._oldEvt.cancelBubble = true;
					}
					
					this._isPropagationStopped = true;
				}
			},
			
			preventDefault: function() {
				if( this.cancelable == true )
				{
					if( typeof(this._oldEvt.preventDefault) != 'undefined' )
					{
						this._oldEvt.preventDefault();
					}
					else
					{
						this._oldEvt.returnValue = false;
					}
					
					this._isDefaultPrevented = true;
				}
			},
			
			//
			// Internal properties
			//
			_isPropagationStopped: false,
			_isDefaultPrevented: false,
			_isDispatched: ( typeof(oldEvt._isDispatched) != 'undefined' ) ? true : false,
			_correction: false,
			_oldEvt: oldEvt
		};
		
		if( ( evt.type == 'dblclick' && this.lastEventDispatched == 'click' ) || ( evt.type == 'click' && this.clickPatch.disableClick == true && evt._isDispatched == false ) )
		{
			evt.preventDefault();
			evt.type = 'dblclick';// Pour que l’évènement ne soit pas traité par l’interface
			this.lastEventDispatched = evt.type;
			return evt;
		}
		else if( evt.type == 'dblclick' )
		{
			evt.type = 'click';
		}
		
		var eventData = this.getEventData(evt.type);
		this.lastEventDispatched = oldEvt.type;
		
		if( arguments.length > 2 )// configuration manuelle de Event.target
		{
			evt.target = arguments[2];
		}
		else if( evt.target == null || ( evt.type == 'change' && evt.currentTarget.nodeName.toLowerCase() == 'input' ) )
		{
			evt.target = evt.currentTarget;
		}
		else if( evt.target.nodeType == Node.TEXT_NODE )// Bug Safari
		{
			evt.target = evt.target.parentNode;
		}
		
		if( eventData.eventType != 'Events' && evt._isDispatched == false )
		{
			evt.bubbles    = eventData.bubble;
			evt.cancelable = eventData.cancelable;
		}
		
		//
		// timeStamp est de type Date
		// http://www.w3.org/TR/2000/REC-DOM-Level-2-Core-20001113/core.html#Core-DOMTimeStamp
		//
		if( typeof(oldEvt.timeStamp) != 'object' )
		{
			evt.timeStamp = new Date();
		}
		
		var targetDoc = ( evt.target.nodeType == Node.DOCUMENT_NODE ) ? evt.target : evt.target.ownerDocument;
		if( evt.view == null )
		{
			evt.view = targetDoc.defaultView;
		}
		
		var isMouseEvent = ( eventData.eventType == 'MouseEvents' );
		var isKeyEvent   = ( eventData.eventType == 'KeyEvents' );
		
		if( isMouseEvent || isKeyEvent )
		{
			evt.altKey   = ( typeof(oldEvt.altKey) != 'undefined' ) ? oldEvt.altKey : false;
			evt.ctrlKey  = ( typeof(oldEvt.ctrlKey) != 'undefined' ) ? oldEvt.ctrlKey : false;
			evt.metaKey  = ( typeof(oldEvt.metaKey) != 'undefined' ) ? oldEvt.metaKey : false;
			evt.shiftKey = ( typeof(oldEvt.shiftKey) != 'undefined' ) ? oldEvt.shiftKey : false;
			
			if( isMouseEvent )
			{
				evt.screenX = oldEvt.screenX;
				evt.screenY = oldEvt.screenY;
				evt.clientX = oldEvt.clientX;
				evt.clientY = oldEvt.clientY;
				
				if( typeof(oldEvt.offsetX) != 'undefined' )
				{
					evt.offsetX = oldEvt.offsetX;
					evt.offsetY = oldEvt.offsetY;
				}
				
				if( typeof(oldEvt.layerX) != 'undefined' )
				{
					evt.layerX = oldEvt.layerX;
					evt.layerY = oldEvt.layerY;
				}
				
				var scrollXval = 0, scrollYval = 0;
				if( typeof(targetDoc.documentElement) == 'undefined' || 
					( targetDoc.documentElement.scrollLeft == 0 && targetDoc.documentElement.scrollTop == 0 )
				  )
				{
					scrollXval = targetDoc.body.scrollLeft;
					scrollYval = targetDoc.body.scrollTop;
				}
				else
				{
					scrollXval = targetDoc.documentElement.scrollLeft;
					scrollYval = targetDoc.documentElement.scrollTop;
				}
				
				if( typeof(oldEvt.pageX) == 'undefined' )
				{
					evt.pageX = (oldEvt.clientX + scrollXval);
					evt.pageY = (oldEvt.clientY + scrollYval);
				}
				else
				{
					evt.pageX = oldEvt.pageX;
					evt.pageY = oldEvt.pageY;
					
					if( evt.pageX == evt.clientX && scrollXval > 0 ) // bug safari
					{
						evt.clientX = (evt.pageX - scrollXval);
						evt.clientY = (evt.pageY - scrollYval);
					}
				}
				
				if( evt.type == 'mousedown' || evt.type == 'mouseup' || evt.type == 'click' )
				{
					evt.button = oldEvt.button;
					evt.detail = ( typeof(oldEvt.detail) != 'undefined' ) ? oldEvt.detail : 1;
					
					//                    Left button - Middle button - Right button
					// W3C:                    0              1               2
					// Moz (avec which):       1              2               3
					// MS:                     1              4               2
					// Opera < 8.0:            1              3               2
					//
					if( evt._isDispatched == false && evt.button != 0 && evt.button != 2 && ( typeof(oldEvt.which) == 'undefined' || oldEvt.which > 0 ) )
					{
						if( typeof(oldEvt.which) != 'undefined' )
						{
							evt.button = oldEvt.which;
						}
						
						evt.button--;
						
						if( evt.button == 2 || evt.button == 3 )// Correction Opera < 8 et MS
						{
							evt.button = 1;
						}
					}
					
					if( evt.type == 'mouseup' && this.clickPatch.disableClick )
					{
						evt.detail = 0;
					}
					else if( oldEvt.type == 'dblclick' )
					{
						evt.detail = 2;
					}
				}
				else if( evt.type == 'mouseover' || evt.type == 'mouseout' )
				{
					if( typeof(oldEvt.relatedTarget) != 'undefined' )
					{
						evt.relatedTarget = oldEvt.relatedTarget;
					}
					else
					{
						evt.relatedTarget = ( evt.type == 'mouseover' ) ? oldEvt.fromElement : oldEvt.toElement;
					}
				}
			}
			else
			{
				evt.keyCode = ( !oldEvt.keyCode && typeof(oldEvt.which) != 'undefined' ) ? oldEvt.which : oldEvt.keyCode;
			}
		}
		
		evt._correction = true;// Afin de ne pas modifier l’objet une seconde fois
		
		return evt;
	},
	
	initEvent: function(typeArg, canBubbleArg, cancelableArg) {
		this.type          = typeArg;
		this.bubbles       = canBubbleArg;
		this.cancelable    = cancelableArg;
	},
	
	initMouseEvent: function(typeArg, canBubbleArg, cancelableArg, viewArg, detailArg, screenXArg, screenYArg, clientXArg, clientYArg, ctrlKeyArg, altKeyArg, shiftKeyArg, metaKeyArg, buttonArg, relatedTargetArg) {
		this.initEvent(typeArg, canBubbleArg, cancelableArg);
		this.view          = viewArg;
		this.detail        = detailArg;
		this.screenX       = screenXArg;
		this.screenY       = screenYArg;
		this.clientX       = clientXArg;
		this.clientY       = clientYArg;
		this.ctrlKey       = ctrlKeyArg;
		this.altKey        = altKeyArg;
		this.shiftKey      = shiftKeyArg;
		this.metaKey       = metaKeyArg;
		this.button        = buttonArg;
		this.relatedTarget = relatedTargetArg;
	},
	
	hasListeners: function(elem, type) {
		if( typeof(elem.listeners) == 'undefined' )
		{
			elem.listeners = [];
		}
		else if( typeof(elem.listeners[type]) != 'undefined' )
		{
			if( arguments.length > 2 )
			{
				if( this.getListeners(elem, type, arguments[2]).length > 0 )
				{
					return true;
				}
			}
			else if( elem.listeners[type].capture.length > 0 || elem.listeners[type].bubble.length > 0 )
			{
				return true;
			}
		}
		
		return false;
	},
	
	getListeners: function(elem, type, useCapture) {
		if( useCapture == true && type != 'load' && type != 'unload' )
		{
			return elem.listeners[type].capture;
		}
		
		return elem.listeners[type].bubble;
	},
	
	getEventData: function(type) {
		for( var i = 0, m = this.eventsList.length; i < m; i++ )
		{
			if( this.eventsList[i].name == type )
			{
				return this.eventsList[i];
			}
		}
		
		return ( type.substr(0, 3) == 'DOM' ) ? this.eventsList[1] : this.eventsList[0];
	},
	
	addProperties: function(elem) {
		if( applyPatch == true )
		{
			if( typeof(elem.addEventListener) != 'undefined' )
			{
				elem.DOM_addEventListener    = elem.addEventListener;
				elem.DOM_removeEventListener = elem.removeEventListener;
				elem.DOM_dispatchEvent       = elem.dispatchEvent;
			}
			
			elem.addEventListener    = this.addListener;
			elem.removeEventListener = this.removeListener;
			elem.dispatchEvent       = this.dispatch;
		}
		
		if( elem.nodeType == Node.DOCUMENT_NODE && typeof(document.createEvent) == 'undefined' )
		{
			document.createEvent = function(eventType) {
				var evt = document.createEventObject(); // Microsoft
				
				evt.type = null;
				evt.initEvent = DOM_Events.initEvent;
				
				switch( eventType )
				{
					case 'MouseEvents':
						evt.initMouseEvent = DOM_Events.initMouseEvent;
						break;
					case 'HTMLEvents':
					case 'Events':
						break;
					default:
						alert('DOMException: NOT_SUPPORTED_ERR');
						return null;
				}
				
				return evt;
			};
		}
		
		elem = null;
	},
	
	//
	// Pour les besoins du script, les évènements 'dblclick', 'keydown', 'keyup' 
	// et 'keypress' sont également listés ici.
	// De plus, la position des deux premières entrées ne doit pas être changée.
	//
	eventsList: [
		{ bubble: true, cancelable: true, eventType: 'Events', name: '' },
		{ bubble: true, cancelable: true, eventType: 'DOMEvents', name: '' },
		{ bubble: true, cancelable: true, eventType: 'MouseEvents', name: 'click' },
		{ bubble: true, cancelable: true, eventType: 'MouseEvents', name: 'dblclick' },
		{ bubble: true, cancelable: true, eventType: 'MouseEvents', name: 'mousedown' },
		{ bubble: true, cancelable: true, eventType: 'MouseEvents', name: 'mouseup' },
		{ bubble: true, cancelable: true, eventType: 'MouseEvents', name: 'mouseover' },
		{ bubble: true, cancelable: true, eventType: 'MouseEvents', name: 'mouseout' },
		{ bubble: true, cancelable: false, eventType: 'MouseEvents', name: 'mousemove' },
		{ bubble: false, cancelable: false, eventType: 'HTMLEvents', name: 'load' },
		{ bubble: false, cancelable: false, eventType: 'HTMLEvents', name: 'unload' },
		{ bubble: true, cancelable: false, eventType: 'HTMLEvents', name: 'abort' },
		{ bubble: true, cancelable: false, eventType: 'HTMLEvents', name: 'error' },
		{ bubble: true, cancelable: false, eventType: 'HTMLEvents', name: 'select' },
		{ bubble: true, cancelable: false, eventType: 'HTMLEvents', name: 'change' },
		{ bubble: true, cancelable: true, eventType: 'HTMLEvents', name: 'submit' },
		{ bubble: true, cancelable: false, eventType: 'HTMLEvents', name: 'reset' },
		{ bubble: false, cancelable: false, eventType: 'HTMLEvents', name: 'focus' },
		{ bubble: false, cancelable: false, eventType: 'HTMLEvents', name: 'blur' },
		{ bubble: true, cancelable: false, eventType: 'HTMLEvents', name: 'resize' },
		{ bubble: true, cancelable: false, eventType: 'HTMLEvents', name: 'scroll' },
		{ bubble: true, cancelable: false, eventType: 'KeyEvents', name: 'keydown' },
		{ bubble: true, cancelable: false, eventType: 'KeyEvents', name: 'keyup' },
		{ bubble: true, cancelable: false, eventType: 'KeyEvents', name: 'keypress' }
	]
};

function DOM_addProperties(elem)
{
	var nodeType = elem.nodeType;
	
	//
	// DOM Core
	//
	if( applyPatch == true )
	{
		elem.DOM_cloneNode = elem.cloneNode;
		elem.cloneNode     = function(deep) {
			var newNode = this.DOM_cloneNode(deep);
			newNode.listeners = [];
			DOM_addProperties(newNode);
			
			var tagList = newNode.getElementsByTagName('*');
			for( var i = 0, m = tagList.length; i < m; i++ )
			{
				DOM_addProperties(tagList[i]);
				tagList[i].listeners = [];
			}
			return newNode;
		};
		
		if( nodeType == Node.DOCUMENT_NODE )
		{
			document.DOM_createElement = document.createElement;
			document.createElement     = function(tagName) {
				var newElem = document.DOM_createElement(tagName);
				DOM_addProperties(newElem);
				return newElem;
			};
		}
	}
	
	//
	// DOM Events
	//
	DOM_Events.addProperties(elem);
	
	//
	// DOM Views
	//
	if( nodeType == Node.DOCUMENT_NODE && typeof(elem.defaultView) != 'object' )
	{
		elem.defaultView = window;
	}
	elem = null;
}

function supportDOM()
{
	var dom_core  = ( typeof(document.implementation) != 'undefined' );
	var dom_event = ( typeof(document.addEventListener) != 'undefined' || typeof(document.attachEvent) != 'undefined' );
	
	return ( dom_core && dom_event );
}

if( supportDOM() )
{
	if( document.location.search.indexOf('noPatch') != -1 )
	{
		applyPatch = false;
	}
	
	if( typeof(Node) == 'undefined' )
	{
		var Node = {
			ELEMENT_NODE: 1,
			ATTRIBUTE_NODE: 2,
			TEXT_NODE: 3,
			CDATA_SECTION_NODE: 4,
			ENTITY_REFERENCE_NODE: 5,
			ENTITY_NODE: 6,
			PROCESSING_INSTRUCTION_NODE: 7,
			COMMENT_NODE: 8,
			DOCUMENT_NODE: 9,
			DOCUMENT_TYPE_NODE: 10,
			DOCUMENT_FRAGMENT_NODE: 11,
			NOTATION_NODE: 12
		};
	}
	
	if( typeof(Event) == 'undefined' )
	{
		var Event = { CAPTURING_PHASE: 1, AT_TARGET: 2, BUBBLING_PHASE: 3 };
	}
	
	DOM_addProperties(document);
	
	if( applyPatch == true )
	{
		var tagCount = 0;
		var patchElement = function() {
			var tagList = document.getElementsByTagName('*');
			for( var m = tagList.length; tagCount < m; tagCount++ )
			{
				DOM_addProperties(tagList[tagCount]);
			}
		};
		var patchTimer = window.setInterval("patchElement();", 20);
		
		document.addEventListener('load', function() {
			window.clearInterval(patchTimer);
			patchElement();
			DOM_Events.clickPatch.initialize();
		}, false);
	}
	else
	{
		DOM_Events.addListener('load', function() {
			DOM_Events.clickPatch.initialize();
		}, false, document);
	}
	
	if( typeof(window.getComputedStyle) == 'undefined' )
	{
		window.getComputedStyle = function(elt, pseudoElt) {
			return ( typeof(this.elem.currentStyle) != 'undefined' ) ? this.elem.currentStyle : null;
		};
	}
}
