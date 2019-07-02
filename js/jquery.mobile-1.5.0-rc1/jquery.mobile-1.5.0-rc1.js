/*!
* jQuery Mobile <%= version %>
* Git HEAD hash: 1f0cec9bcb9d75998e733d580d6f1144c963326e <> Date: Mon Sep 10 2018 04:34:35 UTC
* http://jquerymobile.com
*
* Copyright 2010, 2018 jQuery Foundation, Inc. and other contributors
* Released under the MIT license.
* http://jquery.org/license
*
*/


(function ( root, doc, factory ) {
	if ( typeof define === "function" && define.amd ) {
		// AMD. Register as an anonymous module.
		define( [ "jquery" ], function ( $ ) {
			factory( $, root, doc );
			return $.mobile;
		});
	} else {
		// Browser globals
		factory( root.jQuery, root, doc );
	}
}( this, document, function ( jQuery, window, document, undefined ) {
( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/version',[ "jquery" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} ( function( $ ) {

$.ui = $.ui || {};

return $.ui.version = "1.12.1";

} ) );

/*!
 * jQuery UI Widget 1.12.1
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Widget
//>>group: Core
//>>description: Provides a factory for creating stateful widgets with a common API.
//>>docs: http://api.jqueryui.com/jQuery.widget/
//>>demos: http://jqueryui.com/widget/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/widget',[ "jquery", "./version" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
}( function( $ ) {

var widgetUuid = 0;
var widgetSlice = Array.prototype.slice;

$.cleanData = ( function( orig ) {
	return function( elems ) {
		var events, elem, i;
		for ( i = 0; ( elem = elems[ i ] ) != null; i++ ) {
			try {

				// Only trigger remove when necessary to save time
				events = $._data( elem, "events" );
				if ( events && events.remove ) {
					$( elem ).triggerHandler( "remove" );
				}

			// Http://bugs.jquery.com/ticket/8235
			} catch ( e ) {}
		}
		orig( elems );
	};
} )( $.cleanData );

$.widget = function( name, base, prototype ) {
	var existingConstructor, constructor, basePrototype;

	// ProxiedPrototype allows the provided prototype to remain unmodified
	// so that it can be used as a mixin for multiple widgets (#8876)
	var proxiedPrototype = {};

	var namespace = name.split( "." )[ 0 ];
	name = name.split( "." )[ 1 ];
	var fullName = namespace + "-" + name;

	if ( !prototype ) {
		prototype = base;
		base = $.Widget;
	}

	if ( $.isArray( prototype ) ) {
		prototype = $.extend.apply( null, [ {} ].concat( prototype ) );
	}

	// Create selector for plugin
	$.expr[ ":" ][ fullName.toLowerCase() ] = function( elem ) {
		return !!$.data( elem, fullName );
	};

	$[ namespace ] = $[ namespace ] || {};
	existingConstructor = $[ namespace ][ name ];
	constructor = $[ namespace ][ name ] = function( options, element ) {

		// Allow instantiation without "new" keyword
		if ( !this._createWidget ) {
			return new constructor( options, element );
		}

		// Allow instantiation without initializing for simple inheritance
		// must use "new" keyword (the code above always passes args)
		if ( arguments.length ) {
			this._createWidget( options, element );
		}
	};

	// Extend with the existing constructor to carry over any static properties
	$.extend( constructor, existingConstructor, {
		version: prototype.version,

		// Copy the object used to create the prototype in case we need to
		// redefine the widget later
		_proto: $.extend( {}, prototype ),

		// Track widgets that inherit from this widget in case this widget is
		// redefined after a widget inherits from it
		_childConstructors: []
	} );

	basePrototype = new base();

	// We need to make the options hash a property directly on the new instance
	// otherwise we'll modify the options hash on the prototype that we're
	// inheriting from
	basePrototype.options = $.widget.extend( {}, basePrototype.options );
	$.each( prototype, function( prop, value ) {
		if ( !$.isFunction( value ) ) {
			proxiedPrototype[ prop ] = value;
			return;
		}
		proxiedPrototype[ prop ] = ( function() {
			function _super() {
				return base.prototype[ prop ].apply( this, arguments );
			}

			function _superApply( args ) {
				return base.prototype[ prop ].apply( this, args );
			}

			return function() {
				var __super = this._super;
				var __superApply = this._superApply;
				var returnValue;

				this._super = _super;
				this._superApply = _superApply;

				returnValue = value.apply( this, arguments );

				this._super = __super;
				this._superApply = __superApply;

				return returnValue;
			};
		} )();
	} );
	constructor.prototype = $.widget.extend( basePrototype, {

		// TODO: remove support for widgetEventPrefix
		// always use the name + a colon as the prefix, e.g., draggable:start
		// don't prefix for widgets that aren't DOM-based
		widgetEventPrefix: existingConstructor ? ( basePrototype.widgetEventPrefix || name ) : name
	}, proxiedPrototype, {
		constructor: constructor,
		namespace: namespace,
		widgetName: name,
		widgetFullName: fullName
	} );

	// If this widget is being redefined then we need to find all widgets that
	// are inheriting from it and redefine all of them so that they inherit from
	// the new version of this widget. We're essentially trying to replace one
	// level in the prototype chain.
	if ( existingConstructor ) {
		$.each( existingConstructor._childConstructors, function( i, child ) {
			var childPrototype = child.prototype;

			// Redefine the child widget using the same prototype that was
			// originally used, but inherit from the new version of the base
			$.widget( childPrototype.namespace + "." + childPrototype.widgetName, constructor,
				child._proto );
		} );

		// Remove the list of existing child constructors from the old constructor
		// so the old child constructors can be garbage collected
		delete existingConstructor._childConstructors;
	} else {
		base._childConstructors.push( constructor );
	}

	$.widget.bridge( name, constructor );

	return constructor;
};

$.widget.extend = function( target ) {
	var input = widgetSlice.call( arguments, 1 );
	var inputIndex = 0;
	var inputLength = input.length;
	var key;
	var value;

	for ( ; inputIndex < inputLength; inputIndex++ ) {
		for ( key in input[ inputIndex ] ) {
			value = input[ inputIndex ][ key ];
			if ( input[ inputIndex ].hasOwnProperty( key ) && value !== undefined ) {

				// Clone objects
				if ( $.isPlainObject( value ) ) {
					target[ key ] = $.isPlainObject( target[ key ] ) ?
						$.widget.extend( {}, target[ key ], value ) :

						// Don't extend strings, arrays, etc. with objects
						$.widget.extend( {}, value );

				// Copy everything else by reference
				} else {
					target[ key ] = value;
				}
			}
		}
	}
	return target;
};

$.widget.bridge = function( name, object ) {
	var fullName = object.prototype.widgetFullName || name;
	$.fn[ name ] = function( options ) {
		var isMethodCall = typeof options === "string";
		var args = widgetSlice.call( arguments, 1 );
		var returnValue = this;

		if ( isMethodCall ) {

			// If this is an empty collection, we need to have the instance method
			// return undefined instead of the jQuery instance
			if ( !this.length && options === "instance" ) {
				returnValue = undefined;
			} else {
				this.each( function() {
					var methodValue;
					var instance = $.data( this, fullName );

					if ( options === "instance" ) {
						returnValue = instance;
						return false;
					}

					if ( !instance ) {
						return $.error( "cannot call methods on " + name +
							" prior to initialization; " +
							"attempted to call method '" + options + "'" );
					}

					if ( !$.isFunction( instance[ options ] ) || options.charAt( 0 ) === "_" ) {
						return $.error( "no such method '" + options + "' for " + name +
							" widget instance" );
					}

					methodValue = instance[ options ].apply( instance, args );

					if ( methodValue !== instance && methodValue !== undefined ) {
						returnValue = methodValue && methodValue.jquery ?
							returnValue.pushStack( methodValue.get() ) :
							methodValue;
						return false;
					}
				} );
			}
		} else {

			// Allow multiple hashes to be passed on init
			if ( args.length ) {
				options = $.widget.extend.apply( null, [ options ].concat( args ) );
			}

			this.each( function() {
				var instance = $.data( this, fullName );
				if ( instance ) {
					instance.option( options || {} );
					if ( instance._init ) {
						instance._init();
					}
				} else {
					$.data( this, fullName, new object( options, this ) );
				}
			} );
		}

		return returnValue;
	};
};

$.Widget = function( /* options, element */ ) {};
$.Widget._childConstructors = [];

$.Widget.prototype = {
	widgetName: "widget",
	widgetEventPrefix: "",
	defaultElement: "<div>",

	options: {
		classes: {},
		disabled: false,

		// Callbacks
		create: null
	},

	_createWidget: function( options, element ) {
		element = $( element || this.defaultElement || this )[ 0 ];
		this.element = $( element );
		this.uuid = widgetUuid++;
		this.eventNamespace = "." + this.widgetName + this.uuid;

		this.bindings = $();
		this.hoverable = $();
		this.focusable = $();
		this.classesElementLookup = {};

		if ( element !== this ) {
			$.data( element, this.widgetFullName, this );
			this._on( true, this.element, {
				remove: function( event ) {
					if ( event.target === element ) {
						this.destroy();
					}
				}
			} );
			this.document = $( element.style ?

				// Element within the document
				element.ownerDocument :

				// Element is window or document
				element.document || element );
			this.window = $( this.document[ 0 ].defaultView || this.document[ 0 ].parentWindow );
		}

		this.options = $.widget.extend( {},
			this.options,
			this._getCreateOptions(),
			options );

		this._create();

		if ( this.options.disabled ) {
			this._setOptionDisabled( this.options.disabled );
		}

		this._trigger( "create", null, this._getCreateEventData() );
		this._init();
	},

	_getCreateOptions: function() {
		return {};
	},

	_getCreateEventData: $.noop,

	_create: $.noop,

	_init: $.noop,

	destroy: function() {
		var that = this;

		this._destroy();
		$.each( this.classesElementLookup, function( key, value ) {
			that._removeClass( value, key );
		} );

		// We can probably remove the unbind calls in 2.0
		// all event bindings should go through this._on()
		this.element
			.off( this.eventNamespace )
			.removeData( this.widgetFullName );
		this.widget()
			.off( this.eventNamespace )
			.removeAttr( "aria-disabled" );

		// Clean up events and states
		this.bindings.off( this.eventNamespace );
	},

	_destroy: $.noop,

	widget: function() {
		return this.element;
	},

	option: function( key, value ) {
		var options = key;
		var parts;
		var curOption;
		var i;

		if ( arguments.length === 0 ) {

			// Don't return a reference to the internal hash
			return $.widget.extend( {}, this.options );
		}

		if ( typeof key === "string" ) {

			// Handle nested keys, e.g., "foo.bar" => { foo: { bar: ___ } }
			options = {};
			parts = key.split( "." );
			key = parts.shift();
			if ( parts.length ) {
				curOption = options[ key ] = $.widget.extend( {}, this.options[ key ] );
				for ( i = 0; i < parts.length - 1; i++ ) {
					curOption[ parts[ i ] ] = curOption[ parts[ i ] ] || {};
					curOption = curOption[ parts[ i ] ];
				}
				key = parts.pop();
				if ( arguments.length === 1 ) {
					return curOption[ key ] === undefined ? null : curOption[ key ];
				}
				curOption[ key ] = value;
			} else {
				if ( arguments.length === 1 ) {
					return this.options[ key ] === undefined ? null : this.options[ key ];
				}
				options[ key ] = value;
			}
		}

		this._setOptions( options );

		return this;
	},

	_setOptions: function( options ) {
		var key;

		for ( key in options ) {
			this._setOption( key, options[ key ] );
		}

		return this;
	},

	_setOption: function( key, value ) {
		if ( key === "classes" ) {
			this._setOptionClasses( value );
		}

		this.options[ key ] = value;

		if ( key === "disabled" ) {
			this._setOptionDisabled( value );
		}

		return this;
	},

	_setOptionClasses: function( value ) {
		var classKey, elements, currentElements;

		for ( classKey in value ) {
			currentElements = this.classesElementLookup[ classKey ];
			if ( value[ classKey ] === this.options.classes[ classKey ] ||
					!currentElements ||
					!currentElements.length ) {
				continue;
			}

			// We are doing this to create a new jQuery object because the _removeClass() call
			// on the next line is going to destroy the reference to the current elements being
			// tracked. We need to save a copy of this collection so that we can add the new classes
			// below.
			elements = $( currentElements.get() );
			this._removeClass( currentElements, classKey );

			// We don't use _addClass() here, because that uses this.options.classes
			// for generating the string of classes. We want to use the value passed in from
			// _setOption(), this is the new value of the classes option which was passed to
			// _setOption(). We pass this value directly to _classes().
			elements.addClass( this._classes( {
				element: elements,
				keys: classKey,
				classes: value,
				add: true
			} ) );
		}
	},

	_setOptionDisabled: function( value ) {
		this._toggleClass( this.widget(), this.widgetFullName + "-disabled", null, !!value );

		// If the widget is becoming disabled, then nothing is interactive
		if ( value ) {
			this._removeClass( this.hoverable, null, "ui-state-hover" );
			this._removeClass( this.focusable, null, "ui-state-focus" );
		}
	},

	enable: function() {
		return this._setOptions( { disabled: false } );
	},

	disable: function() {
		return this._setOptions( { disabled: true } );
	},

	_classes: function( options ) {
		var full = [];
		var that = this;

		options = $.extend( {
			element: this.element,
			classes: this.options.classes || {}
		}, options );

		function processClassString( classes, checkOption ) {
			var current, i;
			for ( i = 0; i < classes.length; i++ ) {
				current = that.classesElementLookup[ classes[ i ] ] || $();
				if ( options.add ) {
					current = $( $.unique( current.get().concat( options.element.get() ) ) );
				} else {
					current = $( current.not( options.element ).get() );
				}
				that.classesElementLookup[ classes[ i ] ] = current;
				full.push( classes[ i ] );
				if ( checkOption && options.classes[ classes[ i ] ] ) {
					full.push( options.classes[ classes[ i ] ] );
				}
			}
		}

		this._on( options.element, {
			"remove": "_untrackClassesElement"
		} );

		if ( options.keys ) {
			processClassString( options.keys.match( /\S+/g ) || [], true );
		}
		if ( options.extra ) {
			processClassString( options.extra.match( /\S+/g ) || [] );
		}

		return full.join( " " );
	},

	_untrackClassesElement: function( event ) {
		var that = this;
		$.each( that.classesElementLookup, function( key, value ) {
			if ( $.inArray( event.target, value ) !== -1 ) {
				that.classesElementLookup[ key ] = $( value.not( event.target ).get() );
			}
		} );
	},

	_removeClass: function( element, keys, extra ) {
		return this._toggleClass( element, keys, extra, false );
	},

	_addClass: function( element, keys, extra ) {
		return this._toggleClass( element, keys, extra, true );
	},

	_toggleClass: function( element, keys, extra, add ) {
		add = ( typeof add === "boolean" ) ? add : extra;
		var shift = ( typeof element === "string" || element === null ),
			options = {
				extra: shift ? keys : extra,
				keys: shift ? element : keys,
				element: shift ? this.element : element,
				add: add
			};
		options.element.toggleClass( this._classes( options ), add );
		return this;
	},

	_on: function( suppressDisabledCheck, element, handlers ) {
		var delegateElement;
		var instance = this;

		// No suppressDisabledCheck flag, shuffle arguments
		if ( typeof suppressDisabledCheck !== "boolean" ) {
			handlers = element;
			element = suppressDisabledCheck;
			suppressDisabledCheck = false;
		}

		// No element argument, shuffle and use this.element
		if ( !handlers ) {
			handlers = element;
			element = this.element;
			delegateElement = this.widget();
		} else {
			element = delegateElement = $( element );
			this.bindings = this.bindings.add( element );
		}

		$.each( handlers, function( event, handler ) {
			function handlerProxy() {

				// Allow widgets to customize the disabled handling
				// - disabled as an array instead of boolean
				// - disabled class as method for disabling individual parts
				if ( !suppressDisabledCheck &&
						( instance.options.disabled === true ||
						$( this ).hasClass( "ui-state-disabled" ) ) ) {
					return;
				}
				return ( typeof handler === "string" ? instance[ handler ] : handler )
					.apply( instance, arguments );
			}

			// Copy the guid so direct unbinding works
			if ( typeof handler !== "string" ) {
				handlerProxy.guid = handler.guid =
					handler.guid || handlerProxy.guid || $.guid++;
			}

			var match = event.match( /^([\w:-]*)\s*(.*)$/ );
			var eventName = match[ 1 ] + instance.eventNamespace;
			var selector = match[ 2 ];

			if ( selector ) {
				delegateElement.on( eventName, selector, handlerProxy );
			} else {
				element.on( eventName, handlerProxy );
			}
		} );
	},

	_off: function( element, eventName ) {
		eventName = ( eventName || "" ).split( " " ).join( this.eventNamespace + " " ) +
			this.eventNamespace;
		element.off( eventName ).off( eventName );

		// Clear the stack to avoid memory leaks (#10056)
		this.bindings = $( this.bindings.not( element ).get() );
		this.focusable = $( this.focusable.not( element ).get() );
		this.hoverable = $( this.hoverable.not( element ).get() );
	},

	_delay: function( handler, delay ) {
		function handlerProxy() {
			return ( typeof handler === "string" ? instance[ handler ] : handler )
				.apply( instance, arguments );
		}
		var instance = this;
		return setTimeout( handlerProxy, delay || 0 );
	},

	_hoverable: function( element ) {
		this.hoverable = this.hoverable.add( element );
		this._on( element, {
			mouseenter: function( event ) {
				this._addClass( $( event.currentTarget ), null, "ui-state-hover" );
			},
			mouseleave: function( event ) {
				this._removeClass( $( event.currentTarget ), null, "ui-state-hover" );
			}
		} );
	},

	_focusable: function( element ) {
		this.focusable = this.focusable.add( element );
		this._on( element, {
			focusin: function( event ) {
				this._addClass( $( event.currentTarget ), null, "ui-state-focus" );
			},
			focusout: function( event ) {
				this._removeClass( $( event.currentTarget ), null, "ui-state-focus" );
			}
		} );
	},

	_trigger: function( type, event, data ) {
		var prop, orig;
		var callback = this.options[ type ];

		data = data || {};
		event = $.Event( event );
		event.type = ( type === this.widgetEventPrefix ?
			type :
			this.widgetEventPrefix + type ).toLowerCase();

		// The original event may come from any element
		// so we need to reset the target on the new event
		event.target = this.element[ 0 ];

		// Copy original event properties over to the new event
		orig = event.originalEvent;
		if ( orig ) {
			for ( prop in orig ) {
				if ( !( prop in event ) ) {
					event[ prop ] = orig[ prop ];
				}
			}
		}

		this.element.trigger( event, data );
		return !( $.isFunction( callback ) &&
			callback.apply( this.element[ 0 ], [ event ].concat( data ) ) === false ||
			event.isDefaultPrevented() );
	}
};

$.each( { show: "fadeIn", hide: "fadeOut" }, function( method, defaultEffect ) {
	$.Widget.prototype[ "_" + method ] = function( element, options, callback ) {
		if ( typeof options === "string" ) {
			options = { effect: options };
		}

		var hasOptions;
		var effectName = !options ?
			method :
			options === true || typeof options === "number" ?
				defaultEffect :
				options.effect || defaultEffect;

		options = options || {};
		if ( typeof options === "number" ) {
			options = { duration: options };
		}

		hasOptions = !$.isEmptyObject( options );
		options.complete = callback;

		if ( options.delay ) {
			element.delay( options.delay );
		}

		if ( hasOptions && $.effects && $.effects.effect[ effectName ] ) {
			element[ method ]( options );
		} else if ( effectName !== method && element[ effectName ] ) {
			element[ effectName ]( options.duration, options.easing, callback );
		} else {
			element.queue( function( next ) {
				$( this )[ method ]();
				if ( callback ) {
					callback.call( element[ 0 ] );
				}
				next();
			} );
		}
	};
} );

return $.widget;

} ) );

/*!
 * jQuery Mobile Namespace @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Namespace
//>>group: Core
//>>description: The mobile namespace on the jQuery object

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'ns',[ "jquery" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile = { version: "@VERSION" };

return $.mobile;
} );

/*!
 * jQuery UI Keycode 1.12.1
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Keycode
//>>group: Core
//>>description: Provide keycodes as keynames
//>>docs: http://api.jqueryui.com/jQuery.ui.keyCode/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/keycode',[ "jquery", "./version" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} ( function( $ ) {
return $.ui.keyCode = {
	BACKSPACE: 8,
	COMMA: 188,
	DELETE: 46,
	DOWN: 40,
	END: 35,
	ENTER: 13,
	ESCAPE: 27,
	HOME: 36,
	LEFT: 37,
	PAGE_DOWN: 34,
	PAGE_UP: 33,
	PERIOD: 190,
	RIGHT: 39,
	SPACE: 32,
	TAB: 9,
	UP: 38
};

} ) );

/*!
 * jQuery Mobile Helpers @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Helpers
//>>group: Core
//>>description: Helper functions and references
//>>css.structure: ../css/structure/jquery.mobile.core.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'helpers',[
			"jquery",
			"./ns",
			"jquery-ui/keycode" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

// Subtract the height of external toolbars from the page height, if the page does not have
// internal toolbars of the same type. We take care to use the widget options if we find a
// widget instance and the element's data-attributes otherwise.
var compensateToolbars = function( page, desiredHeight ) {
	var pageParent = page.parent(),
		toolbarsAffectingHeight = [],

		// We use this function to filter fixed toolbars with option updatePagePadding set to
		// true (which is the default) from our height subtraction, because fixed toolbars with
		// option updatePagePadding set to true compensate for their presence by adding padding
		// to the active page. We want to avoid double-counting by also subtracting their
		// height from the desired page height.
		noPadders = function() {
			var theElement = $( this ),
				widgetOptions = $.mobile.toolbar && theElement.data( "mobile-toolbar" ) ?
					theElement.toolbar( "option" ) : {
						position: theElement.attr( "data-" + $.mobile.ns + "position" ),
						updatePagePadding: ( theElement.attr( "data-" + $.mobile.ns +
								"update-page-padding" ) !== false )
					};

			return !( widgetOptions.position === "fixed" &&
				widgetOptions.updatePagePadding === true );
		},
		externalHeaders = pageParent.children( ":jqmData(type='header')" ).filter( noPadders ),
		internalHeaders = page.children( ":jqmData(type='header')" ),
		externalFooters = pageParent.children( ":jqmData(type='footer')" ).filter( noPadders ),
		internalFooters = page.children( ":jqmData(type='footer')" );

	// If we have no internal headers, but we do have external headers, then their height
	// reduces the page height
	if ( internalHeaders.length === 0 && externalHeaders.length > 0 ) {
		toolbarsAffectingHeight = toolbarsAffectingHeight.concat( externalHeaders.toArray() );
	}

	// If we have no internal footers, but we do have external footers, then their height
	// reduces the page height
	if ( internalFooters.length === 0 && externalFooters.length > 0 ) {
		toolbarsAffectingHeight = toolbarsAffectingHeight.concat( externalFooters.toArray() );
	}

	$.each( toolbarsAffectingHeight, function( index, value ) {
		desiredHeight -= $( value ).outerHeight();
	} );

	// Height must be at least zero
	return Math.max( 0, desiredHeight );
};

$.extend( $.mobile, {
	// define the window and the document objects
	window: $( window ),
	document: $( document ),

	// TODO: Remove and use $.ui.keyCode directly
	keyCode: $.ui.keyCode,

	// Place to store various widget extensions
	behaviors: {},

	// Custom logic for giving focus to a page
	focusPage: function( page ) {

		// First, look for an element explicitly marked for page focus
		var focusElement = page.find( "[autofocus]" );

		// If we do not find an element with the "autofocus" attribute, look for the page title
		if ( !focusElement.length ) {
			focusElement = page.find( ".ui-title" ).eq( 0 );
		}

		// Finally, fall back to focusing the page itself
		if ( !focusElement.length ) {
			focusElement = page;
		}

		focusElement.focus();
	},

	// Scroll page vertically: scroll to 0 to hide iOS address bar, or pass a Y value
	silentScroll: function( ypos ) {

		// If user has already scrolled then do nothing
		if ( $.mobile.window.scrollTop() > 0 ) {
			return;
		}

		if ( $.type( ypos ) !== "number" ) {
			ypos = $.mobile.defaultHomeScroll;
		}

		// prevent scrollstart and scrollstop events
		$.event.special.scrollstart.enabled = false;

		setTimeout( function() {
			window.scrollTo( 0, ypos );
			$.mobile.document.trigger( "silentscroll", { x: 0, y: ypos } );
		}, 20 );

		setTimeout( function() {
			$.event.special.scrollstart.enabled = true;
		}, 150 );
	},

	getClosestBaseUrl: function( ele ) {
		// Find the closest page and extract out its url.
		var url = $( ele ).closest( ".ui-page" ).jqmData( "url" ),
			base = $.mobile.path.documentBase.hrefNoHash;

		if ( !$.mobile.base.dynamicBaseEnabled || !url || !$.mobile.path.isPath( url ) ) {
			url = base;
		}

		return $.mobile.path.makeUrlAbsolute( url, base );
	},
	removeActiveLinkClass: function( forceRemoval ) {
		if ( !!$.mobile.activeClickedLink &&
				( !$.mobile.activeClickedLink.closest( ".ui-page-active" ).length ||
				forceRemoval ) ) {

			$.mobile.activeClickedLink.removeClass( "ui-button-active" );
		}
		$.mobile.activeClickedLink = null;
	},

	enhanceable: function( elements ) {
		return this.haveParents( elements, "enhance" );
	},

	hijackable: function( elements ) {
		return this.haveParents( elements, "ajax" );
	},

	haveParents: function( elements, attr ) {
		if ( !$.mobile.ignoreContentEnabled ) {
			return elements;
		}

		var count = elements.length,
			$newSet = $(),
			e, $element, excluded,
			i, c;

		for ( i = 0; i < count; i++ ) {
			$element = elements.eq( i );
			excluded = false;
			e = elements[ i ];

			while ( e ) {
				c = e.getAttribute ? e.getAttribute( "data-" + $.mobile.ns + attr ) : "";

				if ( c === "false" ) {
					excluded = true;
					break;
				}

				e = e.parentNode;
			}

			if ( !excluded ) {
				$newSet = $newSet.add( $element );
			}
		}

		return $newSet;
	},

	getScreenHeight: function() {
		// Native innerHeight returns more accurate value for this across platforms,
		// jQuery version is here as a normalized fallback for platforms like Symbian
		return window.innerHeight || $.mobile.window.height();
	},

	//simply set the active page's minimum height to screen height, depending on orientation
	resetActivePageHeight: function( height ) {
		var page = $( ".ui-page-active" ),
			pageHeight = page.height(),
			pageOuterHeight = page.outerHeight( true );

		height = compensateToolbars( page,
			( typeof height === "number" ) ? height : $( window ).height() );

		// Remove any previous min-height setting
		page.css( "min-height", "" );

		// Set the minimum height only if the height as determined by CSS is insufficient
		if ( page.height() < height ) {
			page.css( "min-height", height - ( pageOuterHeight - pageHeight ) );
		}
	},

	loading: function() {
		// If this is the first call to this function, instantiate a loader widget
		var loader = this.loading._widget || $.mobile.loader().element,

			// Call the appropriate method on the loader
			returnValue = loader.loader.apply( loader, arguments );

		// Make sure the loader is retained for future calls to this function.
		this.loading._widget = loader;

		return returnValue;
	},

	isElementCurrentlyVisible: function( el ) {
		el = typeof el === "string" ? $( el )[ 0 ] : el[ 0 ];

		if( !el ) {
			return true;
		}

		var rect = el.getBoundingClientRect();

		return (
			rect.bottom > 0 &&
			rect.right > 0 &&
			rect.top <
			( window.innerHeight || document.documentElement.clientHeight ) &&
			rect.left <
			( window.innerWidth || document.documentElement.clientWidth ) );
	}
} );

$.addDependents = function( elem, newDependents ) {
	var $elem = $( elem ),
		dependents = $elem.jqmData( "dependents" ) || $();

	$elem.jqmData( "dependents", $( dependents ).add( newDependents ) );
};

// plugins
$.fn.extend( {
	removeWithDependents: function() {
		$.removeWithDependents( this );
	},

	addDependents: function( newDependents ) {
		$.addDependents( this, newDependents );
	},

	// note that this helper doesn't attempt to handle the callback
	// or setting of an html element's text, its only purpose is
	// to return the html encoded version of the text in all cases. (thus the name)
	getEncodedText: function() {
		return $( "<a>" ).text( this.text() ).html();
	},

	// fluent helper function for the mobile namespaced equivalent
	jqmEnhanceable: function() {
		return $.mobile.enhanceable( this );
	},

	jqmHijackable: function() {
		return $.mobile.hijackable( this );
	}
} );

$.removeWithDependents = function( nativeElement ) {
	var element = $( nativeElement );

	( element.jqmData( "dependents" ) || $() ).remove();
	element.remove();
};
$.addDependents = function( nativeElement, newDependents ) {
	var element = $( nativeElement ),
		dependents = element.jqmData( "dependents" ) || $();

	element.jqmData( "dependents", $( dependents ).add( newDependents ) );
};

$.find.matches = function( expr, set ) {
	return $.find( expr, null, null, set );
};

$.find.matchesSelector = function( node, expr ) {
	return $.find( expr, null, null, [ node ] ).length > 0;
};

return $.mobile;
} );

/*!
 * jQuery Mobile Defaults @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Defaults
//>>group: Core
//>>description: Default values for jQuery Mobile
//>>css.structure: ../css/structure/jquery.mobile.core.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'defaults',[
			"jquery",
			"./ns" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.extend( $.mobile, {

	hideUrlBar: true,

	// Keepnative Selector
	keepNative: ":jqmData(role='none'), :jqmData(role='nojs')",

	// Automatically handle clicks and form submissions through Ajax, when same-domain
	ajaxEnabled: true,

	// Automatically load and show pages based on location.hash
	hashListeningEnabled: true,

	// disable to prevent jquery from bothering with links
	linkBindingEnabled: true,

	// Set default page transition - 'none' for no transitions
	defaultPageTransition: "fade",

	// Set maximum window width for transitions to apply - 'false' for no limit
	maxTransitionWidth: false,

	// Set default dialog transition - 'none' for no transitions
	defaultDialogTransition: "pop",

	// Error response message - appears when an Ajax page request fails
	pageLoadErrorMessage: "Error Loading Page",

	// For error messages, which theme does the box use?
	pageLoadErrorMessageTheme: "a",

	// replace calls to window.history.back with phonegaps navigation helper
	// where it is provided on the window object
	phonegapNavigationEnabled: false,

	//automatically initialize the DOM when it's ready
	autoInitializePage: true,

	pushStateEnabled: true,

	// allows users to opt in to ignoring content by marking a parent element as
	// data-ignored
	ignoreContentEnabled: false,

	// default the property to remove dependency on assignment in init module
	pageContainer: $(),

	//enable cross-domain page support
	allowCrossDomainPages: false,

	dialogHashKey: "&ui-state=dialog"
} );
} );

/*!
 * jQuery Mobile Data @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: jqmData
//>>group: Core
//>>description: Mobile versions of Data functions to allow for namespaceing
//>>css.structure: ../css/structure/jquery.mobile.core.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'data',[
			"jquery",
			"./ns" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var nsNormalizeDict = {},
	oldFind = $.find,
	rbrace = /(?:\{[\s\S]*\}|\[[\s\S]*\])$/,
	jqmDataRE = /:jqmData\(([^)]*)\)/g;

$.extend( $.mobile, {

	// Namespace used framework-wide for data-attrs. Default is no namespace

	ns: $.mobileBackcompat === false ? "ui-" : "",

	// Retrieve an attribute from an element and perform some massaging of the value

	getAttribute: function( element, key ) {
		var data;

		element = element.jquery ? element[ 0 ] : element;

		if ( element && element.getAttribute ) {
			data = element.getAttribute( "data-" + $.mobile.ns + key );
		}

		// Copied from core's src/data.js:dataAttr()
		// Convert from a string to a proper data type
		try {
			data = data === "true" ? true :
				data === "false" ? false :
					data === "null" ? null :
						// Only convert to a number if it doesn't change the string
						+data + "" === data ? +data :
							rbrace.test( data ) ? window.JSON.parse( data ) :
								data;
		} catch ( err ) {}

		return data;
	},

	// Expose our cache for testing purposes.
	nsNormalizeDict: nsNormalizeDict,

	// Take a data attribute property, prepend the namespace
	// and then camel case the attribute string. Add the result
	// to our nsNormalizeDict so we don't have to do this again.
	nsNormalize: function( prop ) {
		return nsNormalizeDict[ prop ] ||
			( nsNormalizeDict[ prop ] = $.camelCase( $.mobile.ns + prop ) );
	},

	// Find the closest javascript page element to gather settings data jsperf test
	// http://jsperf.com/single-complex-selector-vs-many-complex-selectors/edit
	// possibly naive, but it shows that the parsing overhead for *just* the page selector vs
	// the page and dialog selector is negligable. This could probably be speed up by
	// doing a similar parent node traversal to the one found in the inherited theme code above
	closestPageData: function( $target ) {
		return $target
			.closest( ":jqmData(role='page'), :jqmData(role='dialog')" )
				.data( "mobile-page" );
	}

} );

// Mobile version of data and removeData and hasData methods
// ensures all data is set and retrieved using jQuery Mobile's data namespace
$.fn.jqmData = function( prop, value ) {
	var result;
	if ( typeof prop !== "undefined" ) {
		if ( prop ) {
			prop = $.mobile.nsNormalize( prop );
		}

		// undefined is permitted as an explicit input for the second param
		// in this case it returns the value and does not set it to undefined
		if ( arguments.length < 2 || value === undefined ) {
			result = this.data( prop );
		} else {
			result = this.data( prop, value );
		}
	}
	return result;
};

$.jqmData = function( elem, prop, value ) {
	var result;
	if ( typeof prop !== "undefined" ) {
		result = $.data( elem, prop ? $.mobile.nsNormalize( prop ) : prop, value );
	}
	return result;
};

$.fn.jqmRemoveData = function( prop ) {
	return this.removeData( $.mobile.nsNormalize( prop ) );
};

$.jqmRemoveData = function( elem, prop ) {
	return $.removeData( elem, $.mobile.nsNormalize( prop ) );
};

$.find = function( selector, context, ret, extra ) {
	if ( selector.indexOf( ":jqmData" ) > -1 ) {
		selector = selector.replace( jqmDataRE, "[data-" + ( $.mobile.ns || "" ) + "$1]" );
	}

	return oldFind.call( this, selector, context, ret, extra );
};

$.extend( $.find, oldFind );

return $.mobile;
} );

/*!
 * jQuery Mobile Core @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>group: exclude

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'core',[
			"./defaults",
			"./data",
			"./helpers" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function() {} );

/*!
 * jQuery Mobile Widget @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Widget Factory
//>>group: Core
//>>description: Widget factory extentions for mobile.
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widget',[
			"jquery",
			"./ns",
			"jquery-ui/widget",
			"./data" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.mobile.widget = $.mobile.widget || {};

} );

/*!
 * jQuery Mobile Theme Option @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Widget Theme
//>>group: Widgets
//>>description: Adds Theme option to widgets
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/widget.theme',[
			"jquery",
			"../core",
			"../widget" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.widget.theme = {
	_create: function() {
		var that = this;
		this._super();
		$.each( this._themeElements(), function( i, toTheme ) {
			that._addClass(
				toTheme.element,
				null,
				toTheme.prefix + that.options[ toTheme.option || "theme" ]
			);
		} );
	},

	_setOption: function( key, value ) {
		var that = this;
		$.each( this._themeElements(), function( i, toTheme ) {
			var themeOption = ( toTheme.option || "theme" );

			if ( themeOption === key ) {
				that._removeClass(
					toTheme.element,
					null,
					toTheme.prefix + that.options[ toTheme.option || "theme" ]
				)
				._addClass( toTheme.element, null, toTheme.prefix + value );
			}
		} );
		this._superApply( arguments );
	}
};

return $.mobile.widget.theme;

} );

/*!
 * jQuery Mobile Loader @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Loading Message
//>>group: Widgets
//>>description: Loading message for page transitions
//>>docs: http://api.jquerymobile.com/loader/
//>>demos: http://demos.jquerymobile.com/@VERSION/loader/
//>>css.structure: ../css/structure/jquery.mobile.core.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/loader',[
			"jquery",
			"../helpers",
			"../defaults",
			"./widget.theme",
			"../widget" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var html = $( "html" );

$.widget( "mobile.loader", {
	version: "@VERSION",

	// NOTE if the global config settings are defined they will override these
	//      options
	options: {
		classes: {
			"ui-loader": "ui-corner-all",
			"ui-loader-icon": "ui-icon-loading"
		},

		enhanced: false,

		// The theme for the loading message
		theme: "a",

		// Whether the text in the loading message is shown
		textVisible: false,

		// The text to be displayed when the popup is shown
		text: "loading"
	},

	_create: function() {
		this.loader = {};

		if ( this.options.enhanced ) {
			this.loader.span = this.element.children( "span" );
			this.loader.header = this.element.children( "h1" );
		} else {
			this.loader.span = $( "<span>" );
			this.loader.header = $( "<h1>" );
		}

		this._addClass( "ui-loader" );
		this._addClass( this.loader.span, "ui-loader-icon" );
		this._addClass( this.loader.header, "ui-loader-header" );

		if ( !this.options.enhanced ) {
			this.element
				.append( this.loader.span )
				.append( this.loader.header );
		}
	},

	_themeElements: function() {
		return [ {
			element: this.element,
			prefix: "ui-body-"
		} ];
	},

	// Turn on/off page loading message. Theme doubles as an object argument with the following
	// shape: { theme: '', text: '', html: '', textVisible: '' }
	// NOTE that the $.mobile.loading* settings and params past the first are deprecated
	// TODO sweet jesus we need to break some of this out
	show: function( theme, msgText, textonly ) {
		var textVisible, message, loadSettings, currentTheme;

		// Use the prototype options so that people can set them globally at mobile init.
		// Consistency, it's what's for dinner.
		if ( $.type( theme ) === "object" ) {
			loadSettings = $.extend( {}, this.options, theme );

			theme = loadSettings.theme;
		} else {
			loadSettings = this.options;

			// Here we prefer the theme value passed as a string argument, then we prefer the
			// global option because we can't use undefined default prototype options, then the
			// prototype option
			theme = theme || loadSettings.theme;
		}

		// Set the message text, prefer the param, then the settings object then loading message
		message = msgText || ( loadSettings.text === false ? "" : loadSettings.text );

		// Prepare the DOM
		this._addClass( html, "ui-loading" );

		textVisible = loadSettings.textVisible;

		currentTheme = this.element.attr( "class" ).match( /\bui-body-[a-z]\b/ ) || [];

		// Add the proper css given the options (theme, text, etc). Force text visibility if the
		// second argument was supplied, or if the text was explicitly set in the object args.
		this._removeClass.apply( this,
				[ "ui-loader-verbose ui-loader-default ui-loader-textonly" ]
					.concat( currentTheme ) )
			._addClass( "ui-loader-" +
			( textVisible || msgText || theme.text ? "verbose" : "default" ) +
			( loadSettings.textonly || textonly ? " ui-loader-textonly" : "" ),
				"ui-body-" + theme );

		this.loader.header.text( message );

		// If the pagecontainer widget has been defined we may use the :mobile-pagecontainer and
		// attach to the element on which the pagecontainer widget has been defined. If not, we
		// attach to the body.
		// TODO: Replace the selector below with $.mobile.pagecontainers[] once #7947 lands
		this.element.appendTo( $.mobile.pagecontainer ?
			$( ":mobile-pagecontainer" ) : $( "body" ) );
	},

	hide: function() {
		this._removeClass( html, "ui-loading" );
	}
} );

return $.widget( "mobile.loader", $.mobile.loader, $.mobile.widget.theme );

} );

/*!
 * jQuery Mobile Loader Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Loading Message Backcompat
//>>group: Widgets
//>>description: The backwards compatible portions of the loader widget

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/loader.backcompat',[
			"jquery",
			"./loader" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {
	$.widget( "mobile.loader", $.mobile.loader, {
		options: {

			// Custom html for the inner content of the loading message
			html: ""
		},

		// DEPRECATED as of 1.5.0 and will be removed in 1.6.0 - we no longer support browsers
		// incapable of native fixed support
		fakeFixLoader: $.noop,

		// DEPRECATED as of 1.5.0 and will be removed in 1.6.0 - we no longer support browsers
		// incapable of native fixed support
		checkLoaderPosition: $.noop,

		show: function( theme ) {
			var html;

			this.resetHtml();

			this._superApply( arguments );

			html = ( $.type( theme ) === "object" && theme.html || this.options.html );

			if ( html ) {
				this.element.html( html );
			}
		},

		resetHtml: function() {
			this.element
				.empty()
				.append( this.loader.span )
				.append( this.loader.header.empty() );
		}
	} );
}

return $.mobile.loader;

} );

/*!
 * jQuery Mobile Match Media Polyfill @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Match Media Polyfill
//>>group: Utilities
//>>description: A workaround for browsers without window.matchMedia

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'media',[
			"jquery",
			"./core" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

/*! matchMedia() polyfill - Test a CSS media type/query in JS. Authors & copyright (c) 2012: Scott Jehl, Paul Irish, Nicholas Zakas. Dual MIT/BSD license */
window.matchMedia = window.matchMedia || ( function( doc, undefined ) {

	var bool,
		docElem = doc.documentElement,
		refNode = docElem.firstElementChild || docElem.firstChild,
		// fakeBody required for <FF4 when executed in <head>
		fakeBody = doc.createElement( "body" ),
		div = doc.createElement( "div" );

	div.id = "mq-test-1";
	div.style.cssText = "position:absolute;top:-100em";
	fakeBody.style.background = "none";
	fakeBody.appendChild( div );

	return function( q ) {

		div.innerHTML = "&shy;<style media=\"" + q + "\"> #mq-test-1 { width: 42px; }</style>";

		docElem.insertBefore( fakeBody, refNode );
		bool = div.offsetWidth === 42;
		docElem.removeChild( fakeBody );

		return {
			matches: bool,
			media: q
		};

	};

}( document ) );

// $.mobile.media uses matchMedia to return a boolean.
$.mobile.media = function( q ) {
	var mediaQueryList = window.matchMedia( q );
	// Firefox returns null in a hidden iframe
	return mediaQueryList && mediaQueryList.matches;
};

return $.mobile.media;

} );

/*!
 * jQuery Mobile Touch Support Test @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Touch support test
//>>group: Core
//>>description: Touch feature test

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'support/touch',[
			"jquery",
			"../ns" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var support = {
	touch: "ontouchend" in document
};

$.mobile.support = $.mobile.support || {};
$.extend( $.support, support );
$.extend( $.mobile.support, support );

return $.support;
} );

/*!
 * jQuery Mobile Orientation @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Orientation support test
//>>group: Core
//>>description: Feature test for orientation

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'support/orientation',[ "jquery" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.extend( $.support, {
	orientation: "orientation" in window && "onorientationchange" in window
} );

return $.support;
} );


/*!
 * jQuery Mobile Support Tests @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>description: Assorted tests to qualify browsers by detecting features
//>>label: Support Tests
//>>group: Core

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'support',[
			"jquery",
			"./core",
			"./media",
			"./support/touch",
			"./support/orientation" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var fakeBody = $( "<body>" ).prependTo( "html" ),
	fbCSS = fakeBody[ 0 ].style,
	vendors = [ "Webkit", "Moz", "O" ],
	webos = "palmGetResource" in window, //only used to rule out scrollTop
	operamini = window.operamini && ( {} ).toString.call( window.operamini ) === "[object OperaMini]",
	nokiaLTE7_3;

// thx Modernizr
function propExists( prop ) {
	var uc_prop = prop.charAt( 0 ).toUpperCase() + prop.substr( 1 ),
		props = ( prop + " " + vendors.join( uc_prop + " " ) + uc_prop ).split( " " ),
		v;

	for ( v in props ) {
		if ( fbCSS[ props[ v ] ] !== undefined ) {
			return true;
		}
	}
}
var bb = window.blackberry && !propExists( "-webkit-transform" ); //only used to rule out box shadow, as it's filled opaque on BB 5 and lower

// inline SVG support test
function inlineSVG() {
	// Thanks Modernizr & Erik Dahlstrom
	var w = window,
		svg = !!w.document.createElementNS && !!w.document.createElementNS( "http://www.w3.org/2000/svg", "svg" ).createSVGRect && !( w.opera && navigator.userAgent.indexOf( "Chrome" ) === -1 ),
		support = function( data ) {
			if ( !( data && svg ) ) {
				$( "html" ).addClass( "ui-nosvg" );
			}
		},
		img = new w.Image();

	img.onerror = function() {
		support( false );
	};
	img.onload = function() {
		support( img.width === 1 && img.height === 1 );
	};
	img.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";
}

function transform3dTest() {
	var mqProp = "transform-3d",
		// Because the `translate3d` test below throws false positives in Android:
		ret = $.mobile.media( "(-" + vendors.join( "-" + mqProp + "),(-" ) + "-" + mqProp + "),(" + mqProp + ")" ),
		el, transforms, t;

	if ( ret ) {
		return !!ret;
	}

	el = document.createElement( "div" );
	transforms = {
		// We’re omitting Opera for the time being; MS uses unprefixed.
		"MozTransform": "-moz-transform",
		"transform": "transform"
	};

	fakeBody.append( el );

	for ( t in transforms ) {
		if ( el.style[ t ] !== undefined ) {
			el.style[ t ] = "translate3d( 100px, 1px, 1px )";
			ret = window.getComputedStyle( el ).getPropertyValue( transforms[ t ] );
		}
	}
	return ( !!ret && ret !== "none" );
}

// Thanks Modernizr
function cssPointerEventsTest() {
	var element = document.createElement( "x" ),
		documentElement = document.documentElement,
		getComputedStyle = window.getComputedStyle,
		supports;

	if ( !( "pointerEvents" in element.style ) ) {
		return false;
	}

	element.style.pointerEvents = "auto";
	element.style.pointerEvents = "x";
	documentElement.appendChild( element );
	supports = getComputedStyle &&
		getComputedStyle( element, "" ).pointerEvents === "auto";
	documentElement.removeChild( element );
	return !!supports;
}

function boundingRect() {
	var div = document.createElement( "div" );
	return typeof div.getBoundingClientRect !== "undefined";
}

// non-UA-based IE version check by James Padolsey, modified by jdalton - from http://gist.github.com/527683
// allows for inclusion of IE 6+, including Windows Mobile 7
$.extend( $.mobile, { browser: {} } );
$.mobile.browser.oldIE = ( function() {
	var v = 3,
		div = document.createElement( "div" ),
		a = div.all || [];

	do {
		div.innerHTML = "<!--[if gt IE " + ( ++v ) + "]><br><![endif]-->";
	} while ( a[ 0 ] );

	return v > 4 ? v : !v;
} )();
$.mobile.browser.newIEMobile = ( function() {
	var div = document.createElement( "div" );
	return ( ( !$.mobile.browser.oldIE ) &&
		"onmsgesturehold" in div &&
		"ontouchstart" in div &&
		"onpointerdown" in div );
} )();

function fixedPosition() {
	var w = window,
		ua = navigator.userAgent,
		platform = navigator.platform,
		// Rendering engine is Webkit, and capture major version
		wkmatch = ua.match( /AppleWebKit\/([0-9]+)/ ),
		wkversion = !!wkmatch && wkmatch[ 1 ],
		ffmatch = ua.match( /Fennec\/([0-9]+)/ ),
		ffversion = !!ffmatch && ffmatch[ 1 ],
		operammobilematch = ua.match( /Opera Mobi\/([0-9]+)/ ),
		omversion = !!operammobilematch && operammobilematch[ 1 ];

	if (
			// iOS 4.3 and older : Platform is iPhone/Pad/Touch and Webkit version is less than 534 (ios5)
			( ( platform.indexOf( "iPhone" ) > -1 || platform.indexOf( "iPad" ) > -1 || platform.indexOf( "iPod" ) > -1 ) && wkversion && wkversion < 534 ) ||
			// Opera Mini
			( w.operamini && ( {} ).toString.call( w.operamini ) === "[object OperaMini]" ) ||
			( operammobilematch && omversion < 7458 ) ||
			//Android lte 2.1: Platform is Android and Webkit version is less than 533 (Android 2.2)
			( ua.indexOf( "Android" ) > -1 && wkversion && wkversion < 533 ) ||
			// Firefox Mobile before 6.0 -
			( ffversion && ffversion < 6 ) ||
			// WebOS less than 3
			( "palmGetResource" in window && wkversion && wkversion < 534 ) ||
			// MeeGo
			( ua.indexOf( "MeeGo" ) > -1 && ua.indexOf( "NokiaBrowser/8.5.0" ) > -1 ) ) {
		return false;
	}

	return true;
}

$.extend( $.support, {
	// Note, Chrome for iOS has an extremely quirky implementation of popstate.
	// We've chosen to take the shortest path to a bug fix here for issue #5426
	// See the following link for information about the regex chosen
	// https://developers.google.com/chrome/mobile/docs/user-agent#chrome_for_ios_user-agent
	pushState: "pushState" in history &&
		"replaceState" in history &&
		// When running inside a FF iframe, calling replaceState causes an error
		!( window.navigator.userAgent.indexOf( "Firefox" ) >= 0 && window.top !== window ) &&
		( window.navigator.userAgent.search( /CriOS/ ) === -1 ),

	mediaquery: $.mobile.media( "only all" ),
	cssPseudoElement: !!propExists( "content" ),
	touchOverflow: !!propExists( "overflowScrolling" ),
	cssTransform3d: transform3dTest(),
	boxShadow: !!propExists( "boxShadow" ) && !bb,
	fixedPosition: fixedPosition(),
	scrollTop: ( "pageXOffset" in window ||
		"scrollTop" in document.documentElement ||
		"scrollTop" in fakeBody[ 0 ] ) && !webos && !operamini,

	cssPointerEvents: cssPointerEventsTest(),
	boundingRect: boundingRect(),
	inlineSVG: inlineSVG
} );

fakeBody.remove();

// $.mobile.ajaxBlacklist is used to override ajaxEnabled on platforms that have known conflicts with hash history updates (BB5, Symbian)
// or that generally work better browsing in regular http for full page refreshes (Opera Mini)
// Note: This detection below is used as a last resort.
// We recommend only using these detection methods when all other more reliable/forward-looking approaches are not possible
nokiaLTE7_3 = ( function() {

	var ua = window.navigator.userAgent;

	//The following is an attempt to match Nokia browsers that are running Symbian/s60, with webkit, version 7.3 or older
	return ua.indexOf( "Nokia" ) > -1 &&
		( ua.indexOf( "Symbian/3" ) > -1 || ua.indexOf( "Series60/5" ) > -1 ) &&
		ua.indexOf( "AppleWebKit" ) > -1 &&
		ua.match( /(BrowserNG|NokiaBrowser)\/7\.[0-3]/ );
} )();

// Support conditions that must be met in order to proceed
// default enhanced qualifications are media query support OR IE 7+

$.mobile.gradeA = function() {
	return ( ( $.support.mediaquery && $.support.cssPseudoElement ) || $.mobile.browser.oldIE && $.mobile.browser.oldIE >= 8 ) && ( $.support.boundingRect || $.fn.jquery.match( /1\.[0-7+]\.[0-9+]?/ ) !== null );
};

$.mobile.ajaxBlacklist =
	// BlackBerry browsers, pre-webkit
	window.blackberry && !window.WebKitPoint ||
	// Opera Mini
	operamini ||
	// Symbian webkits pre 7.3
	nokiaLTE7_3;

// Lastly, this workaround is the only way we've found so far to get pre 7.3 Symbian webkit devices
// to render the stylesheets when they're referenced before this script, as we'd recommend doing.
// This simply reappends the CSS in place, which for some reason makes it apply
if ( nokiaLTE7_3 ) {
	$( function() {
		$( "head link[rel='stylesheet']" ).attr( "rel", "alternate stylesheet" ).attr( "rel", "stylesheet" );
	} );
}

// For ruling out shadows via css
if ( !$.support.boxShadow ) {
	$( "html" ).addClass( "ui-noboxshadow" );
}

return $.support;

} );

/*!
 * jQuery Mobile Navigate Event @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Navigate
//>>group: Events
//>>description: Provides a wrapper around hashchange and popstate
//>>docs: http://api.jquerymobile.com/navigate/
//>>demos: http://api.jquerymobile.com/@VERSION/navigation/

// TODO break out pushstate support test so we don't depend on the whole thing
( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'events/navigate',[
			"jquery",
			"./../ns",
			"./../support" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var $win = $.mobile.window, self,
	dummyFnToInitNavigate = function() {};

$.event.special.beforenavigate = {
	setup: function() {
		$win.on( "navigate", dummyFnToInitNavigate );
	},

	teardown: function() {
		$win.off( "navigate", dummyFnToInitNavigate );
	}
};

$.event.special.navigate = self = {
	bound: false,

	pushStateEnabled: true,

	originalEventName: undefined,

	// If pushstate support is present and push state support is defined to
	// be true on the mobile namespace.
	isPushStateEnabled: function() {
		return $.support.pushState &&
			$.mobile.pushStateEnabled === true &&
			this.isHashChangeEnabled();
	},

	// !! assumes mobile namespace is present
	isHashChangeEnabled: function() {
		return $.mobile.hashListeningEnabled === true;
	},

	// TODO a lot of duplication between popstate and hashchange
	popstate: function( event ) {
		var newEvent, beforeNavigate, state;

		if ( event.isDefaultPrevented() ) {
			return;
		}

		newEvent = new $.Event( "navigate" );
		beforeNavigate = new $.Event( "beforenavigate" );
		state = event.originalEvent.state || {};

		beforeNavigate.originalEvent = event;
		$win.trigger( beforeNavigate );

		if ( beforeNavigate.isDefaultPrevented() ) {
			return;
		}

		if ( event.historyState ) {
			$.extend( state, event.historyState );
		}

		// Make sure the original event is tracked for the end
		// user to inspect incase they want to do something special
		newEvent.originalEvent = event;

		// NOTE we let the current stack unwind because any assignment to
		//      location.hash will stop the world and run this event handler. By
		//      doing this we create a similar behavior to hashchange on hash
		//      assignment
		setTimeout( function() {
			$win.trigger( newEvent, {
				state: state
			} );
		}, 0 );
	},

	hashchange: function( event /*, data */ ) {
		var newEvent = new $.Event( "navigate" ),
			beforeNavigate = new $.Event( "beforenavigate" );

		beforeNavigate.originalEvent = event;
		$win.trigger( beforeNavigate );

		if ( beforeNavigate.isDefaultPrevented() ) {
			return;
		}

		// Make sure the original event is tracked for the end
		// user to inspect incase they want to do something special
		newEvent.originalEvent = event;

		// Trigger the hashchange with state provided by the user
		// that altered the hash
		$win.trigger( newEvent, {
			// Users that want to fully normalize the two events
			// will need to do history management down the stack and
			// add the state to the event before this binding is fired
			// TODO consider allowing for the explicit addition of callbacks
			//      to be fired before this value is set to avoid event timing issues
			state: event.hashchangeState || {}
		} );
	},

	// TODO We really only want to set this up once
	//      but I'm not clear if there's a beter way to achieve
	//      this with the jQuery special event structure
	setup: function( /* data, namespaces */ ) {
		if ( self.bound ) {
			return;
		}

		self.bound = true;

		if ( self.isPushStateEnabled() ) {
			self.originalEventName = "popstate";
			$win.bind( "popstate.navigate", self.popstate );
		} else if ( self.isHashChangeEnabled() ) {
			self.originalEventName = "hashchange";
			$win.bind( "hashchange.navigate", self.hashchange );
		}
	}
};

return $.event.special.navigate;
} );

/*!
 * jQuery Mobile Virtual Mouse @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Virtual Mouse (vmouse) Bindings
//>>group: Core
//>>description: Normalizes touch/mouse events.
//>>docs: http://api.jquerymobile.com/?s=vmouse

// This plugin is an experiment for abstracting away the touch and mouse
// events so that developers don't have to worry about which method of input
// the device their document is loaded on supports.
//
// The idea here is to allow the developer to register listeners for the
// basic mouse events, such as mousedown, mousemove, mouseup, and click,
// and the plugin will take care of registering the correct listeners
// behind the scenes to invoke the listener at the fastest possible time
// for that device, while still retaining the order of event firing in
// the traditional mouse environment, should multiple handlers be registered
// on the same element for different events.
//
// The current version exposes the following virtual events to jQuery bind methods:
// "vmouseover vmousedown vmousemove vmouseup vclick vmouseout vmousecancel"

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'vmouse',[ "jquery" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var dataPropertyName = "virtualMouseBindings",
	touchTargetPropertyName = "virtualTouchID",
	touchEventProps = "clientX clientY pageX pageY screenX screenY".split( " " ),
	virtualEventNames = "vmouseover vmousedown vmousemove vmouseup vclick vmouseout vmousecancel".split( " " ),
	generalProps = ( "altKey bubbles cancelable ctrlKey currentTarget detail eventPhase " +
		"metaKey relatedTarget shiftKey target timeStamp view which" ).split( " " ),
	mouseHookProps = $.event.mouseHooks ? $.event.mouseHooks.props : [],
	mouseEventProps = generalProps.concat( mouseHookProps ),
	activeDocHandlers = {},
	resetTimerID = 0,
	startX = 0,
	startY = 0,
	didScroll = false,
	clickBlockList = [],
	blockMouseTriggers = false,
	blockTouchTriggers = false,
	eventCaptureSupported = "addEventListener" in document,
	$document = $( document ),
	nextTouchID = 1,
	lastTouchID = 0, threshold,
	i;

$.vmouse = {
	moveDistanceThreshold: 10,
	clickDistanceThreshold: 10,
	resetTimerDuration: 1500,
	maximumTimeBetweenTouches: 100
};

function getNativeEvent( event ) {

	while ( event && typeof event.originalEvent !== "undefined" ) {
		event = event.originalEvent;
	}
	return event;
}

function createVirtualEvent( event, eventType ) {

	var t = event.type,
		oe, props, ne, prop, ct, touch, i, j, len;

	event = $.Event( event );
	event.type = eventType;

	oe = event.originalEvent;
	props = generalProps;

	// addresses separation of $.event.props in to $.event.mouseHook.props and Issue 3280
	// https://github.com/jquery/jquery-mobile/issues/3280
	if ( t.search( /^(mouse|click)/ ) > -1 ) {
		props = mouseEventProps;
	}

	// copy original event properties over to the new event
	// this would happen if we could call $.event.fix instead of $.Event
	// but we don't have a way to force an event to be fixed multiple times
	if ( oe ) {
		for ( i = props.length; i; ) {
			prop = props[ --i ];
			event[ prop ] = oe[ prop ];
		}
	}

	// make sure that if the mouse and click virtual events are generated
	// without a .which one is defined
	if ( t.search( /mouse(down|up)|click/ ) > -1 && !event.which ) {
		event.which = 1;
	}

	if ( t.search( /^touch/ ) !== -1 ) {
		ne = getNativeEvent( oe );
		t = ne.touches;
		ct = ne.changedTouches;
		touch = ( t && t.length ) ? t[ 0 ] : ( ( ct && ct.length ) ? ct[ 0 ] : undefined );

		if ( touch ) {
			for ( j = 0, len = touchEventProps.length; j < len; j++ ) {
				prop = touchEventProps[ j ];
				event[ prop ] = touch[ prop ];
			}
		}
	}

	return event;
}

function getVirtualBindingFlags( element ) {

	var flags = {},
		b, k;

	while ( element ) {

		b = $.data( element, dataPropertyName );

		for ( k in b ) {
			if ( b[ k ] ) {
				flags[ k ] = flags.hasVirtualBinding = true;
			}
		}
		element = element.parentNode;
	}
	return flags;
}

function getClosestElementWithVirtualBinding( element, eventType ) {
	var b;
	while ( element ) {

		b = $.data( element, dataPropertyName );

		if ( b && ( !eventType || b[ eventType ] ) ) {
			return element;
		}
		element = element.parentNode;
	}
	return null;
}

function enableTouchBindings() {
	blockTouchTriggers = false;
}

function disableTouchBindings() {
	blockTouchTriggers = true;
}

function enableMouseBindings() {
	lastTouchID = 0;
	clickBlockList.length = 0;
	blockMouseTriggers = false;

	// When mouse bindings are enabled, our
	// touch bindings are disabled.
	disableTouchBindings();
}

function disableMouseBindings() {
	// When mouse bindings are disabled, our
	// touch bindings are enabled.
	enableTouchBindings();
}

function clearResetTimer() {
	if ( resetTimerID ) {
		clearTimeout( resetTimerID );
		resetTimerID = 0;
	}
}

function startResetTimer() {
	clearResetTimer();
	resetTimerID = setTimeout( function() {
		resetTimerID = 0;
		enableMouseBindings();
	}, $.vmouse.resetTimerDuration );
}

function triggerVirtualEvent( eventType, event, flags ) {
	var ve;

	if ( ( flags && flags[ eventType ] ) ||
			( !flags && getClosestElementWithVirtualBinding( event.target, eventType ) ) ) {

		ve = createVirtualEvent( event, eventType );

		$( event.target ).trigger( ve );
	}

	return ve;
}

function mouseEventCallback( event ) {
	var touchID = $.data( event.target, touchTargetPropertyName ),
		ve;

	// It is unexpected if a click event is received before a touchend
	// or touchmove event, however this is a known behavior in Mobile
	// Safari when Mobile VoiceOver (as of iOS 8) is enabled and the user
	// double taps to activate a link element. In these cases if a touch
	// event is not received within the maximum time between touches,
	// re-enable mouse bindings and call the mouse event handler again.
	if ( event.type === "click" && $.data( event.target, "lastTouchType" ) === "touchstart" ) {
		setTimeout( function() {
			if ( $.data( event.target, "lastTouchType" ) === "touchstart" ) {
				enableMouseBindings();
				delete $.data( event.target ).lastTouchType;
				mouseEventCallback( event );
			}
		}, $.vmouse.maximumTimeBetweenTouches );
	}

	if ( !blockMouseTriggers && ( !lastTouchID || lastTouchID !== touchID ) ) {
		ve = triggerVirtualEvent( "v" + event.type, event );
		if ( ve ) {
			if ( ve.isDefaultPrevented() ) {
				event.preventDefault();
			}
			if ( ve.isPropagationStopped() ) {
				event.stopPropagation();
			}
			if ( ve.isImmediatePropagationStopped() ) {
				event.stopImmediatePropagation();
			}
		}
	}
}

function handleTouchStart( event ) {

	var touches = getNativeEvent( event ).touches,
		target, flags, t;

	if ( touches && touches.length === 1 ) {

		target = event.target;
		flags = getVirtualBindingFlags( target );

		$.data( event.target, "lastTouchType", event.type );

		if ( flags.hasVirtualBinding ) {

			lastTouchID = nextTouchID++;
			$.data( target, touchTargetPropertyName, lastTouchID );

			clearResetTimer();

			disableMouseBindings();
			didScroll = false;

			t = getNativeEvent( event ).touches[ 0 ];
			startX = t.pageX;
			startY = t.pageY;

			triggerVirtualEvent( "vmouseover", event, flags );
			triggerVirtualEvent( "vmousedown", event, flags );
		}
	}
}

function handleScroll( event ) {
	if ( blockTouchTriggers ) {
		return;
	}

	if ( !didScroll ) {
		triggerVirtualEvent( "vmousecancel", event, getVirtualBindingFlags( event.target ) );
	}

	$.data( event.target, "lastTouchType", event.type );

	didScroll = true;
	startResetTimer();
}

function handleTouchMove( event ) {
	if ( blockTouchTriggers ) {
		return;
	}

	var t = getNativeEvent( event ).touches[ 0 ],
		didCancel = didScroll,
		moveThreshold = $.vmouse.moveDistanceThreshold,
		flags = getVirtualBindingFlags( event.target );

	$.data( event.target, "lastTouchType", event.type );

	didScroll = didScroll ||
		( Math.abs( t.pageX - startX ) > moveThreshold ||
		Math.abs( t.pageY - startY ) > moveThreshold );

	if ( didScroll && !didCancel ) {
		triggerVirtualEvent( "vmousecancel", event, flags );
	}

	triggerVirtualEvent( "vmousemove", event, flags );
	startResetTimer();
}

function handleTouchEnd( event ) {
	if ( blockTouchTriggers || $.data( event.target, "lastTouchType" ) === undefined ) {
		return;
	}

	disableTouchBindings();
	delete $.data( event.target ).lastTouchType;

	var flags = getVirtualBindingFlags( event.target ),
		ve, t;
	triggerVirtualEvent( "vmouseup", event, flags );

	if ( !didScroll ) {
		ve = triggerVirtualEvent( "vclick", event, flags );
		if ( ve && ve.isDefaultPrevented() ) {
			// The target of the mouse events that follow the touchend
			// event don't necessarily match the target used during the
			// touch. This means we need to rely on coordinates for blocking
			// any click that is generated.
			t = getNativeEvent( event ).changedTouches[ 0 ];
			clickBlockList.push( {
				touchID: lastTouchID,
				x: t.clientX,
				y: t.clientY
			} );

			// Prevent any mouse events that follow from triggering
			// virtual event notifications.
			blockMouseTriggers = true;
		}
	}
	triggerVirtualEvent( "vmouseout", event, flags );
	didScroll = false;

	startResetTimer();
}

function hasVirtualBindings( ele ) {
	var bindings = $.data( ele, dataPropertyName ),
		k;

	if ( bindings ) {
		for ( k in bindings ) {
			if ( bindings[ k ] ) {
				return true;
			}
		}
	}
	return false;
}

function dummyMouseHandler() {
}

function getSpecialEventObject( eventType ) {
	var realType = eventType.substr( 1 );

	return {
		setup: function( /* data, namespace */ ) {
			// If this is the first virtual mouse binding for this element,
			// add a bindings object to its data.

			if ( !hasVirtualBindings( this ) ) {
				$.data( this, dataPropertyName, {} );
			}

			// If setup is called, we know it is the first binding for this
			// eventType, so initialize the count for the eventType to zero.
			var bindings = $.data( this, dataPropertyName );
			bindings[ eventType ] = true;

			// If this is the first virtual mouse event for this type,
			// register a global handler on the document.

			activeDocHandlers[ eventType ] = ( activeDocHandlers[ eventType ] || 0 ) + 1;

			if ( activeDocHandlers[ eventType ] === 1 ) {
				$document.bind( realType, mouseEventCallback );
			}

			// Some browsers, like Opera Mini, won't dispatch mouse/click events
			// for elements unless they actually have handlers registered on them.
			// To get around this, we register dummy handlers on the elements.

			$( this ).bind( realType, dummyMouseHandler );

			// For now, if event capture is not supported, we rely on mouse handlers.
			if ( eventCaptureSupported ) {
				// If this is the first virtual mouse binding for the document,
				// register our touchstart handler on the document.

				activeDocHandlers[ "touchstart" ] = ( activeDocHandlers[ "touchstart" ] || 0 ) + 1;

				if ( activeDocHandlers[ "touchstart" ] === 1 ) {
					$document.bind( "touchstart", handleTouchStart )
						.bind( "touchend", handleTouchEnd )

						// On touch platforms, touching the screen and then dragging your finger
						// causes the window content to scroll after some distance threshold is
						// exceeded. On these platforms, a scroll prevents a click event from being
						// dispatched, and on some platforms, even the touchend is suppressed. To
						// mimic the suppression of the click event, we need to watch for a scroll
						// event. Unfortunately, some platforms like iOS don't dispatch scroll
						// events until *AFTER* the user lifts their finger (touchend). This means
						// we need to watch both scroll and touchmove events to figure out whether
						// or not a scroll happenens before the touchend event is fired.

						.bind( "touchmove", handleTouchMove )
						.bind( "scroll", handleScroll );
				}
			}
		},

		teardown: function( /* data, namespace */ ) {
			// If this is the last virtual binding for this eventType,
			// remove its global handler from the document.

			--activeDocHandlers[eventType];

			if ( !activeDocHandlers[ eventType ] ) {
				$document.unbind( realType, mouseEventCallback );
			}

			if ( eventCaptureSupported ) {
				// If this is the last virtual mouse binding in existence,
				// remove our document touchstart listener.

				--activeDocHandlers["touchstart"];

				if ( !activeDocHandlers[ "touchstart" ] ) {
					$document.unbind( "touchstart", handleTouchStart )
						.unbind( "touchmove", handleTouchMove )
						.unbind( "touchend", handleTouchEnd )
						.unbind( "scroll", handleScroll );
				}
			}

			var $this = $( this ),
				bindings = $.data( this, dataPropertyName );

			// teardown may be called when an element was
			// removed from the DOM. If this is the case,
			// jQuery core may have already stripped the element
			// of any data bindings so we need to check it before
			// using it.
			if ( bindings ) {
				bindings[ eventType ] = false;
			}

			// Unregister the dummy event handler.

			$this.unbind( realType, dummyMouseHandler );

			// If this is the last virtual mouse binding on the
			// element, remove the binding data from the element.

			if ( !hasVirtualBindings( this ) ) {
				$this.removeData( dataPropertyName );
			}
		}
	};
}

// Expose our custom events to the jQuery bind/unbind mechanism.

for ( i = 0; i < virtualEventNames.length; i++ ) {
	$.event.special[ virtualEventNames[ i ] ] = getSpecialEventObject( virtualEventNames[ i ] );
}

// Add a capture click handler to block clicks.
// Note that we require event capture support for this so if the device
// doesn't support it, we punt for now and rely solely on mouse events.
if ( eventCaptureSupported ) {
	document.addEventListener( "click", function( e ) {
		var cnt = clickBlockList.length,
			target = e.target,
			x, y, ele, i, o, touchID;

		if ( cnt ) {
			x = e.clientX;
			y = e.clientY;
			threshold = $.vmouse.clickDistanceThreshold;

			// The idea here is to run through the clickBlockList to see if
			// the current click event is in the proximity of one of our
			// vclick events that had preventDefault() called on it. If we find
			// one, then we block the click.
			//
			// Why do we have to rely on proximity?
			//
			// Because the target of the touch event that triggered the vclick
			// can be different from the target of the click event synthesized
			// by the browser. The target of a mouse/click event that is synthesized
			// from a touch event seems to be implementation specific. For example,
			// some browsers will fire mouse/click events for a link that is near
			// a touch event, even though the target of the touchstart/touchend event
			// says the user touched outside the link. Also, it seems that with most
			// browsers, the target of the mouse/click event is not calculated until the
			// time it is dispatched, so if you replace an element that you touched
			// with another element, the target of the mouse/click will be the new
			// element underneath that point.
			//
			// Aside from proximity, we also check to see if the target and any
			// of its ancestors were the ones that blocked a click. This is necessary
			// because of the strange mouse/click target calculation done in the
			// Android 2.1 browser, where if you click on an element, and there is a
			// mouse/click handler on one of its ancestors, the target will be the
			// innermost child of the touched element, even if that child is no where
			// near the point of touch.

			ele = target;

			while ( ele ) {
				for ( i = 0; i < cnt; i++ ) {
					o = clickBlockList[ i ];
					touchID = 0;

					if ( ( ele === target && Math.abs( o.x - x ) < threshold && Math.abs( o.y - y ) < threshold ) ||
							$.data( ele, touchTargetPropertyName ) === o.touchID ) {
						// XXX: We may want to consider removing matches from the block list
						//      instead of waiting for the reset timer to fire.
						e.preventDefault();
						e.stopPropagation();
						return;
					}
				}
				ele = ele.parentNode;
			}
		}
	}, true );
}
} );

/*!
 * jQuery Mobile Touch Events @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Touch
//>>group: Events
//>>description: Touch events including: touchstart, touchmove, touchend, tap, taphold, swipe, swipeleft, swiperight

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'events/touch',[
			"jquery",
			"../vmouse",
			"../support/touch" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {
var $document = $( document ),
	supportTouch = $.mobile.support.touch,
	touchStartEvent = supportTouch ? "touchstart" : "mousedown",
	touchStopEvent = supportTouch ? "touchend" : "mouseup",
	touchMoveEvent = supportTouch ? "touchmove" : "mousemove";

// setup new event shortcuts
$.each( ( "touchstart touchmove touchend " +
"tap taphold " +
"swipe swipeleft swiperight" ).split( " " ), function( i, name ) {

	$.fn[ name ] = function( fn ) {
		return fn ? this.bind( name, fn ) : this.trigger( name );
	};

	// jQuery < 1.8
	if ( $.attrFn ) {
		$.attrFn[ name ] = true;
	}
} );

function triggerCustomEvent( obj, eventType, event, bubble ) {
	var originalType = event.type;
	event.type = eventType;
	if ( bubble ) {
		$.event.trigger( event, undefined, obj );
	} else {
		$.event.dispatch.call( obj, event );
	}
	event.type = originalType;
}

// also handles taphold
$.event.special.tap = {
	tapholdThreshold: 750,
	emitTapOnTaphold: true,
	setup: function() {
		var thisObject = this,
			$this = $( thisObject ),
			isTaphold = false;

		$this.bind( "vmousedown", function( event ) {
			isTaphold = false;
			if ( event.which && event.which !== 1 ) {
				return true;
			}

			var origTarget = event.target,
				timer, clickHandler;

			function clearTapTimer() {
				if ( timer ) {
					$this.bind( "vclick", clickHandler );
					clearTimeout( timer );
				}
			}

			function clearTapHandlers() {
				clearTapTimer();

				$this.unbind( "vclick", clickHandler )
					.unbind( "vmouseup", clearTapTimer );
				$document.unbind( "vmousecancel", clearTapHandlers );
			}

			clickHandler = function( event ) {
				clearTapHandlers();

				// ONLY trigger a 'tap' event if the start target is
				// the same as the stop target.
				if ( !isTaphold && origTarget === event.target ) {
					triggerCustomEvent( thisObject, "tap", event );
				} else if ( isTaphold ) {
					event.preventDefault();
				}
			};

			$this.bind( "vmouseup", clearTapTimer );

			$document.bind( "vmousecancel", clearTapHandlers );

			timer = setTimeout( function() {
				if ( !$.event.special.tap.emitTapOnTaphold ) {
					isTaphold = true;
				}
				timer = 0;
				triggerCustomEvent( thisObject, "taphold", $.Event( "taphold", { target: origTarget } ) );
			}, $.event.special.tap.tapholdThreshold );
		} );
	},
	teardown: function() {
		$( this ).unbind( "vmousedown" ).unbind( "vclick" ).unbind( "vmouseup" );
		$document.unbind( "vmousecancel" );
	}
};

// Also handles swipeleft, swiperight
$.event.special.swipe = {

	// More than this horizontal displacement, and we will suppress scrolling.
	scrollSupressionThreshold: 30,

	// More time than this, and it isn't a swipe.
	durationThreshold: 1000,

	// Swipe horizontal displacement must be more than this.
	horizontalDistanceThreshold: window.devicePixelRatio >= 2 ? 15 : 30,

	// Swipe vertical displacement must be less than this.
	verticalDistanceThreshold: window.devicePixelRatio >= 2 ? 15 : 30,

	getLocation: function( event ) {
		var winPageX = window.pageXOffset,
			winPageY = window.pageYOffset,
			x = event.clientX,
			y = event.clientY;

		if ( event.pageY === 0 && Math.floor( y ) > Math.floor( event.pageY ) ||
				event.pageX === 0 && Math.floor( x ) > Math.floor( event.pageX ) ) {

			// iOS4 clientX/clientY have the value that should have been
			// in pageX/pageY. While pageX/page/ have the value 0
			x = x - winPageX;
			y = y - winPageY;
		} else if ( y < ( event.pageY - winPageY ) || x < ( event.pageX - winPageX ) ) {

			// Some Android browsers have totally bogus values for clientX/Y
			// when scrolling/zooming a page. Detectable since clientX/clientY
			// should never be smaller than pageX/pageY minus page scroll
			x = event.pageX - winPageX;
			y = event.pageY - winPageY;
		}

		return {
			x: x,
			y: y
		};
	},

	start: function( event ) {
		var data = event.originalEvent.touches ?
				event.originalEvent.touches[ 0 ] : event,
			location = $.event.special.swipe.getLocation( data );
		return {
			time: ( new Date() ).getTime(),
			coords: [ location.x, location.y ],
			origin: $( event.target )
		};
	},

	stop: function( event ) {
		var data = event.originalEvent.touches ?
				event.originalEvent.touches[ 0 ] : event,
			location = $.event.special.swipe.getLocation( data );
		return {
			time: ( new Date() ).getTime(),
			coords: [ location.x, location.y ]
		};
	},

	handleSwipe: function( start, stop, thisObject, origTarget ) {
		if ( stop.time - start.time < $.event.special.swipe.durationThreshold &&
				Math.abs( start.coords[ 0 ] - stop.coords[ 0 ] ) > $.event.special.swipe.horizontalDistanceThreshold &&
				Math.abs( start.coords[ 1 ] - stop.coords[ 1 ] ) < $.event.special.swipe.verticalDistanceThreshold ) {
			var direction = start.coords[ 0 ] > stop.coords[ 0 ] ? "swipeleft" : "swiperight";

			triggerCustomEvent( thisObject, "swipe", $.Event( "swipe", { target: origTarget, swipestart: start, swipestop: stop } ), true );
			triggerCustomEvent( thisObject, direction, $.Event( direction, { target: origTarget, swipestart: start, swipestop: stop } ), true );
			return true;
		}
		return false;

	},

	// This serves as a flag to ensure that at most one swipe event event is
	// in work at any given time
	eventInProgress: false,

	setup: function() {
		var events,
			thisObject = this,
			$this = $( thisObject ),
			context = {};

		// Retrieve the events data for this element and add the swipe context
		events = $.data( this, "mobile-events" );
		if ( !events ) {
			events = { length: 0 };
			$.data( this, "mobile-events", events );
		}
		events.length++;
		events.swipe = context;

		context.start = function( event ) {

			// Bail if we're already working on a swipe event
			if ( $.event.special.swipe.eventInProgress ) {
				return;
			}
			$.event.special.swipe.eventInProgress = true;

			var stop,
				start = $.event.special.swipe.start( event ),
				origTarget = event.target,
				emitted = false;

			context.move = function( event ) {
				if ( !start || event.isDefaultPrevented() ) {
					return;
				}

				stop = $.event.special.swipe.stop( event );
				if ( !emitted ) {
					emitted = $.event.special.swipe.handleSwipe( start, stop, thisObject, origTarget );
					if ( emitted ) {

						// Reset the context to make way for the next swipe event
						$.event.special.swipe.eventInProgress = false;
					}
				}
				// prevent scrolling
				if ( Math.abs( start.coords[ 0 ] - stop.coords[ 0 ] ) > $.event.special.swipe.scrollSupressionThreshold ) {
					event.preventDefault();
				}
			};

			context.stop = function() {
				emitted = true;

				// Reset the context to make way for the next swipe event
				$.event.special.swipe.eventInProgress = false;
				$document.off( touchMoveEvent, context.move );
				context.move = null;
			};

			$document.on( touchMoveEvent, context.move )
				.one( touchStopEvent, context.stop );
		};
		$this.on( touchStartEvent, context.start );
	},

	teardown: function() {
		var events, context;

		events = $.data( this, "mobile-events" );
		if ( events ) {
			context = events.swipe;
			delete events.swipe;
			events.length--;
			if ( events.length === 0 ) {
				$.removeData( this, "mobile-events" );
			}
		}

		if ( context ) {
			if ( context.start ) {
				$( this ).off( touchStartEvent, context.start );
			}
			if ( context.move ) {
				$document.off( touchMoveEvent, context.move );
			}
			if ( context.stop ) {
				$document.off( touchStopEvent, context.stop );
			}
		}
	}
};
$.each( {
	taphold: "tap",
	swipeleft: "swipe.left",
	swiperight: "swipe.right"
}, function( event, sourceEvent ) {

	$.event.special[ event ] = {
		setup: function() {
			$( this ).bind( sourceEvent, $.noop );
		},
		teardown: function() {
			$( this ).unbind( sourceEvent );
		}
	};
} );

return $.event.special;
} );


/*!
 * jQuery Mobile Scroll Events @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Scroll
//>>group: Events
//>>description: Scroll events including: scrollstart, scrollstop

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'events/scroll',[ "jquery" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var scrollEvent = "touchmove scroll";

// setup new event shortcuts
$.each( [ "scrollstart", "scrollstop" ], function( i, name ) {

	$.fn[ name ] = function( fn ) {
		return fn ? this.bind( name, fn ) : this.trigger( name );
	};

	// jQuery < 1.8
	if ( $.attrFn ) {
		$.attrFn[ name ] = true;
	}
} );

// also handles scrollstop
$.event.special.scrollstart = {

	enabled: true,
	setup: function() {

		var thisObject = this,
			$this = $( thisObject ),
			scrolling,
			timer;

		function trigger( event, state ) {
			var originalEventType = event.type;

			scrolling = state;

			event.type = scrolling ? "scrollstart" : "scrollstop";
			$.event.dispatch.call( thisObject, event );
			event.type = originalEventType;
		}

		var scrollStartHandler = $.event.special.scrollstart.handler = function ( event ) {

			if ( !$.event.special.scrollstart.enabled ) {
				return;
			}

			if ( !scrolling ) {
				trigger( event, true );
			}

			clearTimeout( timer );
			timer = setTimeout( function() {
				trigger( event, false );
			}, 50 );
		};

		// iPhone triggers scroll after a small delay; use touchmove instead
		$this.on( scrollEvent, scrollStartHandler );
	},
	teardown: function() {
		$( this ).off( scrollEvent, $.event.special.scrollstart.handler );
	}
};

$.each( {
	scrollstop: "scrollstart"
}, function( event, sourceEvent ) {

	$.event.special[ event ] = {
		setup: function() {
			$( this ).bind( sourceEvent, $.noop );
		},
		teardown: function() {
			$( this ).unbind( sourceEvent );
		}
	};
} );

return $.event.special;
} );

/*!
 * jQuery Mobile Throttled Resize @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Throttled Resize
//>>group: Events
//>>description: Fires a resize event with a slight delay to prevent excessive callback invocation
//>>docs: http://api.jquerymobile.com/throttledresize/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'events/throttledresize',[ "jquery" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var throttle = 250,
	lastCall = 0,
	heldCall,
	curr,
	diff,
	handler = function() {
		curr = ( new Date() ).getTime();
		diff = curr - lastCall;

		if ( diff >= throttle ) {

			lastCall = curr;
			$( this ).trigger( "throttledresize" );

		} else {

			if ( heldCall ) {
				clearTimeout( heldCall );
			}

			// Promise a held call will still execute
			heldCall = setTimeout( handler, throttle - diff );
		}
	};

// throttled resize event
$.event.special.throttledresize = {
	setup: function() {
		$( this ).bind( "resize", handler );
	},
	teardown: function() {
		$( this ).unbind( "resize", handler );
	}
};

return $.event.special.throttledresize;
} );

/*!
 * jQuery Mobile Orientation Change Event @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Orientation Change
//>>group: Events
//>>description: Provides a wrapper around the inconsistent browser implementations of orientationchange
//>>docs: http://api.jquerymobile.com/orientationchange/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'events/orientationchange',[
			"jquery",
			"../support/orientation",
			"./throttledresize" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var win = $( window ),
	event_name = "orientationchange",
	get_orientation,
	last_orientation,
	initial_orientation_is_landscape,
	initial_orientation_is_default,
	portrait_map = { "0": true, "180": true },
	ww, wh, landscape_threshold;

// It seems that some device/browser vendors use window.orientation values 0 and 180 to
// denote the "default" orientation. For iOS devices, and most other smart-phones tested,
// the default orientation is always "portrait", but in some Android and RIM based tablets,
// the default orientation is "landscape". The following code attempts to use the window
// dimensions to figure out what the current orientation is, and then makes adjustments
// to the to the portrait_map if necessary, so that we can properly decode the
// window.orientation value whenever get_orientation() is called.
//
// Note that we used to use a media query to figure out what the orientation the browser
// thinks it is in:
//
//     initial_orientation_is_landscape = $.mobile.media("all and (orientation: landscape)");
//
// but there was an iPhone/iPod Touch bug beginning with iOS 4.2, up through iOS 5.1,
// where the browser *ALWAYS* applied the landscape media query. This bug does not
// happen on iPad.

if ( $.support.orientation ) {

	// Check the window width and height to figure out what the current orientation
	// of the device is at this moment. Note that we've initialized the portrait map
	// values to 0 and 180, *AND* we purposely check for landscape so that if we guess
	// wrong, , we default to the assumption that portrait is the default orientation.
	// We use a threshold check below because on some platforms like iOS, the iPhone
	// form-factor can report a larger width than height if the user turns on the
	// developer console. The actual threshold value is somewhat arbitrary, we just
	// need to make sure it is large enough to exclude the developer console case.

	ww = window.innerWidth || win.width();
	wh = window.innerHeight || win.height();
	landscape_threshold = 50;

	initial_orientation_is_landscape = ww > wh && ( ww - wh ) > landscape_threshold;

	// Now check to see if the current window.orientation is 0 or 180.
	initial_orientation_is_default = portrait_map[ window.orientation ];

	// If the initial orientation is landscape, but window.orientation reports 0 or 180, *OR*
	// if the initial orientation is portrait, but window.orientation reports 90 or -90, we
	// need to flip our portrait_map values because landscape is the default orientation for
	// this device/browser.
	if ( ( initial_orientation_is_landscape && initial_orientation_is_default ) || ( !initial_orientation_is_landscape && !initial_orientation_is_default ) ) {
		portrait_map = { "-90": true, "90": true };
	}
}

// If the event is not supported natively, this handler will be bound to
// the window resize event to simulate the orientationchange event.
function handler() {
	// Get the current orientation.
	var orientation = get_orientation();

	if ( orientation !== last_orientation ) {
		// The orientation has changed, so trigger the orientationchange event.
		last_orientation = orientation;
		win.trigger( event_name );
	}
}

$.event.special.orientationchange = $.extend( {}, $.event.special.orientationchange, {
	setup: function() {
		// If the event is supported natively, return false so that jQuery
		// will bind to the event using DOM methods.
		if ( $.support.orientation && !$.event.special.orientationchange.disabled ) {
			return false;
		}

		// Get the current orientation to avoid initial double-triggering.
		last_orientation = get_orientation();

		// Because the orientationchange event doesn't exist, simulate the
		// event by testing window dimensions on resize.
		win.bind( "throttledresize", handler );
	},
	teardown: function() {
		// If the event is not supported natively, return false so that
		// jQuery will unbind the event using DOM methods.
		if ( $.support.orientation && !$.event.special.orientationchange.disabled ) {
			return false;
		}

		// Because the orientationchange event doesn't exist, unbind the
		// resize event handler.
		win.unbind( "throttledresize", handler );
	},
	add: function( handleObj ) {
		// Save a reference to the bound event handler.
		var old_handler = handleObj.handler;

		handleObj.handler = function( event ) {
			// Modify event object, adding the .orientation property.
			event.orientation = get_orientation();

			// Call the originally-bound event handler and return its result.
			return old_handler.apply( this, arguments );
		};
	}
} );

// Get the current page orientation. This method is exposed publicly, should it
// be needed, as jQuery.event.special.orientationchange.orientation()
$.event.special.orientationchange.orientation = get_orientation = function() {
	var isPortrait = true,
		elem = document.documentElement;

	// prefer window orientation to the calculation based on screensize as
	// the actual screen resize takes place before or after the orientation change event
	// has been fired depending on implementation (eg android 2.3 is before, iphone after).
	// More testing is required to determine if a more reliable method of determining the new screensize
	// is possible when orientationchange is fired. (eg, use media queries + element + opacity)
	if ( $.support.orientation ) {
		// if the window orientation registers as 0 or 180 degrees report
		// portrait, otherwise landscape
		isPortrait = portrait_map[ window.orientation ];
	} else {
		isPortrait = elem && elem.clientWidth / elem.clientHeight < 1.1;
	}

	return isPortrait ? "portrait" : "landscape";
};

$.fn[ event_name ] = function( fn ) {
	return fn ? this.bind( event_name, fn ) : this.trigger( event_name );
};

// jQuery < 1.8
if ( $.attrFn ) {
	$.attrFn[ event_name ] = true;
}

return $.event.special;
} );

/*!
 * jQuery Mobile Events @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Events
//>>group: Events
//>>description: Custom events and shortcuts.

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'events',[
			"jquery",
			"./events/navigate",
			"./events/touch",
			"./events/scroll",
			"./events/orientationchange" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function() {} );

/*!
 * jQuery Mobile Path Utility @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Path Helpers
//>>group: Navigation
//>>description: Path parsing and manipulation helpers
//>>docs: http://api.jquerymobile.com/category/methods/path/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'navigation/path',[
			"jquery",
			"./../ns" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var path, $base,
	dialogHashKey = "&ui-state=dialog";

$.mobile.path = path = {
	uiStateKey: "&ui-state",

	// This scary looking regular expression parses an absolute URL or its relative
	// variants (protocol, site, document, query, and hash), into the various
	// components (protocol, host, path, query, fragment, etc that make up the
	// URL as well as some other commonly used sub-parts. When used with RegExp.exec()
	// or String.match, it parses the URL into a results array that looks like this:
	//
	//     [0]: http://jblas:password@mycompany.com:8080/mail/inbox?msg=1234&type=unread#msg-content
	//     [1]: http://jblas:password@mycompany.com:8080/mail/inbox?msg=1234&type=unread
	//     [2]: http://jblas:password@mycompany.com:8080/mail/inbox
	//     [3]: http://jblas:password@mycompany.com:8080
	//     [4]: http:
	//     [5]: //
	//     [6]: jblas:password@mycompany.com:8080
	//     [7]: jblas:password
	//     [8]: jblas
	//     [9]: password
	//    [10]: mycompany.com:8080
	//    [11]: mycompany.com
	//    [12]: 8080
	//    [13]: /mail/inbox
	//    [14]: /mail/
	//    [15]: inbox
	//    [16]: ?msg=1234&type=unread
	//    [17]: #msg-content
	//
	urlParseRE: /^\s*(((([^:\/#\?]+:)?(?:(\/\/)((?:(([^:@\/#\?]+)(?:\:([^:@\/#\?]+))?)@)?(([^:\/#\?\]\[]+|\[[^\/\]@#?]+\])(?:\:([0-9]+))?))?)?)?((\/?(?:[^\/\?#]+\/+)*)([^\?#]*)))?(\?[^#]+)?)(#.*)?/,

	// Abstraction to address xss (Issue #4787) by removing the authority in
	// browsers that auto-decode it. All references to location.href should be
	// replaced with a call to this method so that it can be dealt with properly here
	getLocation: function( url ) {
		var parsedUrl = this.parseUrl( url || location.href ),
			uri = url ? parsedUrl : location,

			// Make sure to parse the url or the location object for the hash because using
			// location.hash is autodecoded in firefox, the rest of the url should be from
			// the object (location unless we're testing) to avoid the inclusion of the
			// authority
			hash = parsedUrl.hash;

		// mimic the browser with an empty string when the hash is empty
		hash = hash === "#" ? "" : hash;

		return uri.protocol +
			parsedUrl.doubleSlash +
			uri.host +

			// The pathname must start with a slash if there's a protocol, because you
			// can't have a protocol followed by a relative path. Also, it's impossible to
			// calculate absolute URLs from relative ones if the absolute one doesn't have
			// a leading "/".
			( ( uri.protocol !== "" && uri.pathname.substring( 0, 1 ) !== "/" ) ?
				"/" : "" ) +
			uri.pathname +
			uri.search +
			hash;
	},

	//return the original document url
	getDocumentUrl: function( asParsedObject ) {
		return asParsedObject ? $.extend( {}, path.documentUrl ) : path.documentUrl.href;
	},

	parseLocation: function() {
		return this.parseUrl( this.getLocation() );
	},

	//Parse a URL into a structure that allows easy access to
	//all of the URL components by name.
	parseUrl: function( url ) {
		// If we're passed an object, we'll assume that it is
		// a parsed url object and just return it back to the caller.
		if ( $.type( url ) === "object" ) {
			return url;
		}

		var matches = path.urlParseRE.exec( url || "" ) || [];

		// Create an object that allows the caller to access the sub-matches
		// by name. Note that IE returns an empty string instead of undefined,
		// like all other browsers do, so we normalize everything so its consistent
		// no matter what browser we're running on.
		return {
			href: matches[ 0 ] || "",
			hrefNoHash: matches[ 1 ] || "",
			hrefNoSearch: matches[ 2 ] || "",
			domain: matches[ 3 ] || "",
			protocol: matches[ 4 ] || "",
			doubleSlash: matches[ 5 ] || "",
			authority: matches[ 6 ] || "",
			username: matches[ 8 ] || "",
			password: matches[ 9 ] || "",
			host: matches[ 10 ] || "",
			hostname: matches[ 11 ] || "",
			port: matches[ 12 ] || "",
			pathname: matches[ 13 ] || "",
			directory: matches[ 14 ] || "",
			filename: matches[ 15 ] || "",
			search: matches[ 16 ] || "",
			hash: matches[ 17 ] || ""
		};
	},

	//Turn relPath into an asbolute path. absPath is
	//an optional absolute path which describes what
	//relPath is relative to.
	makePathAbsolute: function( relPath, absPath ) {
		var absStack,
			relStack,
			i, d;

		if ( relPath && relPath.charAt( 0 ) === "/" ) {
			return relPath;
		}

		relPath = relPath || "";
		absPath = absPath ? absPath.replace( /^\/|(\/[^\/]*|[^\/]+)$/g, "" ) : "";

		absStack = absPath ? absPath.split( "/" ) : [];
		relStack = relPath.split( "/" );

		for ( i = 0; i < relStack.length; i++ ) {
			d = relStack[ i ];
			switch ( d ) {
			case ".":
				break;
			case "..":
				if ( absStack.length ) {
					absStack.pop();
				}
				break;
			default:
				absStack.push( d );
				break;
			}
		}
		return "/" + absStack.join( "/" );
	},

	//Returns true if both urls have the same domain.
	isSameDomain: function( absUrl1, absUrl2 ) {
		return path.parseUrl( absUrl1 ).domain.toLowerCase() ===
			path.parseUrl( absUrl2 ).domain.toLowerCase();
	},

	//Returns true for any relative variant.
	isRelativeUrl: function( url ) {
		// All relative Url variants have one thing in common, no protocol.
		return path.parseUrl( url ).protocol === "";
	},

	//Returns true for an absolute url.
	isAbsoluteUrl: function( url ) {
		return path.parseUrl( url ).protocol !== "";
	},

	//Turn the specified realtive URL into an absolute one. This function
	//can handle all relative variants (protocol, site, document, query, fragment).
	makeUrlAbsolute: function( relUrl, absUrl ) {
		if ( !path.isRelativeUrl( relUrl ) ) {
			return relUrl;
		}

		if ( absUrl === undefined ) {
			absUrl = this.documentBase;
		}

		var relObj = path.parseUrl( relUrl ),
			absObj = path.parseUrl( absUrl ),
			protocol = relObj.protocol || absObj.protocol,
			doubleSlash = relObj.protocol ? relObj.doubleSlash : ( relObj.doubleSlash || absObj.doubleSlash ),
			authority = relObj.authority || absObj.authority,
			hasPath = relObj.pathname !== "",
			pathname = path.makePathAbsolute( relObj.pathname || absObj.filename, absObj.pathname ),
			search = relObj.search || ( !hasPath && absObj.search ) || "",
			hash = relObj.hash;

		return protocol + doubleSlash + authority + pathname + search + hash;
	},

	//Add search (aka query) params to the specified url.
	addSearchParams: function( url, params ) {
		var u = path.parseUrl( url ),
			p = ( typeof params === "object" ) ? $.param( params ) : params,
			s = u.search || "?";
		return u.hrefNoSearch + s + ( s.charAt( s.length - 1 ) !== "?" ? "&" : "" ) + p + ( u.hash || "" );
	},

	convertUrlToDataUrl: function( absUrl ) {
		var result = absUrl,
			u = path.parseUrl( absUrl );

		if ( path.isEmbeddedPage( u ) ) {
			// For embedded pages, remove the dialog hash key as in getFilePath(),
			// and remove otherwise the Data Url won't match the id of the embedded Page.
			result = u.hash
				.split( dialogHashKey )[ 0 ]
				.replace( /^#/, "" )
				.replace( /\?.*$/, "" );
		} else if ( path.isSameDomain( u, this.documentBase ) ) {
			result = u.hrefNoHash.replace( this.documentBase.domain, "" ).split( dialogHashKey )[ 0 ];
		}

		return window.decodeURIComponent( result );
	},

	//get path from current hash, or from a file path
	get: function( newPath ) {
		if ( newPath === undefined ) {
			newPath = path.parseLocation().hash;
		}
		return path.stripHash( newPath ).replace( /[^\/]*\.[^\/*]+$/, "" );
	},

	//set location hash to path
	set: function( path ) {
		location.hash = path;
	},

	//test if a given url (string) is a path
	//NOTE might be exceptionally naive
	isPath: function( url ) {
		return ( /\// ).test( url );
	},

	//return a url path with the window's location protocol/hostname/pathname removed
	clean: function( url ) {
		return url.replace( this.documentBase.domain, "" );
	},

	//just return the url without an initial #
	stripHash: function( url ) {
		return url.replace( /^#/, "" );
	},

	stripQueryParams: function( url ) {
		return url.replace( /\?.*$/, "" );
	},

	//remove the preceding hash, any query params, and dialog notations
	cleanHash: function( hash ) {
		return path.stripHash( hash.replace( /\?.*$/, "" ).replace( dialogHashKey, "" ) );
	},

	isHashValid: function( hash ) {
		return ( /^#[^#]+$/ ).test( hash );
	},

	//check whether a url is referencing the same domain, or an external domain or different protocol
	//could be mailto, etc
	isExternal: function( url ) {
		var u = path.parseUrl( url );

		return !!( u.protocol &&
			( u.domain.toLowerCase() !== this.documentUrl.domain.toLowerCase() ) );
	},

	hasProtocol: function( url ) {
		return ( /^(:?\w+:)/ ).test( url );
	},

	isEmbeddedPage: function( url ) {
		var u = path.parseUrl( url );

		//if the path is absolute, then we need to compare the url against
		//both the this.documentUrl and the documentBase. The main reason for this
		//is that links embedded within external documents will refer to the
		//application document, whereas links embedded within the application
		//document will be resolved against the document base.
		if ( u.protocol !== "" ) {
			return ( !this.isPath( u.hash ) && u.hash && ( u.hrefNoHash === this.documentUrl.hrefNoHash || ( this.documentBaseDiffers && u.hrefNoHash === this.documentBase.hrefNoHash ) ) );
		}
		return ( /^#/ ).test( u.href );
	},

	squash: function( url, resolutionUrl ) {
		var href, cleanedUrl, search, stateIndex, docUrl,
			isPath = this.isPath( url ),
			uri = this.parseUrl( url ),
			preservedHash = uri.hash,
			uiState = "";

		// produce a url against which we can resolve the provided path
		if ( !resolutionUrl ) {
			if ( isPath ) {
				resolutionUrl = path.getLocation();
			} else {
				docUrl = path.getDocumentUrl( true );
				if ( path.isPath( docUrl.hash ) ) {
					resolutionUrl = path.squash( docUrl.href );
				} else {
					resolutionUrl = docUrl.href;
				}
			}
		}

		// If the url is anything but a simple string, remove any preceding hash
		// eg #foo/bar -> foo/bar
		//    #foo -> #foo
		cleanedUrl = isPath ? path.stripHash( url ) : url;

		// If the url is a full url with a hash check if the parsed hash is a path
		// if it is, strip the #, and use it otherwise continue without change
		cleanedUrl = path.isPath( uri.hash ) ? path.stripHash( uri.hash ) : cleanedUrl;

		// Split the UI State keys off the href
		stateIndex = cleanedUrl.indexOf( this.uiStateKey );

		// store the ui state keys for use
		if ( stateIndex > -1 ) {
			uiState = cleanedUrl.slice( stateIndex );
			cleanedUrl = cleanedUrl.slice( 0, stateIndex );
		}

		// make the cleanedUrl absolute relative to the resolution url
		href = path.makeUrlAbsolute( cleanedUrl, resolutionUrl );

		// grab the search from the resolved url since parsing from
		// the passed url may not yield the correct result
		search = this.parseUrl( href ).search;

		// TODO all this crap is terrible, clean it up
		if ( isPath ) {
			// reject the hash if it's a path or it's just a dialog key
			if ( path.isPath( preservedHash ) || preservedHash.replace( "#", "" ).indexOf( this.uiStateKey ) === 0 ) {
				preservedHash = "";
			}

			// Append the UI State keys where it exists and it's been removed
			// from the url
			if ( uiState && preservedHash.indexOf( this.uiStateKey ) === -1 ) {
				preservedHash += uiState;
			}

			// make sure that pound is on the front of the hash
			if ( preservedHash.indexOf( "#" ) === -1 && preservedHash !== "" ) {
				preservedHash = "#" + preservedHash;
			}

			// reconstruct each of the pieces with the new search string and hash
			href = path.parseUrl( href );
			href = href.protocol + href.doubleSlash + href.host + href.pathname + search +
				preservedHash;
		} else {
			href += href.indexOf( "#" ) > -1 ? uiState : "#" + uiState;
		}

		return href;
	},

	isPreservableHash: function( hash ) {
		return hash.replace( "#", "" ).indexOf( this.uiStateKey ) === 0;
	},

	// Escape weird characters in the hash if it is to be used as a selector
	hashToSelector: function( hash ) {
		var hasHash = ( hash.substring( 0, 1 ) === "#" );
		if ( hasHash ) {
			hash = hash.substring( 1 );
		}
		return ( hasHash ? "#" : "" ) + hash.replace( /([!"#$%&'()*+,./:;<=>?@[\]^`{|}~])/g, "\\$1" );
	},

	// return the substring of a filepath before the dialogHashKey, for making a server
	// request
	getFilePath: function( path ) {
		return path && path.split( dialogHashKey )[ 0 ];
	},

	// check if the specified url refers to the first page in the main
	// application document.
	isFirstPageUrl: function( url ) {
		// We only deal with absolute paths.
		var u = path.parseUrl( path.makeUrlAbsolute( url, this.documentBase ) ),

			// Does the url have the same path as the document?
			samePath = u.hrefNoHash === this.documentUrl.hrefNoHash ||
				( this.documentBaseDiffers &&
				u.hrefNoHash === this.documentBase.hrefNoHash ),

			// Get the first page element.
			fp = $.mobile.firstPage,

			// Get the id of the first page element if it has one.
			fpId = fp && fp[ 0 ] ? fp[ 0 ].id : undefined;

		// The url refers to the first page if the path matches the document and
		// it either has no hash value, or the hash is exactly equal to the id
		// of the first page element.
		return samePath &&
			( !u.hash ||
			u.hash === "#" ||
			( fpId && u.hash.replace( /^#/, "" ) === fpId ) );
	},

	// Some embedded browsers, like the web view in Phone Gap, allow
	// cross-domain XHR requests if the document doing the request was loaded
	// via the file:// protocol. This is usually to allow the application to
	// "phone home" and fetch app specific data. We normally let the browser
	// handle external/cross-domain urls, but if the allowCrossDomainPages
	// option is true, we will allow cross-domain http/https requests to go
	// through our page loading logic.
	isPermittedCrossDomainRequest: function( docUrl, reqUrl ) {
		return $.mobile.allowCrossDomainPages &&
			( docUrl.protocol === "file:" || docUrl.protocol === "content:" ) &&
			reqUrl.search( /^https?:/ ) !== -1;
	}
};

path.documentUrl = path.parseLocation();

$base = $( "head" ).find( "base" );

path.documentBase = $base.length ?
	path.parseUrl( path.makeUrlAbsolute( $base.attr( "href" ), path.documentUrl.href ) ) :
	path.documentUrl;

path.documentBaseDiffers = ( path.documentUrl.hrefNoHash !== path.documentBase.hrefNoHash );

//return the original document base url
path.getDocumentBase = function( asParsedObject ) {
	return asParsedObject ? $.extend( {}, path.documentBase ) : path.documentBase.href;
};

return path;
} );

/*!
 * jQuery Mobile History Manager @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: History Manager
//>>group: Navigation
//>>description: Manages a stack of history entries. Used exclusively by the Navigation Manager
//>>demos: http://demos.jquerymobile.com/@VERSION/navigation/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'navigation/history',[
			"jquery",
			"./../ns",
			"./path" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.History = function( stack, index ) {
	this.stack = stack || [];
	this.activeIndex = index || 0;
};

$.extend( $.mobile.History.prototype, {
	getActive: function() {
		return this.stack[ this.activeIndex ];
	},

	getLast: function() {
		return this.stack[ this.previousIndex ];
	},

	getNext: function() {
		return this.stack[ this.activeIndex + 1 ];
	},

	getPrev: function() {
		return this.stack[ this.activeIndex - 1 ];
	},

	// addNew is used whenever a new page is added
	add: function( url, data ) {
		data = data || {};

		//if there's forward history, wipe it
		if ( this.getNext() ) {
			this.clearForward();
		}

		// if the hash is included in the data make sure the shape
		// is consistent for comparison
		if ( data.hash && data.hash.indexOf( "#" ) === -1 ) {
			data.hash = "#" + data.hash;
		}

		data.url = url;
		this.stack.push( data );
		this.activeIndex = this.stack.length - 1;
	},

	//wipe urls ahead of active index
	clearForward: function() {
		this.stack = this.stack.slice( 0, this.activeIndex + 1 );
	},

	find: function( url, stack, earlyReturn ) {
		stack = stack || this.stack;

		var entry, i,
			length = stack.length, index;

		for ( i = 0; i < length; i++ ) {
			entry = stack[ i ];

			if ( decodeURIComponent( url ) === decodeURIComponent( entry.url ) ||
					decodeURIComponent( url ) === decodeURIComponent( entry.hash ) ) {
				index = i;

				if ( earlyReturn ) {
					return index;
				}
			}
		}

		return index;
	},

	_findById: function( id ) {
		var stackIndex,
			stackLength = this.stack.length;

		for ( stackIndex = 0; stackIndex < stackLength; stackIndex++ ) {
			if ( this.stack[ stackIndex ].id === id ) {
				break;
			}
		}

		return ( stackIndex < stackLength ? stackIndex : undefined );
	},

	closest: function( url, id ) {
		var closest = ( id === undefined ? undefined : this._findById( id ) ),
			a = this.activeIndex;

		// First, we check whether we've found an entry by id. If so, we're done.
		if ( closest !== undefined ) {
			return closest;
		}

		// Failing that take the slice of the history stack before the current index and search
		// for a url match. If one is found, we'll avoid avoid looking through forward history
		// NOTE the preference for backward history movement is driven by the fact that
		//      most mobile browsers only have a dedicated back button, and users rarely use
		//      the forward button in desktop browser anyhow
		closest = this.find( url, this.stack.slice( 0, a ) );

		// If nothing was found in backward history check forward. The `true`
		// value passed as the third parameter causes the find method to break
		// on the first match in the forward history slice. The starting index
		// of the slice must then be added to the result to get the element index
		// in the original history stack :( :(
		//
		// TODO this is hyper confusing and should be cleaned up (ugh so bad)
		if ( closest === undefined ) {
			closest = this.find( url, this.stack.slice( a ), true );
			closest = closest === undefined ? closest : closest + a;
		}

		return closest;
	},

	direct: function( opts ) {
		var newActiveIndex = this.closest( opts.url, opts.id ),
			a = this.activeIndex;

		// save new page index, null check to prevent falsey 0 result
		// record the previous index for reference
		if ( newActiveIndex !== undefined ) {
			this.activeIndex = newActiveIndex;
			this.previousIndex = a;
		}

		// invoke callbacks where appropriate
		//
		// TODO this is also convoluted and confusing
		if ( newActiveIndex < a ) {
			( opts.present || opts.back || $.noop )( this.getActive(), "back" );
		} else if ( newActiveIndex > a ) {
			( opts.present || opts.forward || $.noop )( this.getActive(), "forward" );
		} else if ( newActiveIndex === undefined && opts.missing ) {
			opts.missing( this.getActive() );
		}
	}
} );

return $.mobile.History;
} );

/*!
 * jQuery Mobile Navigator @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Navigation Manager
//>>group: Navigation
//>>description: Manages URL history and information in conjunction with the navigate event

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'navigation/navigator',[
			"jquery",
			"./../ns",
			"../events/navigate",
			"./path",
			"./history" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var path = $.mobile.path,
	initialHref = location.href;

$.mobile.Navigator = function( history ) {
	this.history = history;
	this.ignoreInitialHashChange = true;

	$.mobile.window.bind( {
		"popstate.history": $.proxy( this.popstate, this ),
		"hashchange.history": $.proxy( this.hashchange, this )
	} );
};

$.extend( $.mobile.Navigator.prototype, {
	historyEntryId: 0,
	squash: function( url, data ) {
		var state, href,
			hash = path.isPath( url ) ? path.stripHash( url ) : url;

		href = path.squash( url );

		// make sure to provide this information when it isn't explicitly set in the
		// data object that was passed to the squash method
		state = $.extend( {
			id: ++this.historyEntryId,
			hash: hash,
			url: href
		}, data );

		// replace the current url with the new href and store the state
		// Note that in some cases we might be replacing an url with the
		// same url. We do this anyways because we need to make sure that
		// all of our history entries have a state object associated with
		// them. This allows us to work around the case where $.mobile.back()
		// is called to transition from an external page to an embedded page.
		// In that particular case, a hashchange event is *NOT* generated by the browser.
		// Ensuring each history entry has a state object means that onPopState()
		// will always trigger our hashchange callback even when a hashchange event
		// is not fired.
		//
		//  Check is to make sure that jqm doesn't throw type errors in environments where
		//  window.history is not available (e.g. Chrome packaged apps)
		if ( window.history && window.history.replaceState ) {
			window.history.replaceState( state, state.title || document.title, href );
		}

		// If we haven't yet received the initial popstate, we need to update the reference
		// href so that we compare against the correct location
		if ( this.ignoreInitialHashChange ) {
			initialHref = href;
		}

		return state;
	},

	hash: function( url, href ) {
		var parsed, loc, hash, resolved;

		// Grab the hash for recording. If the passed url is a path
		// we used the parsed version of the squashed url to reconstruct,
		// otherwise we assume it's a hash and store it directly
		parsed = path.parseUrl( url );
		loc = path.parseLocation();

		if ( loc.pathname + loc.search === parsed.pathname + parsed.search ) {
			// If the pathname and search of the passed url is identical to the current loc
			// then we must use the hash. Otherwise there will be no event
			// eg, url = "/foo/bar?baz#bang", location.href = "http://example.com/foo/bar?baz"
			hash = parsed.hash ? parsed.hash : parsed.pathname + parsed.search;
		} else if ( path.isPath( url ) ) {
			resolved = path.parseUrl( href );
			// If the passed url is a path, make it domain relative and remove any trailing hash
			hash = resolved.pathname + resolved.search + ( path.isPreservableHash( resolved.hash ) ? resolved.hash.replace( "#", "" ) : "" );
		} else {
			hash = url;
		}

		return hash;
	},

	// TODO reconsider name
	go: function( url, data, noEvents ) {
		var state, href, hash, popstateEvent,
			isPopStateEvent = $.event.special.navigate.isPushStateEnabled();

		// Get the url as it would look squashed on to the current resolution url
		href = path.squash( url );

		// sort out what the hash sould be from the url
		hash = this.hash( url, href );

		// Here we prevent the next hash change or popstate event from doing any
		// history management. In the case of hashchange we don't swallow it
		// if there will be no hashchange fired (since that won't reset the value)
		// and will swallow the following hashchange
		if ( noEvents && hash !== path.stripHash( path.parseLocation().hash ) ) {
			this.preventNextHashChange = noEvents;
		}

		// IMPORTANT in the case where popstate is supported the event will be triggered
		//      directly, stopping further execution - ie, interupting the flow of this
		//      method call to fire bindings at this expression. Below the navigate method
		//      there is a binding to catch this event and stop its propagation.
		//
		//      We then trigger a new popstate event on the window with a null state
		//      so that the navigate events can conclude their work properly
		//
		// if the url is a path we want to preserve the query params that are available on
		// the current url.
		this.preventHashAssignPopState = true;
		window.location.hash = hash;

		// If popstate is enabled and the browser triggers `popstate` events when the hash
		// is set (this often happens immediately in browsers like Chrome), then the
		// this flag will be set to false already. If it's a browser that does not trigger
		// a `popstate` on hash assignement or `replaceState` then we need avoid the branch
		// that swallows the event created by the popstate generated by the hash assignment
		// At the time of this writing this happens with Opera 12 and some version of IE
		this.preventHashAssignPopState = false;

		state = $.extend( {
			url: href,
			hash: hash,
			title: document.title
		}, data );

		if ( isPopStateEvent ) {
			popstateEvent = new $.Event( "popstate" );
			popstateEvent.originalEvent = new $.Event( "popstate", { state: null } );

			state.id = ( this.squash( url, state ) || {} ).id;

			// Trigger a new faux popstate event to replace the one that we
			// caught that was triggered by the hash setting above.
			if ( !noEvents ) {
				this.ignorePopState = true;
				$.mobile.window.trigger( popstateEvent );
			}
		}

		// record the history entry so that the information can be included
		// in hashchange event driven navigate events in a similar fashion to
		// the state that's provided by popstate
		this.history.add( state.url, state );
	},

	// This binding is intended to catch the popstate events that are fired
	// when execution of the `$.navigate` method stops at window.location.hash = url;
	// and completely prevent them from propagating. The popstate event will then be
	// retriggered after execution resumes
	//
	// TODO grab the original event here and use it for the synthetic event in the
	//      second half of the navigate execution that will follow this binding
	popstate: function( event ) {
		var hash, state;

		// Partly to support our test suite which manually alters the support
		// value to test hashchange. Partly to prevent all around weirdness
		if ( !$.event.special.navigate.isPushStateEnabled() ) {
			return;
		}

		// If this is the popstate triggered by the actual alteration of the hash
		// prevent it completely. History is tracked manually
		if ( this.preventHashAssignPopState ) {
			this.preventHashAssignPopState = false;
			event.stopImmediatePropagation();
			return;
		}

		// if this is the popstate triggered after the `replaceState` call in the go
		// method, then simply ignore it. The history entry has already been captured
		if ( this.ignorePopState ) {
			this.ignorePopState = false;
			return;
		}

		// If there is no state, and the history stack length is one were
		// probably getting the page load popstate fired by browsers like chrome
		// avoid it and set the one time flag to false.
		// TODO: Do we really need all these conditions? Comparing location hrefs
		// should be sufficient.
		if ( !event.originalEvent.state &&
				this.history.stack.length === 1 &&
				this.ignoreInitialHashChange ) {
			this.ignoreInitialHashChange = false;

			if ( location.href === initialHref ) {
				event.preventDefault();
				return;
			}
		}

		// account for direct manipulation of the hash. That is, we will receive a popstate
		// when the hash is changed by assignment, and it won't have a state associated. We
		// then need to squash the hash. See below for handling of hash assignment that
		// matches an existing history entry
		// TODO it might be better to only add to the history stack
		//      when the hash is adjacent to the active history entry
		hash = path.parseLocation().hash;
		if ( !event.originalEvent.state && hash ) {
			// squash the hash that's been assigned on the URL with replaceState
			// also grab the resulting state object for storage
			state = this.squash( hash );

			// record the new hash as an additional history entry
			// to match the browser's treatment of hash assignment
			this.history.add( state.url, state );

			// pass the newly created state information
			// along with the event
			event.historyState = state;

			// do not alter history, we've added a new history entry
			// so we know where we are
			return;
		}

		// If all else fails this is a popstate that comes from the back or forward buttons
		// make sure to set the state of our history stack properly, and record the directionality
		this.history.direct( {
			id: ( event.originalEvent.state || {} ).id,
			url: ( event.originalEvent.state || {} ).url || hash,

			// When the url is either forward or backward in history include the entry
			// as data on the event object for merging as data in the navigate event
			present: function( historyEntry, direction ) {
				// make sure to create a new object to pass down as the navigate event data
				event.historyState = $.extend( {}, historyEntry );
				event.historyState.direction = direction;
			}
		} );
	},

	// NOTE must bind before `navigate` special event hashchange binding otherwise the
	//      navigation data won't be attached to the hashchange event in time for those
	//      bindings to attach it to the `navigate` special event
	// TODO add a check here that `hashchange.navigate` is bound already otherwise it's
	//      broken (exception?)
	hashchange: function( event ) {
		var history, hash;

		// If hashchange listening is explicitly disabled or pushstate is supported
		// avoid making use of the hashchange handler.
		if ( !$.event.special.navigate.isHashChangeEnabled() ||
				$.event.special.navigate.isPushStateEnabled() ) {
			return;
		}

		// On occasion explicitly want to prevent the next hash from propagating because we only
		// with to alter the url to represent the new state do so here
		if ( this.preventNextHashChange ) {
			this.preventNextHashChange = false;
			event.stopImmediatePropagation();
			return;
		}

		history = this.history;
		hash = path.parseLocation().hash;

		// If this is a hashchange caused by the back or forward button
		// make sure to set the state of our history stack properly
		this.history.direct( {
			url: hash,

			// When the url is either forward or backward in history include the entry
			// as data on the event object for merging as data in the navigate event
			present: function( historyEntry, direction ) {
				// make sure to create a new object to pass down as the navigate event data
				event.hashchangeState = $.extend( {}, historyEntry );
				event.hashchangeState.direction = direction;
			},

			// When we don't find a hash in our history clearly we're aiming to go there
			// record the entry as new for future traversal
			//
			// NOTE it's not entirely clear that this is the right thing to do given that we
			//      can't know the users intention. It might be better to explicitly _not_
			//      support location.hash assignment in preference to $.navigate calls
			// TODO first arg to add should be the href, but it causes issues in identifying
			//      embedded pages
			missing: function() {
				history.add( hash, {
					hash: hash,
					title: document.title
				} );
			}
		} );
	}
} );

return $.mobile.Navigator;
} );

/*!
 * jQuery Mobile Navigate Method @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Navigate Method
//>>group: Navigation
//>>description: A wrapper for the primary Navigator and History objects in jQuery Mobile
//>>docs: http://api.jquerymobile.com/jQuery.mobile.navigate/
//>>demos: http://demos.jquerymobile.com/@VERSION/navigation/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'navigation/method',[
			"jquery",
			"./path",
			"./history",
			"./navigator" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

// TODO consider queueing navigation activity until previous activities have completed
//      so that end users don't have to think about it. Punting for now
// TODO !! move the event bindings into callbacks on the navigate event
$.mobile.navigate = function( url, data, noEvents ) {
	$.mobile.navigate.navigator.go( url, data, noEvents );
};

// expose the history on the navigate method in anticipation of full integration with
// existing navigation functionalty that is tightly coupled to the history information
$.mobile.navigate.history = new $.mobile.History();

// instantiate an instance of the navigator for use within the $.navigate method
$.mobile.navigate.navigator = new $.mobile.Navigator( $.mobile.navigate.history );

var loc = $.mobile.path.parseLocation();
$.mobile.navigate.history.add( loc.href, { hash: loc.hash } );

return $.mobile.navigate;
} );

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/safe-active-element',[ "jquery", "./version" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} ( function( $ ) {
return $.ui.safeActiveElement = function( document ) {
	var activeElement;

	// Support: IE 9 only
	// IE9 throws an "Unspecified error" accessing document.activeElement from an <iframe>
	try {
		activeElement = document.activeElement;
	} catch ( error ) {
		activeElement = document.body;
	}

	// Support: IE 9 - 11 only
	// IE may return null instead of an element
	// Interestingly, this only seems to occur when NOT in an iframe
	if ( !activeElement ) {
		activeElement = document.body;
	}

	// Support: IE 11 only
	// IE11 returns a seemingly empty object in some cases when accessing
	// document.activeElement from an <iframe>
	if ( !activeElement.nodeName ) {
		activeElement = document.body;
	}

	return activeElement;
};

} ) );

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/safe-blur',[ "jquery", "./version" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} ( function( $ ) {
return $.ui.safeBlur = function( element ) {

	// Support: IE9 - 10 only
	// If the <body> is blurred, IE will switch windows, see #9420
	if ( element && element.nodeName.toLowerCase() !== "body" ) {
		$( element ).trigger( "blur" );
	}
};

} ) );

/*!
 * jQuery Mobile Base Tag Support @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Base Tag
//>>group: Navigation
//>>description: Dynamic Base Tag Support

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'navigation/base',[
			"jquery",
			"./path",
			"./../ns" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var base,

	// Existing base tag?
	baseElement = $( "head" ).children( "base" ),

	// DEPRECATED as of 1.5.0 and will be removed in 1.6.0. As of 1.6.0 only
	// base.dynamicBaseEnabled will be checked
	getDynamicEnabled = function() {

		// If a value has been set at the old, deprecated location, we return that value.
		// Otherwise we return the value from the new location. We check explicitly for
		// undefined because true and false are both valid values for dynamicBaseEnabled.
		if ( $.mobile.dynamicBaseEnabled !== undefined ) {
			return $.mobile.dynamicBaseEnabled;
		}
		return base.dynamicBaseEnabled;
	};

// base element management, defined depending on dynamic base tag support
// TODO move to external widget
base = {

	// Disable the alteration of the dynamic base tag or links
	dynamicBaseEnabled: true,

	// Make sure base element is defined, for use in routing asset urls that are referenced
	// in Ajax-requested markup
	element: function() {
		if ( !( baseElement && baseElement.length ) ) {
			baseElement = $( "<base>", { href: $.mobile.path.documentBase.hrefNoSearch } )
				.prependTo( $( "head" ) );
		}

		return baseElement;
	},

	// set the generated BASE element's href to a new page's base path
	set: function( href ) {

		// We should do nothing if the user wants to manage their url base manually.
		// Note: Our method of ascertaining whether the user wants to manager their url base
		// manually is DEPRECATED as of 1.5.0 and will be removed in 1.6.0. As of 1.6.0 the
		// flag base.dynamicBaseEnabled will be checked, so the function getDynamicEnabled()
		// will be removed.
		if ( !getDynamicEnabled() ) {
			return;
		}

		// we should use the base tag if we can manipulate it dynamically
		base.element().attr( "href",
			$.mobile.path.makeUrlAbsolute( href, $.mobile.path.documentBase ) );
	},

	// set the generated BASE element's href to a new page's base path
	reset: function( /* href */ ) {

		// DEPRECATED as of 1.5.0 and will be removed in 1.6.0. As of 1.6.0 only
		// base.dynamicBaseEnabled will be checked
		if ( !getDynamicEnabled() ) {
			return;
		}

		base.element().attr( "href", $.mobile.path.documentBase.hrefNoSearch );
	}
};

$.mobile.base = base;

return base;
} );

/*!
 * jQuery Mobile Enhancer @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Enhancer
//>>group: Widgets
//>>description: Enhables declarative initalization of widgets
//>>docs: http://api.jquerymobile.com/enhancer/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/enhancer',[
			"jquery" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var widgetBaseClass,
	installed = false;

$.fn.extend( {
	enhance: function() {
		return $.enhance.enhance( this );
	},
	enhanceWithin: function() {
		this.children().enhance();
		return this;
	},
	enhanceOptions: function() {
		return $.enhance.getOptions( this );
	},
	enhanceRoles: function() {
		return $.enhance.getRoles( this );
	}
} );
$.enhance = $.enhance || {};
$.extend( $.enhance, {

	enhance: function( elem ) {
		var i,
			enhanceables = elem.find( "[" + $.enhance.defaultProp() + "]" ).addBack();

		if ( $.enhance._filter ) {
			enhanceables = $.enhance._filter( enhanceables );
		}

		// Loop over and execute any hooks that exist
		for ( i = 0; i < $.enhance.hooks.length; i++ ) {
			$.enhance.hooks[ i ].call( elem, enhanceables );
		}

		// Call the default enhancer function
		$.enhance.defaultFunction.call( elem, enhanceables );

		return elem;
	},

	// Check if the enhancer has already been defined if it has copy its hooks if not
	// define an empty array
	hooks: $.enhance.hooks || [],

	_filter: $.enhance._filter || false,

	defaultProp: $.enhance.defaultProp || function() { return "data-ui-role"; },

	defaultFunction: function( enhanceables ) {
		enhanceables.each( function() {
			var i,
				roles = $( this ).enhanceRoles();

			for ( i = 0; i < roles.length; i++ ) {
				if ( $.fn[ roles[ i ] ] ) {
					$( this )[ roles[ i ] ]();
				}
			}
		} );
	},

	cache: true,

	roleCache: {},

	getRoles: function( element ) {
		if ( !element.length ) {
			return [];
		}

		var role,

			// Look for cached roles
			roles = $.enhance.roleCache[ !!element[ 0 ].id ? element[ 0 ].id : undefined ];

		// We already have done this return the roles
		if ( roles ) {
			return roles;
		}

		// This is our first time get the attribute and parse it
		role = element.attr( $.enhance.defaultProp() );
		roles = role ? role.match( /\S+/g ) : [];

		// Caches the array of roles for next time
		$.enhance.roleCache[ element[ 0 ].id ] = roles;

		// Return the roles
		return roles;
	},

	optionCache: {},

	getOptions: function( element ) {
		var options = $.enhance.optionCache[ !!element[ 0 ].id ? element[ 0 ].id : undefined ],
			ns;

		// Been there done that return what we already found
		if ( !!options ) {
			return options;
		}

		// This is the first time lets compile the options object
		options = {};
		ns = ( $.mobile.ns || "ui-" ).replace( "-", "" );

		$.each( $( element ).data(), function( option, value ) {
			option = option.replace( ns, "" );

			option = option.charAt( 0 ).toLowerCase() + option.slice( 1 );
			options[ option ] = value;
		} );

		// Cache the options for next time
		$.enhance.optionCache[ element[ 0 ].id ] = options;

		// Return the options
		return options;
	},

	_installWidget: function() {
		if ( $.Widget && !installed ) {
			$.extend( $.Widget.prototype, {
				_getCreateOptions: function( options ) {
					var option, value,
						dataOptions = this.element.enhanceOptions();

					options = options || {};

					// Translate data-attributes to options
					for ( option in this.options ) {
						value = dataOptions[ option ];
						if ( value !== undefined ) {
							options[ option ] = value;
						}
					}
					return options;
				}
			} );
			installed = true;
		}
	}
} );

if ( !$.Widget ) {
	Object.defineProperty( $, "Widget", {
		configurable: true,
		enumerable: true,
		get: function() {
			return widgetBaseClass;
		},
		set: function( newValue ) {
			if ( newValue ) {
				widgetBaseClass = newValue;
				setTimeout( function() {
					$.enhance._installWidget();
				} );
			}
		}
	} );
} else {
	$.enhance._installWidget();
}

return $.enhance;
} );

/*!
 * jQuery Mobile Enhancer @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Enhancer Widget Crawler
//>>group: Widgets
//>>description: Adds support for custom initSlectors on widget prototypes
//>>docs: http://api.jquerymobile.com/enhancer/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/enhancer.widgetCrawler',[
			"jquery",
			"../core",
			"widgets/enhancer" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var widgetCrawler = function( elements, _childConstructors ) {
		$.each( _childConstructors, function( index, constructor ) {
			var prototype = constructor.prototype,
				plugin = $.enhance,
				selector = plugin.initGenerator( prototype ),
				found;

			if( !selector ) {
				return;
			}

			found = elements.find( selector );

			if ( plugin._filter ) {
				found = plugin._filter( found );
			}

			found[ prototype.widgetName ]();
			if ( constructor._childConstructors && constructor._childConstructors.length > 0 ) {
				widgetCrawler( elements, constructor._childConstructors );
			}
		} );
	},
	widgetHook = function() {
		if ( !$.enhance.initGenerator || !$.Widget ) {
			return;
		}

		// Enhance widgets with custom initSelectors
		widgetCrawler( this.addBack(), $.Widget._childConstructors );
	};

$.enhance.hooks.push( widgetHook );

return $.enhance;

} );

/*!
 * jQuery Mobile Enhancer Backcompat@VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Enhancer
//>>group: Widgets
//>>description: Enables declarative initalization of widgets
//>>docs: http://api.jquerymobile.com/enhancer/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/enhancer.backcompat',[
			"jquery",
			"widgets/enhancer",
			"widgets/enhancer.widgetCrawler" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {
if ( $.mobileBackcompat !== false ) {
	var filter = function( elements ) {
			elements = elements.not( $.mobile.keepNative );

			if ( $.mobile.ignoreContentEnabled ) {
				elements.each( function() {
					if ( $( this )
							.closest( "[data-" + $.mobile.ns + "enhance='false']" ).length ) {
						elements = elements.not( this );
					}
				} );
			}
			return elements;
		},
		generator = function( prototype ) {
			return prototype.initSelector ||
				$[ prototype.namespace ][ prototype.widgetName ].prototype.initSelector || false;
		};

	$.enhance._filter = filter;
	$.enhance.defaultProp = function() {
		return "data-" + $.mobile.ns + "role";
	};
	$.enhance.initGenerator = generator;

}

return $.enhance;

} );

/*!
 * jQuery Mobile Page @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Page Creation
//>>group: Core
//>>description: Basic page definition and formatting.
//>>docs: http://api.jquerymobile.com/page/
//>>demos: http://demos.jquerymobile.com/@VERSION/pages/
//>>css.structure: ../css/structure/jquery.mobile.core.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/page',[
			"jquery",
			"../widget",
			"./widget.theme",
			"../core",
			"widgets/enhancer",
			"widgets/enhancer.backcompat",
			"widgets/enhancer.widgetCrawler" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.widget( "mobile.page", {
	version: "@VERSION",

	options: {
		theme: "a",
		domCache: false,

		enhanceWithin: true,
		enhanced: false
	},

	_create: function() {

		// If false is returned by the callbacks do not create the page
		if ( this._trigger( "beforecreate" ) === false ) {
			return false;
		}

		this._establishStructure();
		this._setAttributes();
		this._attachToDOM();
		this._addHandlers();

		if ( this.options.enhanceWithin ) {
			this.element.enhanceWithin();
		}
	},

	_establishStructure: $.noop,

	_setAttributes: function() {
		if ( this.options.role ) {
			this.element.attr( "data-" + $.mobile.ns + "role", this.options.role );
		}
		this.element.attr( "tabindex", "0" );
		this._addClass( "ui-page" );
	},

	_attachToDOM: $.noop,

	_addHandlers: function() {
		this._on( this.element, {
			pagebeforehide: "_handlePageBeforeHide",
			pagebeforeshow: "_handlePageBeforeShow"
		} );
	},

	bindRemove: function( callback ) {
		var page = this.element;

		// When dom caching is not enabled or the page is embedded bind to remove the page on hide
		if ( !page.data( "mobile-page" ).options.domCache &&
				page.is( ":jqmData(external-page='true')" ) ) {

			this._on( this.document, {
				pagecontainerhide: callback || function( e, data ) {

					if ( data.prevPage[ 0 ] !== this.element[ 0 ] ) {
						return;
					}

					// Check if this is a same page transition and if so don't remove the page
					if ( !data.samePage ) {
						var prEvent = new $.Event( "pageremove" );

						this._trigger( "remove", prEvent );

						if ( !prEvent.isDefaultPrevented() ) {
							this.element.removeWithDependents();
						}
					}
				}
			} );
		}
	},

	_themeElements: function() {
		return [ {
			element: this.element,
			prefix: "ui-page-theme-"
		} ];
	},

	_handlePageBeforeShow: function( /* e */ ) {
		this._setContainerSwatch( this.options.theme );
	},

	_handlePageBeforeHide: function() {
		this._setContainerSwatch( "none" );
	},

	_setContainerSwatch: function( swatch ) {
		var pagecontainer = this.element.parent().pagecontainer( "instance" );

		if ( pagecontainer ) {
			pagecontainer.option( "theme", swatch );
		}
	}
} );

$.widget( "mobile.page", $.mobile.page, $.mobile.widget.theme );

return $.mobile.page;

} );

/*!
 * jQuery Mobile Page Container @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Content Management
//>>group: Navigation
//>>description: Widget to create page container which manages pages and transitions
//>>docs: http://api.jquerymobile.com/pagecontainer/
//>>demos: http://demos.jquerymobile.com/@VERSION/navigation/
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/pagecontainer',[
			"jquery",
			"../core",
			"jquery-ui/safe-active-element",
			"jquery-ui/safe-blur",
			"jquery-ui/widget",
			"../navigation/path",
			"../navigation/base",
			"../events/navigate",
			"../navigation/history",
			"../navigation/navigator",
			"../navigation/method",
			"../events/scroll",
			"../support",
			"../widgets/page" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

// These variables make all page containers use the same queue and only navigate one at a time
// queue to hold simultanious page transitions
var pageTransitionQueue = [],

	// Indicates whether or not page is in process of transitioning
	isPageTransitioning = false;

$.widget( "mobile.pagecontainer", {
	version: "@VERSION",

	options: {
		theme: "a",
		changeOptions: {
			transition: undefined,
			reverse: false,
			changeUrl: true,

			// Use changeUrl instead, changeHash is deprecated and will be removed in 1.6
			changeHash: true,
			fromHashChange: false,
			duplicateCachedPage: undefined,

			//loading message shows by default when pages are being fetched during change()
			showLoadMsg: true,
			dataUrl: undefined,
			fromPage: undefined,
			allowSamePageTransition: false
		}
	},

	initSelector: false,

	_create: function() {
		var currentOptions = this.options;

		currentOptions.changeUrl = currentOptions.changeUrl ? currentOptions.changeUrl :
		( currentOptions.changeHash ? true : false );

		// Maintain a global array of pagecontainers
		$.mobile.pagecontainers = ( $.mobile.pagecontainers ? $.mobile.pagecontainers : [] )
			.concat( [ this ] );

		// In the future this will be tracked to give easy access to the active pagecontainer
		// For now we just set it since multiple containers are not supported.
		$.mobile.pagecontainers.active = this;

		this._trigger( "beforecreate" );
		this.setLastScrollEnabled = true;

		this._on( this.window, {

			// Disable a scroll setting when a hashchange has been fired, this only works because
			// the recording of the scroll position is delayed for 100ms after the browser might
			// have changed the position because of the hashchange
			navigate: "_disableRecordScroll",

			// Bind to scrollstop for the first page, "pagechange" won't be fired in that case
			scrollstop: "_delayedRecordScroll"
		} );

		// TODO consider moving the navigation handler OUT of widget into
		//      some other object as glue between the navigate event and the
		//      content widget load and change methods
		this._on( this.window, { navigate: "_filterNavigateEvents" } );

		// TODO move from page* events to content* events
		this._on( { pagechange: "_afterContentChange" } );

		this._addClass( "ui-pagecontainer", "ui-mobile-viewport" );

		// Handle initial hashchange from chrome :(
		this.window.one( "navigate", $.proxy( function() {
			this.setLastScrollEnabled = true;
		}, this ) );
	},

	_setOptions: function( options ) {
		if ( options.theme !== undefined && options.theme !== "none" ) {
			this._removeClass( null, "ui-overlay-" + this.options.theme )
				._addClass( null, "ui-overlay-" + options.theme );
		} else if ( options.theme !== undefined ) {
			this._removeClass( null, "ui-overlay-" + this.options.theme );
		}

		this._super( options );
	},

	_disableRecordScroll: function() {
		this.setLastScrollEnabled = false;
	},

	_enableRecordScroll: function() {
		this.setLastScrollEnabled = true;
	},

	// TODO consider the name here, since it's purpose specific
	_afterContentChange: function() {

		// Once the page has changed, re-enable the scroll recording
		this.setLastScrollEnabled = true;

		// Remove any binding that previously existed on the get scroll which may or may not be
		// different than the scroll element determined for this page previously
		this._off( this.window, "scrollstop" );

		// Determine and bind to the current scoll element which may be the window or in the case
		// of touch overflow the element touch overflow
		this._on( this.window, { scrollstop: "_delayedRecordScroll" } );
	},

	_recordScroll: function() {

		// This barrier prevents setting the scroll value based on the browser scrolling the window
		// based on a hashchange
		if ( !this.setLastScrollEnabled ) {
			return;
		}

		var active = this._getActiveHistory(),
			currentScroll, defaultScroll;

		if ( active ) {
			currentScroll = this._getScroll();
			defaultScroll = this._getDefaultScroll();

			// Set active page's lastScroll prop. If the location we're scrolling to is less than
			// minScrollBack, let it go.
			active.lastScroll = currentScroll < defaultScroll ? defaultScroll : currentScroll;
		}
	},

	_delayedRecordScroll: function() {
		setTimeout( $.proxy( this, "_recordScroll" ), 100 );
	},

	_getScroll: function() {
		return this.window.scrollTop();
	},

	_getDefaultScroll: function() {
		return $.mobile.defaultHomeScroll;
	},

	_filterNavigateEvents: function( e, data ) {
		var url;

		if ( e.originalEvent && e.originalEvent.isDefaultPrevented() ) {
			return;
		}

		url = e.originalEvent.type.indexOf( "hashchange" ) > -1 ? data.state.hash : data.state.url;

		if ( !url ) {
			url = this._getHash();
		}

		if ( !url || url === "#" || url.indexOf( "#" + $.mobile.path.uiStateKey ) === 0 ) {
			url = location.href;
		}

		this._handleNavigate( url, data.state );
	},

	_getHash: function() {
		return $.mobile.path.parseLocation().hash;
	},

	// TODO active page should be managed by the container (ie, it should be a property)
	getActivePage: function() {
		return this.activePage;
	},

	// TODO the first page should be a property set during _create using the logic
	//      that currently resides in init
	_getInitialContent: function() {
		return $.mobile.firstPage;
	},

	// TODO each content container should have a history object
	_getHistory: function() {
		return $.mobile.navigate.history;
	},

	_getActiveHistory: function() {
		return this._getHistory().getActive();
	},

	// TODO the document base should be determined at creation
	_getDocumentBase: function() {
		return $.mobile.path.documentBase;
	},

	back: function() {
		this.go( -1 );
	},

	forward: function() {
		this.go( 1 );
	},

	go: function( steps ) {

		// If hashlistening is enabled use native history method
		if ( $.mobile.hashListeningEnabled ) {
			window.history.go( steps );
		} else {

			// We are not listening to the hash so handle history internally
			var activeIndex = $.mobile.navigate.history.activeIndex,
				index = activeIndex + parseInt( steps, 10 ),
				url = $.mobile.navigate.history.stack[ index ].url,
				direction = ( steps >= 1 ) ? "forward" : "back";

			// Update the history object
			$.mobile.navigate.history.activeIndex = index;
			$.mobile.navigate.history.previousIndex = activeIndex;

			// Change to the new page
			this.change( url, { direction: direction, changeUrl: false, fromHashChange: true } );
		}
	},

	// TODO rename _handleDestination
	_handleDestination: function( to ) {
		var history;

		// Clean the hash for comparison if it's a url
		if ( $.type( to ) === "string" ) {
			to = $.mobile.path.stripHash( to );
		}

		if ( to ) {
			history = this._getHistory();

			// At this point, 'to' can be one of 3 things, a cached page
			// element from a history stack entry, an id, or site-relative /
			// absolute URL. If 'to' is an id, we need to resolve it against
			// the documentBase, not the location.href, since the hashchange
			// could've been the result of a forward/backward navigation
			// that crosses from an external page/dialog to an internal
			// page/dialog.
			//
			// TODO move check to history object or path object?
			to = !$.mobile.path.isPath( to ) ? ( $.mobile.path.makeUrlAbsolute( "#" + to, this._getDocumentBase() ) ) : to;
		}
		return to || this._getInitialContent();
	},

	// The options by which a given page was reached are stored in the history entry for that
	// page. When this function is called, history is already at the new entry. So, when moving
	// back, this means we need to consult the old entry and reverse the meaning of the
	// options. Otherwise, if we're moving forward, we need to consult the options for the
	// current entry.
	_optionFromHistory: function( direction, optionName, fallbackValue ) {
		var history = this._getHistory(),
			entry = ( direction === "back" ? history.getLast() : history.getActive() );

		return ( ( entry && entry[ optionName ] ) || fallbackValue );
	},

	_handleDialog: function( changePageOptions, data ) {
		var to, active,
			activeContent = this.getActivePage();

		// If current active page is not a dialog skip the dialog and continue
		// in the same direction
		// Note: The dialog widget is deprecated as of 1.4.0 and will be removed in 1.5.0.
		// Thus, as of 1.5.0 activeContent.data( "mobile-dialog" ) will always evaluate to
		// falsy, so the second condition in the if-statement below can be removed altogether.
		if ( activeContent && !activeContent.data( "mobile-dialog" ) ) {
			// determine if we're heading forward or backward and continue
			// accordingly past the current dialog
			if ( data.direction === "back" ) {
				this.back();
			} else {
				this.forward();
			}

			// Prevent change() call
			return false;
		} else {

			// If the current active page is a dialog and we're navigating
			// to a dialog use the dialog objected saved in the stack
			to = data.pageUrl;
			active = this._getActiveHistory();

			// Make sure to set the role, transition and reversal
			// as most of this is lost by the domCache cleaning
			$.extend( changePageOptions, {
				role: active.role,
				transition: this._optionFromHistory( data.direction, "transition",
					changePageOptions.transition ),
				reverse: data.direction === "back"
			} );
		}

		return to;
	},

	_handleNavigate: function( url, data ) {

		// Find first page via hash
		// TODO stripping the hash twice with handleUrl
		var to = $.mobile.path.stripHash( url ),
			history = this._getHistory(),

			// Transition is false if it's the first page, undefined otherwise (and may be
			// overridden by default)
			transition = history.stack.length === 0 ? "none" :
				this._optionFromHistory( data.direction, "transition" ),

			// Default options for the changPage calls made after examining the current state of
			// the page and the hash, NOTE that the transition is derived from the previous history
			// entry
			changePageOptions = {
				changeUrl: false,
				fromHashChange: true,
				reverse: data.direction === "back"
			};

		$.extend( changePageOptions, data, {
			transition: transition,
			allowSamePageTransition: this._optionFromHistory( data.direction,
				"allowSamePageTransition" )
		} );

		// TODO move to _handleDestination ?
		// If this isn't the first page, if the current url is a dialog hash
		// key, and the initial destination isn't equal to the current target
		// page, use the special dialog handling
		if ( history.activeIndex > 0 &&
				to.indexOf( $.mobile.dialogHashKey ) > -1 ) {

			to = this._handleDialog( changePageOptions, data );

			if ( to === false ) {
				return;
			}
		}

		this.change( this._handleDestination( to ), changePageOptions );
	},

	_getBase: function() {
		return $.mobile.base;
	},

	_getNs: function() {
		return $.mobile.ns;
	},

	_enhance: function( content, role ) {

		// TODO consider supporting a custom callback, and passing in
		// the settings which includes the role
		return content.page( { role: role } );
	},

	_include: function( page, settings ) {

		// Append to page and enhance
		page.appendTo( this.element );

		// Use the page widget to enhance
		this._enhance( page, settings.role );

		// Remove page on hide
		page.page( "bindRemove" );
	},

	_find: function( absUrl ) {

		// TODO consider supporting a custom callback
		var fileUrl = this._createFileUrl( absUrl ),
			dataUrl = this._createDataUrl( absUrl ),
			page,
			initialContent = this._getInitialContent();

		// Check to see if the page already exists in the DOM.
		// NOTE do _not_ use the :jqmData pseudo selector because parenthesis
		//      are a valid url char and it breaks on the first occurrence
		page = this.element
			.children( "[data-" + this._getNs() +
				"url='" + $.mobile.path.hashToSelector( dataUrl ) + "']" );

		// If we failed to find the page, check to see if the url is a
		// reference to an embedded page. If so, it may have been dynamically
		// injected by a developer, in which case it would be lacking a
		// data-url attribute and in need of enhancement.
		if ( page.length === 0 && dataUrl && !$.mobile.path.isPath( dataUrl ) ) {
			page = this.element.children( $.mobile.path.hashToSelector( "#" + dataUrl ) )
				.attr( "data-" + this._getNs() + "url", dataUrl )
				.jqmData( "url", dataUrl );
		}

		// If we failed to find a page in the DOM, check the URL to see if it
		// refers to the first page in the application. Also check to make sure
		// our cached-first-page is actually in the DOM. Some user deployed
		// apps are pruning the first page from the DOM for various reasons.
		// We check for this case here because we don't want a first-page with
		// an id falling through to the non-existent embedded page error case.
		if ( page.length === 0 &&
				$.mobile.path.isFirstPageUrl( fileUrl ) &&
				initialContent &&
				initialContent.parent().length ) {
			page = $( initialContent );
		}

		return page;
	},

	_getLoader: function() {
		return $.mobile.loading();
	},

	_showLoading: function( delay, theme, msg, textonly ) {

		// This configurable timeout allows cached pages a brief
		// delay to load without showing a message
		if ( this._loadMsg ) {
			return;
		}

		this._loadMsg = setTimeout( $.proxy( function() {
			this._getLoader().loader( "show", theme, msg, textonly );
			this._loadMsg = 0;
		}, this ), delay );
	},

	_hideLoading: function() {

		// Stop message show timer
		clearTimeout( this._loadMsg );
		this._loadMsg = 0;

		// Hide loading message
		this._getLoader().loader( "hide" );
	},

	_showError: function() {

		// Make sure to remove the current loading message
		this._hideLoading();

		// Show the error message
		this._showLoading( 0, $.mobile.pageLoadErrorMessageTheme, $.mobile.pageLoadErrorMessage, true );

		// Hide the error message after a delay
		// TODO configuration
		setTimeout( $.proxy( this, "_hideLoading" ), 1500 );
	},

	_parse: function( html, fileUrl ) {

		// TODO consider allowing customization of this method. It's very JQM specific
		var page,
			all = $( "<div></div>" );

		// Workaround to allow scripts to execute when included in page divs
		all.get( 0 ).innerHTML = html;

		page = all.find( ":jqmData(role='page'), :jqmData(role='dialog')" ).first();

		// If page elem couldn't be found, create one and insert the body element's contents
		if ( !page.length ) {
			page = $( "<div data-" + this._getNs() + "role='page'>" +
				( html.split( /<\/?body[^>]*>/gmi )[ 1 ] || "" ) +
				"</div>" );
		}

		// TODO tagging a page with external to make sure that embedded pages aren't
		// removed by the various page handling code is bad. Having page handling code
		// in many places is bad. Solutions post 1.0
		page.attr( "data-" + this._getNs() + "url", this._createDataUrl( fileUrl ) )
			.attr( "data-" + this._getNs() + "external-page", true );

		return page;
	},

	_setLoadedTitle: function( page, html ) {

		// Page title regexp
		var newPageTitle = html.match( /<title[^>]*>([^<]*)/ ) && RegExp.$1;

		if ( newPageTitle && !page.jqmData( "title" ) ) {
			newPageTitle = $( "<div>" + newPageTitle + "</div>" ).text();
			page.jqmData( "title", newPageTitle );
		}
	},

	_createDataUrl: function( absoluteUrl ) {
		return $.mobile.path.convertUrlToDataUrl( absoluteUrl );
	},

	_createFileUrl: function( absoluteUrl ) {
		return $.mobile.path.getFilePath( absoluteUrl );
	},

	_triggerWithDeprecated: function( name, data, page ) {
		var deprecatedEvent = $.Event( "page" + name ),
			newEvent = $.Event( this.widgetName + name );

		// DEPRECATED
		// Trigger the old deprecated event on the page if it's provided
		( page || this.element ).trigger( deprecatedEvent, data );

		// Use the widget trigger method for the new content* event
		this._trigger( name, newEvent, data );

		return {
			deprecatedEvent: deprecatedEvent,
			event: newEvent
		};
	},

	// TODO it would be nice to split this up more but everything appears to be "one off"
	//      or require ordering such that other bits are sprinkled in between parts that
	//      could be abstracted out as a group
	_loadSuccess: function( absUrl, triggerData, settings, deferred ) {
		var fileUrl = this._createFileUrl( absUrl );

		return $.proxy( function( html, textStatus, xhr ) {

			// Pre-parse html to check for a data-url, use it as the new fileUrl, base path, etc
			var content,

				// TODO handle dialogs again
				pageElemRegex = new RegExp( "(<[^>]+\\bdata-" + this._getNs() + "role=[\"']?page[\"']?[^>]*>)" ),

				dataUrlRegex = new RegExp( "\\bdata-" + this._getNs() + "url=[\"']?([^\"'>]*)[\"']?" );

			// data-url must be provided for the base tag so resource requests can be directed to
			// the correct url. loading into a temprorary element makes these requests immediately
			if ( pageElemRegex.test( html ) &&
					RegExp.$1 &&
					dataUrlRegex.test( RegExp.$1 ) &&
					RegExp.$1 ) {
				fileUrl = $.mobile.path.getFilePath( $( "<div>" + RegExp.$1 + "</div>" ).text() );

				// We specify that, if a data-url attribute is given on the page div, its value
				// must be given non-URL-encoded. However, in this part of the code, fileUrl is
				// assumed to be URL-encoded, so we URL-encode the retrieved value here
				fileUrl = this.window[ 0 ].encodeURIComponent( fileUrl );
			}

			// Don't update the base tag if we are prefetching
			if ( settings.prefetch === undefined ) {
				this._getBase().set( fileUrl );
			}

			content = this._parse( html, fileUrl );

			this._setLoadedTitle( content, html );

			// Add the content reference and xhr to our triggerData.
			triggerData.xhr = xhr;
			triggerData.textStatus = textStatus;

			// DEPRECATED
			triggerData.page = content;

			triggerData.content = content;

			triggerData.toPage = content;

			// If the default behavior is prevented, stop here!
			// Note that it is the responsibility of the listener/handler
			// that called preventDefault(), to resolve/reject the
			// deferred object within the triggerData.
			if ( this._triggerWithDeprecated( "load", triggerData ).event.isDefaultPrevented() ) {
				return;
			}

			this._include( content, settings );

			// Remove loading message.
			if ( settings.showLoadMsg ) {
				this._hideLoading();
			}

			deferred.resolve( absUrl, settings, content );
		}, this );
	},

	_loadDefaults: {
		type: "get",
		data: undefined,

		reload: false,

		// By default we rely on the role defined by the @data-role attribute.
		role: undefined,

		showLoadMsg: false,

		// This delay allows loads that pull from browser cache to
		// occur without showing the loading message.
		loadMsgDelay: 50
	},

	load: function( url, options ) {

		// This function uses deferred notifications to let callers
		// know when the content is done loading, or if an error has occurred.
		var deferred = ( options && options.deferred ) || $.Deferred(),

			// The default load options with overrides specified by the caller.
			settings = $.extend( {}, this._loadDefaults, options ),

			// The DOM element for the content after it has been loaded.
			content = null,

			// The absolute version of the URL passed into the function. This
			// version of the URL may contain dialog/subcontent params in it.
			absUrl = $.mobile.path.makeUrlAbsolute( url, this._findBaseWithDefault() ),
			fileUrl, dataUrl, pblEvent, triggerData;

		// If the caller provided data, and we're using "get" request,
		// append the data to the URL.
		if ( settings.data && settings.type === "get" ) {
			absUrl = $.mobile.path.addSearchParams( absUrl, settings.data );
			settings.data = undefined;
		}

		// If the caller is using a "post" request, reload must be true
		if ( settings.data && settings.type === "post" ) {
			settings.reload = true;
		}

		// The absolute version of the URL minus any dialog/subcontent params.
		// In other words the real URL of the content to be loaded.
		fileUrl = this._createFileUrl( absUrl );

		// The version of the Url actually stored in the data-url attribute of the content. For
		// embedded content, it is just the id of the page. For content within the same domain as
		// the document base, it is the site relative path. For cross-domain content (PhoneGap
		// only) the entire absolute Url is used to load the content.
		dataUrl = this._createDataUrl( absUrl );

		content = this._find( absUrl );

		// If it isn't a reference to the first content and refers to missing embedded content
		// reject the deferred and return
		if ( content.length === 0 &&
				$.mobile.path.isEmbeddedPage( fileUrl ) &&
				!$.mobile.path.isFirstPageUrl( fileUrl ) ) {
			deferred.reject( absUrl, settings );
			return deferred.promise();
		}

		// Reset base to the default document base
		// TODO figure out why we doe this
		this._getBase().reset();

		// If the content we are interested in is already in the DOM, and the caller did not
		// indicate that we should force a reload of the file, we are done. Resolve the deferrred
		// so that users can bind to .done on the promise
		if ( content.length && !settings.reload ) {
			this._enhance( content, settings.role );
			deferred.resolve( absUrl, settings, content );

			// If we are reloading the content make sure we update the base if its not a prefetch
			if ( !settings.prefetch ) {
				this._getBase().set( url );
			}

			return deferred.promise();
		}

		triggerData = {
			url: url,
			absUrl: absUrl,
			toPage: url,
			prevPage: options ? options.fromPage : undefined,
			dataUrl: dataUrl,
			deferred: deferred,
			options: settings
		};

		// Let listeners know we're about to load content.
		pblEvent = this._triggerWithDeprecated( "beforeload", triggerData );

		// If the default behavior is prevented, stop here!
		if ( pblEvent.deprecatedEvent.isDefaultPrevented() ||
				pblEvent.event.isDefaultPrevented() ) {
			return deferred.promise();
		}

		if ( settings.showLoadMsg ) {
			this._showLoading( settings.loadMsgDelay );
		}

		// Reset base to the default document base. Only reset if we are not prefetching.
		if ( settings.prefetch === undefined ) {
			this._getBase().reset();
		}

		if ( !( $.mobile.allowCrossDomainPages ||
				$.mobile.path.isSameDomain( $.mobile.path.documentUrl, absUrl ) ) ) {
			deferred.reject( absUrl, settings );
			return deferred.promise();
		}

		// Load the new content.
		$.ajax( {
			url: fileUrl,
			type: settings.type,
			data: settings.data,
			contentType: settings.contentType,
			dataType: "html",
			success: this._loadSuccess( absUrl, triggerData, settings, deferred ),
			error: this._loadError( absUrl, triggerData, settings, deferred )
		} );

		return deferred.promise();
	},

	_loadError: function( absUrl, triggerData, settings, deferred ) {
		return $.proxy( function( xhr, textStatus, errorThrown ) {

			// Set base back to current path
			this._getBase().set( $.mobile.path.get() );

			// Add error info to our triggerData.
			triggerData.xhr = xhr;
			triggerData.textStatus = textStatus;
			triggerData.errorThrown = errorThrown;

			// Clean up internal pending operations like the loader and the transition lock
			this._hideLoading();
			this._releaseTransitionLock();

			// Let listeners know the page load failed.
			var plfEvent = this._triggerWithDeprecated( "loadfailed", triggerData );

			// If the default behavior is prevented, stop here!
			// Note that it is the responsibility of the listener/handler
			// that called preventDefault(), to resolve/reject the
			// deferred object within the triggerData.
			if ( plfEvent.deprecatedEvent.isDefaultPrevented() ||
					plfEvent.event.isDefaultPrevented() ) {
				return;
			}

			// Remove loading message.
			if ( settings.showLoadMsg ) {
				this._showError();
			}

			deferred.reject( absUrl, settings );
		}, this );
	},

	_getTransitionHandler: function( transition ) {
		transition = $.mobile._maybeDegradeTransition( transition );

		// Find the transition handler for the specified transition. If there isn't one in our
		// transitionHandlers dictionary, use the default one. call the handler immediately to
		// kick off the transition.
		return $.mobile.transitionHandlers[ transition ] || $.mobile.defaultTransitionHandler;
	},

	// TODO move into transition handlers?
	_triggerCssTransitionEvents: function( to, from, prefix ) {
		var samePage = false;

		prefix = prefix || "";

		// TODO decide if these events should in fact be triggered on the container
		if ( from ) {

			// Check if this is a same page transition and tell the handler in page
			if ( to[ 0 ] === from[ 0 ] ) {
				samePage = true;
			}

			// Trigger before show/hide events
			// TODO deprecate nextPage in favor of next
			this._triggerWithDeprecated( prefix + "hide", {

				// Deprecated in 1.4 remove in 1.5
				nextPage: to,
				toPage: to,
				prevPage: from,
				samePage: samePage
			}, from );
		}

		// TODO deprecate prevPage in favor of previous
		this._triggerWithDeprecated( prefix + "show", {
			prevPage: from || $( "" ),
			toPage: to
		}, to );
	},

	_performTransition: function( transition, reverse, to, from ) {
		var transitionDeferred = $.Deferred();

		if ( from ) {
			from.removeClass( "ui-page-active" );
		}
		if ( to ) {
			to.addClass( "ui-page-active" );
		}
		this._delay( function() {
			transitionDeferred.resolve( transition, reverse, to, from, false );
		}, 0 );

		return transitionDeferred.promise();
	},

	// TODO make private once change has been defined in the widget
	_cssTransition: function( to, from, options ) {
		var transition = options.transition,
			reverse = options.reverse,
			deferred = options.deferred,
			promise;

		this._triggerCssTransitionEvents( to, from, "before" );

		// TODO put this in a binding to events *outside* the widget
		this._hideLoading();

		promise = this._performTransition( transition, reverse, to, from );

		promise.done( $.proxy( function() {
			this._triggerCssTransitionEvents( to, from );
		}, this ) );

		// TODO temporary accomodation of argument deferred
		promise.done( function() {
			deferred.resolve.apply( deferred, arguments );
		} );
	},

	_releaseTransitionLock: function() {

		// Release transition lock so navigation is free again
		isPageTransitioning = false;
		if ( pageTransitionQueue.length > 0 ) {
			this.change.apply( this, pageTransitionQueue.pop() );
		}
	},

	_removeActiveLinkClass: function( force ) {

		// Clear out the active button state
		$.mobile.removeActiveLinkClass( force );
	},

	_loadUrl: function( to, triggerData, settings ) {

		// Preserve the original target as the dataUrl value will be simplified eg, removing
		// ui-state, and removing query params from the hash this is so that users who want to use
		// query params have access to them in the event bindings for the page life cycle
		// See issue #5085
		settings.target = to;
		settings.deferred = $.Deferred();

		this.load( to, settings );

		settings.deferred.done( $.proxy( function( url, options, content ) {
			isPageTransitioning = false;

			// Store the original absolute url so that it can be provided to events in the
			// triggerData of the subsequent change() call
			options.absUrl = triggerData.absUrl;

			this.transition( content, triggerData, options );
		}, this ) );

		settings.deferred.fail( $.proxy( function( /* url, options */ ) {
			this._removeActiveLinkClass( true );
			this._releaseTransitionLock();
			this._triggerWithDeprecated( "changefailed", triggerData );
		}, this ) );

		return settings.deferred.promise();
	},

	_triggerPageBeforeChange: function( to, triggerData, settings ) {
		var returnEvents;

		triggerData.prevPage = this.activePage;
		$.extend( triggerData, {
			toPage: to,
			options: settings
		} );

		// NOTE: preserve the original target as the dataUrl value will be simplified eg, removing
		// ui-state, and removing query params from the hash this is so that users who want to use
		// query params have access to them in the event bindings for the page life cycle
		// See issue #5085
		if ( $.type( to ) === "string" ) {

			// If the toPage is a string simply convert it
			triggerData.absUrl = $.mobile.path.makeUrlAbsolute( to, this._findBaseWithDefault() );
		} else {

			// If the toPage is a jQuery object grab the absolute url stored in the load()
			// callback where it exists
			triggerData.absUrl = settings.absUrl;
		}

		// Let listeners know we're about to change the current page.
		returnEvents = this._triggerWithDeprecated( "beforechange", triggerData );

		// If the default behavior is prevented, stop here!
		if ( returnEvents.event.isDefaultPrevented() ||
				returnEvents.deprecatedEvent.isDefaultPrevented() ) {
			return false;
		}

		return true;
	},

	change: function( to, options ) {

		// If we are in the midst of a transition, queue the current request. We'll call
		// change() once we're done with the current transition to service the request.
		if ( isPageTransitioning ) {
			pageTransitionQueue.unshift( arguments );
			return;
		}

		var settings = $.extend( {}, this.options.changeOptions, options ),
			triggerData = {};

		// Make sure we have a fromPage.
		settings.fromPage = settings.fromPage || this.activePage;

		// If the page beforechange default is prevented return early
		if ( !this._triggerPageBeforeChange( to, triggerData, settings ) ) {
			return;
		}

		// We allow "pagebeforechange" observers to modify the to in the trigger data to allow for
		// redirects. Make sure our to is updated. We also need to re-evaluate whether it is a
		// string, because an object can also be replaced by a string
		to = triggerData.toPage;

		// If the caller passed us a url, call load() to make sure it is loaded into the DOM.
		// We'll listen to the promise object it returns so we know when it is done loading or if
		// an error ocurred.
		if ( $.type( to ) === "string" ) {

			// Set the isPageTransitioning flag to prevent any requests from entering this method
			// while we are in the midst of loading a page or transitioning.
			isPageTransitioning = true;

			return this._loadUrl( to, triggerData, settings );
		} else {
			return this.transition( to, triggerData, settings );
		}
	},

	transition: function( toPage, triggerData, settings ) {
		var fromPage, url, pageUrl, fileUrl, active, activeIsInitialPage, historyDir, pageTitle,
			isDialog, alreadyThere, newPageTitle, params, cssTransitionDeferred, beforeTransition;

		// If we are in the midst of a transition, queue the current request. We'll call
		// change() once we're done with the current transition to service the request.
		if ( isPageTransitioning ) {

			// Make sure to only queue the to and settings values so the arguments work with a call
			// to the change method
			pageTransitionQueue.unshift( [ toPage, settings ] );
			return;
		}

		// DEPRECATED - this call only, in favor of the before transition if the page beforechange
		// default is prevented return early
		if ( !this._triggerPageBeforeChange( toPage, triggerData, settings ) ) {
			return;
		}

		triggerData.prevPage = settings.fromPage;

		// If the (content|page)beforetransition default is prevented return early. Note, we have
		// to check for both the deprecated and new events
		beforeTransition = this._triggerWithDeprecated( "beforetransition", triggerData );
		if ( beforeTransition.deprecatedEvent.isDefaultPrevented() ||
				beforeTransition.event.isDefaultPrevented() ) {
			return;
		}

		// Set the isPageTransitioning flag to prevent any requests from entering this method while
		// we are in the midst of loading a page or transitioning.
		isPageTransitioning = true;

		// If we are going to the first-page of the application, we need to make sure
		// settings.dataUrl is set to the application document url. This allows us to avoid
		// generating a document url with an id hash in the case where the first-page of the
		// document has an id attribute specified.
		if ( toPage[ 0 ] === $.mobile.firstPage[ 0 ] && !settings.dataUrl ) {
			settings.dataUrl = $.mobile.path.documentUrl.hrefNoHash;
		}

		// The caller passed us a real page DOM element. Update our internal state and then trigger
		// a transition to the page.
		fromPage = settings.fromPage;
		url = ( settings.dataUrl && $.mobile.path.convertUrlToDataUrl( settings.dataUrl ) ) ||
			toPage.jqmData( "url" );

		// The pageUrl var is usually the same as url, except when url is obscured as a dialog url.
		// pageUrl always contains the file path
		pageUrl = url;
		fileUrl = $.mobile.path.getFilePath( url );
		active = $.mobile.navigate.history.getActive();
		activeIsInitialPage = $.mobile.navigate.history.activeIndex === 0;
		historyDir = 0;
		pageTitle = document.title;
		isDialog = ( settings.role === "dialog" ||
			toPage.jqmData( "role" ) === "dialog" ) &&
			toPage.jqmData( "dialog" ) !== true;

		// By default, we prevent change() requests when the fromPage and toPage are the same
		// element, but folks that generate content manually/dynamically and reuse pages want to be
		// able to transition to the same page. To allow this, they will need to change the default
		// value of allowSamePageTransition to true, *OR*, pass it in as an option when they
		// manually call change(). It should be noted that our default transition animations
		// assume that the formPage and toPage are different elements, so they may behave
		// unexpectedly. It is up to the developer that turns on the allowSamePageTransitiona
		// option to either turn off transition animations, or make sure that an appropriate
		// animation transition is used.
		if ( fromPage && fromPage[ 0 ] === toPage[ 0 ] &&
				!settings.allowSamePageTransition ) {

			isPageTransitioning = false;
			this._triggerWithDeprecated( "transition", triggerData );
			this._triggerWithDeprecated( "change", triggerData );

			// Even if there is no page change to be done, we should keep the urlHistory in sync
			// with the hash changes
			if ( settings.fromHashChange ) {
				$.mobile.navigate.history.direct( { url: url } );
			}

			return;
		}

		// We need to make sure the page we are given has already been enhanced.
		toPage.page( { role: settings.role } );

		// If the change() request was sent from a hashChange event, check to see if the page is
		// already within the urlHistory stack. If so, we'll assume the user hit the forward/back
		// button and will try to match the transition accordingly.
		if ( settings.fromHashChange ) {
			historyDir = settings.direction === "back" ? -1 : 1;
		}

		// We blur the focused element to cause the virtual keyboard to disappear
		$.ui.safeBlur( $.ui.safeActiveElement( this.document[ 0 ] ) );

		// Record whether we are at a place in history where a dialog used to be - if so, do not
		// add a new history entry and do not change the hash either
		alreadyThere = false;

		// If we're displaying the page as a dialog, we don't want the url for the dialog content
		// to be used in the hash. Instead, we want to append the dialogHashKey to the url of the
		// current page.
		if ( isDialog && active ) {

			// On the initial page load active.url is undefined and in that case should be an empty
			// string. Moving the undefined -> empty string back into urlHistory.addNew seemed
			// imprudent given undefined better represents the url state

			// If we are at a place in history that once belonged to a dialog, reuse this state
			// without adding to urlHistory and without modifying the hash. However, if a dialog is
			// already displayed at this point, and we're about to display another dialog, then we
			// must add another hash and history entry on top so that one may navigate back to the
			// original dialog.
			if ( active.url &&
					active.url.indexOf( $.mobile.dialogHashKey ) > -1 &&
					this.activePage &&
					!this.activePage.hasClass( "ui-page-dialog" ) &&
					$.mobile.navigate.history.activeIndex > 0 ) {

				settings.changeUrl = false;
				alreadyThere = true;
			}

			// Normally, we tack on a dialog hash key, but if this is the location of a stale
			// dialog, we reuse the URL from the entry
			url = ( active.url || "" );

			// Account for absolute urls instead of just relative urls use as hashes
			if ( !alreadyThere && url.indexOf( "#" ) > -1 ) {
				url += $.mobile.dialogHashKey;
			} else {
				url += "#" + $.mobile.dialogHashKey;
			}
		}

		// If title element wasn't found, try the page div data attr too.
		// If this is a deep-link or a reload ( active === undefined ) then just use pageTitle
		newPageTitle = ( !active ) ? pageTitle : toPage.jqmData( "title" ) ||
		toPage.children( ":jqmData(type='header')" ).find( ".ui-toolbar-title" ).text();
		if ( !!newPageTitle && pageTitle === document.title ) {
			pageTitle = newPageTitle;
		}
		if ( !toPage.jqmData( "title" ) ) {
			toPage.jqmData( "title", pageTitle );
		}

		// Make sure we have a transition defined.
		settings.transition = settings.transition ||
			( ( historyDir && !activeIsInitialPage ) ? active.transition : undefined ) ||
			( isDialog ? $.mobile.defaultDialogTransition : $.mobile.defaultPageTransition );

		// Add page to history stack if it's not back or forward
		if ( !historyDir && alreadyThere ) {
			$.mobile.navigate.history.getActive().pageUrl = pageUrl;
		}

		// Set the location hash.
		if ( url && !settings.fromHashChange ) {

			// Rebuilding the hash here since we loose it earlier on
			// TODO preserve the originally passed in path
			if ( !$.mobile.path.isPath( url ) && url.indexOf( "#" ) < 0 ) {
				url = "#" + url;
			}

			// TODO the property names here are just silly
			params = {
				allowSamePageTransition: settings.allowSamePageTransition,
				transition: settings.transition,
				title: pageTitle,
				pageUrl: pageUrl,
				role: settings.role
			};

			if ( settings.changeUrl !== false && $.mobile.hashListeningEnabled ) {
				$.mobile.navigate( this.window[ 0 ].encodeURI( url ), params, true );
			} else if ( toPage[ 0 ] !== $.mobile.firstPage[ 0 ] ) {
				$.mobile.navigate.history.add( url, params );
			}
		}

		// Set page title
		document.title = pageTitle;

		// Set "toPage" as activePage deprecated in 1.4 remove in 1.5
		$.mobile.activePage = toPage;

		// New way to handle activePage
		this.activePage = toPage;

		// If we're navigating back in the URL history, set reverse accordingly.
		settings.reverse = settings.reverse || historyDir < 0;

		cssTransitionDeferred = $.Deferred();

		this._cssTransition( toPage, fromPage, {
			transition: settings.transition,
			reverse: settings.reverse,
			deferred: cssTransitionDeferred
		} );

		cssTransitionDeferred.done( $.proxy( function( name, reverse, $to, $from, alreadyFocused ) {
			$.mobile.removeActiveLinkClass();

			// If there's a duplicateCachedPage, remove it from the DOM now that it's hidden
			if ( settings.duplicateCachedPage ) {
				settings.duplicateCachedPage.remove();
			}

			// Despite visibility: hidden addresses issue #2965
			// https://github.com/jquery/jquery-mobile/issues/2965
			if ( !alreadyFocused ) {
				$.mobile.focusPage( toPage );
			}

			this._releaseTransitionLock();
			this._triggerWithDeprecated( "transition", triggerData );
			this._triggerWithDeprecated( "change", triggerData );
		}, this ) );

		return cssTransitionDeferred.promise();
	},

	// Determine the current base url
	_findBaseWithDefault: function() {
		var closestBase = ( this.activePage &&
			$.mobile.getClosestBaseUrl( this.activePage ) );
		return closestBase || $.mobile.path.documentBase.hrefNoHash;
	},

	_themeElements: function() {
		return [
			{
				element: this.element,
				prefix: "ui-overlay-"
			}
		];
	},

	_destroy: function() {
		var myIndex;

		if ( $.mobile.pagecontainers ) {
			myIndex = $.inArray( this.element, $.mobile.pagecontainers );
			if ( myIndex >= 0 ) {
				$.mobile.pagecontainers.splice( myIndex, 1 );
				if ( $.mobile.pagecontainers.length ) {
					$.mobile.pagecontainers.active = $.mobile.pagecontainers[ 0 ];
				} else {
					$.mobile.pagecontainers.active = undefined;
				}
			}
		}

		this._super();
	}
} );

// The following handlers should be bound after mobileinit has been triggered.
// The following deferred is resolved in the init file.
$.mobile.navreadyDeferred = $.Deferred();

$.widget( "mobile.pagecontainer", $.mobile.pagecontainer, $.mobile.widget.theme );

return $.mobile.pagecontainer;

} );

/*!
 * jQuery Mobile animationComplete @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Animation Complete
//>>group: Core
//>>description: A handler for css transition & animation end events to ensure callback is executed

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'animationComplete',[ "jquery" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var props = {
		"animation": {},
		"transition": {}
	},
	testElement = document.createElement( "a" ),
	vendorPrefixes = [ "", "webkit-", "moz-", "o-" ],
	callbackLookupTable = {};

$.each( [ "animation", "transition" ], function( i, test ) {

	// Get correct name for test
	var testName = ( i === 0 ) ? test + "-" + "name" : test;

	$.each( vendorPrefixes, function( j, prefix ) {
		if ( testElement.style[ $.camelCase( prefix + testName ) ] !== undefined ) {
			props[ test ][ "prefix" ] = prefix;
			return false;
		}
	} );

	// Set event and duration names for later use
	props[ test ][ "duration" ] =
		$.camelCase( props[ test ][ "prefix" ] + test + "-" + "duration" );
	props[ test ][ "event" ] =
		$.camelCase( props[ test ][ "prefix" ] + test + "-" + "end" );

	// All lower case if not a vendor prop
	if ( props[ test ][ "prefix" ] === "" ) {
		props[ test ][ "event" ] = props[ test ][ "event" ].toLowerCase();
	}
} );

// If a valid prefix was found then the it is supported by the browser
$.support.cssTransitions = ( props[ "transition" ][ "prefix" ] !== undefined );
$.support.cssAnimations = ( props[ "animation" ][ "prefix" ] !== undefined );

// Remove the testElement
$( testElement ).remove();

// Animation complete callback
$.fn.extend( {
	animationComplete: function( callback, type, fallbackTime ) {
		var timer, duration,
			that = this,
			eventBinding = function() {

				// Clear the timer so we don't call callback twice
				clearTimeout( timer );
				callback.apply( this, arguments );
			},
			animationType = ( !type || type === "animation" ) ? "animation" : "transition";

		if ( !this.length ) {
			return this;
		}

		// Make sure selected type is supported by browser
		if ( ( $.support.cssTransitions && animationType === "transition" ) ||
				( $.support.cssAnimations && animationType === "animation" ) ) {

			// If a fallback time was not passed set one
			if ( fallbackTime === undefined ) {

				// Make sure the was not bound to document before checking .css
				if ( this.context !== document ) {

					// Parse the durration since its in second multiple by 1000 for milliseconds
					// Multiply by 3 to make sure we give the animation plenty of time.
					duration = parseFloat(
							this.css( props[ animationType ].duration )
						) * 3000;
				}

				// If we could not read a duration use the default
				if ( duration === 0 || duration === undefined || isNaN( duration ) ) {
					duration = $.fn.animationComplete.defaultDuration;
				}
			}

			// Sets up the fallback if event never comes
			timer = setTimeout( function() {
				that
					.off( props[ animationType ].event, eventBinding )
					.each( function() {
						callback.apply( this );
					} );
			}, duration );

			// Update lookupTable
			callbackLookupTable[ callback ] = {
				event: props[ animationType ].event,
				binding: eventBinding
			};


			// Bind the event
			return this.one( props[ animationType ].event, eventBinding );
		} else {

			// CSS animation / transitions not supported
			// Defer execution for consistency between webkit/non webkit
			setTimeout( function() {
				that.each( function() {
					callback.apply( this );
				} );
			}, 0 );
			return this;
		}
	},

	removeAnimationComplete: function( callback ) {
		var callbackInfoObject = callbackLookupTable[ callback ];

		return callbackInfoObject ?
		this.off( callbackInfoObject.event, callbackInfoObject.binding ) : this;
	}
} );

// Allow default callback to be configured on mobileInit
$.fn.animationComplete.defaultDuration = 1000;

return $;
} );

/*!
 * jQuery Mobile Transition @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Transition Core
//>>group: Transitions
//>>description: Animated page change base constructor and logic
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/
//>>css.structure: ../css/structure/jquery.mobile.transition.css
//>>css.structure: ../css/structure/jquery.mobile.transition.fade.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/transition',[
			"jquery",
			"../core",

			// TODO event.special.scrollstart
			"../events/scroll",
			"../animationComplete" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

// TODO remove direct references to $.mobile and properties, we should
//      favor injection with params to the constructor
$.mobile.Transition = function() {
	this.init.apply( this, arguments );
};

$.extend( $.mobile.Transition.prototype, {
	toPreClass: " ui-page-pre-in",

	init: function( name, reverse, $to, $from ) {
		$.extend( this, {
			name: name,
			reverse: reverse,
			$to: $to,
			$from: $from,
			deferred: new $.Deferred()
		} );
	},

	cleanFrom: function() {
		this.$from
			.removeClass( "ui-page-active out in reverse " + this.name )
			.height( "" );
	},

	// NOTE overridden by child object prototypes, noop'd here as defaults
	beforeDoneIn: function() {},
	beforeDoneOut: function() {},
	beforeStartOut: function() {},

	doneIn: function() {
		this.beforeDoneIn();

		this.$to.removeClass( "out in reverse " + this.name ).height( "" );

		this.toggleViewportClass();

		// In some browsers (iOS5), 3D transitions block the ability to scroll to the desired location during transition
		// This ensures we jump to that spot after the fact, if we aren't there already.
		if ( $.mobile.window.scrollTop() !== this.toScroll ) {
			this.scrollPage();
		}
		if ( !this.sequential ) {
			this.$to.addClass( "ui-page-active" );
		}
		this.deferred.resolve( this.name, this.reverse, this.$to, this.$from, true );
	},

	doneOut: function( screenHeight, reverseClass, none, preventFocus ) {
		this.beforeDoneOut();
		this.startIn( screenHeight, reverseClass, none, preventFocus );
	},

	hideIn: function( callback ) {
		// Prevent flickering in phonegap container: see comments at #4024 regarding iOS
		this.$to.css( "z-index", -10 );
		callback.call( this );
		this.$to.css( "z-index", "" );
	},

	scrollPage: function() {
		// By using scrollTo instead of silentScroll, we can keep things better in order
		// Just to be precautios, disable scrollstart listening like silentScroll would
		$.event.special.scrollstart.enabled = false;
		//if we are hiding the url bar or the page was previously scrolled scroll to hide or return to position
		if ( $.mobile.hideUrlBar || this.toScroll !== $.mobile.defaultHomeScroll ) {
			window.scrollTo( 0, this.toScroll );
		}

		// reenable scrollstart listening like silentScroll would
		setTimeout( function() {
			$.event.special.scrollstart.enabled = true;
		}, 150 );
	},

	startIn: function( screenHeight, reverseClass, none, preventFocus ) {
		this.hideIn( function() {
			this.$to.addClass( "ui-page-active" + this.toPreClass );

			// Send focus to page as it is now display: block
			if ( !preventFocus ) {
				$.mobile.focusPage( this.$to );
			}

			// Set to page height
			this.$to.height( screenHeight + this.toScroll );

			if ( !none ) {
				this.scrollPage();
			}
		} );

		this.$to
			.removeClass( this.toPreClass )
			.addClass( this.name + " in " + reverseClass );

		if ( !none ) {
			this.$to.animationComplete( $.proxy( function() {
				this.doneIn();
			}, this ) );
		} else {
			this.doneIn();
		}

	},

	startOut: function( screenHeight, reverseClass, none ) {
		this.beforeStartOut( screenHeight, reverseClass, none );

		// Set the from page's height and start it transitioning out
		// Note: setting an explicit height helps eliminate tiling in the transitions
		this.$from
			.height( screenHeight + $.mobile.window.scrollTop() )
			.addClass( this.name + " out" + reverseClass );
	},

	toggleViewportClass: function() {
		this.$to.closest( ".ui-pagecontainer" ).toggleClass( "ui-mobile-viewport-transitioning viewport-" + this.name );
	},

	transition: function( toScroll ) {
		// NOTE many of these could be calculated/recorded in the constructor, it's my
		//      opinion that binding them as late as possible has value with regards to
		//      better transitions with fewer bugs. Ie, it's not guaranteed that the
		//      object will be created and transition will be run immediately after as
		//      it is today. So we wait until transition is invoked to gather the following
		var none,
			reverseClass = this.reverse ? " reverse" : "",
			screenHeight = $( window ).height(),
			maxTransitionOverride = $.mobile.maxTransitionWidth !== false &&
				$.mobile.window.width() > $.mobile.maxTransitionWidth;

		this.toScroll = ( toScroll ? toScroll : 0 );

		none = !$.support.cssTransitions || !$.support.cssAnimations ||
			maxTransitionOverride || !this.name || this.name === "none" ||
			Math.max( $.mobile.window.scrollTop(), this.toScroll ) >
			$.mobile.getMaxScrollForTransition();

		this.toggleViewportClass();

		if ( this.$from && !none ) {
			this.startOut( screenHeight, reverseClass, none );
		} else {
			this.doneOut( screenHeight, reverseClass, none, true );
		}

		return this.deferred.promise();
	}
} );

return $.mobile.Transition;
} );

/*!
 * jQuery Mobile Serial Transition @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Transition Serial
//>>group: Transitions
//>>description: Animated page change with serial transition style application
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/serial',[
			"jquery",
			"../animationComplete",
			"./transition" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.SerialTransition = function() {
	this.init.apply( this, arguments );
};

$.extend( $.mobile.SerialTransition.prototype, $.mobile.Transition.prototype, {
	sequential: true,

	beforeDoneOut: function() {
		if ( this.$from ) {
			this.cleanFrom();
		}
	},

	beforeStartOut: function( screenHeight, reverseClass, none ) {
		this.$from.animationComplete( $.proxy( function() {
			this.doneOut( screenHeight, reverseClass, none );
		}, this ) );
	}
} );

return $.mobile.SerialTransition;
} );

/*!
 * jQuery Mobile Concurrent Transition @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Transition Concurrent
//>>group: Transitions
//>>description: Animated page change with concurrent transition style application
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/concurrent',[
			"jquery",
			"./transition" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.ConcurrentTransition = function() {
	this.init.apply( this, arguments );
};

$.extend( $.mobile.ConcurrentTransition.prototype, $.mobile.Transition.prototype, {
	sequential: false,

	beforeDoneIn: function() {
		if ( this.$from ) {
			this.cleanFrom();
		}
	},

	beforeStartOut: function( screenHeight, reverseClass, none ) {
		this.doneOut( screenHeight, reverseClass, none );
	}
} );

return $.mobile.ConcurrentTransition;
} );

/*!
 * jQuery Mobile Transition Handlers @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Transition Handlers
//>>group: Transitions
//>>description: Animated page change handlers for integrating with Navigation
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/handlers',[
			"jquery",
			"../core",
			"./serial",
			"./concurrent" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

// generate the handlers from the above
var defaultGetMaxScrollForTransition = function() {
	return $( window ).height() * 3;
};

//transition handler dictionary for 3rd party transitions
$.mobile.transitionHandlers = {
	"sequential": $.mobile.SerialTransition,
	"simultaneous": $.mobile.ConcurrentTransition
};

// Make our transition handler the public default.
$.mobile.defaultTransitionHandler = $.mobile.transitionHandlers.sequential;

$.mobile.transitionFallbacks = {};

// If transition is defined, check if css 3D transforms are supported, and if not, if a fallback is specified
$.mobile._maybeDegradeTransition = function( transition ) {
	if ( transition && !$.support.cssTransform3d && $.mobile.transitionFallbacks[ transition ] ) {
		transition = $.mobile.transitionFallbacks[ transition ];
	}

	return transition;
};

// Set the getMaxScrollForTransition to default if no implementation was set by user
$.mobile.getMaxScrollForTransition = $.mobile.getMaxScrollForTransition || defaultGetMaxScrollForTransition;

return $.mobile.transitionHandlers;
} );

/*!
 * jQuery Mobile Page Container @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Content Management
//>>group: Navigation
//>>description: Widget to create page container which manages pages and transitions
//>>docs: http://api.jquerymobile.com/pagecontainer/
//>>demos: http://demos.jquerymobile.com/@VERSION/navigation/
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/pagecontainer.transitions',[
			"jquery",
			"./pagecontainer",

			// For $.mobile.navigate.history
			"../navigation/method",
			"../transitions/handlers" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {
return $.widget( "mobile.pagecontainer", $.mobile.pagecontainer, {
	_getTransitionHandler: function( transition ) {
		transition = $.mobile._maybeDegradeTransition( transition );

		//find the transition handler for the specified transition. If there
		//isn't one in our transitionHandlers dictionary, use the default one.
		//call the handler immediately to kick off the transition.
		return $.mobile.transitionHandlers[ transition ] || $.mobile.defaultTransitionHandler;
	},

	_performTransition: function( transition, reverse, to, from ) {
		var TransitionHandler = this._getTransitionHandler( transition );

		return ( new TransitionHandler( transition, reverse, to, from ) ).transition(
			$.mobile.navigate.history.getActive().lastScroll || $.mobile.defaultHomeScroll );
	}
} );
} );

/*!
 * jQuery Mobile Flip Transition Fallback @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Flip Transition
//>>group: Transitions
//>>description: Flip transition fallback definition for non-3D supporting browsers
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/
//>>css.structure: ../css/structure/jquery.mobile.transition.flip.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/visuals/flip',[
			"jquery",
			"../handlers" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.transitionFallbacks.flip = "fade";

} );

/*!
 * jQuery Mobile Flow Transition Fallback @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Flow Transition
//>>group: Transitions
//>>description: Flow transition fallback definition for non-3D supporting browsers
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/
//>>css.structure: ../css/structure/jquery.mobile.transition.flow.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/visuals/flow',[
			"jquery",
			"../handlers" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.transitionFallbacks.flow = "fade";

} );

/*!
 * jQuery Mobile Pop Transition Fallback @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Pop Transition
//>>group: Transitions
//>>description: Pop transition fallback definition for non-3D supporting browsers
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/
//>>css.structure: ../css/structure/jquery.mobile.transition.pop.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/visuals/pop',[
			"jquery",
			"../handlers" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.transitionFallbacks.pop = "fade";

} );

/*!
 * jQuery Mobile Slide Transition Fallback @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Slide Transition
//>>group: Transitions
//>>description: Slide transition fallback definition for non-3D supporting browsers
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/
//>>css.structure: ../css/structure/jquery.mobile.transition.slide.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/visuals/slide',[
			"jquery",
			"../handlers" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

// Use the simultaneous transitions handler for slide transitions
$.mobile.transitionHandlers.slide = $.mobile.transitionHandlers.simultaneous;

// Set the slide transitions's fallback to "fade"
$.mobile.transitionFallbacks.slide = "fade";

} );

/*!
 * jQuery Mobile Slidedown Transition Fallback @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Slidedown Transition
//>>group: Transitions
//>>description: Slidedown transition fallback definition for non-3D supporting browsers
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/
//>>css.structure: ../css/structure/jquery.mobile.transition.slidedown.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/visuals/slidedown',[
			"jquery",
			"../handlers" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.transitionFallbacks.slidedown = "fade";

} );

/*!
 * jQuery Mobile Slidefade Transition Fallback @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Slidefade Transition
//>>group: Transitions
//>>description: Slideback transition fallback definition for non-3D supporting browsers
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/
//>>css.structure: ../css/structure/jquery.mobile.transition.slidefade.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/visuals/slidefade',[
			"jquery",
			"../handlers" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

// Set the slide transitions's fallback to "fade"
$.mobile.transitionFallbacks.slidefade = "fade";

} );

/*!
 * jQuery Mobile Slideup Transition Fallback @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Slideup Transition
//>>group: Transitions
//>>description: Slidep transition fallback definition for non-3D supporting browsers
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/
//>>css.structure: ../css/structure/jquery.mobile.transition.slideup.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/visuals/slideup',[
			"jquery",
			"../handlers" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.transitionFallbacks.slideup = "fade";

} );

/*!
 * jQuery Mobile Turn Transition Fallback @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Turn Transition
//>>group: Transitions
//>>description: Turn transition fallback definition for non-3D supporting browsers
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/
//>>css.structure: ../css/structure/jquery.mobile.transition.turn.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/visuals/turn',[
			"jquery",
			"../handlers" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.transitionFallbacks.turn = "fade";

} );

/*!
 * jQuery Mobile Transition Visuals @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: All Transitions
//>>group: Transitions
//>>description: All the stock transitions and associated CSS
//>>demos: http://demos.jquerymobile.com/@VERSION/transitions/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'transitions/visuals',[
			"./visuals/flip",
			"./visuals/flow",
			"./visuals/pop",
			"./visuals/slide",
			"./visuals/slidedown",
			"./visuals/slidefade",
			"./visuals/slideup",
			"./visuals/turn" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function() {} );

/*!
 * jQuery Mobile Navigation @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Content Management
//>>group: Navigation
//>>description: Applies the AJAX navigation system to links and forms to enable page transitions
//>>demos: http://demos.jquerymobile.com/@VERSION/navigation/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'navigation',[
			"jquery",
			"./core",
			"./navigation/path",
			"./events/navigate",
			"./navigation/history",
			"./navigation/navigator",
			"./navigation/method",
			"./support",
			"./animationComplete",
			"./widgets/pagecontainer",
			"./widgets/page",
			"./transitions/handlers" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

// resolved on domready
var domreadyDeferred = $.Deferred(),

	// resolved and nulled on window.load()
	loadDeferred = $.Deferred(),

	// function that resolves the above deferred
	pageIsFullyLoaded = function() {

		// Resolve and null the deferred
		loadDeferred.resolve();
		loadDeferred = null;
	},

	path = $.mobile.path,
	documentUrl = path.documentUrl,

	// used to track last vclicked element to make sure its value is added to form data
	$lastVClicked = null;

/* Event Bindings - hashchange, submit, and click */
function findClosestLink( ele ) {
	while ( ele ) {
		// Look for the closest element with a nodeName of "a".
		// Note that we are checking if we have a valid nodeName
		// before attempting to access it. This is because the
		// node we get called with could have originated from within
		// an embedded SVG document where some symbol instance elements
		// don't have nodeName defined on them, or strings are of type
		// SVGAnimatedString.
		if ( ( typeof ele.nodeName === "string" ) && ele.nodeName.toLowerCase() === "a" ) {
			break;
		}
		ele = ele.parentNode;
	}
	return ele;
}


/* internal utility functions */

// NOTE Issue #4950 Android phonegap doesn't navigate back properly
//      when a full page refresh has taken place. It appears that hashchange
//      and replacestate history alterations work fine but we need to support
//      both forms of history traversal in our code that uses backward history
//      movement
$.mobile.back = function() {
	var nav = window.navigator;

	// if the setting is on and the navigator object is
	// available use the phonegap navigation capability
	if ( this.phonegapNavigationEnabled &&
			nav &&
			nav.app &&
			nav.app.backHistory ) {
		nav.app.backHistory();
	} else {
		$.mobile.pagecontainers.active.back();
	}
};

// No-op implementation of transition degradation
$.mobile._maybeDegradeTransition = $.mobile._maybeDegradeTransition || function( transition ) {
	return transition;
};

// Exposed $.mobile methods
$.mobile._registerInternalEvents = function() {
	var getAjaxFormData = function( $form, calculateOnly ) {
		var url,
			ret = true, formData, vclickedName, method;
		if ( !$.mobile.ajaxEnabled ||
				// test that the form is, itself, ajax false
				$form.is( ":jqmData(ajax='false')" ) ||
				// test that $.mobile.ignoreContentEnabled is set and
				// the form or one of it's parents is ajax=false
				!$form.jqmHijackable().length ||
				$form.attr( "target" ) ) {
			return false;
		}

		url = ( $lastVClicked && $lastVClicked.attr( "formaction" ) ) ||
			$form.attr( "action" );
		method = ( $form.attr( "method" ) || "get" ).toLowerCase();

		// If no action is specified, browsers default to using the
		// URL of the document containing the form. Since we dynamically
		// pull in pages from external documents, the form should submit
		// to the URL for the source document of the page containing
		// the form.
		if ( !url ) {
			// Get the @data-url for the page containing the form.
			url = $.mobile.getClosestBaseUrl( $form );

			// NOTE: If the method is "get", we need to strip off the query string
			// because it will get replaced with the new form data. See issue #5710.
			if ( method === "get" ) {
				url = path.parseUrl( url ).hrefNoSearch;
			}

			if ( url === path.documentBase.hrefNoHash ) {
				// The url we got back matches the document base,
				// which means the page must be an internal/embedded page,
				// so default to using the actual document url as a browser
				// would.
				url = documentUrl.hrefNoSearch;
			}
		}

		url = path.makeUrlAbsolute( url, $.mobile.getClosestBaseUrl( $form ) );

		if ( ( path.isExternal( url ) && !path.isPermittedCrossDomainRequest( documentUrl, url ) ) ) {
			return false;
		}

		if ( !calculateOnly ) {
			formData = $form.serializeArray();

			if ( $lastVClicked && $lastVClicked[ 0 ].form === $form[ 0 ] ) {
				vclickedName = $lastVClicked.attr( "name" );
				if ( vclickedName ) {
					// Make sure the last clicked element is included in the form
					$.each( formData, function( key, value ) {
						if ( value.name === vclickedName ) {
							// Unset vclickedName - we've found it in the serialized data already
							vclickedName = "";
							return false;
						}
					} );
					if ( vclickedName ) {
						formData.push( { name: vclickedName, value: $lastVClicked.attr( "value" ) } );
					}
				}
			}

			ret = {
				url: url,
				options: {
					type: method,
					data: $.param( formData ),
					transition: $form.jqmData( "transition" ),
					reverse: $form.jqmData( "direction" ) === "reverse",
					reloadPage: true
				}
			};
		}

		return ret;
	};

	//bind to form submit events, handle with Ajax
	$.mobile.document.delegate( "form", "submit", function( event ) {
		var formData;

		if ( !event.isDefaultPrevented() ) {
			formData = getAjaxFormData( $( this ) );
			if ( formData ) {
				$( this ).closest( ".ui-pagecontainer" )
					.pagecontainer( "change", formData.url, formData.options );
				event.preventDefault();
			}
		}
	} );

	//add active state on vclick
	$.mobile.document.bind( "vclick", function( event ) {
		var theButton,
			target = event.target;

		// if this isn't a left click we don't care. Its important to note
		// that when the virtual event is generated it will create the which attr
		if ( event.which > 1 || !$.mobile.linkBindingEnabled ) {
			return;
		}

		// Record that this element was clicked, in case we need it for correct
		// form submission during the "submit" handler above
		$lastVClicked = $( target );

		// Try to find a target element to which the active class will be applied
		if ( $.data( target, "ui-button" ) ) {
			// If the form will not be submitted via AJAX, do not add active class
			if ( !getAjaxFormData( $( target ).closest( "form" ), true ) ) {
				return;
			}
		} else {
			target = findClosestLink( target );
			if ( !target ||
					( path.parseUrl( target.getAttribute( "href" ) || "#" ).hash === "#" &&
					target.getAttribute( "data-" + $.mobile.ns + "rel" ) !== "back" ) ) {
				return;
			}

			// TODO teach $.mobile.hijackable to operate on raw dom elements so the
			// link wrapping can be avoided
			if ( !$( target ).jqmHijackable().length ) {
				return;
			}
		}

		theButton = $( target ).closest( ".ui-button" );

		if ( theButton.length > 0 &&
				!( theButton.hasClass( "ui-state-disabled" ||

					// DEPRECATED as of 1.4.0 - remove after 1.4.0 release
					// only ui-state-disabled should be present thereafter
					theButton.hasClass( "ui-disabled" ) ) ) ) {
			$.mobile.removeActiveLinkClass( true );
			$.mobile.activeClickedLink = theButton;
			$.mobile.activeClickedLink.addClass( "ui-button-active" );
		}
	} );

	// click routing - direct to HTTP or Ajax, accordingly
	$.mobile.document.bind( "click", function( event ) {
		if ( !$.mobile.linkBindingEnabled || event.isDefaultPrevented() ) {
			return;
		}

		var link = findClosestLink( event.target ),
			$link = $( link ),

			//remove active link class if external (then it won't be there if you come back)
			httpCleanup = function() {
				window.setTimeout( function() {
					$.mobile.removeActiveLinkClass( true );
				}, 200 );
			},
			baseUrl, href,
			useDefaultUrlHandling, isExternal,
			transition, reverse, role;

		// If a button was clicked, clean up the active class added by vclick above
		if ( $.mobile.activeClickedLink &&
				$.mobile.activeClickedLink[ 0 ] === event.target ) {
			httpCleanup();
		}

		// If there is no link associated with the click or its not a left
		// click we want to ignore the click
		// TODO teach $.mobile.hijackable to operate on raw dom elements so the link wrapping
		// can be avoided
		if ( !link || event.which > 1 || !$link.jqmHijackable().length ) {
			return;
		}

		//if there's a data-rel=back attr, go back in history
		if ( $link.is( ":jqmData(rel='back')" ) ) {
			$.mobile.back();
			return false;
		}

		baseUrl = $.mobile.getClosestBaseUrl( $link );

		//get href, if defined, otherwise default to empty hash
		href = path.makeUrlAbsolute( $link.attr( "href" ) || "#", baseUrl );

		//if ajax is disabled, exit early
		if ( !$.mobile.ajaxEnabled && !path.isEmbeddedPage( href ) ) {
			httpCleanup();
			//use default click handling
			return;
		}

		// XXX_jblas: Ideally links to application pages should be specified as
		//            an url to the application document with a hash that is either
		//            the site relative path or id to the page. But some of the
		//            internal code that dynamically generates sub-pages for nested
		//            lists and select dialogs, just write a hash in the link they
		//            create. This means the actual URL path is based on whatever
		//            the current value of the base tag is at the time this code
		//            is called.
		if ( href.search( "#" ) !== -1 &&
				!( path.isExternal( href ) && path.isAbsoluteUrl( href ) ) ) {

			href = href.replace( /[^#]*#/, "" );
			if ( !href ) {
				//link was an empty hash meant purely
				//for interaction, so we ignore it.
				event.preventDefault();
				return;
			} else if ( path.isPath( href ) ) {
				//we have apath so make it the href we want to load.
				href = path.makeUrlAbsolute( href, baseUrl );
			} else {
				//we have a simple id so use the documentUrl as its base.
				href = path.makeUrlAbsolute( "#" + href, documentUrl.hrefNoHash );
			}
		}

		// Should we handle this link, or let the browser deal with it?
		useDefaultUrlHandling = $link.is( "[rel='external']" ) || $link.is( ":jqmData(ajax='false')" ) || $link.is( "[target]" );

		// Some embedded browsers, like the web view in Phone Gap, allow cross-domain XHR
		// requests if the document doing the request was loaded via the file:// protocol.
		// This is usually to allow the application to "phone home" and fetch app specific
		// data. We normally let the browser handle external/cross-domain urls, but if the
		// allowCrossDomainPages option is true, we will allow cross-domain http/https
		// requests to go through our page loading logic.

		//check for protocol or rel and its not an embedded page
		//TODO overlap in logic from isExternal, rel=external check should be
		//     moved into more comprehensive isExternalLink
		isExternal = useDefaultUrlHandling || ( path.isExternal( href ) && !path.isPermittedCrossDomainRequest( documentUrl, href ) );

		if ( isExternal ) {
			httpCleanup();
			//use default click handling
			return;
		}

		//use ajax
		transition = $link.jqmData( "transition" );
		reverse = $link.jqmData( "direction" ) === "reverse" ||
			// deprecated - remove by 1.0
			$link.jqmData( "back" );

		//this may need to be more specific as we use data-rel more
		role = $link.attr( "data-" + $.mobile.ns + "rel" ) || undefined;

		$link.closest( ".ui-pagecontainer" ).pagecontainer( "change", href, {
			transition: transition,
			reverse: reverse,
			role: role,
			link: $link
		});
		event.preventDefault();
	} );

	//prefetch pages when anchors with data-prefetch are encountered
	$.mobile.document.delegate( ".ui-page", "page.prefetch", function() {
		var urls = [],
			that = this;
		$( this ).find( "a:jqmData(prefetch)" ).each( function() {
			var $link = $( this ),
				url = $link.attr( "href" );

			if ( url && $.inArray( url, urls ) === -1 ) {
				urls.push( url );

				$( that ).closest( ".ui-pagecontainer" ).pagecontainer( "load", url, {
					role: $link.attr( "data-" + $.mobile.ns + "rel" ),
					prefetch: true
				});
			}
		} );
	} );

	//set page min-heights to be device specific
	$.mobile.document.bind( "pageshow", function() {

		// We need to wait for window.load to make sure that styles have already been rendered,
		// otherwise heights of external toolbars will have the wrong value
		if ( loadDeferred ) {
			loadDeferred.done( $.mobile.resetActivePageHeight );
		} else {
			$.mobile.resetActivePageHeight();
		}
	} );
	$.mobile.window.bind( "throttledresize", $.mobile.resetActivePageHeight );

}; //navreadyDeferred done callback

$( function() {
	domreadyDeferred.resolve();
} );

// Account for the possibility that the load event has already fired
if ( document.readyState === "complete" ) {
	pageIsFullyLoaded();
} else {
	$.mobile.window.on( "load", pageIsFullyLoaded );
}

$.when( domreadyDeferred, $.mobile.navreadyDeferred ).done( function() {
	$.mobile._registerInternalEvents();
} );

return $.mobile;
} );

/*!
 * jQuery Mobile Degrade Inputs @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Degrade Inputs
//>>group: Utilities
//>>description: Degrades HTM5 input types to compatible HTML4 ones.
//>>docs: http://api.jquerymobile.com/jQuery.mobile.degradeInputsWithin/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'degradeInputs',[
			"jquery",
			"defaults" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.degradeInputs = {
	range: "number",
	search: "text"
};

// Auto self-init widgets
$.mobile.degradeInputsWithin = function( target ) {
	target = typeof target === "string" ? $( target ) : target;

	// Degrade inputs to avoid poorly implemented native functionality
	target.find( "input" ).not( $.mobile.keepNative ).each( function() {
		var html, findstr, repstr,
			element = $( this ),
			type = this.getAttribute( "type" ),
			optType = $.mobile.degradeInputs[ type ] || "text";

		if ( $.mobile.degradeInputs[ type ] ) {
			html = $( "<div>" ).html( element.clone() ).html();

			findstr = /\s+type=["']?\w+['"]?/;
			repstr = " type=\"" + optType + "\" data-" + $.mobile.ns + "type=\"" + type + "\"";

			element.replaceWith( html.replace( findstr, repstr ) );
		}
	} );

};

var hook = function() {
	$.mobile.degradeInputsWithin( this.addBack() );
};

( $.enhance = $.extend( $.enhance, $.extend( { hooks: [] }, $.enhance ) ) ).hooks.unshift( hook );
return $.mobile.degradeInputsWithin;
} );

/*!
 * jQuery Mobile Page Styled As Dialog @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Dialog styling
//>>group: Widgets
//>>description: Styles a page as a modal dialog with inset appearance and overlay background
//>>docs: http://api.jquerymobile.com/page/
//>>demos: http://demos.jquerymobile.com/@VERSION/pages-dialog/
//>>css.structure: ../css/structure/jquery.mobile.dialog.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/page.dialog',[
			"jquery",
			"../widget",
			"./page",
			"../navigation" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.widget( "mobile.page", $.mobile.page, {
	options: {
		classes: {
			"ui-page-dialog-close-button":
				"ui-button ui-corner-all ui-button-icon-only",
			"ui-page-dialog-close-button-icon": "ui-icon-delete ui-icon",
			"ui-page-dialog-contain": "ui-overlay-shadow ui-corner-all"
		},

		// Accepts left, right and none
		closeBtn: "left",
		closeBtnText: "Close",
		overlayTheme: "a",
		dialog: false
	},

	_create: function() {
		this.dialog = {};

		return this._superApply( arguments );
	},

	_establishStructure: function() {
		var returnValue = this._superApply( arguments );

		if ( this.options.dialog ) {
			if ( this.options.enhanced ) {
				this.dialog.wrapper = this.element.children( ".ui-page-dialog-contain" ).eq( 0 );
				if ( this.options.closeBtn !== "none" ) {
					this.dialog.button = this.dialog.wrapper
						.children( ".ui-toolbar-header" )
							.children( "a.ui-page-dialog-close-button" );
					this.dialog.icon = this.dialog.button
						.children( ".ui-page-dialog-close-button-icon" );
				}
			} else {
				this.dialog.wrapper = $( "<div>" );

				// Gut the page
				this.dialog.wrapper.append( this.element.contents() );

				// Establish the button
				this._setCloseButton( this.options.closeBtn, this.options.closeBtnText );
			}
		}

		return returnValue;
	},

	_themeElements: function() {
		var elements = this._super();
		if ( this.options.dialog ) {
			elements.push(
				{
					element: this.dialog.wrapper,
					prefix: "ui-body-"
				}
			);
		}

		return elements;
	},

	_setAttributes: function() {
		var returnValue = this._superApply( arguments );

		if ( this.options.dialog ) {
			this._addClass( "ui-page-dialog", null );
			this._addClass( this.dialog.wrapper, "ui-page-dialog-contain", null );

			// Aria role
			this.dialog.wrapper.attr( "role", "dialog" );
		}

		if ( this.dialog.button && this.options.enhanced ) {
			this._toggleButtonClasses( true, this.options.closeBtn );
		}

		return returnValue;
	},

	_attachToDOM: function() {
		var returnValue = this._superApply( arguments );

		if ( this.options.dialog && !this.options.enhanced ) {
			this.element.append( this.dialog.wrapper );
		}

		return returnValue;
	},

	_toggleButtonClasses: function( add, location ) {
		this._toggleClass( this.dialog.button, "ui-page-dialog-close-button",
			"ui-toolbar-header-button-" + location, add );
		this._toggleClass( this.dialog.icon, "ui-page-dialog-close-button-icon", null, add );
	},

	_setOptions: function( options ) {
		var closeButtonLocation, closeButtonText;

		this._super( options );

		if ( !this.options.dialog ) {
			return;
		}

		if ( options.overlayTheme !== undefined ) {
			if ( $.mobile.activePage[ 0 ] === this.element[ 0 ] ) {

				// Needs the option value to already be set on this.options. This is accomplished
				// by chaining up above, before handling the overlayTheme change.
				this._handlePageBeforeShow();
			}
		}

		if ( options.closeBtnText !== undefined ) {
			closeButtonLocation = this.options.closeBtn;
			closeButtonText = options.closeBtnText;
		}

		if ( options.closeBtn !== undefined ) {
			closeButtonLocation = options.closeBtn;
			closeButtonText = closeButtonText || this.options.closeBtnText;
		}

		if ( closeButtonLocation ) {
			this._setCloseButton( closeButtonLocation, closeButtonText );
		}
	},

	_toggleCloseButtonClickability: function( isClickable ) {
		if ( this.dialog.button ) {
			if ( isClickable ) {
				this.dialog.button.css( "pointer-events", "" );
				this.dialog.button.removeAttr( "tabindex" );
			} else {
				this.dialog.button.css( "pointer-events", "none" );
				this.dialog.button.attr( "tabindex", -1 );
			}
		}
	},

	_handlePageBeforeShow: function() {

		// Make sure the close button is clickable
		this._toggleCloseButtonClickability( true );
		if ( this.options.overlayTheme && this.options.dialog ) {
			this._setContainerSwatch( this.options.overlayTheme );
		} else {
			this._super();
		}
	},

	_handleButtonClick: function() {

		// Render the close button unclickable after one click
		this._toggleCloseButtonClickability( false );
	},

	_setCloseButton: function( location, text ) {
		var destination;

		// Sanitize value
		location = "left" === location ? "left" : "right" === location ? "right" : "none";

		if ( this.dialog.button ) {

			if ( "none" === location ) {

				// Remove existing button
				this._toggleButtonClasses( false, location );
				this._off( this.dialog.button, "click" );
				this.dialog.button.remove();
				this.dialog.button = null;
				this.dialog.icon = null;
			} else {

				// Update existing button
				this._removeClass( this.dialog.button, null,
						"ui-toolbar-header-button-left ui-toolbar-header-button-right" )
					._addClass( this.dialog.button, null, "ui-toolbar-header-button-" + location );
				if ( text ) {

					// Get rid of all text nodes without touching other node types before updating
					// the button's text.
					this.dialog.button.contents()
						.filter( function() {
							return ( this.nodeType === 3 );
						} )
						.remove();
					this.dialog.button.prepend( text );
				}
			}
		} else if ( location !== "none" ) {

			// Create new button
			destination = this.dialog.wrapper
				.children( ".ui-toolbar-header,[data-" + $.mobile.ns + "type='header']" )
					.first();
			if ( destination.length ) {
				this.dialog.button = $( "<a href='#' data-" + $.mobile.ns + "rel='back'></a>" )
					.text( text || this.options.closeBtnText || "" );
				this.dialog.icon = $( "<span>" ).appendTo( this.dialog.button );

				this._toggleButtonClasses( true, location );

				this.dialog.button.prependTo( destination );
				this._on( this.dialog.button, { "click": "_handleButtonClick" } );
			}
		}
	}
} );

} );

/*!
 * jQuery Mobile Widget Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Backcompat option setting code
//>>group: Backcompat
//>>description: Synchronize deprecated style options and the value of the classes option.

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/widget.backcompat',[
			"jquery",
			"../ns",
			"../widget",
			"jquery-ui/widget" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {

	var classSplitterRegex = /\S+/g;

	$.mobile.widget = $.extend( {}, { backcompat: {

		_boolOptions: {
			inline:  "ui-button-inline",
			mini: "ui-mini",
			shadow: "ui-shadow",
			corners: "ui-corner-all"
		},

		_create: function() {
			this._setInitialOptions();
			this._super();
			if ( !this.options.enhanced && this.options.wrapperClass ) {
				this._addClass( this.widget(), null, this.options.wrapperClass );
			}
		},

		_classesToOption: function( value ) {
			if ( this.classProp && ( typeof value[ this.classProp ] === "string" ) ) {
				var that = this,
					valueArray = value[ this.classProp ].match( classSplitterRegex ) || [];

				$.each( this._boolOptions, function( option, className ) {
					if ( that.options[ option ] !== undefined ) {
						if ( $.inArray( className, valueArray ) !== -1 ) {
							that.options[ option ] = true;
						} else {
							that.options[ option ] = false;
						}
					}
				} );
			}
		},

		_getClassValue: function( prop, optionClass, value ) {
			var classes = this.options.classes[ prop ] || "",
				classArray = classes.match( classSplitterRegex ) || [];

				if ( value ) {
					if ( $.inArray( optionClass, classArray ) === -1 ) {
						classArray.push( optionClass );
					}
				} else {
					classArray.splice( $.inArray( optionClass, classArray ), 1 );
				}
				return classArray.join( " " );
		},

		_optionsToClasses: function( option, value ) {
			var prop = this.classProp,
				className = this._boolOptions[ option ];

			if ( prop ) {
				this.option(
					"classes." + prop,
					this._getClassValue( prop, className, value )
				);
			}
		},

		_setInitialOptions: function() {
			var currentClasses,
				options = this.options,
				original = $[ this.namespace ][ this.widgetName ].prototype.options,
				prop = this.classProp,
				that = this;

			if ( prop ) {
				currentClasses =
					( options.classes[ prop ] || "" ).match( classSplitterRegex ) || [];

				// If the classes option value has diverged from the default, then its value takes
				// precedence, causing us to update all the style options to reflect the contents
				// of the classes option value
				if ( original.classes[ prop ] !== options.classes[ prop ] ) {
					$.each( this._boolOptions, function( option, className ) {
						if ( options[ option ] !== undefined ) {
							options[ option ] = ( $.inArray( className, currentClasses ) !== -1 );
						}
					} ) ;

				// Otherwise we assume that we're dealing with legacy code and look for style
				// option values which diverge from the defaults. If we find any that diverge, we
				// update the classes option value accordingly.
				} else {
					$.each( this._boolOptions, function( option, className ) {
						if ( options[ option ] !== original[ option ] ) {
							options.classes[ prop ] =
								that._getClassValue( prop, className, options[ option ] );
						}
					} );
				}
			}
		},

		_setOption: function( key, value ) {
			var widgetElement;

			// Update deprecated option based on classes option
			if ( key === "classes" ) {
				this._classesToOption( value );
			}

			// Update classes options based on deprecated option
			if ( this._boolOptions[ key ] ) {
				this._optionsToClasses( key, value );
			}

			// Update wrapperClass
			if ( key === "wrapperClass" ) {
				widgetElement = this.widget();
				this._removeClass( widgetElement, null, this.options.wrapperClass )
					._addClass( widgetElement, null, value );
			}

			this._superApply( arguments );
		}
	} }, $.mobile.widget );
} else {
	$.mobile.widget.backcompat = {};
}

return $.mobile.widget;

} );

/*!
 * jQuery Mobile Page Styled As Dialog Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Dialog styling backcompat
//>>group: Widgets
//>>description: Style options for page styled as dialog

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/page.dialog.backcompat',[
			"jquery",
			"./widget.backcompat",
			"./page.dialog" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {
	$.widget( "mobile.page", $.mobile.page, {
		options: {
			corners: true
		},

		classProp: "ui-page-dialog-contain",

		_create: function() {

			// Support for deprecated dialog widget functionality
			if ( $.mobile.getAttribute( this.element[ 0 ], "role" ) === "dialog" ||
				this.options.role === "dialog" ) {

				// The page container needs to distinguish a dialog widget from a page styled
				// as a dialog. It does so by looking for the "mobile-dialog" data item on the
				// page element. Since the dialog is no longer a widget, we need to provide a
				// dummy hint
				$.data( this.element[ 0 ], "mobile-dialog", true );
				this.options.dialog = true;
			}
			this._super();
		}
	} );
	$.widget( "mobile.page", $.mobile.page, $.mobile.widget.backcompat );
}

return $.mobile.page;

} );

/*!
 * jQuery Mobile Collapsible @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Collapsible
//>>group: Widgets
//>>description: Creates collapsible content blocks.
//>>docs: http://api.jquerymobile.com/collapsible/
//>>demos: http://demos.jquerymobile.com/@VERSION/collapsible/
//>>css.structure: ../css/structure/jquery.mobile.collapsible.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/collapsible',[
			"jquery",
			"../core",
			"../widget" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var rInitialLetter = /([A-Z])/g;

$.widget( "mobile.collapsible", {
	version: "@VERSION",

	options: {
		enhanced: false,
		expandCueText: null,
		collapseCueText: null,
		collapsed: true,
		heading: "h1,h2,h3,h4,h5,h6,legend",
		collapsedIcon: null,
		expandedIcon: null,
		iconpos: null,
		theme: null,
		contentTheme: null,
		inset: null,
		corners: null,
		mini: null
	},

	_create: function() {
		var elem = this.element,
			ui = {
				accordion: elem
					.closest( ":jqmData(role='collapsible-set')," +
						":jqmData(role='collapsibleset')" +
						( $.mobile.collapsibleset ? ", :mobile-collapsibleset" :
							"" ) )
						.addClass( "ui-collapsible-set" )
			};

		this._ui = ui;
		this._renderedOptions = this._getOptions( this.options );

		if ( this.options.enhanced ) {
			ui.heading = this.element.children( ".ui-collapsible-heading" );
			ui.content = ui.heading.next();
			ui.anchor = ui.heading.children();
			ui.status = ui.anchor.children( ".ui-collapsible-heading-status" );
			ui.icon = ui.anchor.children( ".ui-icon" );
		} else {
			this._enhance( elem, ui );
		}

		this._on( ui.heading, {
			"tap": function() {
				ui.heading.find( "a" ).first().addClass( "ui-button-active" );
			},

			"click": function( event ) {
				this._handleExpandCollapse( !ui.heading.hasClass( "ui-collapsible-heading-collapsed" ) );
				event.preventDefault();
				event.stopPropagation();
			}
		} );
	},

	// Adjust the keys inside options for inherited values
	_getOptions: function( options ) {
		var key,
			accordion = this._ui.accordion,
			accordionWidget = this._ui.accordionWidget;

		// Copy options
		options = $.extend( {}, options );

		if ( accordion.length && !accordionWidget ) {
			this._ui.accordionWidget =
				accordionWidget = accordion.data( "mobile-collapsibleset" );
		}

		for ( key in options ) {

			// Retrieve the option value first from the options object passed in and, if
			// null, from the parent accordion or, if that's null too, or if there's no
			// parent accordion, then from the defaults.
			options[ key ] =
				( options[ key ] != null ) ? options[ key ] :
					( accordionWidget ) ? accordionWidget.options[ key ] :
						accordion.length ? $.mobile.getAttribute( accordion[ 0 ],
							key.replace( rInitialLetter, "-$1" ).toLowerCase() ) :
							null;

			if ( null == options[ key ] ) {
				options[ key ] = $.mobile.collapsible.defaults[ key ];
			}
		}

		return options;
	},

	_themeClassFromOption: function( prefix, value ) {
		return ( value ? ( value === "none" ? "" : prefix + value ) : "" );
	},

	_enhance: function( elem, ui ) {
		var opts = this._renderedOptions,
			contentThemeClass = this._themeClassFromOption( "ui-body-", opts.contentTheme );

		elem.addClass( "ui-collapsible " +
			( opts.inset ? "ui-collapsible-inset " : "" ) +
			( opts.inset && opts.corners ? "ui-corner-all " : "" ) +
			( contentThemeClass ? "ui-collapsible-themed-content " : "" ) );
		ui.originalHeading = elem.children( this.options.heading ).first(),
		ui.content = elem
			.wrapInner( "<div " +
				"class='ui-collapsible-content " +
				contentThemeClass + "'></div>" )
			.children( ".ui-collapsible-content" ),
		ui.heading = ui.originalHeading;

		// Replace collapsibleHeading if it's a legend
		if ( ui.heading.is( "legend" ) ) {
			ui.heading = $( "<div role='heading'>" + ui.heading.html() + "</div>" );
			ui.placeholder = $( "<div><!-- placeholder for legend --></div>" ).insertBefore( ui.originalHeading );
			ui.originalHeading.remove();
		}

		ui.status = $( "<span class='ui-collapsible-heading-status'></span>" );
		ui.anchor = ui.heading
			.detach()
			//modify markup & attributes
			.addClass( "ui-collapsible-heading" )
			.append( ui.status )
			.wrapInner( "<a href='#' class='ui-collapsible-heading-toggle'></a>" )
			.find( "a" )
				.first()
					.addClass( "ui-button " +
						this._themeClassFromOption( "ui-button-", opts.theme ) + " " +
						( opts.mini ? "ui-mini " : "" ) );

		this._updateIcon();

		//drop heading in before content
		ui.heading.insertBefore( ui.content );

		this._handleExpandCollapse( this.options.collapsed );

		return ui;
	},

	_updateIcon: function() {
		var ui = this._ui,
			opts = this._getOptions( this.options ),
			iconclass =
				opts.collapsed ?
				( opts.collapsedIcon ? " ui-icon-" + opts.collapsedIcon : "" ) :
				( opts.expandedIcon ? " ui-icon-" + opts.expandedIcon : "" ),
			method = opts.iconpos === ( "bottom" || "right" ) ? "append" : "prepend";

		if ( ui.icon ) {
			ui.icon.remove();
		}
		if ( ui.space ) {
			ui.space.remove();
		}

		ui.icon = $( "<span class='ui-icon" + ( iconclass ? iconclass + " " : "" ) + "'></span>" );

		if ( opts.iconpos === "left" || opts.iconpos === "right" ||
				opts.iconpos === null ) {
			ui.space = $( "<span class='ui-icon-space'> </span>" );

			ui.anchor[ method ]( ui.space );
		} else {
			ui.icon.addClass( "ui-widget-icon-block" );
		}

		ui.anchor[ method ]( ui.icon );

		if ( opts.iconpos === "right" ) {
			ui.icon.addClass( "ui-collapsible-icon-right" );
		}
	},

	refresh: function() {
		this._applyOptions( this.options );
		this._renderedOptions = this._getOptions( this.options );
		this._updateIcon();
	},

	_applyOptions: function( options ) {
		var isCollapsed, newTheme, oldTheme, hasCorners,
			elem = this.element,
			currentOpts = this._renderedOptions,
			ui = this._ui,
			anchor = ui.anchor,
			status = ui.status,
			opts = this._getOptions( options );

		// First and foremost we need to make sure the collapsible is in the proper
		// state, in case somebody decided to change the collapsed option at the
		// same time as another option
		if ( options.collapsed !== undefined ) {
			this._handleExpandCollapse( options.collapsed );
		}

		isCollapsed = elem.hasClass( "ui-collapsible-collapsed" );

		// We only need to apply the cue text for the current state right away.
		// The cue text for the alternate state will be stored in the options
		// and applied the next time the collapsible's state is toggled
		if ( isCollapsed ) {
			if ( opts.expandCueText !== undefined ) {
				status.text( opts.expandCueText );
			}
		} else {
			if ( opts.collapseCueText !== undefined ) {
				status.text( opts.collapseCueText );
			}
		}

		if ( opts.theme !== undefined ) {
			oldTheme = this._themeClassFromOption( "ui-button-", currentOpts.theme );
			newTheme = this._themeClassFromOption( "ui-button-", opts.theme );
			anchor.removeClass( oldTheme ).addClass( newTheme );
		}

		if ( opts.contentTheme !== undefined ) {
			oldTheme = this._themeClassFromOption( "ui-body-",
				currentOpts.contentTheme );
			newTheme = this._themeClassFromOption( "ui-body-",
				opts.contentTheme );
			ui.content.removeClass( oldTheme ).addClass( newTheme );
		}

		if ( opts.inset !== undefined ) {
			elem.toggleClass( "ui-collapsible-inset", opts.inset );
			hasCorners = !!( opts.inset && ( opts.corners || currentOpts.corners ) );
		}

		if ( opts.corners !== undefined ) {
			hasCorners = !!( opts.corners && ( opts.inset || currentOpts.inset ) );
		}

		if ( hasCorners !== undefined ) {
			elem.toggleClass( "ui-corner-all", hasCorners );
		}

		if ( opts.mini !== undefined ) {
			anchor.toggleClass( "ui-mini", opts.mini );
		}
	},

	_setOptions: function( options ) {
		this._applyOptions( options );
		this._super( options );
		this._renderedOptions = this._getOptions( this.options );

		// If any icon-related options have changed, make sure the new icon
		// state is reflected by first removing all icon-related classes
		// reflecting the current state and then adding all icon-related
		// classes for the new state
		if ( !( options.iconpos === undefined &&
				options.collapsedIcon === undefined &&
				options.expandedIcon === undefined ) ) {

			this._updateIcon();
		}
	},

	_handleExpandCollapse: function( isCollapse ) {
		var opts = this._renderedOptions,
			ui = this._ui;

		ui.status.text( isCollapse ? opts.expandCueText : opts.collapseCueText );
		ui.heading
			.toggleClass( "ui-collapsible-heading-collapsed", isCollapse )
			.find( "a" ).first()
				.removeClass( "ui-button-active" );
		ui.heading
			.toggleClass( "ui-collapsible-heading-collapsed", isCollapse )
			.find( "a" ).first().removeClass( "ui-button-active" );

		if ( ui.icon ) {
			ui.icon.toggleClass( "ui-icon-" + opts.expandedIcon, !isCollapse )

			// logic or cause same icon for expanded/collapsed state would remove the ui-icon-class
			.toggleClass( "ui-icon-" + opts.collapsedIcon, ( isCollapse || opts.expandedIcon === opts.collapsedIcon ) );
		}
		this.element.toggleClass( "ui-collapsible-collapsed", isCollapse );
		ui.content
			.toggleClass( "ui-collapsible-content-collapsed", isCollapse )
			.attr( "aria-hidden", isCollapse )
			.trigger( "updatelayout" );
		this.options.collapsed = isCollapse;
		this._trigger( isCollapse ? "collapse" : "expand" );
	},

	expand: function() {
		this._handleExpandCollapse( false );
	},

	collapse: function() {
		this._handleExpandCollapse( true );
	},

	_destroy: function() {
		var ui = this._ui,
			opts = this.options;

		if ( opts.enhanced ) {
			return;
		}

		if ( ui.placeholder ) {
			ui.originalHeading.insertBefore( ui.placeholder );
			ui.placeholder.remove();
			ui.heading.remove();
		} else {
			ui.status.remove();
			ui.heading
				.removeClass( "ui-collapsible-heading ui-collapsible-heading-collapsed" )
				.children()
					.contents()
					.unwrap();
		}

		if ( ui.icon ) {
			ui.icon.remove();
		}
		if( ui.space ) {
			ui.space.remove();
		}

		ui.anchor.contents().unwrap();
		ui.content.contents().unwrap();
		this.element
			.removeClass( "ui-collapsible ui-collapsible-collapsed " +
				"ui-collapsible-themed-content ui-collapsible-inset ui-corner-all" );
	}
} );

// Defaults to be used by all instances of collapsible if per-instance values
// are unset or if nothing is specified by way of inheritance from an accordion.
// Note that this hash does not contain options "collapsed" or "heading",
// because those are not inheritable.
$.mobile.collapsible.defaults = {
	expandCueText: " click to expand contents",
	collapseCueText: " click to collapse contents",
	collapsedIcon: "plus",
	contentTheme: "inherit",
	expandedIcon: "minus",
	iconpos: "left",
	inset: true,
	corners: true,
	theme: "inherit",
	mini: false
};

return $.mobile.collapsible;

} );

/*!
 * jQuery Mobile First And Last Classes @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: First & Last Classes
//>>group: Widgets
//>>description: Behavior mixin to mark first and last visible item with special classes.

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/addFirstLastClasses',[
			"jquery",
			"../core" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var uiScreenHiddenRegex = /\bui-screen-hidden\b/;
function noHiddenClass( elements ) {
	var index,
		length = elements.length,
		result = [];

	for ( index = 0; index < length; index++ ) {
		if ( !elements[ index ].className.match( uiScreenHiddenRegex ) ) {
			result.push( elements[ index ] );
		}
	}

	return $( result );
}

$.mobile.behaviors.addFirstLastClasses = {
	_getVisibles: function( $els, create ) {
		var visibles;

		if ( create ) {
			visibles = noHiddenClass( $els );
		} else {
			visibles = $els.filter( ":visible" );
			if ( visibles.length === 0 ) {
				visibles = noHiddenClass( $els );
			}
		}

		return visibles;
	},

	_addFirstLastClasses: function( $els, $visibles, create ) {
		$els.removeClass( "ui-first-child ui-last-child" );
		$visibles.eq( 0 ).addClass( "ui-first-child" ).end().last().addClass( "ui-last-child" );
		if ( !create ) {
			this.element.trigger( "updatelayout" );
		}
	},

	_removeFirstLastClasses: function( $els ) {
		$els.removeClass( "ui-first-child ui-last-child" );
	}
};

return $.mobile.behaviors.addFirstLastClasses;

} );

/*!
 * jQuery Mobile Collapsible Set @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Collapsible Sets (Accordions)
//>>group: Widgets
//>>description: For creating grouped collapsible content areas.
//>>docs: http://api.jquerymobile.com/collapsibleset/
//>>demos: http://demos.jquerymobile.com/@VERSION/collapsibleset/
//>>css.structure: ../css/structure/jquery.mobile.collapsible.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/collapsibleSet',[
			"jquery",
			"../widget",
			"./collapsible",
			"./addFirstLastClasses" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.widget( "mobile.collapsibleset", $.extend( {
	version: "@VERSION",

	options: $.extend( {
		enhanced: false
	}, $.mobile.collapsible.defaults ),

	_handleCollapsibleExpand: function( event ) {
		var closestCollapsible = $( event.target ).closest( ".ui-collapsible" );

		if ( closestCollapsible.parent().is( ":mobile-collapsibleset, :jqmData(role='collapsibleset')" ) ) {
			closestCollapsible
				.siblings( ".ui-collapsible:not(.ui-collapsible-collapsed)" )
				.collapsible( "collapse" );
		}
	},

	_create: function() {
		var elem = this.element,
			opts = this.options;

		$.extend( this, {
			_classes: ""
		} );

		this.childCollapsiblesSelector = ":mobile-collapsible, " +
			( "[data-" + $.mobile.ns +  "role='collapsible']" );

		if ( !opts.enhanced ) {
			elem.addClass( "ui-collapsible-set " +
				this._themeClassFromOption( "ui-group-theme-", opts.theme ) + " " +
				( opts.corners && opts.inset ? "ui-corner-all " : "" ) );
			this.element.find( this.childCollapsiblesSelector ).collapsible();
		}

		this._on( elem, { collapsibleexpand: "_handleCollapsibleExpand" } );
	},

	_themeClassFromOption: function( prefix, value ) {
		return ( value ? ( value === "none" ? "" : prefix + value ) : "" );
	},

	_init: function() {
		this._refresh( true );

		// Because the corners are handled by the collapsible itself and the default state is collapsed
		// That was causing https://github.com/jquery/jquery-mobile/issues/4116
		this.element
			.children( this.childCollapsiblesSelector )
				.filter( ":jqmData(collapsed='false')" )
					.collapsible( "expand" );
	},

	_setOptions: function( options ) {
		var ret, hasCorners,
			elem = this.element,
			themeClass = this._themeClassFromOption( "ui-group-theme-", options.theme );

		if ( themeClass ) {
			elem
				.removeClass( this._themeClassFromOption( "ui-group-theme-", this.options.theme ) )
				.addClass( themeClass );
		}

		if ( options.inset !== undefined ) {
			hasCorners = !!( options.inset && ( options.corners || this.options.corners ) );
		}

		if ( options.corners !== undefined ) {
			hasCorners = !!( options.corners && ( options.inset || this.options.inset ) );
		}

		if ( hasCorners !== undefined ) {
			elem.toggleClass( "ui-corner-all", hasCorners );
		}

		ret = this._super( options );
		this.element.children( ":mobile-collapsible" ).collapsible( "refresh" );
		return ret;
	},

	_destroy: function() {
		var el = this.element;

		this._removeFirstLastClasses( el.children( this.childCollapsiblesSelector ) );
		el
			.removeClass( "ui-collapsible-set ui-corner-all " +
				this._themeClassFromOption( "ui-group-theme-", this.options.theme ) )
			.children( ":mobile-collapsible" )
				.collapsible( "destroy" );
	},

	_refresh: function( create ) {
		var collapsiblesInSet = this.element.children( this.childCollapsiblesSelector );

		this.element.find( this.childCollapsiblesSelector ).not( ".ui-collapsible" ).collapsible();

		this._addFirstLastClasses( collapsiblesInSet, this._getVisibles( collapsiblesInSet, create ), create );
	},

	refresh: function() {
		this._refresh( false );
	}
}, $.mobile.behaviors.addFirstLastClasses ) );

} );

/*!
 * jQuery Mobile Grid @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Grid Layouts (Columns)
//>>group: Widgets
//>>description: Applies classes for creating grid or column styling.
//>>docs: http://api.jquerymobile.com/grid-layout/
//>>demos: http://demos.jquerymobile.com/@VERSION/grids/
//>>css.structure:../css/structure/jquery.mobile.grid.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'grid',[ "jquery" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.fn.grid = function( options ) {
	return this.each( function() {

		var $this = $( this ),
			o = $.extend( {
				grid: null
			}, options ),
			$kids = $this.children(),
			gridCols = { solo: 1, a: 2, b: 3, c: 4, d: 5 },
			grid = o.grid,
			iterator,
			letter;

		if ( !grid ) {
			if ( $kids.length <= 5 ) {
				for ( letter in gridCols ) {
					if ( gridCols[ letter ] === $kids.length ) {
						grid = letter;
					}
				}
			} else {
				grid = "a";
				$this.addClass( "ui-grid-duo" );
			}
		}
		iterator = gridCols[ grid ];

		$this.addClass( "ui-grid-" + grid );

		$kids.filter( ":nth-child(" + iterator + "n+1)" ).addClass( "ui-block-a" );

		if ( iterator > 1 ) {
			$kids.filter( ":nth-child(" + iterator + "n+2)" ).addClass( "ui-block-b" );
		}
		if ( iterator > 2 ) {
			$kids.filter( ":nth-child(" + iterator + "n+3)" ).addClass( "ui-block-c" );
		}
		if ( iterator > 3 ) {
			$kids.filter( ":nth-child(" + iterator + "n+4)" ).addClass( "ui-block-d" );
		}
		if ( iterator > 4 ) {
			$kids.filter( ":nth-child(" + iterator + "n+5)" ).addClass( "ui-block-e" );
		}
	} );
};

return $.fn.grid;

} );

/*!
 * jQuery UI Controlgroup 1.12.1
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Controlgroup
//>>group: Widgets
//>>description: Visually groups form control widgets
//>>docs: http://api.jqueryui.com/controlgroup/
//>>demos: http://jqueryui.com/controlgroup/
//>>css.structure: ../../themes/base/core.css
//>>css.structure: ../../themes/base/controlgroup.css
//>>css.theme: ../../themes/base/theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/widgets/controlgroup',[
			"jquery",
			"../widget"
		], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
}( function( $ ) {
var controlgroupCornerRegex = /ui-corner-([a-z]){2,6}/g;

return $.widget( "ui.controlgroup", {
	version: "1.12.1",
	defaultElement: "<div>",
	options: {
		direction: "horizontal",
		disabled: null,
		onlyVisible: true,
		items: {
			"button": "input[type=button], input[type=submit], input[type=reset], button, a",
			"controlgroupLabel": ".ui-controlgroup-label",
			"checkboxradio": "input[type='checkbox'], input[type='radio']",
			"selectmenu": "select",
			"spinner": ".ui-spinner-input"
		}
	},

	_create: function() {
		this._enhance();
	},

	// To support the enhanced option in jQuery Mobile, we isolate DOM manipulation
	_enhance: function() {
		this.element.attr( "role", "toolbar" );
		this.refresh();
	},

	_destroy: function() {
		this._callChildMethod( "destroy" );
		this.childWidgets.removeData( "ui-controlgroup-data" );
		this.element.removeAttr( "role" );
		if ( this.options.items.controlgroupLabel ) {
			this.element
				.find( this.options.items.controlgroupLabel )
				.find( ".ui-controlgroup-label-contents" )
				.contents().unwrap();
		}
	},

	_initWidgets: function() {
		var that = this,
			childWidgets = [];

		// First we iterate over each of the items options
		$.each( this.options.items, function( widget, selector ) {
			var labels;
			var options = {};

			// Make sure the widget has a selector set
			if ( !selector ) {
				return;
			}

			if ( widget === "controlgroupLabel" ) {
				labels = that.element.find( selector );
				labels.each( function() {
					var element = $( this );

					if ( element.children( ".ui-controlgroup-label-contents" ).length ) {
						return;
					}
					element.contents()
						.wrapAll( "<span class='ui-controlgroup-label-contents'></span>" );
				} );
				that._addClass( labels, null, "ui-widget ui-widget-content ui-state-default" );
				childWidgets = childWidgets.concat( labels.get() );
				return;
			}

			// Make sure the widget actually exists
			if ( !$.fn[ widget ] ) {
				return;
			}

			// We assume everything is in the middle to start because we can't determine
			// first / last elements until all enhancments are done.
			if ( that[ "_" + widget + "Options" ] ) {
				options = that[ "_" + widget + "Options" ]( "middle" );
			} else {
				options = { classes: {} };
			}

			// Find instances of this widget inside controlgroup and init them
			that.element
				.find( selector )
				.each( function() {
					var element = $( this );
					var instance = element[ widget ]( "instance" );

					// We need to clone the default options for this type of widget to avoid
					// polluting the variable options which has a wider scope than a single widget.
					var instanceOptions = $.widget.extend( {}, options );

					// If the button is the child of a spinner ignore it
					// TODO: Find a more generic solution
					if ( widget === "button" && element.parent( ".ui-spinner" ).length ) {
						return;
					}

					// Create the widget if it doesn't exist
					if ( !instance ) {
						instance = element[ widget ]()[ widget ]( "instance" );
					}
					if ( instance ) {
						instanceOptions.classes =
							that._resolveClassesValues( instanceOptions.classes, instance );
					}
					element[ widget ]( instanceOptions );

					// Store an instance of the controlgroup to be able to reference
					// from the outermost element for changing options and refresh
					var widgetElement = element[ widget ]( "widget" );
					$.data( widgetElement[ 0 ], "ui-controlgroup-data",
						instance ? instance : element[ widget ]( "instance" ) );

					childWidgets.push( widgetElement[ 0 ] );
				} );
		} );

		this.childWidgets = $( $.unique( childWidgets ) );
		this._addClass( this.childWidgets, "ui-controlgroup-item" );
	},

	_callChildMethod: function( method ) {
		this.childWidgets.each( function() {
			var element = $( this ),
				data = element.data( "ui-controlgroup-data" );
			if ( data && data[ method ] ) {
				data[ method ]();
			}
		} );
	},

	_updateCornerClass: function( element, position ) {
		var remove = "ui-corner-top ui-corner-bottom ui-corner-left ui-corner-right ui-corner-all";
		var add = this._buildSimpleOptions( position, "label" ).classes.label;

		this._removeClass( element, null, remove );
		this._addClass( element, null, add );
	},

	_buildSimpleOptions: function( position, key ) {
		var direction = this.options.direction === "vertical";
		var result = {
			classes: {}
		};
		result.classes[ key ] = {
			"middle": "",
			"first": "ui-corner-" + ( direction ? "top" : "left" ),
			"last": "ui-corner-" + ( direction ? "bottom" : "right" ),
			"only": "ui-corner-all"
		}[ position ];

		return result;
	},

	_spinnerOptions: function( position ) {
		var options = this._buildSimpleOptions( position, "ui-spinner" );

		options.classes[ "ui-spinner-up" ] = "";
		options.classes[ "ui-spinner-down" ] = "";

		return options;
	},

	_buttonOptions: function( position ) {
		return this._buildSimpleOptions( position, "ui-button" );
	},

	_checkboxradioOptions: function( position ) {
		return this._buildSimpleOptions( position, "ui-checkboxradio-label" );
	},

	_selectmenuOptions: function( position ) {
		var direction = this.options.direction === "vertical";
		return {
			width: direction ? "auto" : false,
			classes: {
				middle: {
					"ui-selectmenu-button-open": "",
					"ui-selectmenu-button-closed": ""
				},
				first: {
					"ui-selectmenu-button-open": "ui-corner-" + ( direction ? "top" : "tl" ),
					"ui-selectmenu-button-closed": "ui-corner-" + ( direction ? "top" : "left" )
				},
				last: {
					"ui-selectmenu-button-open": direction ? "" : "ui-corner-tr",
					"ui-selectmenu-button-closed": "ui-corner-" + ( direction ? "bottom" : "right" )
				},
				only: {
					"ui-selectmenu-button-open": "ui-corner-top",
					"ui-selectmenu-button-closed": "ui-corner-all"
				}

			}[ position ]
		};
	},

	_resolveClassesValues: function( classes, instance ) {
		var result = {};
		$.each( classes, function( key ) {
			var current = instance.options.classes[ key ] || "";
			current = $.trim( current.replace( controlgroupCornerRegex, "" ) );
			result[ key ] = ( current + " " + classes[ key ] ).replace( /\s+/g, " " );
		} );
		return result;
	},

	_setOption: function( key, value ) {
		if ( key === "direction" ) {
			this._removeClass( "ui-controlgroup-" + this.options.direction );
		}

		this._super( key, value );
		if ( key === "disabled" ) {
			this._callChildMethod( value ? "disable" : "enable" );
			return;
		}

		this.refresh();
	},

	refresh: function() {
		var children,
			that = this;

		this._addClass( "ui-controlgroup ui-controlgroup-" + this.options.direction );

		if ( this.options.direction === "horizontal" ) {
			this._addClass( null, "ui-helper-clearfix" );
		}
		this._initWidgets();

		children = this.childWidgets;

		// We filter here because we need to track all childWidgets not just the visible ones
		if ( this.options.onlyVisible ) {
			children = children.filter( ":visible" );
		}

		if ( children.length ) {

			// We do this last because we need to make sure all enhancment is done
			// before determining first and last
			$.each( [ "first", "last" ], function( index, value ) {
				var instance = children[ value ]().data( "ui-controlgroup-data" );

				if ( instance && that[ "_" + instance.widgetName + "Options" ] ) {
					var options = that[ "_" + instance.widgetName + "Options" ](
						children.length === 1 ? "only" : value
					);
					options.classes = that._resolveClassesValues( options.classes, instance );
					instance.element[ instance.widgetName ]( options );
				} else {
					that._updateCornerClass( children[ value ](), value );
				}
			} );

			// Finally call the refresh method on each of the child widgets.
			this._callChildMethod( "refresh" );
		}
	}
} );
} ) );

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/escape-selector',[ "jquery", "./version" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} ( function( $ ) {

// Internal use only
return $.ui.escapeSelector = ( function() {
	var selectorEscape = /([!"#$%&'()*+,./:;<=>?@[\]^`{|}~])/g;
	return function( selector ) {
		return selector.replace( selectorEscape, "\\$1" );
	};
} )();

} ) );

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/form',[ "jquery", "./version" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} ( function( $ ) {

// Support: IE8 Only
// IE8 does not support the form attribute and when it is supplied. It overwrites the form prop
// with a string, so we need to find the proper form.
return $.fn.form = function() {
	return typeof this[ 0 ].form === "string" ? this.closest( "form" ) : $( this[ 0 ].form );
};

} ) );

/*!
 * jQuery UI Form Reset Mixin 1.12.1
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Form Reset Mixin
//>>group: Core
//>>description: Refresh input widgets when their form is reset
//>>docs: http://api.jqueryui.com/form-reset-mixin/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/form-reset-mixin',[
			"jquery",
			"./form",
			"./version"
		], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
}( function( $ ) {

return $.ui.formResetMixin = {
	_formResetHandler: function() {
		var form = $( this );

		// Wait for the form reset to actually happen before refreshing
		setTimeout( function() {
			var instances = form.data( "ui-form-reset-instances" );
			$.each( instances, function() {
				this.refresh();
			} );
		} );
	},

	_bindFormResetHandler: function() {
		this.form = this.element.form();
		if ( !this.form.length ) {
			return;
		}

		var instances = this.form.data( "ui-form-reset-instances" ) || [];
		if ( !instances.length ) {

			// We don't use _on() here because we use a single event handler per form
			this.form.on( "reset.ui-form-reset", this._formResetHandler );
		}
		instances.push( this );
		this.form.data( "ui-form-reset-instances", instances );
	},

	_unbindFormResetHandler: function() {
		if ( !this.form.length ) {
			return;
		}

		var instances = this.form.data( "ui-form-reset-instances" );
		instances.splice( $.inArray( this, instances ), 1 );
		if ( instances.length ) {
			this.form.data( "ui-form-reset-instances", instances );
		} else {
			this.form
				.removeData( "ui-form-reset-instances" )
				.off( "reset.ui-form-reset" );
		}
	}
};

} ) );

/*!
 * jQuery UI Labels 1.12.1
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: labels
//>>group: Core
//>>description: Find all the labels associated with a given input
//>>docs: http://api.jqueryui.com/labels/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/labels',[ "jquery", "./version", "./escape-selector" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} ( function( $ ) {

return $.fn.labels = function() {
	var ancestor, selector, id, labels, ancestors;

	// Check control.labels first
	if ( this[ 0 ].labels && this[ 0 ].labels.length ) {
		return this.pushStack( this[ 0 ].labels );
	}

	// Support: IE <= 11, FF <= 37, Android <= 2.3 only
	// Above browsers do not support control.labels. Everything below is to support them
	// as well as document fragments. control.labels does not work on document fragments
	labels = this.eq( 0 ).parents( "label" );

	// Look for the label based on the id
	id = this.attr( "id" );
	if ( id ) {

		// We don't search against the document in case the element
		// is disconnected from the DOM
		ancestor = this.eq( 0 ).parents().last();

		// Get a full set of top level ancestors
		ancestors = ancestor.add( ancestor.length ? ancestor.siblings() : this.siblings() );

		// Create a selector for the label based on the id
		selector = "label[for='" + $.ui.escapeSelector( id ) + "']";

		labels = labels.add( ancestors.find( selector ).addBack( selector ) );

	}

	// Return whatever we have found for labels
	return this.pushStack( labels );
};

} ) );

/*!
 * jQuery UI Checkboxradio 1.12.1
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Checkboxradio
//>>group: Widgets
//>>description: Enhances a form with multiple themeable checkboxes or radio buttons.
//>>docs: http://api.jqueryui.com/checkboxradio/
//>>demos: http://jqueryui.com/checkboxradio/
//>>css.structure: ../../themes/base/core.css
//>>css.structure: ../../themes/base/button.css
//>>css.structure: ../../themes/base/checkboxradio.css
//>>css.theme: ../../themes/base/theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/widgets/checkboxradio',[
			"jquery",
			"../escape-selector",
			"../form-reset-mixin",
			"../labels",
			"../widget"
		], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
}( function( $ ) {

$.widget( "ui.checkboxradio", [ $.ui.formResetMixin, {
	version: "1.12.1",
	options: {
		disabled: null,
		label: null,
		icon: true,
		classes: {
			"ui-checkboxradio-label": "ui-corner-all",
			"ui-checkboxradio-icon": "ui-corner-all"
		}
	},

	_getCreateOptions: function() {
		var disabled, labels;
		var that = this;
		var options = this._super() || {};

		// We read the type here, because it makes more sense to throw a element type error first,
		// rather then the error for lack of a label. Often if its the wrong type, it
		// won't have a label (e.g. calling on a div, btn, etc)
		this._readType();

		labels = this.element.labels();

		// If there are multiple labels, use the last one
		this.label = $( labels[ labels.length - 1 ] );
		if ( !this.label.length ) {
			$.error( "No label found for checkboxradio widget" );
		}

		this.originalLabel = "";

		// We need to get the label text but this may also need to make sure it does not contain the
		// input itself.
		this.label.contents().not( this.element[ 0 ] ).each( function() {

			// The label contents could be text, html, or a mix. We concat each element to get a
			// string representation of the label, without the input as part of it.
			that.originalLabel += this.nodeType === 3 ? $( this ).text() : this.outerHTML;
		} );

		// Set the label option if we found label text
		if ( this.originalLabel ) {
			options.label = this.originalLabel;
		}

		disabled = this.element[ 0 ].disabled;
		if ( disabled != null ) {
			options.disabled = disabled;
		}
		return options;
	},

	_create: function() {
		var checked = this.element[ 0 ].checked;

		this._bindFormResetHandler();

		if ( this.options.disabled == null ) {
			this.options.disabled = this.element[ 0 ].disabled;
		}

		this._setOption( "disabled", this.options.disabled );
		this._addClass( "ui-checkboxradio", "ui-helper-hidden-accessible" );
		this._addClass( this.label, "ui-checkboxradio-label", "ui-button ui-widget" );

		if ( this.type === "radio" ) {
			this._addClass( this.label, "ui-checkboxradio-radio-label" );
		}

		if ( this.options.label && this.options.label !== this.originalLabel ) {
			this._updateLabel();
		} else if ( this.originalLabel ) {
			this.options.label = this.originalLabel;
		}

		this._enhance();

		if ( checked ) {
			this._addClass( this.label, "ui-checkboxradio-checked", "ui-state-active" );
			if ( this.icon ) {
				this._addClass( this.icon, null, "ui-state-hover" );
			}
		}

		this._on( {
			change: "_toggleClasses",
			focus: function() {
				this._addClass( this.label, null, "ui-state-focus ui-visual-focus" );
			},
			blur: function() {
				this._removeClass( this.label, null, "ui-state-focus ui-visual-focus" );
			}
		} );
	},

	_readType: function() {
		var nodeName = this.element[ 0 ].nodeName.toLowerCase();
		this.type = this.element[ 0 ].type;
		if ( nodeName !== "input" || !/radio|checkbox/.test( this.type ) ) {
			$.error( "Can't create checkboxradio on element.nodeName=" + nodeName +
				" and element.type=" + this.type );
		}
	},

	// Support jQuery Mobile enhanced option
	_enhance: function() {
		this._updateIcon( this.element[ 0 ].checked );
	},

	widget: function() {
		return this.label;
	},

	_getRadioGroup: function() {
		var group;
		var name = this.element[ 0 ].name;
		var nameSelector = "input[name='" + $.ui.escapeSelector( name ) + "']";

		if ( !name ) {
			return $( [] );
		}

		if ( this.form.length ) {
			group = $( this.form[ 0 ].elements ).filter( nameSelector );
		} else {

			// Not inside a form, check all inputs that also are not inside a form
			group = $( nameSelector ).filter( function() {
				return $( this ).form().length === 0;
			} );
		}

		return group.not( this.element );
	},

	_toggleClasses: function() {
		var checked = this.element[ 0 ].checked;
		this._toggleClass( this.label, "ui-checkboxradio-checked", "ui-state-active", checked );

		if ( this.options.icon && this.type === "checkbox" ) {
			this._toggleClass( this.icon, null, "ui-icon-check ui-state-checked", checked )
				._toggleClass( this.icon, null, "ui-icon-blank", !checked );
		}

		if ( this.type === "radio" ) {
			this._getRadioGroup()
				.each( function() {
					var instance = $( this ).checkboxradio( "instance" );

					if ( instance ) {
						instance._removeClass( instance.label,
							"ui-checkboxradio-checked", "ui-state-active" );
					}
				} );
		}
	},

	_destroy: function() {
		this._unbindFormResetHandler();

		if ( this.icon ) {
			this.icon.remove();
			this.iconSpace.remove();
		}
	},

	_setOption: function( key, value ) {

		// We don't allow the value to be set to nothing
		if ( key === "label" && !value ) {
			return;
		}

		this._super( key, value );

		if ( key === "disabled" ) {
			this._toggleClass( this.label, null, "ui-state-disabled", value );
			this.element[ 0 ].disabled = value;

			// Don't refresh when setting disabled
			return;
		}
		this.refresh();
	},

	_updateIcon: function( checked ) {
		var toAdd = "ui-icon ui-icon-background ";

		if ( this.options.icon ) {
			if ( !this.icon ) {
				this.icon = $( "<span>" );
				this.iconSpace = $( "<span> </span>" );
				this._addClass( this.iconSpace, "ui-checkboxradio-icon-space" );
			}

			if ( this.type === "checkbox" ) {
				toAdd += checked ? "ui-icon-check ui-state-checked" : "ui-icon-blank";
				this._removeClass( this.icon, null, checked ? "ui-icon-blank" : "ui-icon-check" );
			} else {
				toAdd += "ui-icon-blank";
			}
			this._addClass( this.icon, "ui-checkboxradio-icon", toAdd );
			if ( !checked ) {
				this._removeClass( this.icon, null, "ui-icon-check ui-state-checked" );
			}
			this.icon.prependTo( this.label ).after( this.iconSpace );
		} else if ( this.icon !== undefined ) {
			this.icon.remove();
			this.iconSpace.remove();
			delete this.icon;
		}
	},

	_updateLabel: function() {

		// Remove the contents of the label ( minus the icon, icon space, and input )
		var contents = this.label.contents().not( this.element[ 0 ] );
		if ( this.icon ) {
			contents = contents.not( this.icon[ 0 ] );
		}
		if ( this.iconSpace ) {
			contents = contents.not( this.iconSpace[ 0 ] );
		}
		contents.remove();

		this.label.append( this.options.label );
	},

	refresh: function() {
		var checked = this.element[ 0 ].checked,
			isDisabled = this.element[ 0 ].disabled;

		this._updateIcon( checked );
		this._toggleClass( this.label, "ui-checkboxradio-checked", "ui-state-active", checked );
		if ( this.options.label !== null ) {
			this._updateLabel();
		}

		if ( isDisabled !== this.options.disabled ) {
			this._setOptions( { "disabled": isDisabled } );
		}
	}

} ] );

return $.ui.checkboxradio;

} ) );

/*!
 * jQuery UI Button 1.12.1
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Button
//>>group: Widgets
//>>description: Enhances a form with themeable buttons.
//>>docs: http://api.jqueryui.com/button/
//>>demos: http://jqueryui.com/button/
//>>css.structure: ../../themes/base/core.css
//>>css.structure: ../../themes/base/button.css
//>>css.theme: ../../themes/base/theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/widgets/button',[
			"jquery",

			// These are only for backcompat
			// TODO: Remove after 1.12
			"./controlgroup",
			"./checkboxradio",

			"../keycode",
			"../widget"
		], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
}( function( $ ) {

$.widget( "ui.button", {
	version: "1.12.1",
	defaultElement: "<button>",
	options: {
		classes: {
			"ui-button": "ui-corner-all"
		},
		disabled: null,
		icon: null,
		iconPosition: "beginning",
		label: null,
		showLabel: true
	},

	_getCreateOptions: function() {
		var disabled,

			// This is to support cases like in jQuery Mobile where the base widget does have
			// an implementation of _getCreateOptions
			options = this._super() || {};

		this.isInput = this.element.is( "input" );

		disabled = this.element[ 0 ].disabled;
		if ( disabled != null ) {
			options.disabled = disabled;
		}

		this.originalLabel = this.isInput ? this.element.val() : this.element.html();
		if ( this.originalLabel ) {
			options.label = this.originalLabel;
		}

		return options;
	},

	_create: function() {
		if ( !this.option.showLabel & !this.options.icon ) {
			this.options.showLabel = true;
		}

		// We have to check the option again here even though we did in _getCreateOptions,
		// because null may have been passed on init which would override what was set in
		// _getCreateOptions
		if ( this.options.disabled == null ) {
			this.options.disabled = this.element[ 0 ].disabled || false;
		}

		this.hasTitle = !!this.element.attr( "title" );

		// Check to see if the label needs to be set or if its already correct
		if ( this.options.label && this.options.label !== this.originalLabel ) {
			if ( this.isInput ) {
				this.element.val( this.options.label );
			} else {
				this.element.html( this.options.label );
			}
		}
		this._addClass( "ui-button", "ui-widget" );
		this._setOption( "disabled", this.options.disabled );
		this._enhance();

		if ( this.element.is( "a" ) ) {
			this._on( {
				"keyup": function( event ) {
					if ( event.keyCode === $.ui.keyCode.SPACE ) {
						event.preventDefault();

						// Support: PhantomJS <= 1.9, IE 8 Only
						// If a native click is available use it so we actually cause navigation
						// otherwise just trigger a click event
						if ( this.element[ 0 ].click ) {
							this.element[ 0 ].click();
						} else {
							this.element.trigger( "click" );
						}
					}
				}
			} );
		}
	},

	_enhance: function() {
		if ( !this.element.is( "button" ) ) {
			this.element.attr( "role", "button" );
		}

		if ( this.options.icon ) {
			this._updateIcon( "icon", this.options.icon );
			this._updateTooltip();
		}
	},

	_updateTooltip: function() {
		this.title = this.element.attr( "title" );

		if ( !this.options.showLabel && !this.title ) {
			this.element.attr( "title", this.options.label );
		}
	},

	_updateIcon: function( option, value ) {
		var icon = option !== "iconPosition",
			position = icon ? this.options.iconPosition : value,
			displayBlock = position === "top" || position === "bottom";

		// Create icon
		if ( !this.icon ) {
			this.icon = $( "<span>" );

			this._addClass( this.icon, "ui-button-icon", "ui-icon" );

			if ( !this.options.showLabel ) {
				this._addClass( "ui-button-icon-only" );
			}
		} else if ( icon ) {

			// If we are updating the icon remove the old icon class
			this._removeClass( this.icon, null, this.options.icon );
		}

		// If we are updating the icon add the new icon class
		if ( icon ) {
			this._addClass( this.icon, null, value );
		}

		this._attachIcon( position );

		// If the icon is on top or bottom we need to add the ui-widget-icon-block class and remove
		// the iconSpace if there is one.
		if ( displayBlock ) {
			this._addClass( this.icon, null, "ui-widget-icon-block" );
			if ( this.iconSpace ) {
				this.iconSpace.remove();
			}
		} else {

			// Position is beginning or end so remove the ui-widget-icon-block class and add the
			// space if it does not exist
			if ( !this.iconSpace ) {
				this.iconSpace = $( "<span> </span>" );
				this._addClass( this.iconSpace, "ui-button-icon-space" );
			}
			this._removeClass( this.icon, null, "ui-wiget-icon-block" );
			this._attachIconSpace( position );
		}
	},

	_destroy: function() {
		this.element.removeAttr( "role" );

		if ( this.icon ) {
			this.icon.remove();
		}
		if ( this.iconSpace ) {
			this.iconSpace.remove();
		}
		if ( !this.hasTitle ) {
			this.element.removeAttr( "title" );
		}
	},

	_attachIconSpace: function( iconPosition ) {
		this.icon[ /^(?:end|bottom)/.test( iconPosition ) ? "before" : "after" ]( this.iconSpace );
	},

	_attachIcon: function( iconPosition ) {
		this.element[ /^(?:end|bottom)/.test( iconPosition ) ? "append" : "prepend" ]( this.icon );
	},

	_setOptions: function( options ) {
		var newShowLabel = options.showLabel === undefined ?
				this.options.showLabel :
				options.showLabel,
			newIcon = options.icon === undefined ? this.options.icon : options.icon;

		if ( !newShowLabel && !newIcon ) {
			options.showLabel = true;
		}
		this._super( options );
	},

	_setOption: function( key, value ) {
		if ( key === "icon" ) {
			if ( value ) {
				this._updateIcon( key, value );
			} else if ( this.icon ) {
				this.icon.remove();
				if ( this.iconSpace ) {
					this.iconSpace.remove();
				}
			}
		}

		if ( key === "iconPosition" ) {
			this._updateIcon( key, value );
		}

		// Make sure we can't end up with a button that has neither text nor icon
		if ( key === "showLabel" ) {
				this._toggleClass( "ui-button-icon-only", null, !value );
				this._updateTooltip();
		}

		if ( key === "label" ) {
			if ( this.isInput ) {
				this.element.val( value );
			} else {

				// If there is an icon, append it, else nothing then append the value
				// this avoids removal of the icon when setting label text
				this.element.html( value );
				if ( this.icon ) {
					this._attachIcon( this.options.iconPosition );
					this._attachIconSpace( this.options.iconPosition );
				}
			}
		}

		this._super( key, value );

		if ( key === "disabled" ) {
			this._toggleClass( null, "ui-state-disabled", value );
			this.element[ 0 ].disabled = value;
			if ( value ) {
				this.element.blur();
			}
		}
	},

	refresh: function() {

		// Make sure to only check disabled if its an element that supports this otherwise
		// check for the disabled class to determine state
		var isDisabled = this.element.is( "input, button" ) ?
			this.element[ 0 ].disabled : this.element.hasClass( "ui-button-disabled" );

		if ( isDisabled !== this.options.disabled ) {
			this._setOptions( { disabled: isDisabled } );
		}

		this._updateTooltip();
	}
} );

// DEPRECATED
if ( $.uiBackCompat !== false ) {

	// Text and Icons options
	$.widget( "ui.button", $.ui.button, {
		options: {
			text: true,
			icons: {
				primary: null,
				secondary: null
			}
		},

		_create: function() {
			if ( this.options.showLabel && !this.options.text ) {
				this.options.showLabel = this.options.text;
			}
			if ( !this.options.showLabel && this.options.text ) {
				this.options.text = this.options.showLabel;
			}
			if ( !this.options.icon && ( this.options.icons.primary ||
					this.options.icons.secondary ) ) {
				if ( this.options.icons.primary ) {
					this.options.icon = this.options.icons.primary;
				} else {
					this.options.icon = this.options.icons.secondary;
					this.options.iconPosition = "end";
				}
			} else if ( this.options.icon ) {
				this.options.icons.primary = this.options.icon;
			}
			this._super();
		},

		_setOption: function( key, value ) {
			if ( key === "text" ) {
				this._super( "showLabel", value );
				return;
			}
			if ( key === "showLabel" ) {
				this.options.text = value;
			}
			if ( key === "icon" ) {
				this.options.icons.primary = value;
			}
			if ( key === "icons" ) {
				if ( value.primary ) {
					this._super( "icon", value.primary );
					this._super( "iconPosition", "beginning" );
				} else if ( value.secondary ) {
					this._super( "icon", value.secondary );
					this._super( "iconPosition", "end" );
				}
			}
			this._superApply( arguments );
		}
	} );

	$.fn.button = ( function( orig ) {
		return function() {
			if ( !this.length || ( this.length && this[ 0 ].tagName !== "INPUT" ) ||
					( this.length && this[ 0 ].tagName === "INPUT" && (
						this.attr( "type" ) !== "checkbox" && this.attr( "type" ) !== "radio"
					) ) ) {
				return orig.apply( this, arguments );
			}
			if ( !$.ui.checkboxradio ) {
				$.error( "Checkboxradio widget missing" );
			}
			if ( arguments.length === 0 ) {
				return this.checkboxradio( {
					"icon": false
				} );
			}
			return this.checkboxradio.apply( this, arguments );
		};
	} )( $.fn.button );

	$.fn.buttonset = function() {
		if ( !$.ui.controlgroup ) {
			$.error( "Controlgroup widget missing" );
		}
		if ( arguments[ 0 ] === "option" && arguments[ 1 ] === "items" && arguments[ 2 ] ) {
			return this.controlgroup.apply( this,
				[ arguments[ 0 ], "items.button", arguments[ 2 ] ] );
		}
		if ( arguments[ 0 ] === "option" && arguments[ 1 ] === "items" ) {
			return this.controlgroup.apply( this, [ arguments[ 0 ], "items.button" ] );
		}
		if ( typeof arguments[ 0 ] === "object" && arguments[ 0 ].items ) {
			arguments[ 0 ].items = {
				button: arguments[ 0 ].items
			};
		}
		return this.controlgroup.apply( this, arguments );
	};
}

return $.ui.button;

} ) );

/*!
 * jQuery Mobile Button @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Mobile Button
//>>group: Forms
//>>description: Consistent styling for native butttons.
//>>docs: http://api.jquerymobile.com/button/
//>>demos: http://demos.jquerymobile.com/@VERSION/button/
//>>css.structure: ../css/structure/jquery.mobile.forms.slider.tooltip.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/button',[
			"jquery",
			"../../core",
			"../../widget",
			"../widget.theme",
			"jquery-ui/widgets/button" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.widget( "ui.button", $.ui.button, {
	options: {
		enhanced: false,
		theme: null
	},

	_enhance: function() {
		if ( !this.options.enhanced ) {
			this._super();
		} else if ( this.options.icon ) {
			this.icon = this.element.find( "ui-button-icon" );
		}
	},

	_themeElements: function() {
		this.options.theme = this.options.theme ? this.options.theme : "inherit";

		return [
			{
				element: this.widget(),
				prefix: "ui-button-"
			}
		];
	}
} );

$.widget( "ui.button", $.ui.button, $.mobile.widget.theme );

$.ui.button.prototype.options.classes = {
	"ui-button": "ui-shadow ui-corner-all"
};

return $.ui.button;

} );

/*!
 * jQuery Mobile Navbar @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Navbars
//>>group: Widgets
//>>description: Formats groups of links as horizontal navigation bars.
//>>docs: http://api.jquerymobile.com/navbar/
//>>demos: http://demos.jquerymobile.com/@VERSION/navbar/
//>>css.structure: ../css/structure/jquery.mobile.navbar.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/navbar',[
			"jquery",
			"./forms/button",
			"../widget" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.widget( "mobile.navbar", {
	version: "@VERSION",

	options: {
		iconpos: "top",
		maxbutton: 5
	},

	_create: function() {
		var that = this;
		var navbar = that.element;
		var navButtons = navbar.find( "a" );
		var numButtons = navButtons.length;
		var maxButton = this.options.maxbutton;
		var iconpos = navButtons.filter( ":jqmData(icon)" ).length ?
				that.options.iconpos : undefined;

		navbar.addClass( "ui-navbar" )
			.attr( "role", "navigation" )
			.find( "ul" );

		this.navbar = navbar;
		this.navButtons = navButtons;
		this.numButtons = numButtons;
		this.maxButton = maxButton;
		this.iconpos = iconpos;

		 if ( numButtons <= maxButton ) {
			navButtons.each( function() {
				that._makeNavButton( this, iconpos );
			} );
		} else {
			this._createNavRows();
		}

	},

	_createNavRows: function() {
		var rowCount;
		var row;
		var pos;
		var buttonItem;
		var overflowNav;
		var navRow;
		var navItems = this.navbar.find( "li" );
		var buttonCount = this.numButtons;
		var maxButton = this.maxButton;

		rowCount = ( buttonCount % maxButton ) === 0 ?
						( buttonCount / maxButton ) :
						Math.floor( buttonCount / maxButton ) + 1;

		// Prep for new rows
		for ( pos = 1; pos < rowCount ; pos++ ) {
			navRow = $( "<ul>" );
			this._addClass( navRow, "ui-navbar-row ui-navbar-row-" + pos );
			navRow.appendTo( this.navbar );
		}

		// Enhance buttons and move to new rows
		for ( pos = 0; pos < buttonCount ; pos++ ) {
			buttonItem = navItems.eq( pos );
			this._makeNavButton( buttonItem.find( "a" ), this.iconpos );
			if ( pos + 1 > maxButton ) {
				buttonItem.detach();
				row = ( ( pos + 1 ) % maxButton ) === 0 ?
						Math.floor( ( pos ) / maxButton ) :
						Math.floor( ( pos + 1 ) / maxButton );
				overflowNav = "ul.ui-navbar-row-" + row;
				this.navbar.find( overflowNav ).append( buttonItem );
			}
		}
	},

	_makeNavButton: function( button, iconpos ) {
		var isDisabled = false;
		if ( $( button ).hasClass( "ui-state-disabled" ) ) {
			isDisabled = true;
		}
		$( button ).button( {
			iconPosition: iconpos,
			disabled: isDisabled
		 } );
	},

	refresh: function() {
		var that = this;

		this.navButtons = this.navbar.find( "a" );
		this.numButtons = this.navButtons.length;

		this._addClass( this.navbar, "ui-navbar" );
		this.navbar.attr( "role", "navigation" )
			.find( "ul" );

		 if ( this.numButtons <= this.maxButton ) {
			this.navButtons.each( function() {
				that._makeNavButton( this, that.iconpos );
			} );
		} else {
			this._createNavRows();
		}
	},

	_destroy: function() {
		var navrows;

		if ( this.numButtons > this.maxButton ) {
			navrows = this.navbar.find( ".ui-navbar-row li" ).detach();
			$( ".ui-navbar-row" ).remove();
			this.navbar.find( "ul" ).append( navrows );
		}

		this._removeClass( this.navbar, "ui-navbar" );

		this.navButtons.each( function() {
			$( this ).button( "destroy" );
		} );
	}
} );

} );

/*!
 * jQuery Mobile Navbar @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Navbar Backcompat
//>>group: Widgets
//>>description: Packcompat Formats groups of links as horizontal navigation bars.
//>>docs: http://api.jquerymobile.com/navbar/
//>>demos: http://demos.jquerymobile.com/@VERSION/navbar/
//>>css.structure: ../css/structure/jquery.mobile.navbar.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css
( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/navbar.backcompat',[
			"jquery",
			"./navbar",
			"./widget.backcompat" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {
	return $.widget( "mobile.navbar", $.mobile.navbar, {
		_create: function() {
			var that = this;
			this._super();

			// Deprecated in 1.5
			that._on( that.element, {
				"vclick a": function( event ) {
					var activeBtn = $( event.target );

					if ( !( activeBtn.hasClass( "ui-state-disabled" ) ||
						activeBtn.hasClass( "ui-button-active" ) ) ) {

						that.navButtons.removeClass( "ui-button-active" );
						activeBtn.addClass( "ui-button-active" );

						// The code below is a workaround to fix #1181
						$( document ).one( "pagehide", function() {
							activeBtn.removeClass( "ui-button-active" );
						} );
					}
				}
			} );

			// Deprecated in 1.5
			// Buttons in the navbar with ui-state-persist class should
			// regain their active state before page show
			that.navbar.closest( ".ui-page" ).bind( "pagebeforeshow", function() {
				that.navButtons.filter( ".ui-state-persist" ).addClass( "ui-button-active" );
			} );
		}
	} );
}

} );

/*!
 * jQuery UI Focusable 1.12.1
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: :focusable Selector
//>>group: Core
//>>description: Selects elements which can be focused.
//>>docs: http://api.jqueryui.com/focusable-selector/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/focusable',[ "jquery", "./version" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} ( function( $ ) {

// Selectors
$.ui.focusable = function( element, hasTabindex ) {
	var map, mapName, img, focusableIfVisible, fieldset,
		nodeName = element.nodeName.toLowerCase();

	if ( "area" === nodeName ) {
		map = element.parentNode;
		mapName = map.name;
		if ( !element.href || !mapName || map.nodeName.toLowerCase() !== "map" ) {
			return false;
		}
		img = $( "img[usemap='#" + mapName + "']" );
		return img.length > 0 && img.is( ":visible" );
	}

	if ( /^(input|select|textarea|button|object)$/.test( nodeName ) ) {
		focusableIfVisible = !element.disabled;

		if ( focusableIfVisible ) {

			// Form controls within a disabled fieldset are disabled.
			// However, controls within the fieldset's legend do not get disabled.
			// Since controls generally aren't placed inside legends, we skip
			// this portion of the check.
			fieldset = $( element ).closest( "fieldset" )[ 0 ];
			if ( fieldset ) {
				focusableIfVisible = !fieldset.disabled;
			}
		}
	} else if ( "a" === nodeName ) {
		focusableIfVisible = element.href || hasTabindex;
	} else {
		focusableIfVisible = hasTabindex;
	}

	return focusableIfVisible && $( element ).is( ":visible" ) && visible( $( element ) );
};

// Support: IE 8 only
// IE 8 doesn't resolve inherit to visible/hidden for computed values
function visible( element ) {
	var visibility = element.css( "visibility" );
	while ( visibility === "inherit" ) {
		element = element.parent();
		visibility = element.css( "visibility" );
	}
	return visibility !== "hidden";
}

$.extend( $.expr[ ":" ], {
	focusable: function( element ) {
		return $.ui.focusable( element, $.attr( element, "tabindex" ) != null );
	}
} );

return $.ui.focusable;

} ) );

/*!
 * jQuery Mobile Popup @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Popups
//>>group: Widgets
//>>description: Popup windows
//>>docs: http://api.jquerymobile.com/popup/
//>>demos: http://demos.jquerymobile.com/@VERSION/popup/
//>>css.structure: ../css/structure/jquery.mobile.popup.css
//>>css.structure: ../css/structure/jquery.mobile.transition.css
//>>css.structure: ../css/structure/jquery.mobile.transition.fade.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

// Lessons:
// You must remove nav bindings even if there is no history. Make sure you
// remove nav bindings in the same frame as the beginning of the close process
// if there is no history. If there is history, remove nav bindings from the nav
// bindings handler - that way, only one of them can fire per close process.

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/popup',[
			"jquery",
			"jquery-ui/focusable",
			"jquery-ui/safe-active-element",
			"jquery-ui/safe-blur",
			"../widget",
			"./widget.theme",
			"../support",
			"../events/navigate",
			"../navigation/path",
			"../navigation/history",
			"../navigation/navigator",
			"../navigation/method",
			"../animationComplete",
			"../navigation" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

function pointInRectangle( x, y, windowCoordinates ) {
	return ( x >= windowCoordinates.x && x <= windowCoordinates.x + windowCoordinates.cx &&
		y >= windowCoordinates.y && y <= windowCoordinates.y + windowCoordinates.cy );
}

function isOutOfSight( element, windowCoordinates ) {
	var offset = element.offset(),
		width = element.outerWidth( true ),
		height = element.outerHeight( true );

	return !(
		pointInRectangle( offset.left, offset.top, windowCoordinates ) ||
		pointInRectangle( offset.left + width, offset.top, windowCoordinates ) ||
		pointInRectangle( offset.left + width, offset.top + height, windowCoordinates ) ||
		pointInRectangle( offset.left, offset.top + height, windowCoordinates ) );
}

function fitSegmentInsideSegment( windowSize, segmentSize, offset, desired ) {
	var returnValue = desired;

	if ( windowSize < segmentSize ) {

		// Center segment if it's bigger than the window
		returnValue = offset + ( windowSize - segmentSize ) / 2;
	} else {

		// Else center it at the desired coordinate while keeping it completely inside the window
		returnValue = Math.min( Math.max( offset, desired - segmentSize / 2 ),
			offset + windowSize - segmentSize );
	}

	return returnValue;
}

function getWindowCoordinates( theWindow ) {
	return {
		x: theWindow.scrollLeft(),
		y: theWindow.scrollTop(),
		cx: ( theWindow[ 0 ].innerWidth || theWindow.width() ),
		cy: ( theWindow[ 0 ].innerHeight || theWindow.height() )
	};
}

var hook = function() {

	// Links within content areas, tests included with page
	$( this )
		.find( "a" )
		.jqmEnhanceable()
		.filter( ":jqmData(rel='popup')[href][href!='']" )
		.each( function() {

			// Accessibility info for popups
			var element = this,
				idref = element.getAttribute( "href" ).substring( 1 );

			if ( idref ) {
				element.setAttribute( "aria-haspopup", true );
				element.setAttribute( "aria-owns", idref );
				element.setAttribute( "aria-expanded", false );
			}
		} );
};

( $.enhance = $.extend( $.enhance, $.extend( { hooks: [] }, $.enhance ) ) ).hooks.push( hook );

$.widget( "mobile.popup", {
	version: "@VERSION",

	options: {
		classes: {
			"ui-popup": "ui-corner-all ui-overlay-shadow"
		},
		theme: "inherit",
		overlayTheme: "inherit",
		transition: "none",
		positionTo: "origin",
		tolerance: null,
		closeLinkSelector: "a[data-ui-rel='back']",
		dismissible: true,
		enhanced: false,

		// NOTE Windows Phone 7 - 8.1 has a scroll position caching issue that requires us to
		//      disable popup history management by default
		//      https://github.com/jquery/jquery-mobile/issues/4784
		//
		// NOTE this option is modified in _create!
		history: !( $.mobile.browser.oldIE || $.mobile.browser.newIEMobile )
	},

	// When the user depresses the mouse/finger on an element inside the popup while the popup is
	// open, we ignore resize events for a short while. This prevents #6961.
	_handleDocumentVmousedown: function( theEvent ) {
		if ( this._isOpen && $.contains( this._ui.container[ 0 ], theEvent.target ) ) {
			this._ignoreResizeEvents();
		}
	},

	_create: function() {
		var theElement = this.element,
			myId = theElement.attr( "id" ),
			currentOptions = this.options;

		// We need to adjust the history option to be false if there's no AJAX nav. We can't do it
		// in the option declarations because those are run before it is determined whether there
		// shall be AJAX nav.
		currentOptions.history = currentOptions.history && $.mobile.ajaxEnabled &&
			$.mobile.hashListeningEnabled;

		// Define instance variables
		$.extend( this, {
			_scrollTop: 0,
			_page: theElement.closest( ".ui-page" ),
			_ui: null,
			_fallbackTransition: "",
			_currentTransition: false,
			_prerequisites: null,
			_isOpen: false,
			_tolerance: null,
			_resizeData: null,
			_ignoreResizeTo: 0,
			_orientationchangeInProgress: false
		} );

		if ( this._page.length === 0 ) {
			this._page = $( "body" );
		}

		if ( currentOptions.enhanced ) {
			this._ui = {
				container: theElement.parent(),
				screen: theElement.parent().prev(),
				placeholder: $( this.document[ 0 ].getElementById( myId + "-placeholder" ) )
			};

			// We call _addClasses() even though as per the requirements for enhanced: true, the
			// classes should already be added, because we want tracking for the classes. This
			// should not result in reflows, because the class attribute will be set to a value it
			// already has.
			this._addClasses();
		} else {
			this._enhance();
			this._applyTransition( currentOptions.transition );
		}
		this
			._setTolerance( currentOptions.tolerance )
			._ui.focusElement = this._ui.container;

		// Event handlers
		this._on( this._ui.screen, { "vclick": "_eatEventAndClose" } );
		this._on( this.window, {
			orientationchange: "_handleWindowOrientationchange",
			resize: "_handleWindowResize",
			keyup: "_handleWindowKeyUp"
		} );
		this._on( this.document, {
			vmousedown: "_handleDocumentVmousedown",
			focusin: "_handleDocumentFocusIn"
		} );
	},

	_themeElements: function() {
		return [
			{
				element: this._ui.screen,
				prefix: "ui-overlay-",
				option: "overlayTheme"
			},
			{
				element: this.element,
				prefix: "ui-body-"
			}
		];
	},

	_addClasses: function() {
		this._addClass( this._ui.placeholder, null, "ui-screen-hidden" );
		this._addClass( this._ui.screen, "ui-popup-screen", "ui-screen-hidden" );
		this._addClass( this._ui.container,
			"ui-popup-container ui-popup-hidden ui-popup-truncate" );
		this._addClass( "ui-popup" );
	},

	_enhance: function() {
		var myId = this.element.attr( "id" ),
			placeholder = "placeholder",
			ui = {
				screen: $( "<div>" ),
				placeholder: $( "<div>" ),
				container: $( "<div>" )
			},
			fragment = this.document[ 0 ].createDocumentFragment();

		this._ui = ui;

		fragment.appendChild( ui.screen[ 0 ] );
		fragment.appendChild( ui.container[ 0 ] );

		if ( myId ) {
			ui.screen.attr( "id", myId + "-screen" );
			ui.container.attr( "id", myId + "-popup" );
			ui.placeholder.attr( "id", myId + "-placeholder" );
			placeholder = "placeholder for " + myId;
		}

		// Using a wonderful golfing tradition we leave a token where the element used to be so we
		// know where to put it back upon _destroy()
		ui.placeholder.html( "<!-- " + placeholder + " -->" ).insertBefore( this.element );

		// We detach the payload, add the classes, and then insert it into the popup container
		this.element.detach();

		// This._ui needs to be fully established before we can all this._addClasses(), because
		// this._addClasses() uses this._ui, but we don't want any of the UI elements to be
		// attached, so as to avoid reflows as this._addClasses() adds classes.
		this._addClasses();

		// Apply the proto
		this.element.appendTo( ui.container );
		this._page[ 0 ].appendChild( fragment );

		return ui;
	},

	_eatEventAndClose: function( theEvent ) {
		theEvent.preventDefault();
		theEvent.stopImmediatePropagation();
		if ( this.options.dismissible ) {
			this.close();
		}
		return false;
	},

	// Make sure the screen covers the entire document - CSS is sometimes not enough for
	// accomplishing this.
	_resizeScreen: function() {
		var screen = this._ui.screen,
			popupHeight = this._ui.container.outerHeight( true ),
			screenHeight = screen.removeAttr( "style" ).height(),

			// Subtracting 1 here is necessary for an obscure Android 4.0 bug where the browser
			// hangs if the screen covers the entire document :/
			documentHeight = this.document.height() - 1;

		if ( screenHeight < documentHeight ) {
			screen.height( documentHeight );
		} else if ( popupHeight > screenHeight ) {
			screen.height( popupHeight );
		}
	},

	_handleWindowKeyUp: function( theEvent ) {
		if ( this._isOpen && theEvent.keyCode === $.mobile.keyCode.ESCAPE ) {
			return this._eatEventAndClose( theEvent );
		}
	},

	_expectResizeEvent: function() {
		var windowCoordinates = getWindowCoordinates( this.window );

		if ( this._resizeData ) {
			if ( windowCoordinates.x === this._resizeData.windowCoordinates.x &&
					windowCoordinates.y === this._resizeData.windowCoordinates.y &&
					windowCoordinates.cx === this._resizeData.windowCoordinates.cx &&
					windowCoordinates.cy === this._resizeData.windowCoordinates.cy ) {

				// Timeout not refreshed
				return false;
			} else {

				// Clear existing timeout - it will be refreshed below
				clearTimeout( this._resizeData.timeoutId );
			}
		}

		this._resizeData = {
			timeoutId: this._delay( "_resizeTimeout", 200 ),
			windowCoordinates: windowCoordinates
		};

		return true;
	},

	_resizeTimeout: function() {
		if ( this._isOpen ) {
			if ( !this._expectResizeEvent() ) {
				if ( this._ui.container.hasClass( "ui-popup-hidden" ) ) {

					// Effectively rapid-open the popup while leaving the screen intact
					this._removeClass( this._ui.container, "ui-popup-hidden ui-popup-truncate" );
					this.reposition( { positionTo: "window" } );
					this._ignoreResizeEvents();
				}

				this._resizeScreen();
				this._resizeData = null;
				this._orientationchangeInProgress = false;
			}
		} else {
			this._resizeData = null;
			this._orientationchangeInProgress = false;
		}
	},

	_stopIgnoringResizeEvents: function() {
		this._ignoreResizeTo = 0;
	},

	_ignoreResizeEvents: function() {
		if ( this._ignoreResizeTo ) {
			clearTimeout( this._ignoreResizeTo );
		}
		this._ignoreResizeTo = this._delay( "_stopIgnoringResizeEvents", 1000 );
	},

	_handleWindowResize: function( /* theEvent */ ) {
		if ( this._isOpen && this._ignoreResizeTo === 0 ) {
			if ( isOutOfSight( this._ui.container, getWindowCoordinates( this.window ) ) &&
					( this._expectResizeEvent() || this._orientationchangeInProgress ) &&
					!this._ui.container.hasClass( "ui-popup-hidden" ) ) {

				// Effectively rapid-close the popup while leaving the screen intact
				this._addClass( this._ui.container, "ui-popup-hidden ui-popup-truncate" );
				this._ui.container.removeAttr( "style" );
			}
		}
	},

	_handleWindowOrientationchange: function( /* theEvent */ ) {
		if ( !this._orientationchangeInProgress && this._isOpen && this._ignoreResizeTo === 0 ) {
			this._expectResizeEvent();
			this._orientationchangeInProgress = true;
		}
	},

	// When the popup is open, attempting to focus on an element that is not a child of the popup
	// will redirect focus to the popup
	_handleDocumentFocusIn: function( theEvent ) {
		var target,
			targetElement = theEvent.target,
			ui = this._ui;

		if ( !this._isOpen ) {
			return;
		}

		if ( targetElement !== ui.container[ 0 ] ) {
			target = $( targetElement );
			if ( !$.contains( ui.container[ 0 ], targetElement ) ) {
				$( $.ui.safeActiveElement( this.document[ 0 ] ) ).one( "focus",
					$.proxy( function() {
						$.ui.safeBlur( targetElement );
					}, this ) );
				ui.focusElement.focus();
				theEvent.preventDefault();
				theEvent.stopImmediatePropagation();
				return false;
			} else if ( ui.focusElement[ 0 ] === ui.container[ 0 ] ) {
				ui.focusElement = target;
			}
		}

		this._ignoreResizeEvents();
	},

	_applyTransition: function( value ) {
		if ( value ) {
			this._removeClass( this._ui.container, null, this._fallbackTransition );
			if ( value !== "none" ) {
				this._fallbackTransition = $.mobile._maybeDegradeTransition( value );
				if ( this._fallbackTransition === "none" ) {
					this._fallbackTransition = "";
				}
				this._addClass( this._ui.container, null, this._fallbackTransition );
			}
		}

		return this;
	},

	_setOptions: function( newOptions ) {
		if ( newOptions.transition !== undefined ) {
			if ( !this._currentTransition ) {
				this._applyTransition( newOptions.transition );
			}
		}

		if ( newOptions.tolerance !== undefined ) {
			this._setTolerance( newOptions.tolerance );
		}

		if ( newOptions.disabled !== undefined ) {
			if ( newOptions.disabled ) {
				this.close();
			}
		}

		return this._super( newOptions );
	},

	_setTolerance: function( value ) {
		var tol = { t: 30, r: 15, b: 30, l: 15 },
			ar;

		if ( value !== undefined ) {
			ar = String( value ).split( "," );

			$.each( ar, function( idx, val ) {
				ar[ idx ] = parseInt( val, 10 );
			} );

			switch ( ar.length ) {

			// All values are to be the same
			case 1:
				if ( !isNaN( ar[ 0 ] ) ) {
					tol.t = tol.r = tol.b = tol.l = ar[ 0 ];
				}
				break;

			// The first value denotes top/bottom tolerance, and the second value denotes
			// left/right tolerance
			case 2:
				if ( !isNaN( ar[ 0 ] ) ) {
					tol.t = tol.b = ar[ 0 ];
				}
				if ( !isNaN( ar[ 1 ] ) ) {
					tol.l = tol.r = ar[ 1 ];
				}
				break;

			// The array contains values in the order top, right, bottom, left
			case 4:
				if ( !isNaN( ar[ 0 ] ) ) {
					tol.t = ar[ 0 ];
				}
				if ( !isNaN( ar[ 1 ] ) ) {
					tol.r = ar[ 1 ];
				}
				if ( !isNaN( ar[ 2 ] ) ) {
					tol.b = ar[ 2 ];
				}
				if ( !isNaN( ar[ 3 ] ) ) {
					tol.l = ar[ 3 ];
				}
				break;

			default:
				break;
			}
		}

		this._tolerance = tol;
		return this;
	},

	_clampPopupWidth: function( infoOnly ) {
		var menuSize,
			windowCoordinates = getWindowCoordinates( this.window ),

			// Rectangle within which the popup must fit
			rectangle = {
				x: this._tolerance.l,
				y: windowCoordinates.y + this._tolerance.t,
				cx: windowCoordinates.cx - this._tolerance.l - this._tolerance.r,
				cy: windowCoordinates.cy - this._tolerance.t - this._tolerance.b
			};

		if ( !infoOnly ) {

			// Clamp the width of the menu before grabbing its size
			this._ui.container.css( "max-width", rectangle.cx );
		}

		menuSize = {
			cx: this._ui.container.outerWidth( true ),
			cy: this._ui.container.outerHeight( true )
		};

		return { rc: rectangle, menuSize: menuSize };
	},

	_calculateFinalLocation: function( desired, clampInfo ) {
		var returnValue,
			rectangle = clampInfo.rc,
			menuSize = clampInfo.menuSize;

		// Center the menu over the desired coordinates, while not going outside the window
		// tolerances. This will center wrt. the window if the popup is too large.
		returnValue = {
			left: fitSegmentInsideSegment( rectangle.cx, menuSize.cx, rectangle.x, desired.x ),
			top: fitSegmentInsideSegment( rectangle.cy, menuSize.cy, rectangle.y, desired.y )
		};

		// Make sure the top of the menu is visible
		returnValue.top = Math.max( 0, returnValue.top );

		// If the height of the menu is smaller than the height of the document align the bottom
		// with the bottom of the document

		returnValue.top -= Math.min( returnValue.top,
			Math.max( 0, returnValue.top + menuSize.cy - this.document.height() ) );

		return returnValue;
	},

	// Try and center the overlay over the given coordinates
	_placementCoords: function( desired ) {
		return this._calculateFinalLocation( desired, this._clampPopupWidth() );
	},

	_createPrerequisites: function( screenPrerequisite, containerPrerequisite, whenDone ) {
		var prerequisites,
			self = this;

		// It is important to maintain both the local variable prerequisites and
		// self._prerequisites. The local variable remains in the closure of the functions which
		// call the callbacks passed in. The comparison between the local variable and
		// self._prerequisites is necessary, because once a function has been passed to
		// .animationComplete() it will be called next time an animation completes, even if that's
		// not the animation whose end the function was supposed to catch (for example, if an abort
		// happens during the opening animation, the .animationComplete handler is not called for
		// that animation anymore, but the handler remains attached, so it is called the next time
		// the popup is opened - making it stale. Comparing the local variable prerequisites to the
		// widget-level variable self._prerequisites ensures that callbacks triggered by a stale
		// .animationComplete will be ignored.

		prerequisites = {
			screen: $.Deferred(),
			container: $.Deferred()
		};

		prerequisites.screen.done( function() {
			if ( prerequisites === self._prerequisites ) {
				screenPrerequisite();
			}
		} );

		prerequisites.container.done( function() {
			if ( prerequisites === self._prerequisites ) {
				containerPrerequisite();
			}
		} );

		$.when( prerequisites.screen, prerequisites.container ).done( function() {
			if ( prerequisites === self._prerequisites ) {
				self._prerequisites = null;
				whenDone();
			}
		} );

		self._prerequisites = prerequisites;
	},

	_animate: function( args ) {

		// NOTE Before removing the default animation of the screen this had an animate callback
		//      that would resolve the deferred. Now the deferred is resolved immediately
		// TODO Remove the dependency on the screen deferred.
		this._removeClass( this._ui.screen, null, args.classToRemove )
			._addClass( this._ui.screen, null, args.screenClassToAdd );

		args.prerequisites.screen.resolve();

		if ( args.transition && args.transition !== "none" ) {
			if ( args.applyTransition ) {
				this._applyTransition( args.transition );
			}
			if ( this._fallbackTransition ) {
				this._addClass( this._ui.container, null, args.containerClassToAdd )
					._removeClass( this._ui.container, null, args.classToRemove );
				this._ui.container
					.animationComplete( $.proxy( args.prerequisites.container, "resolve" ) );
				return;
			}
		}
		this._removeClass( this._ui.container, null, args.classToRemove );
		args.prerequisites.container.resolve();
	},

	// The desired coordinates passed in will be returned untouched if no reference element can be
	// identified via desiredPosition.positionTo. Nevertheless, this function ensures that its
	// return value always contains valid x and y coordinates by specifying the center middle of
	// the window if the coordinates are absent. options: {
	//   x: coordinate,
	//   y: coordinate,
	//   positionTo: string: "origin", "window", or jQuery selector
	// }
	_desiredCoords: function( openOptions ) {
		var offset,
			dst = null,
			windowCoordinates = getWindowCoordinates( this.window ),
			x = openOptions.x,
			y = openOptions.y,
			pTo = openOptions.positionTo;

		// Establish which element will serve as the reference
		if ( pTo && pTo !== "origin" ) {
			if ( pTo === "window" ) {
				x = windowCoordinates.cx / 2 + windowCoordinates.x;
				y = windowCoordinates.cy / 2 + windowCoordinates.y;
			} else {
				try {
					dst = $( pTo );
				} catch ( err ) {
					dst = null;
				}
				if ( dst ) {
					dst.filter( ":visible" );
					if ( dst.length === 0 ) {
						dst = null;
					}
				}
			}
		}

		// If an element was found, center over it
		if ( dst ) {
			offset = dst.offset();
			x = offset.left + dst.outerWidth() / 2;
			y = offset.top + dst.outerHeight() / 2;
		}

		// Make sure x and y are valid numbers - center over the window
		if ( $.type( x ) !== "number" || isNaN( x ) ) {
			x = windowCoordinates.cx / 2 + windowCoordinates.x;
		}
		if ( $.type( y ) !== "number" || isNaN( y ) ) {
			y = windowCoordinates.cy / 2 + windowCoordinates.y;
		}

		return { x: x, y: y };
	},

	_reposition: function( openOptions ) {

		// We only care about position-related parameters for repositioning
		openOptions = {
			x: openOptions.x,
			y: openOptions.y,
			positionTo: openOptions.positionTo
		};
		this._trigger( "beforeposition", undefined, openOptions );
		this._ui.container.offset( this._placementCoords( this._desiredCoords( openOptions ) ) );
	},

	reposition: function( openOptions ) {
		if ( this._isOpen ) {
			this._reposition( openOptions );
		}
	},

	_openPrerequisitesComplete: function() {
		var id = this.element.attr( "id" ),
			firstFocus = this._ui.container.find( ":focusable" ).first(),
			focusElement = $.ui.safeActiveElement( this.document[ 0 ] );

		this._addClass( this._ui.container, "ui-popup-active" );
		this._isOpen = true;
		this._resizeScreen();

		// Check to see if currElement is not a child of the container.  If it's not, blur
		if ( focusElement && !$.contains( this._ui.container[ 0 ], focusElement ) ) {
			$.ui.safeBlur( focusElement );
		}
		if ( firstFocus.length > 0 ) {
			this._ui.focusElement = firstFocus;
		}
		this._ignoreResizeEvents();
		if ( id ) {
			this.document.find( "[aria-haspopup='true'][aria-owns='" +
				$.mobile.path.hashToSelector( id ) + "']" ).attr( "aria-expanded", true );
		}
		this._ui.container.attr( "tabindex", 0 );
		this._trigger( "afteropen" );
	},

	_open: function( options ) {
		var openOptions = $.extend( {}, this.options, options ),

			// TODO move blacklist to private method
			androidBlacklist = ( function() {
				var ua = navigator.userAgent,

					// Rendering engine is Webkit, and capture major version
					wkmatch = ua.match( /AppleWebKit\/([0-9\.]+)/ ),
					wkversion = !!wkmatch && wkmatch[ 1 ],
					androidmatch = ua.match( /Android (\d+(?:\.\d+))/ ),
					andversion = !!androidmatch && androidmatch[ 1 ],
					chromematch = ua.indexOf( "Chrome" ) > -1;

				// Platform is Android, WebKit version is greater than 534.13 ( Android 3.2.1 ) and
				// not Chrome.
				if ( androidmatch !== null && andversion === "4.0" && wkversion &&
						wkversion > 534.13 && !chromematch ) {
					return true;
				}
				return false;
			}() );

		// Count down to triggering "popupafteropen" - we have two prerequisites:
		// 1. The popup window animation completes (container())
		// 2. The screen opacity animation completes (screen())
		this._createPrerequisites(
			$.noop,
			$.noop,
			$.proxy( this, "_openPrerequisitesComplete" ) );

		this._currentTransition = openOptions.transition;
		this._applyTransition( openOptions.transition );

		this._removeClass( this._ui.screen, null, "ui-screen-hidden" );
		this._removeClass( this._ui.container, "ui-popup-truncate" );

		// Give applications a chance to modify the contents of the container before it appears
		this._reposition( openOptions );

		this._removeClass( this._ui.container, "ui-popup-hidden" );

		if ( this.options.overlayTheme && androidBlacklist ) {

			// TODO: The native browser on Android 4.0.X ("Ice Cream Sandwich") suffers from an
			// issue where the popup overlay appears to be z-indexed above the popup itself when
			// certain other styles exist on the same page -- namely, any element set to
			// `position: fixed` and certain types of input. These issues are reminiscent of
			// previously uncovered bugs in older versions of Android's native browser:
			// https://github.com/scottjehl/Device-Bugs/issues/3
			// This fix closes the following bugs ( I use "closes" with reluctance, and stress that
			// this issue should be revisited as soon as possible ):
			// https://github.com/jquery/jquery-mobile/issues/4816
			// https://github.com/jquery/jquery-mobile/issues/4844
			// https://github.com/jquery/jquery-mobile/issues/4874

			// TODO sort out why this._page isn't working
			this._addClass( this.element.closest( ".ui-page" ), "ui-popup-open" );
		}
		this._animate( {
			additionalCondition: true,
			transition: openOptions.transition,
			classToRemove: "",
			screenClassToAdd: "in",
			containerClassToAdd: "in",
			applyTransition: false,
			prerequisites: this._prerequisites
		} );
	},

	_closePrerequisiteScreen: function() {
		this._removeClass( this._ui.screen, null, "out" )
			._addClass( this._ui.screen, null, "ui-screen-hidden" );
	},

	_closePrerequisiteContainer: function() {
		this._removeClass( this._ui.container, null, "reverse out" )
			._addClass( this._ui.container, "ui-popup-hidden ui-popup-truncate" );
		this._ui.container.removeAttr( "style" );
	},

	_closePrerequisitesDone: function() {
		var container = this._ui.container,
			id = this.element.attr( "id" );

		// Remove the global mutex for popups
		$.mobile.popup.active = undefined;

		// Blur elements inside the container, including the container
		$( ":focus", container[ 0 ] ).add( container[ 0 ] ).blur();

		if ( id ) {
			this.document.find( "[aria-haspopup='true'][aria-owns='" +
				$.mobile.path.hashToSelector( id ) + "']" ).attr( "aria-expanded", false );
		}

		this._ui.container.removeAttr( "tabindex" );

		// Alert users that the popup is closed
		this._trigger( "afterclose" );
	},

	_close: function( immediate ) {
		this._removeClass( this._ui.container, "ui-popup-active" )
			._removeClass( this._page, "ui-popup-open" );

		this._isOpen = false;

		// Count down to triggering "popupafterclose" - we have two prerequisites:
		// 1. The popup window reverse animation completes (container())
		// 2. The screen opacity animation completes (screen())
		this._createPrerequisites(
			$.proxy( this, "_closePrerequisiteScreen" ),
			$.proxy( this, "_closePrerequisiteContainer" ),
			$.proxy( this, "_closePrerequisitesDone" ) );

		this._animate( {
			additionalCondition: this._ui.screen.hasClass( "in" ),
			transition: ( immediate ? "none" : ( this._currentTransition ) ),
			classToRemove: "in",
			screenClassToAdd: "out",
			containerClassToAdd: "reverse out",
			applyTransition: true,
			prerequisites: this._prerequisites
		} );
	},

	_unenhance: function() {
		if ( this.options.enhanced ) {
			return;
		}

		this.element

			// Cannot directly insertAfter() - we need to detach() first, because
			// insertAfter() will do nothing if the payload div was not attached
			// to the DOM at the time the widget was created, and so the payload
			// will remain inside the container even after we call insertAfter().
			// If that happens and we remove the container a few lines below, we
			// will cause an infinite recursion - #5244
			.detach()
			.insertAfter( this._ui.placeholder );
		this._ui.screen.remove();
		this._ui.container.remove();
		this._ui.placeholder.remove();
	},

	_destroy: function() {
		if ( this.options.enhanced ) {
			this.classesElementLookup = {};
		}
		if ( $.mobile.popup.active === this ) {
			this.element.one( "popupafterclose", $.proxy( this, "_unenhance" ) );
			this.close();
		} else {
			this._unenhance();
		}
	},

	_closePopup: function( theEvent, data ) {
		var parsedDst, toUrl,
			immediate = false;

		if ( $.mobile.popup.active !== this ||
				( theEvent &&
					( theEvent.isDefaultPrevented() ||
					( theEvent.type === "navigate" && data && data.state && data.state.url &&
						data.state.url === this._myUrl ) ) ) ||
				!this._isOpen ) {
			return;
		}

		// Restore location on screen
		window.scrollTo( 0, this._scrollTop );

		if ( theEvent && theEvent.type === "pagebeforechange" && data ) {

			// Determine whether we need to rapid-close the popup, or whether we can take the time
			// to run the closing transition
			if ( typeof data.toPage === "string" ) {
				parsedDst = data.toPage;
			} else {
				parsedDst = data.toPage.jqmData( "url" );
			}
			parsedDst = $.mobile.path.parseUrl( parsedDst );
			toUrl = parsedDst.pathname + parsedDst.search + parsedDst.hash;

			if ( this._pageUrl !== $.mobile.path.makeUrlAbsolute( toUrl ) ||
					data.options.reloadPage ) {

				// Going to a different page - close immediately
				immediate = true;
			} else {
				theEvent.preventDefault();
			}
		}

		// Remove nav bindings
		this._off( this.window, "navigate pagebeforechange" );

		// Unbind click handlers added when history is disabled
		this._off( this.element, "click" );

		this._close( immediate );
	},

	// Any navigation event after a popup is opened should close the popup.
	// NOTE The pagebeforechange is bound to catch navigation events that don't alter the url
	//      (eg, dialogs from popups)
	_bindContainerClose: function() {
		this._on( true, this.window, {
			navigate: "_closePopup",
			pagebeforechange: "_closePopup"
		} );
	},

	widget: function() {
		return this._ui.container;
	},

	_handleCloseLink: function( theEvent ) {
		this.close();
		theEvent.preventDefault();
	},

	// TODO No clear deliniation of what should be here and what should be in _open. Seems to be
	// "visual" vs "history" for now
	open: function( options ) {
		var url, hashkey, activePage, currentIsDialog, hasHash, urlHistory,
			events = {},
			self = this,
			currentOptions = this.options;

		// Make sure open is idempotent
		if ( $.mobile.popup.active || currentOptions.disabled ) {
			return this;
		}

		// Set the global popup mutex
		$.mobile.popup.active = this;
		this._scrollTop = this.window.scrollTop();

		// If history alteration is disabled close on navigate events and leave the url as is
		if ( !( currentOptions.history ) ) {
			self._open( options );
			self._bindContainerClose();

			// When history is disabled we have to grab the data-rel back link clicks so we can
			// close the popup instead of relying on history to do it for us
			events[ "click " + currentOptions.closeLinkSelector ] = "_handleCloseLink";
			this._on( events );

			return this;
		}

		// Cache some values for min/readability
		urlHistory = $.mobile.navigate.history;
		hashkey = $.mobile.dialogHashKey;
		activePage = $.mobile.activePage;
		currentIsDialog = ( activePage ? activePage.hasClass( "ui-page-dialog" ) : false );
		this._pageUrl = url = urlHistory.getActive().url;
		hasHash = ( url.indexOf( hashkey ) > -1 ) && !currentIsDialog &&
			( urlHistory.activeIndex > 0 );

		if ( hasHash ) {
			self._open( options );
			self._bindContainerClose();
			return this;
		}

		// If the current url has no dialog hash key proceed as normal otherwise, if the page is a
		// dialog simply tack on the hash key
		if ( url.indexOf( hashkey ) === -1 && !currentIsDialog ) {
			url = url + ( url.indexOf( "#" ) > -1 ? hashkey : "#" + hashkey );
		} else {
			url = $.mobile.path.parseLocation().hash + hashkey;
		}

		// Swallow the the initial navigation event, and bind for the next
		this.window.one( "beforenavigate", function( theEvent ) {
			theEvent.preventDefault();
			self._open( options );
			self._bindContainerClose();
		} );

		this.urlAltered = true;
		this._myUrl = url;
		$.mobile.navigate( url, { role: "dialog" } );

		return this;
	},

	close: function() {

		// Make sure close is idempotent
		if ( $.mobile.popup.active !== this ) {
			return this;
		}

		this._scrollTop = this.window.scrollTop();

		if ( this.options.history && this.urlAltered ) {
			$.mobile.back();
			this.urlAltered = false;
		} else {

			// Simulate the nav bindings having fired
			this._closePopup();
		}

		return this;
	}
} );

$.widget( "mobile.popup", $.mobile.popup, $.mobile.widget.theme );

// TODO this can be moved inside the widget
$.mobile.popup.handleLink = function( $link ) {
	var offset,
		path = $.mobile.path,

		// NOTE make sure to get only the hash from the href because ie7 (wp7)
		//      returns the absolute href in this case ruining the element selection
		popup = $( path.hashToSelector( path.parseUrl( $link.attr( "href" ) ).hash ) ).first();

	if ( popup.length > 0 && popup.data( "mobile-popup" ) ) {
		offset = $link.offset();
		popup.popup( "open", {
			x: offset.left + $link.outerWidth() / 2,
			y: offset.top + $link.outerHeight() / 2,
			transition: $link.jqmData( "transition" ),
			positionTo: $link.jqmData( "position-to" )
		} );
	}

	// Remove after delay
	setTimeout( function() {
		$link.removeClass( "ui-button-active" );
	}, 300 );
};

// TODO move inside _create
$.mobile.document.on( "pagebeforechange", function( theEvent, data ) {
	if ( data.options.role === "popup" ) {
		$.mobile.popup.handleLink( data.options.link );
		theEvent.preventDefault();
	}
} );

return $.mobile.popup;

} );

/*!
 * jQuery Mobile Listview @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Listview
//>>group: Widgets
//>>description: Applies listview styling of various types (standard, numbered, split button, etc.)
//>>docs: http://api.jquerymobile.com/listview/
//>>demos: http://demos.jquerymobile.com/@VERSION/listview/
//>>css.structure: ../css/structure/jquery.mobile.listview.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/listview',[
			"jquery",
			"../widget",
			"./addFirstLastClasses" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

function addItemToDictionary( itemClassDict, element, key, extra ) {

	// Construct the dictionary key from the key class and the extra class
	var dictionaryKey = [ key ].concat( extra ? [ extra ] : [] ).join( "|" );

	if ( !itemClassDict[ dictionaryKey ] ) {
		itemClassDict[ dictionaryKey ] = [];
	}

	itemClassDict[ dictionaryKey ].push( element );
}

var getAttribute = $.mobile.getAttribute,
	countBubbleClassRegex = /\bui-listview-item-count-bubble\b/;

function filterBubbleSpan() {
	var child, parentNode,
		anchorHash = { "a": true, "A": true };

	for ( child = this.firstChild ; !!child ; child = child.nextSibling ) {

		// Accept list item when we've found an element with class
		// ui-listview-item-count-bubble
		if ( child.className && child.className.match( countBubbleClassRegex ) ) {
			return true;
		}

		// Descend into anchor, remembering where we've been
		if ( anchorHash[ child.nodeName ] ) {
			parentNode = child;
			child = child.firstChild;
		}

		// When done with anchor, resume checking children of list item
		if ( !child && parentNode ) {
			child = parentNode;
			parentNode = null;
		}
	}
}

return $.widget( "mobile.listview", $.extend( {
	version: "@VERSION",

	options: {
		classes: {
			"ui-listview-inset": "ui-corner-all ui-shadow"
		},
		theme: "inherit",
		dividerTheme: "inherit",
		icon: "caret-r",
		splitIcon: "caret-r",
		splitTheme: "inherit",
		inset: false,
		enhanced: false
	},

	_create: function() {
		this._addClass( "ui-listview" );
		if ( this.options.inset ) {
			this._addClass( "ui-listview-inset" );
		}
		this._refresh( true );
	},

	// We only handle the theme option through the theme extension. Theme options concerning list
	// items such as splitTheme and dividerTheme have to be handled in refresh().
	_themeElements: function() {
		return [ {
			element: this.element,
			prefix: "ui-group-theme-"
		} ];
	},

	_setOption: function( key, value ) {
		if ( key === "inset" ) {
			this._toggleClass( this.element, "ui-listview-inset", null, !!value );
		}

		return this._superApply( arguments );
	},

	_getChildrenByTagName: function( ele, lcName, ucName ) {
		var results = [],
			dict = {};
		dict[ lcName ] = dict[ ucName ] = true;
		ele = ele.firstChild;
		while ( ele ) {
			if ( dict[ ele.nodeName ] ) {
				results.push( ele );
			}
			ele = ele.nextSibling;
		}
		return $( results );
	},

	_beforeListviewRefresh: $.noop,
	_afterListviewRefresh: $.noop,

	updateItems: function( items ) {
		this._refresh( false, items );
	},

	refresh: function() {
		this._refresh();
	},

	_processListItem: function( /* item */ ) {
		return true;
	},

	_processListItemAnchor: function( /* a */ ) {
		return true;
	},

	_refresh: function( create, items ) {
		var buttonClass, pos, numli, item, itemClass, itemExtraClass, itemTheme, itemIcon, icon, a,
			isDivider, value, last, splittheme, li, dictionaryKey, span, allItems, newSpan,
			currentOptions = this.options,
			list = this.element,
			ol = !!$.nodeName( list[ 0 ], "ol" ),
			start = list.attr( "start" ),
			itemClassDict = {};

		// Check if a start attribute has been set while taking a value of 0 into account
		if ( ol && ( start || start === 0 ) ) {
			list.css( "counter-reset", "listnumbering " + ( parseInt( start, 10 ) - 1 ) );
		}

		this._beforeListviewRefresh();

		// We need all items even if a set was passed in - we just won't iterate over them in the
		// main refresh loop.
		allItems = this._getChildrenByTagName( list[ 0 ], "li", "LI" );
		li = items || allItems;

		for ( pos = 0, numli = li.length; pos < numli; pos++ ) {
			item = li.eq( pos );
			itemClass = "ui-listview-item";
			itemExtraClass = undefined;

			if ( create || this._processListItem( item ) ) {
				a = this._getChildrenByTagName( item[ 0 ], "a", "A" );
				isDivider = ( getAttribute( item[ 0 ], "role" ) === "list-divider" );
				value = item.attr( "value" );
				itemTheme = getAttribute( item[ 0 ], "theme" );

				if ( a.length && ( ( this._processListItemAnchor( a ) && !isDivider ) ||
						create ) ) {
					itemIcon = getAttribute( item[ 0 ], "icon" );
					icon = ( itemIcon === false ) ? false : ( itemIcon || currentOptions.icon );

					buttonClass = "ui-button";

					if ( itemTheme ) {
						buttonClass += " ui-button-" + itemTheme;
					}

					if ( a.length > 1 ) {
						itemClass += " ui-listview-item-has-alternate";

						last = a.last();
						splittheme = getAttribute( last[ 0 ], "theme" ) ||
							currentOptions.splitTheme || itemTheme;

						newSpan = false;
						span = last.children( ".ui-listview-item-split-icon" );
						if ( !span.length ) {
							span = $( "<span>" );
							newSpan = true;
						}

						addItemToDictionary( itemClassDict, span[ 0 ],
							"ui-listview-item-split-icon", "ui-icon ui-icon-" +
								( getAttribute( last[ 0 ], "icon" ) || itemIcon ||
									currentOptions.splitIcon ) );
						addItemToDictionary( itemClassDict, last[ 0 ],
							"ui-listview-item-split-button",
							"ui-button ui-button-icon-only" +
								( splittheme ? " ui-button-" + splittheme : "" ) );
						last.attr( "title", $.trim( last.getEncodedText() ) );

						if ( newSpan ) {
							last.empty().prepend( span );
						}

						// Reduce to the first anchor, because only the first gets the buttonClass
						a = a.first();
					} else if ( icon ) {

						newSpan = false;
						span = a.children( ".ui-listview-item-icon" );
						if ( !span.length ) {
							span = $( "<span>" );
							newSpan = true;
						}

						addItemToDictionary( itemClassDict, span[ 0 ], "ui-listview-item-icon",
							"ui-icon ui-icon-" + icon + " ui-widget-icon-floatend" );

						if ( newSpan ) {
							a.prepend( span );
						}
					}

					// Apply buttonClass to the (first) anchor
					addItemToDictionary( itemClassDict, a[ 0 ], "ui-listview-item-button",
						buttonClass );
				} else if ( isDivider ) {
					itemClass += " ui-listview-item-divider";
					itemExtraClass = "ui-bar-" + ( itemTheme || currentOptions.dividerTheme ||
						currentOptions.theme || "inherit" );

					item.attr( "role", "heading" );
				} else if ( a.length <= 0 ) {
					itemClass += " ui-listview-item-static";
					itemExtraClass = "ui-body-" + ( itemTheme ? itemTheme : "inherit" );
				}
				if ( ol && value ) {
					item.css( "counter-reset", "listnumbering " + ( parseInt( value, 10 ) - 1 ) );
				}
			}

			// Instead of setting item class directly on the list item
			// at this point in time, push the item into a dictionary
			// that tells us what class to set on it so we can do this after this
			// processing loop is finished.
			addItemToDictionary( itemClassDict, item[ 0 ], itemClass, itemExtraClass );
		}

		// Set the appropriate listview item classes on each list item.
		// The main reason we didn't do this
		// in the for-loop above is because we can eliminate per-item function overhead
		// by calling addClass() and children() once or twice afterwards. This
		// can give us a significant boost on platforms like WP7.5.

		for ( dictionaryKey in itemClassDict ) {

			// Split the dictionary key back into key classes and extra classes and construct the
			// _addClass() parameter list
			this._addClass.apply( this,
				[ $( itemClassDict[ dictionaryKey ] ) ]
					.concat( dictionaryKey.split( "|" ) ) );
		}

		this._addClass(
			li.filter( filterBubbleSpan ),
			"ui-listview-item-has-count" );

		this._afterListviewRefresh();

		// NOTE: Using the extension addFirstLastClasses is deprecated as of 1.5.0 and this and the
		// extension itself will be removed in 1.6.0.
		this._addFirstLastClasses( allItems, this._getVisibles( allItems, create ), create );
	}
}, $.mobile.behaviors.addFirstLastClasses ) );

} );

/*!
 * jQuery Mobile Navbar @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>description: navbar morebutton extension.
//>>label: NavbarMoreButton
//>>group: Widgets
//>>css.structure: ../css/structure/jquery.mobile.navbar.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
    if ( typeof define === "function" && define.amd ) {

        // AMD. Register as an anonymous module.
        define( 'widgets/navbar.morebutton',[
            "jquery",
            "./navbar",
            "./popup",
            "./listview",
            "../widget" ], factory );
    } else {

        // Browser globals
        factory( jQuery );
    }
} )( function( $ ) {

return $.widget( "mobile.navbar", $.mobile.navbar, {

    options: {
        morebutton: false,
        morebuttontext: "...",
        morebuttoniconpos: "top",
        morebuttonicon: null
    },

    _create: function() {
        this._super();
        if ( this.options.morebutton  && this.numButtons > this.maxButton ) {
            this._createNavPopup();
        }
    },

    _id: function() {
        return this.element.attr( "id" ) || ( this.widgetName + this.uuid );
    },

    _createNavRows: function() {
        if ( this.options.morebutton ) {
            return;
        }

        this._super();
    },

    _createNavPopup: function() {
        var popupDiv;
        var popupNav;
        var moreButton;
        var pos;
        var buttonItem;
        var id = this._id() + "-popup";
        var navItems = this.navbar.find( "li" );
        var buttonCount = navItems.length;
        var maxButton = this.maxButton;
        var iconpos = this.iconpos;
        var icon = this.options.morebuttonicon;

        popupDiv = $( "<div id='" + id + "'></div>" );
        this._addClass( popupDiv, "ui-navbar-popup" );
        popupNav = $( "<ul>" );
        this._addClass( popupNav, "ui-navbar-popupnav" );
        popupNav.appendTo( popupDiv );

        // Enhance buttons and move to new rows
        for ( pos = 0; pos < buttonCount; pos++ ) {
            buttonItem = navItems.eq( pos );
            this._makeNavButton( buttonItem.find( "a" ), iconpos );
            if ( pos + 1 === maxButton ) {

                moreButton = $( "<li></li>" ).append( $( "<button></button>" )
                                    .attr( "data-rel", "popup" )
                                    .button( {
                                        icon: icon,
                                        iconPosition: this.options.morebuttoniconpos,
                                        label: this.options.morebuttontext
                                    } ) );
                this._on( moreButton, {
                    "click": "_openMoreButton"
                } );
                this.navbar.find( "ul" ).first().append( moreButton );
            }
            if ( pos + 1 >= maxButton ) {
                buttonItem.detach();
                popupNav.append( buttonItem );
            }
            popupNav.listview();

        }
        popupDiv.appendTo( this.navbar );
        popupDiv.popup( { positionTo: moreButton } );

        this.moreButton = moreButton;
        this.popupDiv = popupDiv;
    },

    _openMoreButton: function() {
        $( "#" + this._id() + "-popup" ).popup( "open" );
    },

    refresh: function() {
        var newitems;
        var that = this;
        var iconpos = this.iconpos;
        if ( !this.options.morebutton ) {
          this._super();
          return;
        }

        if ( this.popupDiv ) {
            newitems = this.moreButton.parent().nextAll();
            newitems.find( "a" ).each( function() {
              that._makeNavButton( this, iconpos );
            } );
            newitems.appendTo( this.popupDiv.find( "ul" ) );
        }
        this._createNavPopup();
    },

    _destroy: function() {
        var navitems;

        if ( !this.options.morebutton ) {
            this._super();
            return;
        }

        if ( this.popupDiv ) {
            navitems = this.popupDiv.find( "li" ).detach();
            this.popupDiv.remove();
            this.moreButton.parent().remove();
            this.navbar.find( "ul" ).append( navitems );
            this.navbar.removeClass( "ui-navbar" );
            this.navButtons = this.navbar.find( "a" );
            this.navButtons.each( function() {
                $( this ).button( "destroy" );
            } );
        }
    }
} );

} );

/*!
 * jQuery Mobile Listview Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Listview Backcompat
//>>group: Widgets
//>>description: Listview style options preserved for backwards compatibility
//>>docs: http://api.jquerymobile.com/listview/
//>>demos: http://demos.jquerymobile.com/@VERSION/listview/
//>>css.structure: ../css/structure/jquery.mobile.listview.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/listview.backcompat',[
			"jquery",
			"./widget.theme",
			"./widget.backcompat",
			"./listview" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {
	var listviewItemClassRegex = /\bui-listview-item-static\b|\bui-listview-item-divider\b/;
	var buttonClassRegex = /\bui-button\b/;

	$.widget( "mobile.listview", $.mobile.listview, {
		options: {
			corners: true,
			shadow: true
		},
		classProp: "ui-listview-inset",
		_processListItem: function( item ) {
			return !listviewItemClassRegex.test( item[ 0 ].className );
		},

		_processListItemAnchor: function( a ) {
			return !buttonClassRegex.test( a[ 0 ].className );
		}
	} );
	$.widget( "mobile.listview", $.mobile.listview, $.mobile.widget.backcompat );
}

} );

/*!
 * jQuery Mobile Listview Autodividers @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Listview Autodividers
//>>group: Widgets
//>>description: Generates dividers for listview items
//>>docs: http://api.jquerymobile.com/listview/#option-autodividers
//>>demos: http://demos.jquerymobile.com/@VERSION/listview/#Autodividers

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/listview.autodividers',[
			"jquery",
			"./listview" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var dividerClassRegex = /\bui-listview-item-divider\b/;

function defaultAutodividersSelector( elt ) {

	// Look for the text in the given element
	var text = $.trim( elt.text() ) || null;

	if ( !text ) {
		return null;
	}

	// Create the text for the divider (first uppercased letter)
	text = text.slice( 0, 1 ).toUpperCase();

	return text;
}

return $.widget( "mobile.listview", $.mobile.listview, {
	options: {
		autodividers: false,
		autodividersSelector: defaultAutodividersSelector
	},

	_beforeListviewRefresh: function() {
		if ( this.options.autodividers ) {
			this._replaceDividers();
		}
		return this._superApply( arguments );
	},

	_replaceDividers: function() {
		var existingDivider, existingDividerText, lastDividerText,
			items = this._getChildrenByTagName( this.element[ 0 ], "li", "LI" );

		items.each( $.proxy( function( index, item ) {
			var divider, dividerText;

			item = $( item );

			// This tests whether the item is a divider - first we check the class name, and second
			// we check the slower way, via the data attribute
			if ( ( item[ 0 ].className && item[ 0 ].className.match( dividerClassRegex ) ) ||
					item[ 0 ].getAttribute( "data-" + $.mobile.ns + "role" ) === "list-divider" ) {

				// The last item can't be a divider
				if ( index === items.length - 1 ) {
					item.remove();
					return false;
				}

				// If the previous item was a divider, remove it
				if ( existingDivider ) {
					existingDivider.remove();
				}

				// The current item becomes the previous divider
				existingDivider = item;
				existingDividerText = item.text();

				// If we've found a divider for a heading that already has a divider, remove it to
				// coalesce two adjacent groups with identical headings
				if ( existingDividerText === lastDividerText ) {
					existingDivider.remove();
					existingDivider = null;
					existingDividerText = null;
				}
			} else {
				dividerText = this.options.autodividersSelector( item );

				// If this item is preceded by a suitable divider reuse it
				if ( existingDivider ) {
					if ( existingDividerText === dividerText ) {

						// We prevent the generation of a divider below by setting the
						// lastDividerText here
						lastDividerText = existingDividerText;
					} else {

						// The preceding item is not a suitable divider
						existingDivider.remove();
					}

					// We only keep a reference to an existing divider for one iteration, because
					// the item immediately succeeding an existing divider will inform us as to
					// whether the divider we've found is suitable for the current group
					existingDivider = null;
					existingDividerText = null;
				}

				// If we haven't found a suitable divider and a new group has started, generate a
				// new divider
				if ( dividerText && lastDividerText !== dividerText ) {
					divider = document.createElement( "li" );
					divider.appendChild( document.createTextNode( dividerText ) );
					divider.setAttribute( "data-" + $.mobile.ns + "role", "list-divider" );
					item.before( divider );
				}

				lastDividerText = dividerText;
			}
		}, this ) );
	}
} );

} );

/*!
 * jQuery Mobile Listview Hide Dividers @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Listview Hide Dividers
//>>group: Widgets
//>>description: Hides dividers when all items in the section they designate become hidden
//>>docs: http://api.jquerymobile.com/listview/#option-hideDividers
//>>demos: http://demos.jquerymobile.com/@VERSION/listview/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/listview.hidedividers',[
			"jquery",
			"./listview" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var rdivider = /(^|\s)ui-listview-item-divider($|\s)/,
	rhidden = /(^|\s)ui-screen-hidden($|\s)/;

return $.widget( "mobile.listview", $.mobile.listview, {
	options: {
		hideDividers: false
	},

	_afterListviewRefresh: function() {
		var items, idx, item,
			hideDivider = true;

		this._superApply( arguments );

		if ( this.options.hideDividers ) {
			items = this._getChildrenByTagName( this.element[ 0 ], "li", "LI" );
			for ( idx = items.length - 1; idx > -1; idx-- ) {
				item = items[ idx ];
				if ( item.className.match( rdivider ) ) {
					if ( hideDivider ) {
						item.className = item.className + " ui-screen-hidden";
					}
					hideDivider = true;
				} else {
					if ( !item.className.match( rhidden ) ) {
						hideDivider = false;
					}
				}
			}
		}
	}
} );

} );

/*!
 * jQuery Mobile No JS @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: “nojs” Classes
//>>group: Utilities
//>>description: Adds class to make elements hidden to A grade browsers
//>>docs: http://api.jquerymobile.com/global-config/#keepNative
//>>css.structure: ../css/structure/jquery.mobile.core.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'nojs',[
			"jquery",
			"./ns" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.nojs = function( target ) {
	$( ":jqmData(role='nojs')", target ).addClass( "ui-nojs" );
};

return $.mobile.nojs;

} );

/*!
 * jQuery UI Unique ID 1.12.1
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: uniqueId
//>>group: Core
//>>description: Functions to generate and remove uniqueId's
//>>docs: http://api.jqueryui.com/uniqueId/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/unique-id',[ "jquery", "./version" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} ( function( $ ) {

return $.fn.extend( {
	uniqueId: ( function() {
		var uuid = 0;

		return function() {
			return this.each( function() {
				if ( !this.id ) {
					this.id = "ui-id-" + ( ++uuid );
				}
			} );
		};
	} )(),

	removeUniqueId: function() {
		return this.each( function() {
			if ( /^ui-id-\d+$/.test( this.id ) ) {
				$( this ).removeAttr( "id" );
			}
		} );
	}
} );

} ) );

/*!
 * jQuery UI Accordion 1.12.1
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Accordion
//>>group: Widgets
// jscs:disable maximumLineLength
//>>description: Displays collapsible content panels for presenting information in a limited amount of space.
// jscs:enable maximumLineLength
//>>docs: http://api.jqueryui.com/accordion/
//>>demos: http://jqueryui.com/accordion/
//>>css.structure: ../../themes/base/core.css
//>>css.structure: ../../themes/base/accordion.css
//>>css.theme: ../../themes/base/theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/widgets/accordion',[
			"jquery",
			"../version",
			"../keycode",
			"../unique-id",
			"../widget"
		], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
}( function( $ ) {

return $.widget( "ui.accordion", {
	version: "1.12.1",
	options: {
		active: 0,
		animate: {},
		classes: {
			"ui-accordion-header": "ui-corner-top",
			"ui-accordion-header-collapsed": "ui-corner-all",
			"ui-accordion-content": "ui-corner-bottom"
		},
		collapsible: false,
		event: "click",
		header: "> li > :first-child, > :not(li):even",
		heightStyle: "auto",
		icons: {
			activeHeader: "ui-icon-triangle-1-s",
			header: "ui-icon-triangle-1-e"
		},

		// Callbacks
		activate: null,
		beforeActivate: null
	},

	hideProps: {
		borderTopWidth: "hide",
		borderBottomWidth: "hide",
		paddingTop: "hide",
		paddingBottom: "hide",
		height: "hide"
	},

	showProps: {
		borderTopWidth: "show",
		borderBottomWidth: "show",
		paddingTop: "show",
		paddingBottom: "show",
		height: "show"
	},

	_create: function() {
		var options = this.options;

		this.prevShow = this.prevHide = $();
		this._addClass( "ui-accordion", "ui-widget ui-helper-reset" );
		this.element.attr( "role", "tablist" );

		// Don't allow collapsible: false and active: false / null
		if ( !options.collapsible && ( options.active === false || options.active == null ) ) {
			options.active = 0;
		}

		this._processPanels();

		// handle negative values
		if ( options.active < 0 ) {
			options.active += this.headers.length;
		}
		this._refresh();
	},

	_getCreateEventData: function() {
		return {
			header: this.active,
			panel: !this.active.length ? $() : this.active.next()
		};
	},

	_createIcons: function() {
		var icon, children,
			icons = this.options.icons;

		if ( icons ) {
			icon = $( "<span>" );
			this._addClass( icon, "ui-accordion-header-icon", "ui-icon " + icons.header );
			icon.prependTo( this.headers );
			children = this.active.children( ".ui-accordion-header-icon" );
			this._removeClass( children, icons.header )
				._addClass( children, null, icons.activeHeader )
				._addClass( this.headers, "ui-accordion-icons" );
		}
	},

	_destroyIcons: function() {
		this._removeClass( this.headers, "ui-accordion-icons" );
		this.headers.children( ".ui-accordion-header-icon" ).remove();
	},

	_destroy: function() {
		var contents;

		// Clean up main element
		this.element.removeAttr( "role" );

		// Clean up headers
		this.headers
			.removeAttr( "role aria-expanded aria-selected aria-controls tabIndex" )
			.removeUniqueId();

		this._destroyIcons();

		// Clean up content panels
		contents = this.headers.next()
			.css( "display", "" )
			.removeAttr( "role aria-hidden aria-labelledby" )
			.removeUniqueId();

		if ( this.options.heightStyle !== "content" ) {
			contents.css( "height", "" );
		}
	},

	_setOption: function( key, value ) {
		if ( key === "active" ) {

			// _activate() will handle invalid values and update this.options
			this._activate( value );
			return;
		}

		if ( key === "event" ) {
			if ( this.options.event ) {
				this._off( this.headers, this.options.event );
			}
			this._setupEvents( value );
		}

		this._super( key, value );

		// Setting collapsible: false while collapsed; open first panel
		if ( key === "collapsible" && !value && this.options.active === false ) {
			this._activate( 0 );
		}

		if ( key === "icons" ) {
			this._destroyIcons();
			if ( value ) {
				this._createIcons();
			}
		}
	},

	_setOptionDisabled: function( value ) {
		this._super( value );

		this.element.attr( "aria-disabled", value );

		// Support: IE8 Only
		// #5332 / #6059 - opacity doesn't cascade to positioned elements in IE
		// so we need to add the disabled class to the headers and panels
		this._toggleClass( null, "ui-state-disabled", !!value );
		this._toggleClass( this.headers.add( this.headers.next() ), null, "ui-state-disabled",
			!!value );
	},

	_keydown: function( event ) {
		if ( event.altKey || event.ctrlKey ) {
			return;
		}

		var keyCode = $.ui.keyCode,
			length = this.headers.length,
			currentIndex = this.headers.index( event.target ),
			toFocus = false;

		switch ( event.keyCode ) {
		case keyCode.RIGHT:
		case keyCode.DOWN:
			toFocus = this.headers[ ( currentIndex + 1 ) % length ];
			break;
		case keyCode.LEFT:
		case keyCode.UP:
			toFocus = this.headers[ ( currentIndex - 1 + length ) % length ];
			break;
		case keyCode.SPACE:
		case keyCode.ENTER:
			this._eventHandler( event );
			break;
		case keyCode.HOME:
			toFocus = this.headers[ 0 ];
			break;
		case keyCode.END:
			toFocus = this.headers[ length - 1 ];
			break;
		}

		if ( toFocus ) {
			$( event.target ).attr( "tabIndex", -1 );
			$( toFocus ).attr( "tabIndex", 0 );
			$( toFocus ).trigger( "focus" );
			event.preventDefault();
		}
	},

	_panelKeyDown: function( event ) {
		if ( event.keyCode === $.ui.keyCode.UP && event.ctrlKey ) {
			$( event.currentTarget ).prev().trigger( "focus" );
		}
	},

	refresh: function() {
		var options = this.options;
		this._processPanels();

		// Was collapsed or no panel
		if ( ( options.active === false && options.collapsible === true ) ||
				!this.headers.length ) {
			options.active = false;
			this.active = $();

		// active false only when collapsible is true
		} else if ( options.active === false ) {
			this._activate( 0 );

		// was active, but active panel is gone
		} else if ( this.active.length && !$.contains( this.element[ 0 ], this.active[ 0 ] ) ) {

			// all remaining panel are disabled
			if ( this.headers.length === this.headers.find( ".ui-state-disabled" ).length ) {
				options.active = false;
				this.active = $();

			// activate previous panel
			} else {
				this._activate( Math.max( 0, options.active - 1 ) );
			}

		// was active, active panel still exists
		} else {

			// make sure active index is correct
			options.active = this.headers.index( this.active );
		}

		this._destroyIcons();

		this._refresh();
	},

	_processPanels: function() {
		var prevHeaders = this.headers,
			prevPanels = this.panels;

		this.headers = this.element.find( this.options.header );
		this._addClass( this.headers, "ui-accordion-header ui-accordion-header-collapsed",
			"ui-state-default" );

		this.panels = this.headers.next().filter( ":not(.ui-accordion-content-active)" ).hide();
		this._addClass( this.panels, "ui-accordion-content", "ui-helper-reset ui-widget-content" );

		// Avoid memory leaks (#10056)
		if ( prevPanels ) {
			this._off( prevHeaders.not( this.headers ) );
			this._off( prevPanels.not( this.panels ) );
		}
	},

	_refresh: function() {
		var maxHeight,
			options = this.options,
			heightStyle = options.heightStyle,
			parent = this.element.parent();

		this.active = this._findActive( options.active );
		this._addClass( this.active, "ui-accordion-header-active", "ui-state-active" )
			._removeClass( this.active, "ui-accordion-header-collapsed" );
		this._addClass( this.active.next(), "ui-accordion-content-active" );
		this.active.next().show();

		this.headers
			.attr( "role", "tab" )
			.each( function() {
				var header = $( this ),
					headerId = header.uniqueId().attr( "id" ),
					panel = header.next(),
					panelId = panel.uniqueId().attr( "id" );
				header.attr( "aria-controls", panelId );
				panel.attr( "aria-labelledby", headerId );
			} )
			.next()
				.attr( "role", "tabpanel" );

		this.headers
			.not( this.active )
				.attr( {
					"aria-selected": "false",
					"aria-expanded": "false",
					tabIndex: -1
				} )
				.next()
					.attr( {
						"aria-hidden": "true"
					} )
					.hide();

		// Make sure at least one header is in the tab order
		if ( !this.active.length ) {
			this.headers.eq( 0 ).attr( "tabIndex", 0 );
		} else {
			this.active.attr( {
				"aria-selected": "true",
				"aria-expanded": "true",
				tabIndex: 0
			} )
				.next()
					.attr( {
						"aria-hidden": "false"
					} );
		}

		this._createIcons();

		this._setupEvents( options.event );

		if ( heightStyle === "fill" ) {
			maxHeight = parent.height();
			this.element.siblings( ":visible" ).each( function() {
				var elem = $( this ),
					position = elem.css( "position" );

				if ( position === "absolute" || position === "fixed" ) {
					return;
				}
				maxHeight -= elem.outerHeight( true );
			} );

			this.headers.each( function() {
				maxHeight -= $( this ).outerHeight( true );
			} );

			this.headers.next()
				.each( function() {
					$( this ).height( Math.max( 0, maxHeight -
						$( this ).innerHeight() + $( this ).height() ) );
				} )
				.css( "overflow", "auto" );
		} else if ( heightStyle === "auto" ) {
			maxHeight = 0;
			this.headers.next()
				.each( function() {
					var isVisible = $( this ).is( ":visible" );
					if ( !isVisible ) {
						$( this ).show();
					}
					maxHeight = Math.max( maxHeight, $( this ).css( "height", "" ).height() );
					if ( !isVisible ) {
						$( this ).hide();
					}
				} )
				.height( maxHeight );
		}
	},

	_activate: function( index ) {
		var active = this._findActive( index )[ 0 ];

		// Trying to activate the already active panel
		if ( active === this.active[ 0 ] ) {
			return;
		}

		// Trying to collapse, simulate a click on the currently active header
		active = active || this.active[ 0 ];

		this._eventHandler( {
			target: active,
			currentTarget: active,
			preventDefault: $.noop
		} );
	},

	_findActive: function( selector ) {
		return typeof selector === "number" ? this.headers.eq( selector ) : $();
	},

	_setupEvents: function( event ) {
		var events = {
			keydown: "_keydown"
		};
		if ( event ) {
			$.each( event.split( " " ), function( index, eventName ) {
				events[ eventName ] = "_eventHandler";
			} );
		}

		this._off( this.headers.add( this.headers.next() ) );
		this._on( this.headers, events );
		this._on( this.headers.next(), { keydown: "_panelKeyDown" } );
		this._hoverable( this.headers );
		this._focusable( this.headers );
	},

	_eventHandler: function( event ) {
		var activeChildren, clickedChildren,
			options = this.options,
			active = this.active,
			clicked = $( event.currentTarget ),
			clickedIsActive = clicked[ 0 ] === active[ 0 ],
			collapsing = clickedIsActive && options.collapsible,
			toShow = collapsing ? $() : clicked.next(),
			toHide = active.next(),
			eventData = {
				oldHeader: active,
				oldPanel: toHide,
				newHeader: collapsing ? $() : clicked,
				newPanel: toShow
			};

		event.preventDefault();

		if (

				// click on active header, but not collapsible
				( clickedIsActive && !options.collapsible ) ||

				// allow canceling activation
				( this._trigger( "beforeActivate", event, eventData ) === false ) ) {
			return;
		}

		options.active = collapsing ? false : this.headers.index( clicked );

		// When the call to ._toggle() comes after the class changes
		// it causes a very odd bug in IE 8 (see #6720)
		this.active = clickedIsActive ? $() : clicked;
		this._toggle( eventData );

		// Switch classes
		// corner classes on the previously active header stay after the animation
		this._removeClass( active, "ui-accordion-header-active", "ui-state-active" );
		if ( options.icons ) {
			activeChildren = active.children( ".ui-accordion-header-icon" );
			this._removeClass( activeChildren, null, options.icons.activeHeader )
				._addClass( activeChildren, null, options.icons.header );
		}

		if ( !clickedIsActive ) {
			this._removeClass( clicked, "ui-accordion-header-collapsed" )
				._addClass( clicked, "ui-accordion-header-active", "ui-state-active" );
			if ( options.icons ) {
				clickedChildren = clicked.children( ".ui-accordion-header-icon" );
				this._removeClass( clickedChildren, null, options.icons.header )
					._addClass( clickedChildren, null, options.icons.activeHeader );
			}

			this._addClass( clicked.next(), "ui-accordion-content-active" );
		}
	},

	_toggle: function( data ) {
		var toShow = data.newPanel,
			toHide = this.prevShow.length ? this.prevShow : data.oldPanel;

		// Handle activating a panel during the animation for another activation
		this.prevShow.add( this.prevHide ).stop( true, true );
		this.prevShow = toShow;
		this.prevHide = toHide;

		if ( this.options.animate ) {
			this._animate( toShow, toHide, data );
		} else {
			toHide.hide();
			toShow.show();
			this._toggleComplete( data );
		}

		toHide.attr( {
			"aria-hidden": "true"
		} );
		toHide.prev().attr( {
			"aria-selected": "false",
			"aria-expanded": "false"
		} );

		// if we're switching panels, remove the old header from the tab order
		// if we're opening from collapsed state, remove the previous header from the tab order
		// if we're collapsing, then keep the collapsing header in the tab order
		if ( toShow.length && toHide.length ) {
			toHide.prev().attr( {
				"tabIndex": -1,
				"aria-expanded": "false"
			} );
		} else if ( toShow.length ) {
			this.headers.filter( function() {
				return parseInt( $( this ).attr( "tabIndex" ), 10 ) === 0;
			} )
				.attr( "tabIndex", -1 );
		}

		toShow
			.attr( "aria-hidden", "false" )
			.prev()
				.attr( {
					"aria-selected": "true",
					"aria-expanded": "true",
					tabIndex: 0
				} );
	},

	_animate: function( toShow, toHide, data ) {
		var total, easing, duration,
			that = this,
			adjust = 0,
			boxSizing = toShow.css( "box-sizing" ),
			down = toShow.length &&
				( !toHide.length || ( toShow.index() < toHide.index() ) ),
			animate = this.options.animate || {},
			options = down && animate.down || animate,
			complete = function() {
				that._toggleComplete( data );
			};

		if ( typeof options === "number" ) {
			duration = options;
		}
		if ( typeof options === "string" ) {
			easing = options;
		}

		// fall back from options to animation in case of partial down settings
		easing = easing || options.easing || animate.easing;
		duration = duration || options.duration || animate.duration;

		if ( !toHide.length ) {
			return toShow.animate( this.showProps, duration, easing, complete );
		}
		if ( !toShow.length ) {
			return toHide.animate( this.hideProps, duration, easing, complete );
		}

		total = toShow.show().outerHeight();
		toHide.animate( this.hideProps, {
			duration: duration,
			easing: easing,
			step: function( now, fx ) {
				fx.now = Math.round( now );
			}
		} );
		toShow
			.hide()
			.animate( this.showProps, {
				duration: duration,
				easing: easing,
				complete: complete,
				step: function( now, fx ) {
					fx.now = Math.round( now );
					if ( fx.prop !== "height" ) {
						if ( boxSizing === "content-box" ) {
							adjust += fx.now;
						}
					} else if ( that.options.heightStyle !== "content" ) {
						fx.now = Math.round( total - toHide.outerHeight() - adjust );
						adjust = 0;
					}
				}
			} );
	},

	_toggleComplete: function( data ) {
		var toHide = data.oldPanel,
			prev = toHide.prev();

		this._removeClass( toHide, "ui-accordion-content-active" );
		this._removeClass( prev, "ui-accordion-header-active" )
			._addClass( prev, "ui-accordion-header-collapsed" );

		// Work around for rendering bug in IE (#5421)
		if ( toHide.length ) {
			toHide.parent()[ 0 ].className = toHide.parent()[ 0 ].className;
		}
		this._trigger( "activate", null, data );
	}
} );

} ) );

/*!
 * jQuery Mobile Button Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Button
//>>group: Forms
//>>description: Backwards-compatibility for buttons.
//>>docs: http://api.jquerymobile.com/button/
//>>demos: http://demos.jquerymobile.com/@VERSION/button/
//>>css.structure: ../css/structure/jquery.mobile.core.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/button.backcompat',[
			"jquery",
			"../../core",
			"../../widget",
			"../widget.backcompat",
			"./button" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {
	$.widget( "ui.button", $.ui.button, {
		initSelector: "input[type='button'], input[type='submit'], input[type='reset'], button," +
		" [data-role='button']",
		options: {
			iconpos: "left",
			mini: false,
			wrapperClass: null,
			inline: null,
			shadow: true,
			corners: true
		},

		classProp: "ui-button",

		_create: function() {
			if ( this.options.iconPosition !== $.ui.button.prototype.options.iconPosition ) {
				this._seticonPosition( this.options.iconPosition );
			} else if ( this.options.iconpos !== $.ui.button.prototype.options.iconpos ) {
				this._seticonpos( this.options.iconpos );
			}
			this._super();
		},

		_seticonPosition: function( value ) {
			if ( value === "end" ) {
				this.options.iconpos = "right";
			} else if ( value !== "left" ) {
				this.options.iconpos = value;
			}
		},

		_seticonpos: function( value ) {
			if ( value === "right" ) {
				this._setOption( "iconPosition", "end" );
			} else if ( value !== "left" ) {
				this._setOption( "iconPosition", value );
			}
		},

		_setOption: function( key, value ) {
			if ( key === "iconPosition" || key === "iconpos" ) {
				this[ "_set" + key ]( value );
			}
			this._superApply( arguments );
		}
	} );
	return $.widget( "ui.button", $.ui.button, $.mobile.widget.backcompat );
}

} );

/*!
 * jQuery Mobile Checkboxradio @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Checkboxes & Radio Buttons
//>>group: Forms
//>>description: Consistent styling for checkboxes/radio buttons.
//>>docs: http://api.jquerymobile.com/checkboxradio/
//>>demos: http://demos.jquerymobile.com/@VERSION/checkboxradio-checkbox/
//>>css.structure: ../css/structure/jquery.mobile.forms.checkboxradio.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/checkboxradio',[
			"jquery",
			"../../core",
			"../../widget",
			"jquery-ui/widgets/checkboxradio",
			"../widget.theme" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.widget( "ui.checkboxradio", $.ui.checkboxradio, {
	options: {
		enhanced: false,
		theme: "inherit"
	},

	_enhance: function() {
		if ( !this.options.enhanced ) {
			this._super();
		} else if ( this.options.icon ) {
			this.icon = this.element.parent().find( ".ui-checkboxradio-icon" );
		}
	},

	_themeElements: function() {
		return [
			{
				element: this.widget(),
				prefix: "ui-button-"
			}
		];
	}
} );

$.widget( "ui.checkboxradio", $.ui.checkboxradio, $.mobile.widget.theme );

return $.ui.checkboxradio;

} );

/*!
 * jQuery Mobile Checkboxradio Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Checkboxes & Radio Buttons
//>>group: Forms
//>>description: Consistent styling for checkboxes/radio buttons.
//>>docs: http://api.jquerymobile.com/checkboxradio/
//>>demos: http://demos.jquerymobile.com/@VERSION/checkboxradio-checkbox/
//>>css.structure: ../css/structure/jquery.mobile.forms.checkboxradio.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/checkboxradio.backcompat',[
			"jquery",
			"../../core",
			"../../widget",
			"../widget.theme",
			"../widget.backcompat",
			"./checkboxradio" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {
	$.widget( "ui.checkboxradio", $.ui.checkboxradio, {
		initSelector: "input[type='radio'],input[type='checkbox']:not(:jqmData(role='flipswitch'))",
		options: {

			// Unimplemented until its decided if this will move to ui widget
			iconpos: "left",
			mini: false,
			wrapperClass: null
		},

		classProp: "ui-checkboxradio-label"
	} );
	$.widget( "ui.checkboxradio", $.ui.checkboxradio, $.mobile.widget.backcompat );
}

return $.ui.checkboxradio;

} );

/*!
 * jQuery Mobile Zoom @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Zoom Handling
//>>group: Utilities
//>>description: Utility methods for enabling and disabling user scaling (pinch zoom)

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'zoom',[
			"jquery",
			"./core" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var meta = $( "meta[name=viewport]" ),
	initialContent = meta.attr( "content" ),
	disabledZoom = initialContent + ",maximum-scale=1, user-scalable=no",
	enabledZoom = initialContent + ",maximum-scale=10, user-scalable=yes",
	disabledInitially = /(user-scalable[\s]*=[\s]*no)|(maximum-scale[\s]*=[\s]*1)[$,\s]/.test( initialContent );

$.mobile.zoom = $.extend( {}, {
	enabled: !disabledInitially,
	locked: false,
	disable: function( lock ) {
		if ( !disabledInitially && !$.mobile.zoom.locked ) {
			meta.attr( "content", disabledZoom );
			$.mobile.zoom.enabled = false;
			$.mobile.zoom.locked = lock || false;
		}
	},
	enable: function( unlock ) {
		if ( !disabledInitially && ( !$.mobile.zoom.locked || unlock === true ) ) {
			meta.attr( "content", enabledZoom );
			$.mobile.zoom.enabled = true;
			$.mobile.zoom.locked = false;
		}
	},
	restore: function() {
		if ( !disabledInitially ) {
			meta.attr( "content", initialContent );
			$.mobile.zoom.enabled = true;
		}
	}
} );

return $.mobile.zoom;
} );

/*!
 * jQuery Mobile Textinput @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Text Inputs & Textareas
//>>group: Forms
//>>description: Enhances and consistently styles text inputs.
//>>docs: http://api.jquerymobile.com/textinput/
//>>demos: http://demos.jquerymobile.com/@VERSION/textinput/
//>>css.structure: ../css/structure/jquery.mobile.forms.textinput.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/textinput',[
			"jquery",
			"../../core",
			"../../widget",
			"../../degradeInputs",
			"../widget.theme",
			"../../zoom" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.widget( "mobile.textinput", {
	version: "@VERSION",

	options: {
		classes: {
			"ui-textinput": "ui-corner-all ui-shadow-inset",
			"ui-textinput-search-icon": "ui-icon ui-alt-icon ui-icon-search"
		},

		theme: "inherit",

		// This option defaults to true on iOS devices.
		preventFocusZoom: /iPhone|iPad|iPod/.test( navigator.platform ) && navigator.userAgent.indexOf( "AppleWebKit" ) > -1,
		enhanced: false
	},

	_create: function() {

		var options = this.options,
			isSearch = this.element.is( "[type='search'], :jqmData(type='search')" ),
			isTextarea = this.element[ 0 ].nodeName.toLowerCase() === "textarea";

		if ( this.element.prop( "disabled" ) ) {
			options.disabled = true;
		}

		$.extend( this, {
			isSearch: isSearch,
			isTextarea: isTextarea
		} );

		this._autoCorrect();

		if ( !options.enhanced ) {
			this._enhance();
		} else {
			this._outer = ( isTextarea ? this.element : this.element.parent() );
			if ( isSearch ) {
				this._searchIcon = this._outer.children( ".ui-textinput-search-icon" );
			}
		}

		this._addClass( this._outer,
			"ui-textinput ui-textinput-" + ( this.isSearch ? "search" : "text" ) );

		if ( this._searchIcon ) {
			this._addClass( this._searchIcon, "ui-textinput-search-icon" );
		}

		this._on( {
			"focus": "_handleFocus",
			"blur": "_handleBlur"
		} );

		if ( options.disabled !== undefined ) {
			this.element.prop( "disabled", !!options.disabled );
			this._toggleClass( this._outer, null, "ui-state-disabled", !!options.disabled );
		}

	},

	refresh: function() {
		this._setOptions( {
			"disabled": this.element.is( ":disabled" )
		} );
	},

	_themeElements: function() {
		return [
			{
				element: this._outer,
				prefix: "ui-body-"
			}
		];
	},

	_enhance: function() {
		var outer;

		if ( !this.isTextarea ) {
			outer = $( "<div>" );
			if ( this.isSearch ) {
				this._searchIcon = $( "<span>" ).prependTo( outer );
			}
		} else {
			outer = this.element;
		}

		this._outer = outer;

		// Now that we're done building up the wrapper, wrap the input in it
		if ( !this.isTextarea ) {
			outer.insertBefore( this.element ).append( this.element );
		}
	},

	widget: function() {
		return this._outer;
	},

	_autoCorrect: function() {

		// XXX: Temporary workaround for issue 785 (Apple bug 8910589).
		//      Turn off autocorrect and autocomplete on non-iOS 5 devices
		//      since the popup they use can't be dismissed by the user. Note
		//      that we test for the presence of the feature by looking for
		//      the autocorrect property on the input element. We currently
		//      have no test for iOS 5 or newer so we're temporarily using
		//      the touchOverflow support flag for jQM 1.0. Yes, I feel dirty.
		//      - jblas
		if ( typeof this.element[ 0 ].autocorrect !== "undefined" &&
				!$.support.touchOverflow ) {

			// Set the attribute instead of the property just in case there
			// is code that attempts to make modifications via HTML.
			this.element[ 0 ].setAttribute( "autocorrect", "off" );
			this.element[ 0 ].setAttribute( "autocomplete", "off" );
		}
	},

	_handleBlur: function() {
		this._removeClass( this._outer, null, "ui-focus" );
		if ( this.options.preventFocusZoom ) {
			$.mobile.zoom.enable( true );
		}
	},

	_handleFocus: function() {

		// In many situations, iOS will zoom into the input upon tap, this
		// prevents that from happening
		if ( this.options.preventFocusZoom ) {
			$.mobile.zoom.disable( true );
		}
		this._addClass( this._outer, null, "ui-focus" );
	},

	_setOptions: function( options ) {
		if ( options.disabled !== undefined ) {
			this.element.prop( "disabled", !!options.disabled );
			this._toggleClass( this._outer, null, "ui-state-disabled", !!options.disabled );
		}
		return this._superApply( arguments );
	},

	_destroy: function() {
		if ( this.options.enhanced ) {
			this.classesElementLookup = {};
			return;
		}
		if ( this._searchIcon ) {
			this._searchIcon.remove();
		}
		if ( !this.isTextarea ) {
			this.element.unwrap();
		}
	}
} );

return $.widget( "mobile.textinput", $.mobile.textinput, $.mobile.widget.theme );

} );

/*!
 * jQuery Mobile Form Reset @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Form Reset
//>>group: Forms
//>>description: A behavioral mixin that forces a widget to react to a form reset

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/reset',[
			"jquery",
			"../../core" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.behaviors.formReset = {
	_handleFormReset: function() {
		this._on( this.element.closest( "form" ), {
			reset: function() {
				this._delay( "_reset" );
			}
		} );
	}
};

return $.mobile.behaviors.formReset;

} );

/*!
 * jQuery Mobile Slider @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Slider
//>>group: Forms
//>>description: Slider form widget
//>>docs: http://api.jquerymobile.com/button/
//>>demos: http://demos.jquerymobile.com/@VERSION/button/
//>>css.structure: ../css/structure/jquery.mobile.forms.slider.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/slider',[
			"jquery",
			"../../core",
			"../../widget",
			"./textinput",
			"../../vmouse",
			"../widget.theme",
			"./reset" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.widget( "mobile.slider", $.extend( {
	version: "@VERSION",

	initSelector: "input[type='range'], :jqmData(type='range'), :jqmData(role='slider')",

	widgetEventPrefix: "slide",

	options: {
		theme: "inherit",
		trackTheme: "inherit",
		classes: {
			"ui-slider-track": "ui-shadow-inset ui-corner-all",
			"ui-slider-input": "ui-shadow-inset ui-corner-all"
		}
	},

	_create: function() {

		// TODO: Each of these should have comments explain what they're for
		var control = this.element,
			cType = control[ 0 ].nodeName.toLowerCase(),
			isRangeslider = control.parent().is( ":jqmData(role='rangeslider')" ),
			controlID = control.attr( "id" ),
			$label = $( "[for='" + controlID + "']" ),
			labelID = $label.attr( "id" ) || controlID + "-label",
			min = parseFloat( control.attr( "min" ) ),
			max = parseFloat( control.attr( "max" ) ),
			step = window.parseFloat( control.attr( "step" ) || 1 ),
			domHandle = document.createElement( "a" ),
			handle = $( domHandle ),
			domSlider = document.createElement( "div" ),
			slider = $( domSlider ),
			wrapper;

		$label.attr( "id", labelID );

		domHandle.setAttribute( "href", "#" );
		domSlider.setAttribute( "role", "application" );
		this._addClass( slider, "ui-slider-track" );
		this._addClass( handle, "ui-slider-handle" );
		domSlider.appendChild( domHandle );

		handle.attr( {
			"role": "slider",
			"aria-valuemin": min,
			"aria-valuemax": max,
			"aria-valuenow": this._value(),
			"aria-valuetext": this._value(),
			"title": this._value(),
			"aria-labelledby": labelID
		} );

		$.extend( this, {
			slider: slider,
			handle: handle,
			control: control,
			type: cType,
			step: step,
			max: max,
			min: min,
			isRangeslider: isRangeslider,
			dragging: false,
			beforeStart: null,
			userModified: false,
			mouseMoved: false
		} );

		// Monitor the input for updated values
		this._addClass( "ui-slider-input" );

		this._on( control, {
			"change": "_controlChange",
			"keyup": "_controlKeyup",
			"blur": "_controlBlur",
			"vmouseup": "_controlVMouseUp"
		} );

		slider.bind( "vmousedown", $.proxy( this._sliderVMouseDown, this ) )
			.bind( "vclick", false );

		// We have to instantiate a new function object for the unbind to work properly
		// since the method itself is defined in the prototype (causing it to unbind everything)
		this._on( document, { "vmousemove": "_preventDocumentDrag" } );
		this._on( slider.add( document ), { "vmouseup": "_sliderVMouseUp" } );

		slider.insertAfter( control );

		// Wrap in a div for styling purposes
		if ( !isRangeslider ) {
			wrapper = "<div class='ui-slider'></div>";

			control.add( slider ).wrapAll( wrapper );
		}

		// Bind the handle event callbacks and set the context to the widget instance
		this._on( this.handle, {
			"vmousedown": "_handleVMouseDown",
			"keydown": "_handleKeydown",
			"keyup": "_handleKeyup"
		} );

		this.handle.bind( "vclick", false );

		this._handleFormReset();

		this.refresh( undefined, undefined, true );
	},

	_setOptions: function( options ) {
		if ( options.disabled !== undefined ) {
			this._setDisabled( options.disabled );
		}
		this._super( options );
	},

	_controlChange: function( event ) {

		// If the user dragged the handle, the "change" event was triggered from
		// inside refresh(); don't call refresh() again
		if ( this._trigger( "controlchange", event ) === false ) {
			return false;
		}
		if ( !this.mouseMoved ) {
			this.refresh( this._value(), true );
		}
	},

	_controlKeyup: function( /* event */ ) {

		// Necessary?
		this.refresh( this._value(), true, true );
	},

	_controlBlur: function( /* event */ ) {
		this.refresh( this._value(), true );
	},

	// It appears the clicking the up and down buttons in chrome on
	// range/number inputs doesn't trigger a change until the field is
	// blurred. Here we check thif the value has changed and refresh
	_controlVMouseUp: function( /* event */ ) {
		this._checkedRefresh();
	},

	// NOTE force focus on handle
	_handleVMouseDown: function( /* event */ ) {
		this.handle.focus();
	},

	_handleKeydown: function( event ) {
		var index = this._value();
		if ( this.options.disabled ) {
			return;
		}

		// In all cases prevent the default and mark the handle as active
		switch ( event.keyCode ) {
		case $.mobile.keyCode.HOME:
		case $.mobile.keyCode.END:
		case $.mobile.keyCode.PAGE_UP:
		case $.mobile.keyCode.PAGE_DOWN:
		case $.mobile.keyCode.UP:
		case $.mobile.keyCode.RIGHT:
		case $.mobile.keyCode.DOWN:
		case $.mobile.keyCode.LEFT:
			event.preventDefault();

			if ( !this._keySliding ) {
				this._keySliding = true;

				// TODO: We don't use this class for styling. Do we need it?
				this._addClass( this.handle, null, "ui-state-active" );
			}

			break;
		}

		// Move the slider according to the keypress
		switch ( event.keyCode ) {
		case $.mobile.keyCode.HOME:
			this.refresh( this.min );
			break;
		case $.mobile.keyCode.END:
			this.refresh( this.max );
			break;
		case $.mobile.keyCode.PAGE_UP:
		case $.mobile.keyCode.UP:
		case $.mobile.keyCode.RIGHT:
			this.refresh( index + this.step );
			break;
		case $.mobile.keyCode.PAGE_DOWN:
		case $.mobile.keyCode.DOWN:
		case $.mobile.keyCode.LEFT:
			this.refresh( index - this.step );
			break;
		}
	},

	_handleKeyup: function( /* event */ ) {
		if ( this._keySliding ) {
			this._keySliding = false;
			this._removeClass( this.handle, null, "ui-state-active" ); /* See comment above. */
		}
	},

	_sliderVMouseDown: function( event ) {

		// NOTE: we don't do this in refresh because we still want to
		//       support programmatic alteration of disabled inputs
		if ( this.options.disabled || !( event.which === 1 ||
			event.which === 0 || event.which === undefined ) ) {
			return false;
		}
		if ( this._trigger( "beforestart", event ) === false ) {
			return false;
		}
		this.dragging = true;
		this.userModified = false;
		this.mouseMoved = false;

		this.refresh( event );
		this._trigger( "start" );
		return false;
	},

	_sliderVMouseUp: function() {
		if ( this.dragging ) {
			this.dragging = false;
			this.mouseMoved = false;
			this._trigger( "stop" );
			return false;
		}
	},

	_preventDocumentDrag: function( event ) {

		// NOTE: we don't do this in refresh because we still want to
		//       support programmatic alteration of disabled inputs
		if ( this._trigger( "drag", event ) === false ) {
			return false;
		}
		if ( this.dragging && !this.options.disabled ) {

			// This.mouseMoved must be updated before refresh() because it will be
			// used in the control "change" event
			this.mouseMoved = true;

			this.refresh( event );

			// Only after refresh() you can calculate this.userModified
			this.userModified = this.beforeStart !== this.element[ 0 ].selectedIndex;
			return false;
		}
	},

	_checkedRefresh: function() {
		if ( this.value !== this._value() ) {
			this.refresh( this._value() );
		}
	},

	_value: function() {
		return parseFloat( this.element.val() );
	},

	_reset: function() {
		this.refresh( undefined, false, true );
	},

	refresh: function( val, isfromControl, preventInputUpdate ) {

		// NOTE: we don't return here because we want to support programmatic
		//       alteration of the input value, which should still update the slider

		var self = this,
			left, width, data, tol,
			pxStep, percent,
			control, min, max, step,
			newval, valModStep, alignValue, percentPerStep,
			handlePercent, aPercent, bPercent,
			valueChanged;

		this._addClass( self.slider, "ui-slider-track" );
		if ( this.options.disabled || this.element.prop( "disabled" ) ) {
			this.disable();
		}

		// Set the stored value for comparison later
		this.value = this._value();
		this._addClass( this.handle, null, "ui-button ui-shadow" );

		control = this.element;
		min = parseFloat( control.attr( "min" ) );
		max = parseFloat( control.attr( "max" ) );
		step = ( parseFloat( control.attr( "step" ) ) > 0 ) ?
				parseFloat( control.attr( "step" ) ) : 1;

		if ( typeof val === "object" ) {
			data = val;

			// A slight tolerance helped get to the ends of the slider
			tol = 8;

			left = this.slider.offset().left;
			width = this.slider.width();
			pxStep = width / ( ( max - min ) / step );
			if ( !this.dragging ||
					data.pageX < left - tol ||
					data.pageX > left + width + tol ) {
				return;
			}
			if ( pxStep > 1 ) {
				percent = ( ( data.pageX - left ) / width ) * 100;
			} else {
				percent = Math.round( ( ( data.pageX - left ) / width ) * 100 );
			}
		} else {
			if ( val == null ) {
				val = parseFloat( control.val() || 0 ) ;
			}
			percent = ( parseFloat( val ) - min ) / ( max - min ) * 100;
		}

		if ( isNaN( percent ) ) {
			return;
		}

		newval = ( percent / 100 ) * ( max - min ) + min;

		//From jQuery UI slider, the following source will round to the nearest step
		valModStep = ( newval - min ) % step;
		alignValue = newval - valModStep;

		if ( Math.abs( valModStep ) * 2 >= step ) {
			alignValue += ( valModStep > 0 ) ? step : ( -step );
		}

		percentPerStep = 100 / ( ( max - min ) / step );

		// Since JavaScript has problems with large floats, round
		// the final value to 5 digits after the decimal point (see jQueryUI: #4124)
		newval = parseFloat( alignValue.toFixed( 5 ) );

		if ( typeof pxStep === "undefined" ) {
			pxStep = width / ( ( max - min ) / step );
		}
		if ( pxStep > 1 ) {
			percent = ( newval - min ) * percentPerStep * ( 1 / step );
		}
		if ( percent < 0 ) {
			percent = 0;
		}

		if ( percent > 100 ) {
			percent = 100;
		}

		if ( newval < min ) {
			newval = min;
		}

		if ( newval > max ) {
			newval = max;
		}

		this.handle.css( "left", percent + "%" );

		this.handle[ 0 ].setAttribute( "aria-valuenow",  newval );

		this.handle[ 0 ].setAttribute( "aria-valuetext", newval );

		this.handle[ 0 ].setAttribute( "title", newval );

		if ( this.valuebg ) {
			this.valuebg.css( "width", percent + "%" );
		}

		// Drag the label widths
		if ( this._labels ) {
			handlePercent = this.handle.width() / this.slider.width() * 100;
			aPercent = percent && handlePercent + ( 100 - handlePercent ) * percent / 100;
			bPercent = percent === 100 ? 0 : Math.min( handlePercent + 100 - aPercent, 100 );

			this._labels.each( function() {
				var ab = $( this ).hasClass( "ui-slider-label-a" );
				$( this ).width( ( ab ? aPercent : bPercent ) + "%" );
			} );
		}

		if ( !preventInputUpdate ) {
			valueChanged = false;

			// Update control"s value
			valueChanged = parseFloat( control.val() ) !== newval;
			control.val( newval );

			if ( this._trigger( "beforechange", val ) === false ) {
				return false;
			}
			if ( !isfromControl && valueChanged ) {
				control.trigger( "change" );
			}
		}
	},

	_themeElements: function() {
		return [
			{
				element: this.handle,
				prefix: "ui-button-"
			},
			{
				element: this.control,
				prefix: "ui-body-"
			},
			{
				element: this.slider,
				prefix: "ui-body-",
				option: "trackTheme"
			},
			{
				element: this.element,
				prefix: "ui-body-"
			}
		];
	},

	_setDisabled: function( value ) {
		value = !!value;
		this.element.prop( "disabled", value );

		this._toggleClass( this.slider, null, "ui-state-disabled", value );
		this.slider.attr( "aria-disabled", value );

		this._toggleClass( null, "ui-state-disabled", value );
	}

}, $.mobile.behaviors.formReset ) );

return $.widget( "mobile.slider", $.mobile.slider, $.mobile.widget.theme );

} );

/*!
 * jQuery Mobile Slider Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Slider
//>>group: Forms
//>>description: Deprecated Slider features

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/slider.backcompat',[
			"jquery",
			"../widget.backcompat",
			"./slider" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {

	$.widget( "mobile.slider", $.mobile.slider, {
		options: {
			corners: true,
			mini: false,
			highlight: false
		},
		classProp: "ui-slider",
		_create: function() {
			this._super();

			if ( this.options.mini ) {
				this._addClass( this.slider, "ui-mini", null );
			}

			if ( this.options.highlight ) {
				this._setHighlight( this.options.highlight );
			}

			if ( this.options.corners !== undefined ) {
				this._setCorners( this.options.corners );
			}
		},

		refresh: function( val, isfromControl, preventInputUpdate ) {
			this._super( val, isfromControl, preventInputUpdate );
			if ( this.options.highlight && this.slider.find( ".ui-slider-bg" ).length === 0 ) {
				this.valuebg = ( function( slider ) {
					var bg = document.createElement( "div" );
					bg.className = "ui-slider-bg " + "ui-button-active";
					return $( bg ).prependTo( slider );
				} )( this.slider );
			}
		},

		_setHighlight: function( value ) {
			if ( value ) {
				this.options.highlight = !!value;
				this.refresh();
			} else if ( this.valuebg ) {
				this.valuebg.remove();
				this.valuebg = false;
			}
		},

		_setCorners: function( value ) {
			this._toggleClass( this.slider, null, "ui-corner-all", value );
			this._toggleClass( this.element, null, "ui-corner-all", value );
		}
	} );

	$.widget( "mobile.slider", $.mobile.slider, $.mobile.widget.backcompat );

}

return $.mobile.slider;

} );

/*!
 * jQuery Mobile Slider Tooltip @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Slidertooltip
//>>group: Forms
//>>description: Slider tooltip extension
//>>docs: http://api.jquerymobile.com/slider/
//>>demos: http://demos.jquerymobile.com/@VERSION/slider-tooltip/
//>>css.structure: ../css/structure/jquery.mobile.forms.slider.tooltip.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/slider.tooltip',[
			"jquery",
			"./slider" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var popup;

function getPopup() {
	if ( !popup ) {
		popup = $( "<div></div>", {
			"class": "ui-slider-popup ui-shadow ui-corner-all"
		} );
	}
	return popup.clone();
}

return $.widget( "mobile.slider", $.mobile.slider, {
	options: {
		popupEnabled: false,
		showValue: false
	},

	_create: function() {
		this._super();

		$.extend( this, {
			_currentValue: null,
			_popup: null,
			_popupVisible: false
		} );

		this._setOption( "popupEnabled", this.options.popupEnabled );

		this._on( this.handle.add( this.slider ), { "vmousedown": "_showPopup" } );
		this._on( this.slider.add( this.document ), { "vmouseup": "_hidePopup" } );
		this._refresh();
	},

	// position the popup centered 5px above the handle
	_positionPopup: function() {
		var dstOffset = this.handle.offset();

		this._popup.offset( {
			left: dstOffset.left + ( this.handle.width() - this._popup.width() ) / 2,
			top: dstOffset.top - this._popup.outerHeight() - 5
		} );
	},

	_setOption: function( key, value ) {
		this._super( key, value );

		if ( key === "showValue" ) {
			this.handle.html( value && !this.options.mini ? this._value() : "" );
		} else if ( key === "popupEnabled" ) {
			if ( value && !this._popup ) {
				this._popup = getPopup()
					.addClass( "ui-body-" + ( this.options.theme || "a" ) )
					.hide()
					.insertBefore( this.element );
			}
		}
	},

	// show value on the handle and in popup
	refresh: function() {
		this._super.apply( this, arguments );
		this._refresh();
	},

	_refresh: function() {
		var o = this.options, newValue;

		if ( o.popupEnabled ) {
			// remove the title attribute from the handle (which is
			// responsible for the annoying tooltip); NB we have
			// to do it here as the jqm slider sets it every time
			// the slider's value changes :(
			this.handle.removeAttr( "title" );
		}

		newValue = this._value();
		if ( newValue === this._currentValue ) {
			return;
		}
		this._currentValue = newValue;

		if ( o.popupEnabled && this._popup ) {
			this._positionPopup();
			this._popup.html( newValue );
		}

		if ( o.showValue && !this.options.mini ) {
			this.handle.html( newValue );
		}
	},

	_showPopup: function() {
		if ( this.options.popupEnabled && !this._popupVisible ) {
			this._popup.show();
			this._positionPopup();
			this._popupVisible = true;
		}
	},

	_hidePopup: function() {
		var o = this.options;

		if ( o.popupEnabled && this._popupVisible ) {
			if ( o.showValue && !o.mini ) {
				this.handle.html( this._value() );
			}
			this._popup.hide();
			this._popupVisible = false;
		}
	}
} );

} );

/*!
 * jQuery Mobile Flipswitch @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Flip Switch
//>>group: Forms
//>>description: Consistent styling for native select menus. Tapping opens a native select menu.
//>>docs: http://api.jquerymobile.com/flipswitch/
//>>demos: http://demos.jquerymobile.com/@VERSION/flipswitch/
//>>css.structure: ../css/structure/jquery.mobile.forms.flipswitch.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/flipswitch',[
			"jquery",
			"../../core",
			"../../widget",
			"../../zoom",
			"./reset" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var selectorEscapeRegex = /([!"#$%&'()*+,./:;<=>?@[\]^`{|}~])/g;

return $.widget( "mobile.flipswitch", $.extend( {
	version: "@VERSION",

	options: {
		onText: "On",
		offText: "Off",
		theme: null,
		enhanced: false,
		classes: {
			"ui-flipswitch": "ui-shadow-inset ui-corner-all",
			"ui-flipswitch-on": "ui-shadow"
		}
	},

	_create: function() {
		var labels;

		this._originalTabIndex = this.element.attr( "tabindex" );
		this.type = this.element[ 0 ].nodeName.toLowerCase();

		if ( !this.options.enhanced ) {
			this._enhance();
		} else {
			$.extend( this, {
				flipswitch: this.element.parent(),
				on: this.element.find( ".ui-flipswitch-on" ).eq( 0 ),
				off: this.element.find( ".ui-flipswitch-off" ).eq( 0 )
			} );
		}

		this._handleFormReset();

		this.element.attr( "tabindex", "-1" );
		this._on( {
			"focus": "_handleInputFocus"
		} );

		if ( this.element.is( ":disabled" ) ) {
			this._setOptions( {
				"disabled": true
			} );
		}

		this._on( this.flipswitch, {
			"click": "_toggle",
			"swipeleft": "_left",
			"swiperight": "_right"
		} );

		this._on( this.on, {
			"keydown": "_keydown"
		} );

		this._on( {
			"change": "refresh"
		} );

		// On iOS we need to prevent default when the label is clicked, otherwise it drops down
		// the native select menu. We nevertheless pass the click onto the element like the
		// native code would.
		if ( this.element[ 0 ].nodeName.toLowerCase() === "select" ) {
			labels = this._findLabels();
			if ( labels.length ) {
				this._on( labels, {
					"click": function( event ) {
						this.element.click();
						event.preventDefault();
					}
				} );
			}
		}
	},

	_handleInputFocus: function() {
		this.on.focus();
	},

	widget: function() {
		return this.flipswitch;
	},

	_left: function() {
		this.flipswitch.removeClass( "ui-flipswitch-active" );
		if ( this.type === "select" ) {
			this.element.get( 0 ).selectedIndex = 0;
		} else {
			this.element.prop( "checked", false );
		}
		this.element.trigger( "change" );
	},

	_right: function() {
		this._addClass( this.flipswitch, "ui-flipswitch-active" );
		if ( this.type === "select" ) {
			this.element.get( 0 ).selectedIndex = 1;
		} else {
			this.element.prop( "checked", true );
		}
		this.element.trigger( "change" );
	},

	_enhance: function() {
		var flipswitch = $( "<div>" ),
			options = this.options,
			element = this.element,
			tabindex = this._originalTabIndex || 0,
			theme = options.theme ? options.theme : "inherit",

			// The "on" button is an anchor so it's focusable
			on = $( "<span tabindex='" + tabindex + "'></span>" ),
			off = $( "<span></span>" ),
			onText = ( this.type === "input" ) ?
				options.onText : element.find( "option" ).eq( 1 ).text(),
			offText = ( this.type === "input" ) ?
				options.offText : element.find( "option" ).eq( 0 ).text();

		this._addClass( on, "ui-flipswitch-on", "ui-button ui-button-inherit" );
		on.text( onText );
		this._addClass( off, "ui-flipswitch-off" );
		off.text( offText );

		this._addClass( flipswitch, "ui-flipswitch", "ui-bar-" + theme + " " +
				( ( element.is( ":checked" ) ||
				element
					.find( "option" )
						.eq( 1 )
						.is( ":selected" ) ) ? "ui-flipswitch-active" : "" ) +
				( element.is( ":disabled" ) ? " ui-state-disabled" : "" ) );

		flipswitch.append( on, off );

		this._addClass( "ui-flipswitch-input" );
		element.after( flipswitch ).appendTo( flipswitch );

		$.extend( this, {
			flipswitch: flipswitch,
			on: on,
			off: off
		} );
	},

	_reset: function() {
		this.refresh();
	},

	refresh: function() {
		var direction,
			existingDirection = this.flipswitch
				.hasClass( "ui-flipswitch-active" ) ? "_right" : "_left";

		if ( this.type === "select" ) {
			direction = ( this.element.get( 0 ).selectedIndex > 0 ) ? "_right" : "_left";
		} else {
			direction = this.element.prop( "checked" ) ? "_right" : "_left";
		}

		if ( direction !== existingDirection ) {
			this[ direction ]();
		}
	},

	// Copied with modifications from checkboxradio
	_findLabels: function() {
		var input = this.element[ 0 ],
			labelsList = input.labels;

		if ( labelsList && labelsList.length ) {
			labelsList = $( labelsList );
		} else {
			labelsList = this.element.closest( "label" );
			if ( labelsList.length === 0 ) {

				// NOTE: Windows Phone could not find the label through a selector
				// filter works though.
				labelsList = $( this.document[ 0 ].getElementsByTagName( "label" ) )
					.filter( "[for='" +
						input.getAttribute( "id" ).replace( selectorEscapeRegex, "\\$1" ) +
						"']" );
			}
		}

		return labelsList;
	},

	_toggle: function() {
		var direction = this.flipswitch.hasClass( "ui-flipswitch-active" ) ? "_left" : "_right";

		this[ direction ]();
	},

	_keydown: function( e ) {
		if ( e.which === $.mobile.keyCode.LEFT ) {
			this._left();
		} else if ( e.which === $.mobile.keyCode.RIGHT ) {
			this._right();
		} else if ( e.which === $.mobile.keyCode.SPACE ) {
			this._toggle();
			e.preventDefault();
		}
	},

	_setOptions: function( options ) {
		if ( options.theme !== undefined ) {
			var currentTheme = this.options.theme ? this.options.theme : "inherit",
				newTheme = options.theme ? options.theme : "inherit";

			this._removeClass( this.flipswitch, null,  "ui-bar-" + currentTheme );
			this._addClass( this.flipswitch, null,  "ui-bar-" + newTheme );
		}
		if ( options.onText !== undefined ) {
			this.on.text( options.onText );
		}
		if ( options.offText !== undefined ) {
			this.off.text( options.offText );
		}
		if ( options.disabled !== undefined ) {
			this._toggleClass( this.flipswitch, null, "ui-state-disabled", options.disabled );
		}

		this._super( options );
	},

	_destroy: function() {
		if ( this.options.enhanced ) {
			return;
		}
		if ( this._originalTabIndex != null ) {
			this.element.attr( "tabindex", this._originalTabIndex );
		} else {
			this.element.removeAttr( "tabindex" );
		}
		this.on.remove();
		this.off.remove();
		this.element.unwrap();
		this.element.removeClass( "ui-flipswitch-input" );
		this.flipswitch.remove();
	}

}, $.mobile.behaviors.formReset ) );

} );

/*!
 * jQuery Mobile Flipswitch Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Flipswitch
//>>group: Forms
//>>description: Deprecated rangeslider features

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/flipswitch.backcompat',[
			"jquery",
			"../widget.backcompat",
			"./flipswitch" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {

	$.widget( "mobile.flipswitch", $.mobile.flipswitch, {
		options: {
			corners: true,
			mini: false,
			wrapperClass: null
		},
		classProp: "ui-flipswitch"
	} );

	$.widget( "mobile.flipswitch", $.mobile.flipswitch, $.mobile.widget.backcompat );

}

return $.mobile.flipswitch;

} );

/*!
 * jQuery Mobile Range Slider @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Range Slider
//>>group: Forms
//>>description: Range Slider form widget
//>>docs: http://api.jquerymobile.com/rangeslider/
//>>demos: http://demos.jquerymobile.com/@VERSION/rangeslider/
//>>css.structure: ../css/structure/jquery.mobile.forms.rangeslider.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/rangeslider',[
			"jquery",
			"../../core",
			"../../widget",
			"./textinput",
			"../../vmouse",
			"./reset",
			"../widget.theme",
			"./slider" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.widget( "mobile.rangeslider", $.extend( {
	version: "@VERSION",

	options: {
		theme: "inherit",
		trackTheme: "inherit"
	},

	_create: function() {
		var _inputFirst = this.element.find( "input" ).first(),
		_inputLast = this.element.find( "input" ).last(),
		_label = this.element.find( "label" ).first(),
		_sliderWidgetFirst = $.data( _inputFirst.get( 0 ), "mobile-slider" ) ||
			$.data( _inputFirst.slider().get( 0 ), "mobile-slider" ),
		_sliderWidgetLast = $.data( _inputLast.get( 0 ), "mobile-slider" ) ||
			$.data( _inputLast.slider().get( 0 ), "mobile-slider" ),
		_sliderFirst = _sliderWidgetFirst.slider,
		_sliderLast = _sliderWidgetLast.slider,
		firstHandle = _sliderWidgetFirst.handle,
		_sliders = $( "<div>" );
		this._addClass( _sliders, "ui-rangeslider-sliders" );
		_sliders.appendTo( this.element );

		this._addClass( _inputFirst, "ui-rangeslider-first" );
		this._addClass( _inputLast, "ui-rangeslider-last" );
		this._addClass( "ui-rangeslider" );

		_sliderFirst.appendTo( _sliders );
		_sliderLast.appendTo( _sliders );
		_label.insertBefore( this.element );
		firstHandle.prependTo( _sliderLast );

		$.extend( this, {
			_inputFirst: _inputFirst,
			_inputLast: _inputLast,
			_sliderFirst: _sliderFirst,
			_sliderLast: _sliderLast,
			_label: _label,
			_targetVal: null,
			_sliderTarget: false,
			_sliders: _sliders,
			_proxy: false
		} );

		this.refresh();
		this._on( this.element.find( "input.ui-slider-input" ), {
			"slidebeforestart": "_slidebeforestart",
			"slidestop": "_slidestop",
			"slidedrag": "_slidedrag",
			"slidebeforechange": "_change",
			"blur": "_change",
			"keyup": "_change"
		} );
		this._on( {
			"mousedown":"_change"
		} );
		this._on( this.element.closest( "form" ), {
			"reset":"_handleReset"
		} );
		this._on( firstHandle, {
			"vmousedown": "_dragFirstHandle"
		} );
	},
	_handleReset: function() {
		var self = this;

		// We must wait for the stack to unwind before updating
		// otherwise sliders will not have updated yet
		setTimeout( function() {
			self._updateHighlight();
		}, 0 );
	},

	_dragFirstHandle: function( event ) {

		// If the first handle is dragged send the event to the first slider
		$.data( this._inputFirst.get( 0 ), "mobile-slider" ).dragging = true;
		$.data( this._inputFirst.get( 0 ), "mobile-slider" ).refresh( event );
		$.data( this._inputFirst.get( 0 ), "mobile-slider" )._trigger( "start" );
		return false;
	},

	_slidedrag: function( event ) {
		var first = $( event.target ).is( this._inputFirst ),
			otherSlider = ( first ) ? this._inputLast : this._inputFirst;

		this._sliderTarget = false;

		// If the drag was initiated on an extreme and the other handle is
		// focused send the events to the closest handle
		if ( ( this._proxy === "first" && first ) || ( this._proxy === "last" && !first ) ) {
			$.data( otherSlider.get( 0 ), "mobile-slider" ).dragging = true;
			$.data( otherSlider.get( 0 ), "mobile-slider" ).refresh( event );
			return false;
		}
	},

	_slidestop: function( event ) {
		var first = $( event.target ).is( this._inputFirst );

		this._proxy = false;

		// This stops dragging of the handle and brings the active track to the front
		// this makes clicks on the track go the the last handle used
		this.element.find( "input" ).trigger( "vmouseup" );
		this._sliderFirst.css( "z-index", first ? 1 : "" );
	},

	_slidebeforestart: function( event ) {
		this._sliderTarget = false;

		// If the track is the target remember this and the original value
		if ( $( event.originalEvent.target ).hasClass( "ui-slider-track" ) ) {
			this._sliderTarget = true;
			this._targetVal = $( event.target ).val();
		}
	},

	_setOptions: function( options ) {
		if ( options.theme !== undefined ) {
			this._setTheme( options.theme );
		}

		if ( options.trackTheme !== undefined ) {
			this._setTrackTheme( options.trackTheme );
		}

		if ( options.disabled !== undefined ) {
			this._setDisabled( options.disabled );
		}

		this._super( options );
		this.refresh();
	},

	refresh: function() {
		var $el = this.element,
			o = this.options;

		if ( this._inputFirst.is( ":disabled" ) || this._inputLast.is( ":disabled" ) ) {
			this.options.disabled = true;
		}

		$el.find( "input" ).slider( {
			theme: o.theme,
			trackTheme: o.trackTheme,
			disabled: o.disabled
		} ).slider( "refresh" );
		this._updateHighlight();
	},

	_change: function( event ) {
		if ( event.type === "keyup" ) {
			this._updateHighlight();
			return false;
		}

		var self = this,
			min = parseFloat( this._inputFirst.val(), 10 ),
			max = parseFloat( this._inputLast.val(), 10 ),
			first = $( event.target ).hasClass( "ui-rangeslider-first" ),
			thisSlider = first ? this._inputFirst : this._inputLast,
			otherSlider = first ? this._inputLast : this._inputFirst;

		if ( ( this._inputFirst.val() > this._inputLast.val() && event.type === "mousedown" &&
			!$( event.target ).hasClass( "ui-slider-handle" ) ) ) {
			thisSlider.blur();
		} else if ( event.type === "mousedown" ) {
			return;
		}
		if ( min > max && !this._sliderTarget ) {

			// This prevents min from being greater than max
			thisSlider.val( first ? max : min ).slider( "refresh" );
			this._trigger( "normalize" );
		} else if ( min > max ) {

			// This makes it so clicks on the target on either extreme go to the closest handle
			thisSlider.val( this._targetVal ).slider( "refresh" );

			// You must wait for the stack to unwind so
			// first slider is updated before updating second
			setTimeout( function() {
				otherSlider.val( first ? min : max ).slider( "refresh" );
				$.data( otherSlider.get( 0 ), "mobile-slider" ).handle.focus();
				self._sliderFirst.css( "z-index", first ? "" : 1 );
				self._trigger( "normalize" );
			}, 0 );
			this._proxy = ( first ) ? "first" : "last";
		}

		// Fixes issue where when both _sliders are at min they cannot be adjusted
		if ( min === max ) {
			$.data( thisSlider.get( 0 ), "mobile-slider" ).handle.css( "z-index", 1 );
			$.data( otherSlider.get( 0 ), "mobile-slider" ).handle.css( "z-index", 0 );
		} else {
			$.data( otherSlider.get( 0 ), "mobile-slider" ).handle.css( "z-index", "" );
			$.data( thisSlider.get( 0 ), "mobile-slider" ).handle.css( "z-index", "" );
		}

		this._updateHighlight();

		if ( min > max ) {
			return false;
		}
	},

	_themeElements: function() {
		return [
			{
				element: this.element.find( ".ui-slider-track" ),
				prefix: "ui-bar-"
			}
		];
	},

	_updateHighlight: function() {
		var min = parseInt( $.data( this._inputFirst.get( 0 ), "mobile-slider" )
								.handle.get( 0 ).style.left, 10 ),
			max = parseInt( $.data( this._inputLast.get( 0 ), "mobile-slider" )
								.handle.get( 0 ).style.left, 10 ),
			width = ( max - min );

		this.element.find( ".ui-slider-bg" ).css( {
			"margin-left": min + "%",
			"width": width + "%"
		} );
	},

	_setTheme: function( value ) {
		this._inputFirst.slider( "option", "theme", value );
		this._inputLast.slider( "option", "theme", value );
	},

	_setTrackTheme: function( value ) {
		this._inputFirst.slider( "option", "trackTheme", value );
		this._inputLast.slider( "option", "trackTheme", value );
	},

	_setDisabled: function( value ) {
		this._inputFirst.prop( "disabled", value );
		this._inputLast.prop( "disabled", value );
	},

	_destroy: function() {
		this._label.prependTo( this.element );
		this._inputFirst.after( this._sliderFirst );
		this._inputLast.after( this._sliderLast );
		this._sliders.remove();
		this.element.find( "input" ).slider( "destroy" );
	}

}, $.mobile.behaviors.formReset ) );

return $.widget( "mobile.rangeslider", $.mobile.rangeslider, $.mobile.widget.theme );

} );

/*!
 * jQuery Mobile Rangeslider Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Rangeslider
//>>group: Forms
//>>description: Deprecated rangeslider features

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/rangeslider.backcompat',[
			"jquery",
			"../widget.backcompat",
			"./rangeslider" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {

	$.widget( "mobile.rangeslider", $.mobile.rangeslider, {
		options: {
			corners: true,
			mini: false,
			highlight: true
		},
		classProp: "ui-rangeslider",
		_create: function() {
			this._super();

			this.element.find( "input" ).slider( {
				mini: this.options.mini,
				highlight: this.options.highlight
			} ).slider( "refresh" );

			this._updateHighlight();

			if ( this.options.mini ) {
				this._addClass( "ui-mini", null );
				this._addClass( this._sliderFirst, "ui-mini", null );
				this._addClass( this._sliderLast, "ui-mini", null );
			}
		}
	} );

	$.widget( "mobile.rangeslider", $.mobile.rangeslider, $.mobile.widget.backcompat );

}

return $.mobile.rangeslider;

} );

/*!
 * jQuery Mobile Textinput Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Text Inputs & Textareas Backcompat
//>>group: Forms
//>>description: Backcompat for textinput widgets.
//>>docs: http://api.jquerymobile.com/textinput/
//>>demos: http://demos.jquerymobile.com/@VERSION/textinput/
//>>css.structure: ../css/structure/jquery.mobile.forms.textinput.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/textinput.backcompat',[
			"jquery",
			"../widget.backcompat",
			"./textinput" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {
	$.widget( "mobile.textinput", $.mobile.textinput, {
		initSelector: "input[type='text']," +
			"input[type='search']," +
			":jqmData(type='search')," +
			"input[type='number']:not(:jqmData(type='range'))," +
			":jqmData(type='number')," +
			"input[type='password']," +
			"input[type='email']," +
			"input[type='url']," +
			"input[type='tel']," +
			"textarea," +
			"input[type='time']," +
			"input[type='date']," +
			"input[type='month']," +
			"input[type='week']," +
			"input[type='datetime']," +
			"input[type='datetime-local']," +
			"input[type='color']," +
			"input:not([type])," +
			"input[type='file']",
		options: {
			corners: true,
			mini: false,
			wrapperClass: null
		},
		classProp: "ui-textinput"
	} );
	$.widget( "mobile.textinput", $.mobile.textinput, $.mobile.widget.backcompat );
}

return $.mobile.textinput;

} );

/*!
 * jQuery Mobile Clear Button @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Text Input Clear Button
//>>group: Forms
//>>description: Add the ability to have a clear button
//>>docs: http://api.jquerymobile.com/textinput/#option-clearBtn
//>>demos: http://demos.jquerymobile.com/@VERSION/textinput/
//>>css.structure: ../css/structure/jquery.mobile.forms.textinput.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/clearButton',[
			"jquery",
			"./textinput" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.widget( "mobile.textinput", $.mobile.textinput, {
	options: {
		classes: {
			"ui-textinput-clear-button": "ui-corner-all"
		},
		clearBtn: false,
		clearBtnText: "Clear text"
	},

	_create: function() {
		this._super();

		if ( this.isSearch ) {
			this.options.clearBtn = true;
		}

		// We do nothing on startup if the options is off or if this is not a wrapped input
		if ( !this.options.clearBtn || this.isTextarea ) {
			return;
		}

		if ( this.options.enhanced ) {
			this._clearButton = this._outer.children( ".ui-textinput-clear-button" );
			this._clearButtonIcon = this._clearButton
				.children( ".ui-textinput-clear-button-icon" );
			this._toggleClasses( true );
			this._bindClearEvents();
		} else {
			this._addClearButton();
		}
	},

	_clearButtonClick: function( event ) {
		this.element.val( "" )
			.focus()
			.trigger( "change" );
		event.preventDefault();
	},

	_toggleClasses: function( add ) {
		this._toggleClass( this._outer, "ui-textinput-has-clear-button", null, add );
		this._toggleClass( this._clearButton, "ui-textinput-clear-button",
			"ui-button ui-button-icon-only ui-button-right", add );
		this._toggleClass( this._clearButtonIcon, "ui-textinput-clear-button-icon",
			"ui-icon-delete ui-icon", add );
		this._toggleClass( "ui-textinput-hide-clear", null, add );
	},

	_addClearButton: function() {
		this._clearButtonIcon = $( "<span>" );
		this._clearButton = $( "<a href='#' tabindex='-1' aria-hidden='true'></a>" )
			.attr( "title", this.options.clearBtnText )
			.text( this.options.clearBtnText )
			.append( this._clearButtonIcon );
		this._toggleClasses( true );
		this._clearButton.appendTo( this._outer );
		this._bindClearEvents();
		this._toggleClear();
	},

	_removeClearButton: function() {
		this._toggleClasses( false );
		this._unbindClearEvents();
		this._clearButton.remove();
		clearTimeout( this._toggleClearDelay );
		delete this._toggleClearDelay;
	},

	_bindClearEvents: function() {
		this._on( this._clearButton, {
			"click": "_clearButtonClick"
		} );

		this._on( {
			"keyup": "_toggleClear",
			"change": "_toggleClear",
			"input": "_toggleClear",
			"focus": "_toggleClear",
			"blur": "_toggleClear",
			"cut": "_toggleClear",
			"paste": "_toggleClear"

		} );
	},

	_unbindClearEvents: function() {
		this._off( this._clearButton, "click" );
		this._off( this.element, "keyup change input focus blur cut paste" );
	},

	_setOptions: function( options ) {
		this._super( options );

		if ( options.clearBtn !== undefined && !this.isTextarea ) {
			if ( options.clearBtn ) {
				this._addClearButton();
			} else {
				this._removeClearButton();
			}
		}

		if ( options.clearBtnText !== undefined && this._clearButton !== undefined ) {
			this._clearButton.text( options.clearBtnText )
				.attr( "title", options.clearBtnText );
		}
	},

	_toggleClear: function() {
		this._toggleClearDelay = this._delay( "_toggleClearClass", 0 );
	},

	_toggleClearClass: function() {
		this._toggleClass( this._clearButton, "ui-textinput-clear-button-hidden",
			undefined, !this.element.val() );
		this._clearButton.attr( "aria-hidden", !this.element.val() );
		delete this._toggleClearDelay;
	},

	_destroy: function() {
		this._super();
		if ( !this.options.enhanced && this._clearButton ) {
			this._removeClearButton();
		}
	}

} );

} );

/*!
 * jQuery Mobile Autogrow @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Textarea Autogrow
//>>group: Forms
//>>description: Textarea elements automatically grow/shrink to accommodate their contents.
//>>docs: http://api.jquerymobile.com/textinput/#option-autogrow
//>>css.structure: ../css/structure/jquery.mobile.forms.textinput.autogrow.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/autogrow',[
			"jquery",
			"./textinput" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.widget( "mobile.textinput", $.mobile.textinput, {
	options: {
		autogrow: true,
		keyupTimeoutBuffer: 100
	},

	_create: function() {
		this._super();

		if ( this.options.autogrow && this.isTextarea ) {
			this._autogrow();
		}
	},

	_autogrow: function() {
		this._addClass( "ui-textinput-autogrow" );

		this._on( {
			"keyup": "_timeout",
			"change": "_timeout",
			"input": "_timeout",
			"paste": "_timeout"
		} );

		// Attach to the various you-have-become-visible notifications that the
		// various framework elements emit.
		// TODO: Remove all but the updatelayout handler once #6426 is fixed.
		this._handleShow( "create" );
		this._on( true, this.document, {
			"popupbeforeposition": "_handleShow",
			"updatelayout": "_handleShow",
			"panelopen": "_handleShow"
		} );
	},

	// Synchronously fix the widget height if this widget's parents are such
	// that they show/hide content at runtime. We still need to check whether
	// the widget is actually visible in case it is contained inside multiple
	// such containers. For example: panel contains collapsible contains
	// autogrow textinput. The panel may emit "panelopen" indicating that its
	// content has become visible, but the collapsible is still collapsed, so
	// the autogrow textarea is still not visible.
	_handleShow: function( event ) {
		if ( event === "create" || ( $.contains( event.target, this.element[ 0 ] ) &&
				this.element.is( ":visible" ) ) ) {

			if ( event !== "create" && event.type !== "popupbeforeposition" ) {
				this._addClass( "ui-textinput-autogrow-resize" );
				this.element
					.animationComplete(
						$.proxy( function() {
							this._removeClass( "ui-textinput-autogrow-resize" );
						}, this ),
						"transition" );
			}
			this._prepareHeightUpdate();
		}
	},

	_unbindAutogrow: function() {
		this._removeClass( "ui-textinput-autogrow" );
		this._off( this.element, "keyup change input paste" );
		this._off( this.document,
			"pageshow popupbeforeposition updatelayout panelopen" );
	},

	keyupTimeout: null,

	_prepareHeightUpdate: function( delay ) {
		if ( this.keyupTimeout ) {
			clearTimeout( this.keyupTimeout );
		}
		if ( delay === undefined ) {
			this._updateHeight();
		} else {
			this.keyupTimeout = this._delay( "_updateHeight", delay );
		}
	},

	_timeout: function() {
		this._prepareHeightUpdate( this.options.keyupTimeoutBuffer );
	},

	_updateHeight: function() {
		var paddingTop, paddingBottom, paddingHeight, scrollHeight, clientHeight,
			borderTop, borderBottom, borderHeight, height,
			scrollTop = this.window.scrollTop();
		this.keyupTimeout = 0;

		// IE8 textareas have the onpage property - others do not
		if ( !( "onpage" in this.element[ 0 ] ) ) {
			this.element.css( {
				"height": 0,
				"min-height": 0,
				"max-height": 0
			} );
		}

		scrollHeight = this.element[ 0 ].scrollHeight;
		clientHeight = this.element[ 0 ].clientHeight;
		borderTop = parseFloat( this.element.css( "border-top-width" ) );
		borderBottom = parseFloat( this.element.css( "border-bottom-width" ) );
		borderHeight = borderTop + borderBottom;
		height = scrollHeight + borderHeight + 15;

		// Issue 6179: Padding is not included in scrollHeight and
		// clientHeight by Firefox if no scrollbar is visible. Because
		// textareas use the border-box box-sizing model, padding should be
		// included in the new (assigned) height. Because the height is set
		// to 0, clientHeight == 0 in Firefox. Therefore, we can use this to
		// check if padding must be added.
		if ( clientHeight === 0 ) {
			paddingTop = parseFloat( this.element.css( "padding-top" ) );
			paddingBottom = parseFloat( this.element.css( "padding-bottom" ) );
			paddingHeight = paddingTop + paddingBottom;

			height += paddingHeight;
		}

		this.element.css( {
			"height": height,
			"min-height": "",
			"max-height": ""
		} );

		this.window.scrollTop( scrollTop );
	},

	refresh: function() {
		if ( this.options.autogrow && this.isTextarea ) {
			this._updateHeight();
		}
	},

	_setOptions: function( options ) {

		this._super( options );

		if ( options.autogrow !== undefined && this.isTextarea ) {
			if ( options.autogrow ) {
				this._autogrow();
			} else {
				this._unbindAutogrow();
			}
		}
	},

	_destroy: function() {
		this._unbindAutogrow();
		this._super();
	}

} );
} );

/*!
 * jQuery Mobile Select Menu @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Selects
//>>group: Forms
//>>description: Consistent styling for native select menus. Tapping opens a native select menu.
//>>docs: http://api.jquerymobile.com/selectmenu/
//>>demos: http://demos.jquerymobile.com/@VERSION/selectmenu/
//>>css.structure: ../css/structure/jquery.mobile.forms.select.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/select',[
			"jquery",
			"jquery-ui/labels",
			"../../core",
			"../../widget",
			"../../zoom",
			"../../navigation/path",
			"../widget.theme",
			"jquery-ui/form-reset-mixin" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var selectmenu = $.widget( "mobile.selectmenu", [ {
	version: "@VERSION",

	options: {
		classes: {
			"ui-selectmenu-button": "ui-corner-all ui-shadow"
		},
		theme: "inherit",
		icon: "caret-d",
		iconpos: "right",
		nativeMenu: true,

		// This option defaults to true on iOS devices.
		preventFocusZoom: /iPhone|iPad|iPod/.test( navigator.platform ) &&
			navigator.userAgent.indexOf( "AppleWebKit" ) > -1
	},

	_button: function() {
		return $( "<div/>" );
	},

	_themeElements: function() {
		return [
			{
				element: this.button,
				prefix: "ui-button-"
			}
		];
	},

	_setDisabled: function( value ) {
		this.element.prop( "disabled", value );
		this.button.attr( "aria-disabled", value );
		return this._setOption( "disabled", value );
	},

	_focusButton: function() {
		var that = this;

		setTimeout( function() {
			that.button.focus();
		}, 40 );
	},

	_selectOptions: function() {
		return this.element.find( "option" );
	},

	// Setup items that are generally necessary for select menu extension
	_preExtension: function() {
		var classes = "";

		this.element = this.element;
		this.selectWrapper = $( "<div>" );
		this._addClass( this.selectWrapper, "ui-selectmenu", classes );
		this.selectWrapper.insertBefore( this.element );
		this.element.detach();

		this.selectId = this.element.attr( "id" ) || ( "select-" + this.uuid );
		this.buttonId = this.selectId + "-button";
		this.isMultiple = this.element[ 0 ].multiple;

		this.element.appendTo( this.selectWrapper );
		this.label = this.element.labels().first();
	},

	_destroy: function() {
		if ( this.selectWrapper.length > 0 ) {
			this.element.insertAfter( this.selectWrapper );
			this.selectWrapper.remove();
		}
		this._unbindFormResetHandler();
	},

	_create: function() {
		var options = this.options,
			iconpos = options.icon ?
				( options.iconpos || this.element.attr( "data-" + this._ns() + "iconpos" ) ) :
					false;

		this._preExtension();

		this.button = this._button();

		this.button.attr( "id", this.buttonId );
		this._addClass( this.button, "ui-selectmenu-button", "ui-button" );
		this.button.insertBefore( this.element );

		if ( this.options.icon ) {
			this.icon = $( "<span>" );
			this._addClass( this.icon, "ui-selectmenu-button-icon",
				"ui-icon-" + options.icon + " ui-icon ui-widget-icon-float" +
					( iconpos === "right" ? "end" : "beginning" ) );
			this.button.prepend( this.icon );
		}

		this.setButtonText();

		// Opera does not properly support opacity on select elements
		// In Mini, it hides the element, but not its text
		// On the desktop,it seems to do the opposite
		// for these reasons, using the nativeMenu option results in a full native select in Opera
		if ( options.nativeMenu && window.opera && window.opera.version ) {
			this._addClass( this.button, "ui-selectmenu-nativeonly" );
		}

		// Add counter for multi selects
		if ( this.isMultiple ) {
			this.buttonCount = $( "<span>" ).hide();
			this._addClass( this.buttonCount, "ui-selectmenu-count-bubble",
				"ui-listview-item-count-bubble ui-body-inherit" );
			this._addClass( this.button, null, "ui-listview-item-has-count" );
			this.buttonCount.appendTo( this.button );
		}

		// Disable if specified
		if ( options.disabled || this.element.prop( "disabled" ) ) {
			this.disable();
		}

		// Events on native select
		this._on( this.element, {
			change: "refresh"
		} );

		this._bindFormResetHandler();

		this._on( this.button, {
			keydown: "_handleKeydown"
		} );

		this.build();
	},

	build: function() {
		var that = this;

		this.element
			.appendTo( that.button )
			.bind( "vmousedown", function() {

				// Add active class to button
				that.button.addClass( "ui-button-active" );
			} )
			.bind( "focus", function() {
				that.button.addClass( "ui-focus" );
			} )
			.bind( "blur", function() {
				that.button.removeClass( "ui-focus" );
			} )
			.bind( "focus vmouseover", function() {
				that.button.trigger( "vmouseover" );
			} )
			.bind( "vmousemove", function() {

				// Remove active class on scroll/touchmove
				that.button.removeClass( "ui-button-active" );
			} )
			.bind( "change blur vmouseout", function() {
				that.button.trigger( "vmouseout" )
					.removeClass( "ui-button-active" );
			} );

		// In many situations, iOS will zoom into the select upon tap, this prevents that from
		// happening
		that.button.bind( "vmousedown", function() {
			if ( that.options.preventFocusZoom ) {
				$.mobile.zoom.disable( true );
			}
		} );
		that.label.bind( "click focus", function() {
			if ( that.options.preventFocusZoom ) {
				$.mobile.zoom.disable( true );
			}
		} );
		that.element.bind( "focus", function() {
			if ( that.options.preventFocusZoom ) {
				$.mobile.zoom.disable( true );
			}
		} );
		that.button.bind( "mouseup", function() {
			if ( that.options.preventFocusZoom ) {
				setTimeout( function() {
					$.mobile.zoom.enable( true );
				}, 0 );
			}
		} );
		that.element.bind( "blur", function() {
			if ( that.options.preventFocusZoom ) {
				$.mobile.zoom.enable( true );
			}
		} );
	},

	selected: function() {
		return this._selectOptions().filter( ":selected" );
	},

	selectedIndices: function() {
		var that = this;

		return this.selected().map( function() {
			return that._selectOptions().index( this );
		} ).get();
	},

	setButtonText: function() {
		var that = this,
			selected = this.selected(),
			text = this.placeholder,
			span = $( "<span>" );

		this.button.children( "span" )
			.not( ".ui-selectmenu-count-bubble,.ui-selectmenu-button-icon" )
			.remove().end().end()
			.append( ( function() {
				if ( selected.length ) {
					text = selected.map( function() {
						return $( this ).text();
					} ).get().join( ", " );
				}

				if ( text ) {
					span.text( text );
				} else {

					// Set the contents to &nbsp; which we write as &#160; to be XHTML compliant.
					// See gh-6699
					span.html( "&#160;" );
				}

				// Hide from assistive technologies, as otherwise this will create redundant text
				// announcement - see gh-8256
				span.attr( "aria-hidden", "true" );

				// TODO possibly aggregate multiple select option classes
				that._addClass( span, "ui-selectmenu-button-text",
					[ that.element.attr( "class" ), selected.attr( "class" ) ].join( " " ) );
				that._removeClass( span, null, "ui-screen-hidden" );
				return span;
			} )() );
	},

	setButtonCount: function() {
		var selected = this.selected();

		// Multiple count inside button
		if ( this.isMultiple ) {
			this.buttonCount[ selected.length > 1 ? "show" : "hide" ]().text( selected.length );
		}
	},

	_handleKeydown: function( /* event */ ) {
		this._delay( "_refreshButton" );
	},

	_refreshButton: function() {
		this.setButtonText();
		this.setButtonCount();
	},

	refresh: function() {
		this._refreshButton();
	},

	// Functions open and close preserved in native selects to simplify users code when looping
	// over selects
	open: $.noop,
	close: $.noop,

	disable: function() {
		this._setDisabled( true );
		this.button.addClass( "ui-state-disabled" );
	},

	enable: function() {
		this._setDisabled( false );
		this.button.removeClass( "ui-state-disabled" );
	}
}, $.ui.formResetMixin ] );

return $.widget( "mobile.selectmenu", selectmenu, $.mobile.widget.theme );

} );

/*!
 * jQuery Mobile Selectmenu Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Popups
//>>group: Widgets
//>>description: Deprecated selectmenu features

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/select.backcompat',[
			"jquery",
			"../widget.backcompat",
			"./select" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {

	$.widget( "mobile.selectmenu", $.mobile.selectmenu, {
		options: {
			inline: false,
			corners: true,
			shadow: true,
			mini: false
		},

		initSelector: "select:not( :jqmData(role='slider')):not( :jqmData(role='flipswitch') )",

		classProp: "ui-selectmenu-button"
	} );

	$.widget( "mobile.selectmenu", $.mobile.selectmenu, $.mobile.widget.backcompat );
}

return $.mobile.selectmenu;

} );


/*!
 * jQuery Mobile Toolbar @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Toolbars
//>>group: Widgets
//>>description: Headers and footers
//>>docs: http://api.jquerymobile.com/toolbar/
//>>demos: http://demos.jquerymobile.com/@VERSION/toolbar/
//>>css.structure: ../css/structure/jquery.mobile.core.css
//>>css.structure: ../css/structure/jquery.mobile.toolbar.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/toolbar',[
			"jquery",
			"../widget",
			"../core",
			"../navigation",
			"./widget.theme",
			"../zoom" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.widget( "mobile.toolbar", {
	version: "@VERSION",

	options: {
		theme: "inherit",
		addBackBtn: false,
		backBtnTheme: null,
		backBtnText: "Back",
		type: "toolbar",
		ariaRole: null
	},

	_create: function() {
		var leftbutton, rightbutton,
			role =  this.options.type,
			page = this.element.closest( ".ui-page" ),
			toolbarAriaRole = this.options.ariaRole === null ?
				role === "header" ? "banner" :
				( role === "footer" ? "contentinfo" : "toolbar" ) :
				this.options.ariaRole;
		if ( page.length === 0 ) {
			page = false;
			this._on( this.document, {
				"pageshow": "refresh"
			} );
		}
		$.extend( this, {
			role: role,
			page: page,
			leftbutton: leftbutton,
			rightbutton: rightbutton
		} );
		this.element.attr( "role", toolbarAriaRole );
		this._addClass( "ui-toolbar" + ( role !== "toolbar" ? "-" + role : "" ) );
		this.refresh();
		this._setOptions( this.options );
	},
	_setOptions: function( o ) {
		if ( o.addBackBtn ) {
			this._updateBackButton();
		}
		if ( o.backBtnText !== undefined ) {
			this.element
				.find( ".ui-toolbar-back-button .ui-button-text" ).text( o.backBtnText );
		}

		this._super( o );
	},
	refresh: function() {
		if ( !this.page ) {
			this._setRelative();
			if ( this.role === "footer" ) {
				this.element.appendTo( "body" );
			} else if ( this.role === "header" ) {
				this._updateBackButton();
			}
		}
		this._addHeadingClasses();
	},

	//We only want this to run on non fixed toolbars so make it easy to override
	_setRelative: function() {
		$( "[data-" + $.mobile.ns + "role='page']" ).css( { "position": "relative" } );
	},

	_updateBackButton: function() {
		var backButton,
			options = this.options,
			theme = options.backBtnTheme || options.theme;

		// Retrieve the back button or create a new, empty one
		backButton = this._backButton = ( this._backButton || {} );

		// We add a back button only if the option to do so is on
		if ( this.options.addBackBtn &&

				// This must also be a header toolbar
				this.role === "header" &&

				// There must be multiple pages in the DOM
				$( ".ui-page" ).length > 1 &&
				( this.page ?

					// If the toolbar is internal the page's URL must differ from the hash
					( this.page[ 0 ].getAttribute( "data-" + $.mobile.ns + "url" ) !==
						$.mobile.path.stripHash( location.hash ) ) :

					// Otherwise, if the toolbar is external there must be at least one
					// history item to which one can go back
					( $.mobile.navigate && $.mobile.navigate.history &&
						$.mobile.navigate.history.activeIndex > 0 ) ) &&

				// The toolbar does not have a left button
				!this.leftbutton ) {

			// Skip back button creation if one is already present
			if ( !backButton.attached ) {
				this.backButton = backButton.element = ( backButton.element ||
					$( "<a role='button' href='#' " +
						"class='ui-button ui-corner-all ui-shadow ui-toolbar-header-button-left " +
							( theme ? "ui-button-" + theme + " " : "" ) +
							"ui-toolbar-back-button ui-icon-carat-l ui-icon-beginning' " +
						"data-" + $.mobile.ns + "rel='back'>" + options.backBtnText +
						"</a>" ) )
						.prependTo( this.element );
				backButton.attached = true;
			}

		// If we are not adding a back button, then remove the one present, if any
		} else if ( backButton.element ) {
			backButton.element.detach();
			backButton.attached = false;
		}
	},
	_addHeadingClasses: function() {
		this.headerElements = this.element.children( "h1, h2, h3, h4, h5, h6" );
		this._addClass( this.headerElements, "ui-toolbar-title" );

		this.headerElements

			// Regardless of h element number in src, it becomes h1 for the enhanced page
			.attr( {
				"role": "heading",
				"aria-level": "1"
			} );
	},
	_destroy: function() {
		var currentTheme;

		this.headerElements.removeAttr( "role aria-level" );

		if ( this.role === "header" ) {
			if ( this.backButton ) {
				this.backButton.remove();
			}
		}

		currentTheme = this.options.theme ? this.options.theme : "inherit";
		this.element.removeAttr( "role" );
	},
	_themeElements: function() {
		var elements = [
			{
				element: this.element,
				prefix: "ui-bar-"
			}
		];
		if ( this.options.addBackBtn && this.backButton !== undefined ) {
			elements.push( {
				element: this.backButton,
				prefix: "ui-button-",
				option: "backBtnTheme"
			} );
		}
		return elements;
	}
} );

return $.widget( "mobile.toolbar", $.mobile.toolbar, $.mobile.widget.theme );

} );

/*!
 * jQuery Mobile Custom Select @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Selects: Custom menus
//>>group: Forms
//>>description: Select menu extension for menu styling, placeholder options, and multi-select.
//>>docs: http://api.jquerymobile.com/selectmenu/
//>>demos: http://demos.jquerymobile.com/@VERSION/selectmenu-custom/
//>>css.structure: ../css/structure/jquery.mobile.forms.select.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/select.custom',[
			"jquery",
			"../../core",
			"../../navigation",
			"./select",
			"../toolbar",
			"../listview",
			"../page.dialog.backcompat",
			"../popup" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var unfocusableItemSelector = ".ui-disabled,.ui-state-disabled,.ui-listview-item-divider," +
		".ui-screen-hidden";

return $.widget( "mobile.selectmenu", $.mobile.selectmenu, {
	options: {
		classes: {
			"ui-selectmenu-custom-header-close-button": "ui-corner-all"
		},
		overlayTheme: null,
		dividerTheme: null,
		hidePlaceholderMenuItems: true,
		closeText: "Close"
	},

	_ns: function() {
		return "ui-";
	},

	_create: function() {
		var o = this.options;

		this._origTabIndex = ( this.element.attr( "tabindex" ) === undefined ) ? false :
			this.element.attr( "tabindex" );

		// Custom selects cannot exist inside popups, so revert the "nativeMenu" option to true if
		// a parent is a popup
		o.nativeMenu = o.nativeMenu ||
			( this.element.closest( "[data-" + this._ns() +
			"role='popup'],:mobile-popup" ).length > 0 );

		return this._super();
	},

	_handleSelectFocus: function() {
		this.element.blur();
		this.button.focus();
	},

	_handleKeydown: function( event ) {
		this._super( event );
		this._handleButtonVclickKeydown( event );
	},

	_handleButtonVclickKeydown: function( event ) {
		if ( this.options.disabled || this.isOpen || this.options.nativeMenu ) {
			return;
		}

		if ( event.type === "vclick" ||
				event.keyCode &&
					( event.keyCode === $.ui.keyCode.ENTER ||
						event.keyCode === $.ui.keyCode.SPACE ) ) {

			this._decideFormat();
			if ( this.menuType === "overlay" ) {
				this.button
					.attr( "href", "#" + this.popupId )
					.attr( "data-" + this._ns() + "rel", "popup" );
			} else {
				this.button
					.attr( "href", "#" + this.dialogId )
					.attr( "data-" + this._ns() + "rel", "dialog" );
			}
			this.isOpen = true;

			// Do not prevent default, so the navigation may have a chance to actually open the
			// chosen format
		}
	},

	_handleListFocus: function( e ) {
		var params = ( e.type === "focusin" ) ?
			{ tabindex: "0", event: "vmouseover" } :
			{ tabindex: "-1", event: "vmouseout" };

		$( e.target )
			.attr( "tabindex", params.tabindex )
			.trigger( params.event );
	},

	_goToAdjacentItem: function( item, target, direction ) {
		var adjacent = item[ direction + "All" ]()
			.not( unfocusableItemSelector + ",[data-" + this._ns() + "role='placeholder']" )
				.first();

		// If there's a previous option, focus it
		if ( adjacent.length ) {
			target
				.blur()
				.attr( "tabindex", "-1" );

			adjacent.find( "a" ).first().focus();
		}
	},

	_handleListKeydown: function( event ) {
		var target = $( event.target ),
			li = target.closest( "li" );

		// Switch logic based on which key was pressed
		switch ( event.keyCode ) {

		// Up or left arrow keys
		case 38:
			this._goToAdjacentItem( li, target, "prev" );
			return false;

		// Down or right arrow keys
		case 40:
			this._goToAdjacentItem( li, target, "next" );
			return false;

		// If enter or space is pressed, trigger click
		case 13:
		case 32:
			target.trigger( "click" );
			return false;
		}
	},

	// Focus the button before the page containing the widget replaces the dialog page
	_handleBeforeTransition: function( event, data ) {
		var focusButton;

		if ( data && data.prevPage && data.prevPage[ 0 ] === this.menuPage[ 0 ] ) {
			focusButton = $.proxy( function() {
				this._delay( function() {
					this._focusButton();
				} );
			}, this );

			if ( data.options && data.options.transition && data.options.transition !== "none" ) {
				data.prevPage.animationComplete( focusButton );
			} else {
				focusButton();
			}
		}
	},

	_handleHeaderCloseClick: function() {
		if ( this.menuType === "overlay" ) {
			this.close();
			return false;
		}
	},

	_handleListItemClick: function( event ) {
		var anchors,
			listItem = $( event.target ).closest( "li" ),

			// Index of option tag to be selected
			oldIndex = this.element[ 0 ].selectedIndex,
			newIndex = $.mobile.getAttribute( listItem, "option-index" ),
			option = this._selectOptions().eq( newIndex )[ 0 ];

		// Toggle selected status on the tag for multi selects
		option.selected = this.isMultiple ? !option.selected : true;

		// Toggle checkbox class for multiple selects
		if ( this.isMultiple ) {
			anchors = listItem.find( "a" );
			this._toggleClass( anchors, null, "ui-checkbox-on", option.selected );
			this._toggleClass( anchors, null, "ui-checkbox-off", !option.selected );
		}

		// If it's not a multiple select, trigger change after it has finished closing
		if ( !this.isMultiple && oldIndex !== newIndex ) {
			this._triggerChange = true;
		}

		// Trigger change if it's a multiple select
		// Hide custom select for single selects only - otherwise focus clicked item
		// We need to grab the clicked item the hard way, because the list may have been rebuilt
		if ( this.isMultiple ) {
			this.element.trigger( "change" );
			this.list.find( "li:not(.ui-listview-item-divider)" ).eq( newIndex )
				.find( "a" ).first().focus();
		} else {
			this.close();
		}

		event.preventDefault();
	},

	build: function() {
		if ( this.options.nativeMenu ) {
			return this._super();
		}

		var selectId, popupId, dialogId, label, thisPage, isMultiple, menuId,
			themeAttr, overlayTheme, overlayThemeAttr, dividerThemeAttr,
			menuPage, menuPageHeader, listbox, list, header, headerTitle, menuPageContent,
			menuPageClose, headerClose, headerCloseIcon,
			o = this.options;

		selectId = this.selectId;
		popupId = selectId + "-listbox";
		dialogId = selectId + "-dialog";
		label = this.label;
		thisPage = this.element.closest( ".ui-page" );
		isMultiple = this.element[ 0 ].multiple;
		menuId = selectId + "-menu";
		themeAttr = o.theme ? ( " data-" + this._ns() + "theme='" + o.theme + "'" ) : "";
		overlayTheme = o.overlayTheme || o.theme || null;
		overlayThemeAttr = overlayTheme ? ( " data-" + this._ns() +
		"overlay-theme='" + overlayTheme + "'" ) : "";
		dividerThemeAttr = ( o.dividerTheme && this.element.children( "optgroup" ).length > 0 ) ?
			( " data-" + this._ns() + "divider-theme='" + o.dividerTheme + "'" ) : "";
		menuPage = $( "<div data-" + this._ns() + "role='page' " +
			"data-" + this._ns() + "dialog='true'>" +
			"<div></div>" +
			"</div>" )
			.attr( "id", dialogId );
		menuPageContent = menuPage.children();

		// Adding the data-type attribute allows the dialog widget to place the close button before
		// the toolbar is instantiated
		menuPageHeader = $( "<div data-" + this._ns() + "type='header'><h1></h1></div>" )
			.prependTo( menuPage );
		listbox = $( "<div></div>" )
			.attr( "id", popupId )
			.insertAfter( this.element )
			.popup();
		list = $( "<ul role='listbox' aria-labelledby='" +
			this.buttonId + "'" + themeAttr + dividerThemeAttr + "></ul>" )
			.attr( "id", menuId )
			.appendTo( listbox );
		header = $( "<div>" )
			.prependTo( listbox );
		headerTitle = $( "<h1></h1>" ).appendTo( header );

		menuPage.page();

		// Instantiate the toolbars after everything else so that when they are created they find
		// the page in which they are contained.
		menuPageHeader.add( header ).toolbar( { type: "header" } );

		this._addClass( menuPage, "ui-selectmenu-custom" );
		this._addClass( menuPageContent, null, "ui-content" );
		this._addClass( listbox, null, "ui-selectmenu-custom" );
		this._addClass( list, null, "ui-selectmenu-custom-list" );

		if ( this.isMultiple ) {
			headerClose = $( "<a>", {
				"role": "button",
				"href": "#"
			} );
			headerCloseIcon = $( "<span>" );
			this._addClass( headerCloseIcon, "ui-selectmenu-custom-header-close-button-icon",
				"ui-icon ui-icon-delete" );
			headerClose.append( headerCloseIcon );
			this._addClass( headerClose, "ui-selectmenu-custom-header-close-button",
				"ui-button ui-toolbar-header-button-left ui-button-icon-only" );
			headerClose.appendTo( header );
		}

		$.extend( this, {
			selectId: selectId,
			menuId: menuId,
			popupId: popupId,
			dialogId: dialogId,
			thisPage: thisPage,
			menuPage: menuPage,
			menuPageHeader: menuPageHeader,
			label: label,
			isMultiple: isMultiple,
			theme: o.theme,
			listbox: listbox,
			list: list,
			header: header,
			headerTitle: headerTitle,
			headerClose: headerClose,
			menuPageContent: menuPageContent,
			menuPageClose: menuPageClose,
			placeholder: ""
		} );

		// Create list from select, update state
		this.refresh();

		this.element.attr( "tabindex", "-1" );
		this._on( this.element, { focus: "_handleSelectFocus" } );

		// Button events
		this._on( this.button, {
			vclick: "_handleButtonVclickKeydown"
		} );

		// Events for list items
		this.list.attr( "role", "listbox" );
		this._on( this.list, {
			"focusin": "_handleListFocus",
			"focusout": "_handleListFocus",
			"keydown": "_handleListKeydown",
			"click li:not(.ui-disabled,.ui-state-disabled,.ui-listview-item-divider)":
				"_handleListItemClick"
		} );

		// Events on the popup
		this._on( this.listbox, { popupafterclose: "_popupClosed" } );

		// Close button on small overlays
		if ( this.isMultiple ) {
			this._on( this.headerClose, { click: "_handleHeaderCloseClick" } );
		}

		this._on( this.document, { pagecontainerbeforetransition: "_handleBeforeTransition" } );

		return this;
	},

	_popupClosed: function() {
		this.close();
		this._delayedTrigger();
	},

	_delayedTrigger: function() {
		if ( this._triggerChange ) {
			this.element.trigger( "change" );
		}
		this._triggerChange = false;
	},

	_isRebuildRequired: function() {
		var list = this.list.find( "li" ),
			options = this._selectOptions().not( ".ui-screen-hidden" );

		// TODO exceedingly naive method to determine difference ignores value changes etc in favor
		// of a forcedRebuild from the user in the refresh method
		return options.text() !== list.text();
	},

	selected: function() {
		return this._selectOptions()
			.filter( ":selected:not( [data-" + this._ns() + "placeholder='true'] )" );
	},

	refresh: function( force ) {
		var indices, items;

		if ( this.options.nativeMenu ) {
			return this._super( force );
		}

		if ( force || this._isRebuildRequired() ) {
			this._buildList();
		}

		indices = this.selectedIndices();

		this.setButtonText();
		this.setButtonCount();

		items = this.list.find( "li:not(.ui-listview-item-divider)" );
		this._removeClass( items.find( "a" ), null, "ui-button-active" );

		items.attr( "aria-selected", false );

		items.each( $.proxy( function( i, element ) {
			var anchors,
				item = $( element );
			if ( $.inArray( i, indices ) > -1 ) {

				// Aria selected attr
				item.attr( "aria-selected", true );

				// Multiple selects: add the "on" checkbox state to the icon
				if ( this.isMultiple ) {
					anchors = item.find( "a" );
					this._removeClass( anchors, null, "ui-checkbox-off" );
					this._addClass( anchors, null, "ui-checkbox-on" );
				} else {
					if ( item.hasClass( "ui-screen-hidden" ) ) {
						this._addClass( item.next().find( "a" ), null, "ui-button-active" );
					} else {
						this._addClass( item.find( "a" ), null, "ui-button-active" );
					}
				}
			} else if ( this.isMultiple ) {
				anchors = item.find( "a" );
				this._removeClass( anchors, null, "ui-checkbox-on" );
				this._addClass( anchors, null, "ui-checkbox-off" );
			}
		}, this ) );
	},

	close: function() {
		if ( this.options.disabled || !this.isOpen ) {
			return;
		}

		if ( this.menuType === "page" ) {
			if ( this.menuPage.hasClass( "ui-page-active" ) ) {
				$.mobile.back();
			}
		} else {
			this.listbox.popup( "close" );
		}

		this._focusButton();

		// Allow the dialog to be closed again
		this.isOpen = false;
	},

	open: function() {
		this.button.click();
	},

	_focusMenuItem: function() {
		var selector = this.list.find( "a.ui-button-active" );
		if ( selector.length === 0 ) {
			selector = this.list.find( "li:not(" + unfocusableItemSelector +
				",[data-" + this._ns() + "role='placeholder'] ) a.ui-button" );
		}
		selector.first().focus();
	},

	_setTheme: function( key, value ) {
		this.listbox.popup( "option", key, value );

		// We cannot pass inherit to the dialog because pages are supposed to set the theme for
		// the pagecontainer in which they reside. If they set it to inherit the pagecontainer
		// will not inherit from anything above it.
		if ( value !== "inherit" ) {
			this.menuPage.page( "option", key, value );
		}

		if ( key === "theme" ) {
			this.header.toolbar( "option", key, value );
			this.menuPageHeader.toolbar( "option", key, value );
		}
	},

	_setOption: function( key, value ) {
		if ( !this.options.nativeMenu && ( key === "theme" || key === "overlayTheme" ) ) {
			this._setTheme( key, value );
		}

		if ( key === "hidePlaceholderMenuItems" ) {
			this._superApply( arguments );
			this.refresh( true );
			return;
		}

		if ( key === "closeText" ) {
			this.headerClose.text( value );
		}

		return this._superApply( arguments );
	},

	_decideFormat: function() {
		var pageWidget,
			theWindow = this.window,
			selfListParent = this.list.parent(),
			menuHeight = selfListParent.outerHeight(),
			scrollTop = theWindow.scrollTop(),
			buttonOffset = this.button.offset().top,
			screenHeight = theWindow.height();

		if ( menuHeight > screenHeight - 80 || !$.support.scrollTop ) {

			this.menuPage.appendTo( this.element.closest( ".ui-pagecontainer" ) );
			this.menuPageClose = this.menuPage.find( ".ui-toolbar-header a" );

			// Prevent the parent page from being removed from the DOM, otherwise the results of
			// selecting a list item in the dialog fall into a black hole
			pageWidget = this.thisPage.page( "instance" );
			pageWidget._off( pageWidget.document, "pagecontainerhide" );

			// For WebOS/Opera Mini (set lastscroll using button offset)
			if ( scrollTop === 0 && buttonOffset > screenHeight ) {
				this.thisPage.one( "pagehide", function() {
					$( this ).data( $.camelCase( this._ns() + "lastScroll" ), buttonOffset );
				} );
			}

			this._on( this.document, {
				pagecontainershow: "_handlePageContainerShow",
				pagecontainerhide: "_handlePageContainerHide"
			} );

			this.menuType = "page";
			this.menuPageContent.append( this.list );
			this.menuPage
				.find( "div .ui-toolbar-title" )
					.text( this.label.getEncodedText() || this.placeholder );
		} else {
			this.menuType = "overlay";

			this.listbox.one( { popupafteropen: $.proxy( this, "_focusMenuItem" ) } );
		}
		this._setTheme( "theme", this.options.theme );
		this._setTheme( "overlayTheme", this.options.overlayTheme );
	},

	_handlePageContainerShow: function( event, data ) {
		if ( data.toPage[ 0 ] === this.menuPage[ 0 ] ) {
			this._off( this.document, "pagecontainershow" );
			this._focusMenuItem();
		}
	},

	_handlePageContainerHide: function( event, data ) {
		if ( data.prevPage[ 0 ] === this.menuPage[ 0 ] ) {
			this._off( this.document, "pagecontainershow" );

			// After the dialog's done, we may want to trigger change if the value has actually
			// changed
			this._delayedTrigger();

			// TODO centralize page removal binding / handling in the page plugin.
			// Suggestion from @jblas to do refcounting.
			//
			// TODO extremely confusing dependency on the open method where the pagehide.remove
			// bindings are stripped to prevent the parent page from disappearing. The way we're
			// keeping pages in the DOM right now sucks
			//
			// Rebind the page remove that was unbound in the open function to allow for the parent
			// page removal from actions other than the use of a dialog sized custom select
			//
			// Doing this here provides for the back button on the custom select dialog
			this.thisPage.page( "bindRemove" );
			this.menuPage.detach();
			this.list.appendTo( this.listbox );
			this.close();
		}
	},

	_buildList: function() {
		var o = this.options,
			placeholder = this.placeholder,
			needPlaceholder = true,
			dataIcon = "false",
			optionsList, numOptions, select,
			dataPrefix = "data-" + this._ns(),
			dataIndexAttr = dataPrefix + "option-index",
			dataIconAttr = dataPrefix + "icon",
			dataRoleAttr = dataPrefix + "role",
			dataPlaceholderAttr = dataPrefix + "placeholder",
			fragment = document.createDocumentFragment(),
			isPlaceholderItem = false,
			optGroup,
			i,
			option, optionElement, parent, text, anchor, classes,
			optLabel, divider, item;

		this.list.empty().filter( ".ui-listview" ).listview( "destroy" );
		optionsList = this._selectOptions();
		numOptions = optionsList.length;
		select = this.element[ 0 ];

		for ( i = 0; i < numOptions; i++, isPlaceholderItem = false ) {
			option = optionsList[ i ];
			optionElement = $( option );

			// Do not create options based on ui-screen-hidden select options
			if ( optionElement.hasClass( "ui-screen-hidden" ) ) {
				continue;
			}

			parent = option.parentNode;
			classes = [];

			// Although using .text() here raises the risk that, when we later paste this into the
			// list item we end up pasting possibly malicious things like <script> tags, that risk
			// only arises if we do something like $( "<li><a href='#'>" + text + "</a></li>" ). We
			// don't do that. We do document.createTextNode( text ) instead, which guarantees that
			// whatever we paste in will end up as text, with characters like <, > and & escaped.
			text = optionElement.text();
			anchor = document.createElement( "a" );
			anchor.setAttribute( "href", "#" );
			anchor.appendChild( document.createTextNode( text ) );

			// Are we inside an optgroup?
			if ( parent !== select && parent.nodeName.toLowerCase() === "optgroup" ) {
				optLabel = parent.getAttribute( "label" );
				if ( optLabel !== optGroup ) {
					divider = document.createElement( "li" );
					divider.setAttribute( dataRoleAttr, "list-divider" );
					divider.setAttribute( "role", "option" );
					divider.setAttribute( "tabindex", "-1" );
					divider.appendChild( document.createTextNode( optLabel ) );
					fragment.appendChild( divider );
					optGroup = optLabel;
				}
			}

			if ( needPlaceholder &&
				( !option.getAttribute( "value" ) ||
					text.length === 0 ||
					optionElement.data( $.camelCase( this._ns() + "placeholder" ) ) ) ) {
				needPlaceholder = false;
				isPlaceholderItem = true;

				// If we have identified a placeholder, record the fact that it was us who have
				// added the placeholder to the option. Mark it retroactively in the select.
				if ( null === option.getAttribute( dataPlaceholderAttr ) ) {
					this._removePlaceholderAttr = true;
				}
				option.setAttribute( dataPlaceholderAttr, true );
				if ( o.hidePlaceholderMenuItems ) {
					classes.push( "ui-screen-hidden" );
				}
				if ( placeholder !== text ) {
					placeholder = this.placeholder = text;
				}
			}

			item = document.createElement( "li" );
			if ( option.disabled ) {
				classes.push( "ui-state-disabled" );
				item.setAttribute( "aria-disabled", true );
			}
			item.setAttribute( dataIndexAttr, i );
			item.setAttribute( dataIconAttr, dataIcon );
			if ( isPlaceholderItem ) {
				item.setAttribute( dataPlaceholderAttr, true );
			}
			item.className = classes.join( " " );
			item.setAttribute( "role", "option" );
			anchor.setAttribute( "tabindex", "-1" );
			if ( this.isMultiple ) {
				this._addClass( $( anchor ), null, "ui-button ui-checkbox-off ui-icon-end" );
			}

			item.appendChild( anchor );
			fragment.appendChild( item );
		}

		this.list[ 0 ].appendChild( fragment );

		// Hide header if it's not a multiselect and there's no placeholder
		if ( !this.isMultiple && !placeholder.length ) {
			this._addClass( this.header, null, "ui-screen-hidden" );
		} else {
			this.headerTitle.text( this.placeholder );
		}

		// Now populated, create listview
		this.list.listview();
	},

	_button: function() {
		var attributes = {
				"href": "#",
				"role": "button",

				// TODO value is undefined at creation
				"id": this.buttonId,
				"aria-haspopup": "true",

				// TODO value is undefined at creation
				"aria-owns": this.menuId
			};
		attributes[ "data-" + this._ns() + "transition" ] = "pop";

		if ( this._origTabIndex ) {
			attributes.tabindex = this._origTabIndex;
		}
		return this.options.nativeMenu ? this._super() : $( "<a>", attributes );
	},

	_destroy: function() {

		if ( !this.options.nativeMenu ) {
			this.close();

			// Restore the tabindex attribute to its original value
			if ( this._origTabIndex !== undefined ) {
				if ( this._origTabIndex !== false ) {
					this.element.attr( "tabindex", this._origTabIndex );
				} else {
					this.element.removeAttr( "tabindex" );
				}
			}

			// Remove the placeholder attribute if we were the ones to add it
			if ( this._removePlaceholderAttr ) {
				this._selectOptions().removeAttr( "data-" + this._ns() + "placeholder" );
			}

			// Remove the popup
			this.listbox.remove();

			// Remove the dialog
			this.menuPage.remove();
		}

		// Chain up
		this._super();
	}
} );

} );

/*!
 * jQuery Mobile Selectmenu Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Popups
//>>group: Widgets
//>>description: Deprecated selectmenu features

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/forms/select.custom.backcompat',[
			"jquery",
			"./select.custom" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {

	$.widget( "mobile.selectmenu", $.mobile.selectmenu, {
		_ns: function() {
			return $.mobile.ns || "";
		}
	} );
}

return $.mobile.selectmenu;

} );


/*!
 * jQuery Mobile Controlgroup @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Controlgroups
//>>group: Forms
//>>description: Visually groups sets of buttons, checks, radios, etc.
//>>docs: http://api.jquerymobile.com/toolbar/
//>>demos: http://demos.jquerymobile.com/@VERSION/toolbar-fixed/
//>>css.structure: ../css/structure/jquery.mobile.controlgroup.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/controlgroup',[
			"jquery",
			"jquery-ui/widget",
			"./widget.theme",
			"jquery-ui/widgets/controlgroup" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.widget( "ui.controlgroup", $.ui.controlgroup, {
	options: {
		theme: "inherit"
	},

	_create: function() {
		this._super();
		this._on( this.document, {
			"pagecontainershow": function( event, ui ) {
				if ( $.contains( ui.toPage[ 0 ], this.element[ 0 ] ) ) {
					this.refresh();
				}
			}
		} );
	},

	// Deprecated as of 1.5.0 and will be removed in 1.6.0
	// This method is no longer necessary since controlgroup no longer has a wrapper
	container: function() {
		return this.element;
	},

	_themeElements: function() {
		return [
			{
				element: this.widget(),
				prefix: "ui-group-theme-"
			}
		];
	}
} );

$.widget( "ui.controlgroup", $.ui.controlgroup, $.mobile.widget.theme );

return $.ui.controlgroup;

} );

/*!
 * jQuery Mobile Controlgroup Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Controlgroups
//>>group: Forms
//>>description: Visually groups sets of buttons, checks, radios, etc.
//>>docs: http://api.jquerymobile.com/controlgroup/
//>>demos: http://demos.jquerymobile.com/@VERSION/controlgroup/
//>>css.structure: ../css/structure/jquery.mobile.controlgroup.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/controlgroup.backcompat',[
			"jquery",
			"jquery-ui/widget",
			"./widget.theme",
			"jquery-ui/widgets/controlgroup",
			"./controlgroup",
			"./widget.backcompat" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.widget( "ui.controlgroup", $.ui.controlgroup, {
	options: {
		shadow: false,

		//Corners: true,
		direction: "vertical",
		type: "vertical",
		mini: false
	},

	_create: function() {
		if ( this.options.direction !== $.ui.controlgroup.prototype.options.direction ) {
			this.options.type = this.options.direction;
		} else if ( this.options.type !== $.ui.controlgroup.prototype.options.type ) {
			this._setOption( "direction", this.options.type );
		}
		this._super();
	},

	classProp: "ui-controlgroup",

	_setOption: function( key, value ) {
		if ( key === "direction" ) {
			this.options.type = value;
		}
		if ( key === "type" ) {
			this._setOption( "direction", value );
		}
		this._superApply( arguments );
	}
} );

$.widget( "ui.controlgroup", $.ui.controlgroup, $.mobile.widget.backcompat );

return $.ui.controlgroup;

} );

/*!
 * jQuery Mobile Selectmenu Controlgroup Integration @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Selectmenu Controlgroup Integration
//>>group: Forms
//>>description: Selectmenu integration for controlgroups

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/controlgroup.selectmenu',[
			"jquery",
			"./controlgroup" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var uiButtonInlineRegex = /ui-button-inline/g;
var uiShadowRegex = /ui-shadow/g;

return $.widget( "ui.controlgroup", $.ui.controlgroup, {

	_selectmenuOptions: function( position ) {
		var isVertical = ( this.options.direction === "vertical" );
		var inlineClass = isVertical ? "" : "ui-button-inline";

		return {
			classes: {
				middle: {
					"ui-selectmenu": inlineClass,
					"ui-selectmenu-button": ""
				},
				first: {
					"ui-selectmenu": inlineClass,
					"ui-selectmenu-button":
						"ui-corner-" + ( isVertical ? "top" : "left" )
				},
				last: {
					"ui-selectmenu": inlineClass,
					"ui-selectmenu-button":
						"ui-corner-" + ( isVertical ? "bottom" : "right" )
				},
				only: {
					"ui-selectmenu": inlineClass,
					"ui-selectmenu-button": "ui-corner-all"
				}
			}[ position ]
		};
	},

	// The native element of an enhanced and disabled selectmenu widget fails the :visible test.
	// This will cause controlgroup to ignore it in the calculation of the corner classes. Thus, in
	// the case of the selectmenu, we need to transfer the controlgroup information from the native
	// select element to its parent which is still visible.
	//
	// The selectmenu widget's wrapper needs to have the class ui-button-inline, but only when the
	// selectmenu is oriented horizontally. Thus, we remove it here, and allow the refresh() to
	// determine whether it needs to be added.
	//
	// The ui-shadow class needs to be removed here.
	_initWidgets: function() {
		this._superApply( arguments );

		this.childWidgets = this.childWidgets.map( function() {
			var selectmenuWidget = $.data( this, "mobile-selectmenu" );
			if ( selectmenuWidget ) {

				// Transfer data to parent node
				$.data( this.parentNode, "ui-controlgroup-data",
					$.data( this, "ui-controlgroup-data" ) );
				$.removeData( this, "ui-controlgroup-data" );

				// Remove the class ui-button-inline. It may be re-added if this controlgroup is
				// horizontal.
				selectmenuWidget.option( "classes.ui-selectmenu",
					selectmenuWidget.option( "classes.ui-selectmenu" )
						.replace( uiButtonInlineRegex, "" )
						.trim() );
				selectmenuWidget.option( "classes.ui-selectmenu-button",
					selectmenuWidget.option( "classes.ui-selectmenu-button" )
						.replace( uiShadowRegex, "" )
						.trim() );

				return this.parentNode;
			}
			return this;
		} );
	}

} );

} );

/*!
 * jQuery Mobile Fixed Toolbar @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Toolbars: Fixed
//>>group: Widgets
//>>description: Behavior for "fixed" headers and footers
//>>docs: http://api.jquerymobile.com/toolbar/
//>>demos: http://demos.jquerymobile.com/@VERSION/toolbar-fixed/
//>>css.structure: ../css/structure/jquery.mobile.fixedToolbar.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/fixedToolbar',[
			"jquery",
			"../widget",
			"../core",
			"../animationComplete",
			"../navigation",
			"./page",
			"./toolbar",
			"../zoom" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.widget( "mobile.toolbar", $.mobile.toolbar, {
	options: {
		position:null,
		visibleOnPageShow: true,
		disablePageZoom: true,

		// Can be none, fade, slide (slide maps to slideup or slidedown)
		transition: "slide",
		fullscreen: false,
		updatePagePadding: true
	},

	_create: function() {
		this._super();
		if ( this.options.position === "fixed" ) {
			this.pagecontainer = this.element.closest( ".ui-mobile-viewport" );
			this._makeFixed();
		}
	},

	_makeFixed: function() {
		this._addClass( "ui-toolbar-" + this.role + "-fixed" );
		this.updatePagePadding();
		this._addTransitionClass();
		this._bindPageEvents();
	},

	_setOptions: function( o ) {
		if ( o.position === "fixed" && this.options.position !== "fixed" ) {
			this._makeFixed();
		}
		if ( this.options.position === "fixed" ) {
			var pageActive = $( ".ui-page-active" ),
				currentPage = !!this.page ?
				this.page :
				pageActive.length ?
				pageActive :
				$( ".ui-page" ).eq( 0 );
			if ( o.fullscreen !== undefined ) {
				if ( o.fullscreen ) {
					this._addClass( "ui-toolbar-" + this.role + "-fullscreen" );
					this._addClass( currentPage,
						"ui-toolbar-page-" + this.role + "-fullscreen" );
				} else {

					// If not fullscreen, add class to page to set top or bottom padding
					this._removeClass( "ui-toolbar-" + this.role + "-fullscreen" );
					this._removeClass( currentPage,
						"ui-toolbar-page-" + this.role + "-fullscreen" );
					this._addClass( currentPage, "ui-toolbar-page-" + this.role + "-fixed" );
				}
			}
		}
		this._super( o );
	},

	_addTransitionClass: function() {
		var transitionClass = this.options.transition;

		if ( transitionClass && transitionClass !== "none" ) {

			// Use appropriate slide for header or footer
			if ( transitionClass === "slide" ) {
				transitionClass = this.role === "header" ? "slidedown" : "slideup";
			}

			this._addClass( null, transitionClass );
		}
	},

	_bindPageEvents: function() {
		var page = ( !!this.page ) ? this.element.closest( ".ui-page" ) : this.document;

		//Page event bindings
		// Fixed toolbars require page zoom to be disabled, otherwise usability issues crop up
		// This method is meant to disable zoom while a fixed-positioned toolbar page is visible
		this._on( page, {
			"pagebeforeshow": "_handlePageBeforeShow",
			"webkitAnimationStart":"_handleAnimationStart",
			"animationstart":"_handleAnimationStart",
			"updatelayout": "_handleAnimationStart",
			"pageshow": "_handlePageShow",
			"pagebeforehide": "_handlePageBeforeHide"
		} );
	},

	_handlePageBeforeShow: function() {
		var o = this.options;
		if ( o.disablePageZoom ) {
			$.mobile.zoom.disable( true );
		}
		if ( !o.visibleOnPageShow ) {
			this.hide( true );
		}
	},

	_handleAnimationStart: function() {
		if ( this.options.updatePagePadding ) {
			this.updatePagePadding( ( !!this.page ) ? this.page : ".ui-page-active" );
		}
	},

	_handlePageShow: function() {
		this.updatePagePadding( ( !!this.page ) ? this.page : ".ui-page-active" );
		if ( this.options.updatePagePadding ) {
			this._on( this.window, { "throttledresize": "updatePagePadding" } );
		}
	},

	_handlePageBeforeHide: function() {
		if ( this.options.disablePageZoom ) {
			$.mobile.zoom.enable( true );
		}
		if ( this.options.updatePagePadding ) {
			this._off( this.window, "throttledresize" );
		}
	},

	_visible: true,

	// This will set the content element's top or bottom padding equal to the toolbar's height
	updatePagePadding: function( tbPage ) {
		var $el = this.element,
			header = ( this.role === "header" ),
			pos = parseFloat( $el.css( header ? "top" : "bottom" ) );

		// This behavior only applies to "fixed", not "fullscreen"
		if ( this.options.fullscreen ) { return; }

		// TbPage argument can be a Page object or an event, if coming from throttled resize.
		tbPage = ( tbPage && tbPage.type === undefined && tbPage ) ||
			this.page || $el.closest( ".ui-page" );
		tbPage = ( !!this.page ) ? this.page : ".ui-page-active";
		$( tbPage ).css( "padding-" + ( header ? "top" : "bottom" ), $el.outerHeight() + pos );
	},

	_useTransition: function( notransition ) {
		var $win = this.window,
			$el = this.element,
			scroll = $win.scrollTop(),
			elHeight = $el.height(),
			pHeight = ( !!this.page ) ? $el.closest( ".ui-page" ).height() :
				$( ".ui-page-active" ).height(),
			viewportHeight = $( window ).height();

		return !notransition &&
			( this.options.transition && this.options.transition !== "none" &&
			(
				( this.role === "header" && !this.options.fullscreen && scroll > elHeight ) ||
				( this.role === "footer" && !this.options.fullscreen &&
					scroll + viewportHeight < pHeight - elHeight )
			) || this.options.fullscreen
			);
	},

	show: function( notransition ) {
		if ( this._useTransition( notransition ) ) {
			this._animationInProgress = "show";
			this._removeClass( null, "out" );
			this._removeClass( "ui-toolbar-fixed-hidden" );
			this._addClass( null, "in" );
			this.element.animationComplete( $.proxy( function() {
				if ( this._animationInProgress === "show" ) {
					this._animationInProgress = false;
					this._removeClass( null, "in" );
				}
			}, this ) );
		} else {
			this._removeClass( "ui-toolbar-fixed-hidden" );
		}
		this._visible = true;
	},

	hide: function( notransition ) {

		// If it's a slide transition, our new transitions need the
		// reverse class as well to slide outward
		var	outClass =  this.options.transition === "slide" ? " reverse" : "";

		if ( this._useTransition( notransition ) ) {
			this._animationInProgress = "hide";
			this._addClass( null, "out" );
			this._addClass( null, outClass );
			this._removeClass( null, "in" );
			this.element.animationComplete( $.proxy( function() {
				if ( this._animationInProgress === "hide" ) {
					this._animationInProgress = false;
					this._addClass( "ui-toolbar-fixed-hidden" );
					this._removeClass( null, "out" );
					this._removeClass( null, outClass );
				}
			}, this ) );
		} else {
			this._addClass( "ui-toolbar-fixed-hidden" )._removeClass( null, outClass );
		}
		this._visible = false;
	},

	toggle: function() {
		this[ this._visible ? "hide" : "show" ]();
	},

	_setRelative: function() {
		if ( this.options.position !== "fixed" ) {
			this._super();
		}
	},

	_destroy: function() {
		var pageClasses, toolbarClasses, hasFixed, header, hasFullscreen,
			page = this.pagecontainer.pagecontainer( "getActivePage" );

		this._super();
		if ( this.options.position === "fixed" ) {
			hasFixed = $( "body>.ui-" + this.role + "-fixed" )
						.add( page.find( ".ui-" + this.role + "-fixed" ) )
						.not( this.element ).length > 0;
			hasFullscreen = $( "body>.ui-" + this.role + "-fixed" )
						.add( page.find( ".ui-" + this.role + "-fullscreen" ) )
						.not( this.element ).length > 0;
			toolbarClasses =  "ui-header-fixed ui-footer-fixed ui-header-fullscreen in out" +
				" ui-footer-fullscreen fade slidedown slideup ui-fixed-hidden";
			this._removeClass( toolbarClasses );
			if ( !hasFullscreen ) {
				pageClasses = "ui-page-" + this.role + "-fullscreen";
			}
			if ( !hasFixed ) {
				header = this.role === "header";
				pageClasses += " ui-page-" + this.role + "-fixed";
				page.css( "padding-" + ( header ? "top" : "bottom" ), "" );
			}
			this._removeClass( page, pageClasses );
		}
	}

} );
} );

/*!
 * jQuery Mobile Fixed Toolbar @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Toolbars: Fixed
//>>group: Widgets
//>>description: Behavior for "fixed" headers and footers - be sure to also include the
//>> item 'Browser specific workarounds for "fixed" headers and footers' when supporting
//>> Android 2.x
//>>docs: http://api.jquerymobile.com/toolbar/
//>>demos: http://demos.jquerymobile.com/@VERSION/toolbar-fixed/
//>>css.structure: ../css/structure/jquery.mobile.fixedToolbar.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/fixedToolbar.backcompat',[
			"jquery",
			"./fixedToolbar" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {

	return $.widget( "mobile.toolbar", $.mobile.toolbar, {

		options: {
			hideDuringFocus: "input, textarea, select",
			tapToggle: true,
			supportBlacklist: function() {
				return $.noop;
			}
		},

		_hideDuringFocusData: {
			delayShow: 0,
			delayHide: 0,
			isVisible: true
		},

		_handlePageFocusinFocusout: function( event ) {
			var data = this._hideDuringFocusData;

			// This hides the toolbars on a keyboard pop to give more screen room and prevent
			// ios bug which positions fixed toolbars in the middle of the screen on pop if the
			// input is near the top or bottom of the screen addresses issues #4410 Footer
			// navbar moves up when clicking on a textbox in an Android environment and issue
			// #4113 Header and footer change their position after keyboard popup - iOS and
			// issue #4410 Footer navbar moves up when clicking on a textbox in an Android
			// environment
			if ( this.options.hideDuringFocus && screen.width < 1025 &&
					$( event.target ).is( this.options.hideDuringFocus ) &&
					!$( event.target )
						.closest( ".ui-toolbar-header-fixed, .ui-toolbar-footer-fixed" ).length ) {

				// Fix for issue #4724 Moving through form in Mobile Safari with "Next" and
				// "Previous" system controls causes fixed position, tap-toggle false Header to
				// reveal itself isVisible instead of self._visible because the focusin and
				// focusout events fire twice at the same time Also use a delay for hiding the
				// toolbars because on Android native browser focusin is direclty followed by a
				// focusout when a native selects opens and the other way around when it closes.
				if ( event.type === "focusout" && !data.isVisible ) {
					data.isVisible = true;

					// Wait for the stack to unwind and see if we have jumped to another input
					clearTimeout( data.delayHide );
					data.delayShow = this._delay( "show", 0 );
				} else if ( event.type === "focusin" && !!data.isVisible ) {

					// If we have jumped to another input clear the time out to cancel the show
					clearTimeout( data.delayShow );
						data.isVisible = false;
						data.delayHide = this._delay( "hide", 0 );
				}
			}
		},

		_attachToggleHandlersToPage: function( page ) {
			this._on( page, {
				focusin: "_handlePageFocusinFocusout",
				focusout: "_handlePageFocusinFocusout"
			} );
			return this._superApply( arguments );
		},

		_makeFixed: function() {
			this._super();
			this._workarounds();
		},

		//Check the browser and version and run needed workarounds
		_workarounds: function() {
			var ua = navigator.userAgent,

				// Rendering engine is Webkit, and capture major version
				wkmatch = ua.match( /AppleWebKit\/([0-9]+)/ ),
				wkversion = !!wkmatch && wkmatch[ 1 ],
				os = null,
				self = this;

			if ( ua.indexOf( "Android" ) > -1 ) {
				os = "android";
			} else {
				return;
			}

			if ( os === "android" && wkversion && wkversion < 534 ) {

				//Android 2.3 run all Android 2.3 workaround
				self._bindScrollWorkaround();
				self._bindListThumbWorkaround();
			} else {
				return;
			}
		},

		//Utility class for checking header and footer positions relative to viewport
		_viewportOffset: function() {
			var $el = this.element,
				header = $el.hasClass( "ui-toolbar-header" ),
				offset = Math.abs( $el.offset().top - this.window.scrollTop() );
			if ( !header ) {
				offset = Math.round( offset - this.window.height() + $el.outerHeight() ) - 60;
			}
			return offset;
		},

		//Bind events for _triggerRedraw() function
		_bindScrollWorkaround: function() {
			var self = this;

			//Bind to scrollstop and check if the toolbars are correctly positioned
			this._on( this.window, { scrollstop: function() {
					var viewportOffset = self._viewportOffset();

					//Check if the header is visible and if its in the right place
					if ( viewportOffset > 2 && self._visible ) {
						self._triggerRedraw();
					}
			} } );
		},

		// This addresses issue #4250 Persistent footer instability in v1.1 with long select lists
		// in Android 2.3.3 and issue #3748 Android 2.x: Page transitions broken when fixed toolbars
		// used the absolutely positioned thumbnail in a list view causes problems with fixed
		// position buttons above in a nav bar setting the li's to
		// -webkit-transform:translate3d(0,0,0); solves this problem to avoid potential issues in
		// other platforms we scope this with the class ui-android-2x-fix
		_bindListThumbWorkaround: function() {
			var pageActive = $( ".ui-page-active" ),
				currentPage = !!this.page ? this.page : pageActive.length ?
				pageActive : $( ".ui-page" ).eq( 0 );
			this._addClass( currentPage, "ui-toolbar-android-2x-fix" );
		},

		// Addresses issues #4337 Fixed header problem after scrolling content on iOS and Android
		// and device bugs project issue #1 Form elements can lose click hit area in
		// position: fixed containers. This also addresses not on fixed toolbars page in docs
		// adding 1px of padding to the bottom then removing it causes a "redraw"
		// which positions the toolbars correctly (they will always be visually correct)
		_triggerRedraw: function() {
			var paddingBottom = parseFloat( $( ".ui-page-active" ).css( "padding-bottom" ) );

			//Trigger page redraw to fix incorrectly positioned fixed elements
			$( ".ui-page-active" ).css( "padding-bottom", ( paddingBottom + 1 ) + "px" );

			//If the padding is reset with out a timeout the reposition will not occure.
			//this is independent of JQM the browser seems to need the time to react.
			setTimeout( function() {
				$( ".ui-page-active" ).css( "padding-bottom", paddingBottom + "px" );
			}, 0 );
		},

		destroy: function() {
			this._super();
			var pageActive = $( ".ui-page-active" ),
				currentPage = !!this.page ? this.page : pageActive.length ?
				pageActive : $( ".ui-page" ).eq( 0 );

			//Remove the class we added to the page previously in android 2.x
			this._removeClass( currentPage, "ui-toolbar-android-2x-fix" );
		}

	} );

}
} );

/*!
 * jQuery Mobile Popup Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Popups
//>>group: Widgets
//>>description: Deprecated popup features

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/popup.backcompat',[
			"jquery",
			"./widget.backcompat",
			"./popup" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

if ( $.mobileBackcompat !== false ) {

	$.widget( "mobile.popup", $.mobile.popup, {
		options: {
			wrapperClass: null,
			closeLinkSelector: "a:jqmData(rel='back')",
			shadow: true,
			corners: true
		},
		classProp: "ui-popup"
	} );

	$.widget( "mobile.popup", $.mobile.popup, $.mobile.widget.backcompat );

	// We override the class being toggled in response to changes in the value of the shadow option
	// because the default implementation toggles the ui-shadow class, whereas we need it to toggle
	// the ui-overlay-shadow class.
	$.mobile.popup.prototype._boolOptions.shadow = "ui-overlay-shadow";

}

return $.mobile.popup;

} );

/*!
 * jQuery Mobile Popup Arrow @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: popuparrow
//>>group: Widgets
//>>description: Arrow for popups
//>>docs: http://api.jquerymobile.com/popup/#option-arrow
//>>demos: http://demos.jquerymobile.com/@VERSION/popup/#Arrow
//>>css.structure: ../css/structure/jquery.mobile.popup.arrow.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/popup.arrow',[
			"jquery",
			"./popup" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.widget( "mobile.popup", $.mobile.popup, {
	options: {
		classes: {
			"ui-popup-arrow": "ui-overlay-shadow"
		},
		arrow: ""
	},

	_create: function() {
		var arrow,
			ret = this._superApply( arguments );

		if ( this.options.arrow ) {
			if ( this.options.enhanced ) {
				arrow = {
					gd: this.element.children( ".ui-popup-arrow-guide" ),
					ct: this.element.children( ".ui-popup-arrow-container" )
				};
				arrow.ar = arrow.ct.children( ".ui-popup-arrow" );
				arrow.arEls = arrow.ct.add( arrow.gd );
				this._addArrowClasses( arrow );
			} else {
				arrow = this._addArrow();
			}
			this._ui.arrow = arrow;
		}

		return ret;
	},

	_addArrowClasses: function( arrow ) {
		this._addClass( arrow.gd, "ui-popup-arrow-guide" );
		this._addClass( arrow.ct, "ui-popup-arrow-container",
			( $.mobile.browser.oldIE && $.mobile.browser.oldIE <= 8 ) ? "ie" : "" );
		this._addClass( arrow.ar, "ui-popup-arrow", "ui-body-inherit" );
	},

	_addArrow: function() {
		var containerDiv = this.document[ 0 ].createElement( "div" ),
			arrowDiv = this.document[ 0 ].createElement( "div" ),
			guideDiv = this.document[ 0 ].createElement( "div" ),
			arrow = {
				arEls: $( [ containerDiv, guideDiv ] ),
				gd: $( guideDiv ),
				ct: $( containerDiv ),
				ar: $( arrowDiv )
			};

		containerDiv.appendChild( arrowDiv );

		this._addArrowClasses( arrow );

		arrow.arEls.hide().appendTo( this.element );

		return arrow;
	},

	_unenhance: function() {
		var ar = this._ui.arrow;

		if ( ar ) {
			ar.arEls.remove();
		}

		return this._super();
	},

	// Pretend to show an arrow described by @p and @dir and calculate the
	// distance from the desired point. If a best-distance is passed in, return
	// the minimum of the one passed in and the one calculated.
	_tryAnArrow: function( p, dir, desired, s, best ) {
		var result, r, diff,
			desiredForArrow = {},
			tip = {};

		// If the arrow has no wiggle room along the edge of the popup, it cannot
		// be displayed along the requested edge without it sticking out.
		if ( s.arFull[ p.dimKey ] > s.guideDims[ p.dimKey ] ) {
			return best;
		}

		desiredForArrow[ p.fst ] = desired[ p.fst ] +
			( s.arHalf[ p.oDimKey ] + s.menuHalf[ p.oDimKey ] ) * p.offsetFactor -
			s.contentBox[ p.fst ] +
			( s.clampInfo.menuSize[ p.oDimKey ] - s.contentBox[ p.oDimKey ] ) *
			p.arrowOffsetFactor;
		desiredForArrow[ p.snd ] = desired[ p.snd ];

		result = s.result || this._calculateFinalLocation( desiredForArrow, s.clampInfo );
		r = { x: result.left, y: result.top };

		tip[ p.fst ] = r[ p.fst ] + s.contentBox[ p.fst ] + p.tipOffset;
		tip[ p.snd ] = Math.max( result[ p.prop ] + s.guideOffset[ p.prop ] + s.arHalf[ p.dimKey ],
			Math.min( result[ p.prop ] + s.guideOffset[ p.prop ] + s.guideDims[ p.dimKey ] -
				s.arHalf[ p.dimKey ], desired[ p.snd ] ) );

		diff = Math.abs( desired.x - tip.x ) + Math.abs( desired.y - tip.y );
		if ( !best || diff < best.diff ) {

			// Convert tip offset to coordinates inside the popup
			tip[ p.snd ] -= s.arHalf[ p.dimKey ] + result[ p.prop ] + s.contentBox[ p.snd ];
			best = { dir: dir, diff: diff, result: result, posProp: p.prop, posVal: tip[ p.snd ] };
		}

		return best;
	},

	_getPlacementState: function( clamp ) {
		var offset, gdOffset,
			ar = this._ui.arrow,
			state = {
				clampInfo: this._clampPopupWidth( !clamp ),
				arFull: { cx: ar.ct.width(), cy: ar.ct.height() },
				guideDims: { cx: ar.gd.width(), cy: ar.gd.height() },
				guideOffset: ar.gd.offset()
			};

		offset = this.element.offset();

		ar.gd.css( { left: 0, top: 0, right: 0, bottom: 0 } );
		gdOffset = ar.gd.offset();
		state.contentBox = {
			x: gdOffset.left - offset.left,
			y: gdOffset.top - offset.top,
			cx: ar.gd.width(),
			cy: ar.gd.height()
		};
		ar.gd.removeAttr( "style" );

		// The arrow box moves between guideOffset and guideOffset + guideDims - arFull
		state.guideOffset = {
			left: state.guideOffset.left - offset.left,
			top: state.guideOffset.top - offset.top
		};
		state.arHalf = {
			cx: state.arFull.cx / 2,
			cy: state.arFull.cy / 2
		};
		state.menuHalf = {
			cx: state.clampInfo.menuSize.cx / 2,
			cy: state.clampInfo.menuSize.cy / 2
		};

		return state;
	},

	_placementCoords: function( desired ) {
		var state, best, params,
			optionValue = this.options.arrow,
			ar = this._ui.arrow;

		if ( !ar ) {
			return this._super( desired );
		}

		ar.arEls.show();

		state = this._getPlacementState( true );
		params = {
			"l": { fst: "x", snd: "y", prop: "top", dimKey: "cy", oDimKey: "cx", offsetFactor: 1,
				tipOffset: -state.arHalf.cx, arrowOffsetFactor: 0
			},
			"r": {
				fst: "x", snd: "y", prop: "top", dimKey: "cy", oDimKey: "cx", offsetFactor: -1,
					tipOffset: state.arHalf.cx + state.contentBox.cx, arrowOffsetFactor: 1
			},
			"b": {
				fst: "y", snd: "x", prop: "left", dimKey: "cx", oDimKey: "cy", offsetFactor: -1,
					tipOffset: state.arHalf.cy + state.contentBox.cy, arrowOffsetFactor: 1
			},
			"t": {
				fst: "y", snd: "x", prop: "left", dimKey: "cx", oDimKey: "cy", offsetFactor: 1,
					tipOffset: -state.arHalf.cy, arrowOffsetFactor: 0
			}
		};

		// Try each side specified in the options to see on which one the arrow
		// should be placed such that the distance between the tip of the arrow and
		// the desired coordinates is the shortest.
		$.each( ( optionValue === true ? "l,t,r,b" : optionValue ).split( "," ),
			$.proxy( function( key, value ) {
				best = this._tryAnArrow( params[ value ], value, desired, state, best );
			}, this ) );

		// Could not place the arrow along any of the edges - behave as if showing
		// the arrow was turned off.
		if ( !best ) {
			ar.arEls.hide();
			return this._super( desired );
		}

		// Move the arrow into place
		this._removeClass( ar.ct,
			"ui-popup-arrow-l ui-popup-arrow-t ui-popup-arrow-r ui-popup-arrow-b" )
			._addClass( ar.ct, "ui-popup-arrow-" + best.dir );
		ar.ct
			.removeAttr( "style" ).css( best.posProp, best.posVal )
			.show();

		return best.result;
	},

	_setOptions: function( opts ) {
		var ar = this._ui.arrow,
			ret = this._super( opts );

		if ( opts.arrow !== undefined ) {
			if ( !ar && opts.arrow ) {
				this._ui.arrow = this._addArrow();

				// Important to return here so we don't set the same options all over
				// again below.
				return;
			} else if ( ar && !opts.arrow ) {
				ar.arEls.remove();
				delete this._ui.arrow;
			}
		}

		return ret;
	},

	_destroy: function() {
		var ar = this._ui.arrow;

		if ( ar ) {
			ar.arEls.remove();
		}

		return this._super();
	}
} );

} );

/*!
 * jQuery Mobile Popup Arrow Backcompat @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Popups
//>>group: Widgets
//>>description: Deprecated popup arrow features

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/popup.arrow.backcompat',[
			"jquery",
			"./popup.backcompat",
			"./popup.arrow" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var shadowClassRe = /\bui-overlay-shadow\b/;

if ( $.mobileBackcompat !== false ) {

	$.widget( "mobile.popup", $.mobile.popup, {
		_setInitialOptions: function() {
			var classes = this.options.classes;

			this._super();

			// If the value for the ui-popup-arrow class key has not changed we assume we're
			// dealing with legacy code, so we make sure the presence of the ui-overlay-shadow
			// class in the ui-popup-arrow key reflects the value of the "shadow" option.
			if ( classes[ "ui-popup-arrow" ] ===
					$[ this.namespace ][ this.widgetName ].prototype.options
						.classes[ "ui-popup-arrow" ] ) {

				classes[ "ui-popup-arrow" ] = this._getClassValue( classes[ "ui-popup-arrow" ],
					"ui-overlay-shadow", this.options.shadow );
			}
		},

		// The presence of the class ui-overlay-shadow in ui-popup must be synchronized to its
		// presence in ui-popup-arrow as long as the shadow option is supported, because the widget
		// backcompat synchronizes its presence in ui-popup to the value of the shadow option.
		_setOption: function( key, value ) {
			var popupHasShadow;

			if ( key === "classes" ) {
				popupHasShadow = value[ "ui-popup" ].match( shadowClassRe );
				if ( value[ "ui-popup-arrow" ].match( shadowClassRe ) !== popupHasShadow ) {
					value[ "ui-popup-arrow" ] = this._getClassValue( value[ "ui-popup-arrow" ],
						"ui-overlay-shadow", popupHasShadow );
				}
			}

			return this._superApply( arguments );
		}
	} );

}

return $.mobile.popup;

} );

/*!
 * jQuery Mobile Panel @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Panel
//>>group: Widgets
//>>description: Responsive presentation and behavior for HTML data panels
//>>docs: http://api.jquerymobile.com/panel/
//>>demos: http://demos.jquerymobile.com/@VERSION/panel/
//>>css.structure: ../css/structure/jquery.mobile.panel.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/panel',[
			"jquery",
			"../widget",
			"./page" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.widget( "mobile.panel", {
	version: "@VERSION",

	options: {
		classes: {
		},
		animate: true,
		theme: null,
		position: "left",
		dismissible: true,

		// Accepts reveal, push, overlay
		display: "reveal",
		swipeClose: true,
		positionFixed: false
	},

	_closeLink: null,
	_parentPage: null,
	_page: null,
	_modal: null,
	_panelInner: null,
	_wrapper: null,
	_fixedToolbars: null,

	_create: function() {
		var el = this.element,
			parentPage = el.closest( ".ui-page, :jqmData(role='page')" );

		// Expose some private props to other methods
		$.extend( this, {
			_closeLink: el.find( ":jqmData(rel='close')" ),
			_parentPage: ( parentPage.length > 0 ) ? parentPage : false,
			_openedPage: null,
			_page: this._getPage,
			_panelInner: this._getPanelInner(),
			_fixedToolbars: this._getFixedToolbars
		} );
		if ( this.options.display !== "overlay" ) {
			this._getWrapper();
		}

		this._addClass( "ui-panel ui-panel-closed", this._getPanelClasses() );

		// If animating, add the class to do so
		if ( $.support.cssTransform3d && !!this.options.animate ) {
			this._addClass( "ui-panel-animate" );
		}

		this._bindUpdateLayout();
		this._bindCloseEvents();
		this._bindLinkListeners();
		this._bindPageEvents();

		if ( !!this.options.dismissible ) {
			this._createModal();
		}

		this._bindSwipeEvents();
		this._superApply( arguments );
	},

	_safelyWrap: function( parent, wrapperHtml, children ) {
		if ( children.length ) {
			children.eq( 0 ).before( wrapperHtml );
			wrapperHtml.append( children );
			return children.parent();
		} else {
			return $( wrapperHtml ).appendTo( parent );
		}
	},

	_getPanelInner: function() {
		var panelInner = this.element.find( ".ui-panel-inner" );

		if ( panelInner.length === 0 ) {
			panelInner = $( "<div>" );
			this._addClass( panelInner, "ui-panel-inner" );
			panelInner = this._safelyWrap( this.element, panelInner, this.element.children() );
		}

		return panelInner;
	},

	_createModal: function() {
		var that = this,
			target = that._parentPage ? that._parentPage.parent() : that.element.parent();

		that._modal = $( "<div>" );
		that._addClass( that._modal, "ui-panel-dismiss" );

		that._modal.on( "mousedown", function() {
				that.close();
			} )
			.appendTo( target );
	},

	_getPage: function() {
		var page = this._openedPage || this._parentPage || $( ".ui-page-active" );

		return page;
	},

	_getWrapper: function() {
		var thePage,
			wrapper = this._page().find( ".ui-panel-wrapper" );

		if ( wrapper.length === 0 ) {
			thePage = this._page();
			wrapper = $( "<div>" );
			this._addClass( wrapper, "ui-panel-wrapper" );
			wrapper = this._safelyWrap( thePage, wrapper,
				this._page().children( ".ui-toolbar-header:not(.ui-toolbar-header-fixed), " +
					"[data-" + $.mobile.ns + "role='toolbar'],"  +
					".ui-content:not(.ui-popup)," +
					".ui-toolbar-footer:not(.ui-toolbar-footer-fixed)" ) );
		}

		this._wrapper = wrapper;
	},

	_getFixedToolbars: function() {
		var extFixedToolbars = $( "body" )
								.children( ".ui-toolbar-header-fixed, .ui-toolbar-footer-fixed" ),
			intFixedToolbars = this._page()
								.find( ".ui-toolbar-header-fixed, .ui-toolbar-footer-fixed" ),
			fixedToolbars = extFixedToolbars.add( intFixedToolbars );

		this._addClass( fixedToolbars, "ui-panel-fixed-toolbar" );

		return fixedToolbars;
	},

	_getPosDisplayClasses: function( prefix ) {
		return prefix + "-position-" +
			this.options.position + " " + prefix +
			"-display-" + this.options.display;
	},

	_getPanelClasses: function() {
		var panelClasses = this._getPosDisplayClasses( "ui-panel" ) +
			" " + "ui-body-" + ( this.options.theme ? this.options.theme : "inherit" );

		if ( !!this.options.positionFixed ) {
			panelClasses += " ui-panel-fixed";
		}

		return panelClasses;
	},

	_handleCloseClick: function( event ) {
		if ( !event.isDefaultPrevented() ) {
			this.close();
		}
	},

	_bindCloseEvents: function() {
		this._on( this._closeLink, {
			"click": "_handleCloseClick"
		} );

		this._on( {
			"click a:jqmData(ajax='false')": "_handleCloseClick"
		} );
	},

	_positionPanel: function( scrollToTop ) {
		var heightWithMargins, heightWithoutMargins,
			that = this,
			panelInnerHeight = that._panelInner.outerHeight(),
			expand = panelInnerHeight > this.window.height();

		if ( expand || !that.options.positionFixed ) {
			if ( expand ) {
				that._unfixPanel();
				$.mobile.resetActivePageHeight( panelInnerHeight );
			} else if ( !this._parentPage ) {
				heightWithMargins = this.element.outerHeight( true );
				if ( heightWithMargins < this.document.height() ) {
					heightWithoutMargins = this.element.outerHeight();

					// Set the panel's total height (including margins) to the document height
					this.element.outerHeight( this.document.height() -
						( heightWithMargins - heightWithoutMargins ) );
				}
			}
			if ( scrollToTop === true &&
				!$.mobile.isElementCurrentlyVisible( ".ui-content" ) ) {
				this.window[ 0 ].scrollTo( 0, $.mobile.defaultHomeScroll );
			}
		} else {
			that._fixPanel();
		}
	},

	_bindFixListener: function() {
		this._on( this.window, { "throttledresize": "_positionPanel" } );
	},

	_unbindFixListener: function() {
		this._off( this.window, "throttledresize" );
	},

	_unfixPanel: function() {
		if ( !!this.options.positionFixed && $.support.fixedPosition ) {
			this._removeClass( "ui-panel-fixed" );
		}
	},

	_fixPanel: function() {
		if ( !!this.options.positionFixed && $.support.fixedPosition ) {
			this._addClass( "ui-panel-fixed" );
		}
	},

	_bindUpdateLayout: function() {
		var that = this;

		that.element.on( "updatelayout", function( /* e */ ) {
			if ( that._open ) {
				that._positionPanel();
			}
		} );
	},

	_bindLinkListeners: function() {
		this._on( "body", {
			"click a": "_handleClick"
		} );

	},

	_handleClick: function( e ) {
		var link,
			panelId = this.element.attr( "id" ),
			that = this;

		if ( e.currentTarget.href.split( "#" )[ 1 ] === panelId && panelId !== undefined ) {

			e.preventDefault();
			link = $( e.target );
			if ( link.hasClass( "ui-button" ) ) {
				this._addClass( link, null, "ui-button-active" );
				this.element.one( "panelopen panelclose", function() {
					that._removeClass( link, null, "ui-button-active" );
				} );
			}
			this.toggle();
		}
	},

	_handleSwipe: function( event ) {
		if ( !event.isDefaultPrevented() ) {
			this.close();
		}
	},

	_bindSwipeEvents: function() {
		var handler = {};

		// Close the panel on swipe if the swipe event's default is not prevented
		if ( this.options.swipeClose ) {
			handler[ "swipe" + this.options.position ] = "_handleSwipe";
			this._on( ( this._modal ? this.element.add( this._modal ) : this.element ), handler );
		}
	},

	_bindPageEvents: function() {
		var that = this;

		this.document

			// Close the panel if another panel on the page opens
			.on( "panelbeforeopen", function( e ) {
				if ( that._open && e.target !== that.element[ 0 ] ) {
					that.close();
				}
			} )

			// On escape, close? might need to have a target check too...
			.on( "keyup.panel", function( e ) {
				if ( e.keyCode === 27 && that._open ) {
					that.close();
				}
			} );
		if ( !this._parentPage && this.options.display !== "overlay" ) {
			this._on( this.document, {
				"pageshow": function() {
					this._openedPage = null;
					this._getWrapper();
				}
			} );
		}

		// Clean up open panels after page hide
		if ( that._parentPage ) {
			this.document.on( "pagehide", ":jqmData(role='page')", function() {
				if ( that._open ) {
					that.close( true );
				}
			} );
		} else {
			this.document.on( "pagebeforehide", function() {
				if ( that._open ) {
					that.close( true );
				}
			} );
		}
	},

	// State storage of open or closed
	_open: false,
	_pageContentOpenClasses: null,
	_modalOpenClasses: null,

	open: function( immediate ) {
		if ( !this._open ) {
			var that = this,
				o = that.options,

				complete = function() {

					// Bail if the panel was closed before the opening animation has completed
					if ( !that._open ) {
						return;
					}

					if ( o.display !== "overlay" ) {
						that._addClass( that._wrapper, "ui-panel-page-content-open" );
						that._addClass( that._fixedToolbars(), "ui-panel-page-content-open" );
					}

					that._bindFixListener();

					that._trigger( "open" );

					that._openedPage = that._page();
				},

				_openPanel = function() {
					that._off( that.document, "panelclose" );
					that._page().jqmData( "panel", "open" );

					if ( $.support.cssTransform3d && !!o.animate && o.display !== "overlay" ) {
						that._addClass( that._wrapper, "ui-panel-animate" );
						that._addClass( that._fixedToolbars(), "ui-panel-animate" );
					}

					if ( !immediate && $.support.cssTransform3d && !!o.animate ) {
						( that._wrapper || that.element )
							.animationComplete( complete, "transition" );
					} else {
						setTimeout( complete, 0 );
					}

					if ( o.theme && o.display !== "overlay" ) {
						that._addClass( that._page().parent(),
							"ui-panel-page-container-themed ui-panel-page-container-" + o.theme );
					}

					that._removeClass( "ui-panel-closed" )
						._addClass( "ui-panel-open" );

					that._positionPanel( true );

					that._pageContentOpenClasses =
						that._getPosDisplayClasses( "ui-panel-page-content" );

					if ( o.display !== "overlay" ) {
						that._addClass( that._page().parent(), "ui-panel-page-container" );
						that._addClass( that._wrapper, that._pageContentOpenClasses );
						that._addClass( that._fixedToolbars(), that._pageContentOpenClasses );
					}

					that._modalOpenClasses =
						that._getPosDisplayClasses( "ui-panel-dismiss" ) +
						" ui-panel-dismiss-open";

					if ( that._modal ) {
						that._addClass( that._modal, that._modalOpenClasses );

						that._modal.height(
							Math.max( that._modal.height(), that.document.height() ) );
					}
				};

			that._trigger( "beforeopen" );

			if ( that._page().jqmData( "panel" ) === "open" ) {
				that._on( that.document, {
					"panelclose": _openPanel
				} );
			} else {
				_openPanel();
			}

			that._open = true;
		}
	},

	close: function( immediate ) {
		if ( this._open ) {
			var that = this,

				// Record what the page is the moment the process of closing begins, because it
				// may change by the time the process completes
				currentPage = that._page(),
				o = this.options,

				complete = function() {
					if ( o.theme && o.display !== "overlay" ) {
						that._removeClass( currentPage.parent(),
							"ui-panel-page-container-themed ui-panel-page-container-" + o.theme );
					}

					that._addClass( "ui-panel-closed" );

					// Scroll to the top
					that._positionPanel( true );

					if ( o.display !== "overlay" ) {
						that._removeClass( currentPage.parent(), "ui-panel-page-container" );
						that._removeClass( that._wrapper, "ui-panel-page-content-open" );
						that._removeClass( that._fixedToolbars(), "ui-panel-page-content-open" );
					}

					if ( $.support.cssTransform3d && !!o.animate && o.display !== "overlay" ) {
						that._removeClass( that._wrapper, "ui-panel-animate" );
						that._removeClass( that._fixedToolbars(), "ui-panel-animate" );
					}

					that._fixPanel();
					that._unbindFixListener();
					$.mobile.resetActivePageHeight();

					currentPage.jqmRemoveData( "panel" );

					that._trigger( "close" );

					that._openedPage = null;
				},
				_closePanel = function() {

					that._removeClass( "ui-panel-open" );

					if ( o.display !== "overlay" ) {
						that._removeClass( that._wrapper, that._pageContentOpenClasses );
						that._removeClass( that._fixedToolbars(), that._pageContentOpenClasses );
					}

					if ( !immediate && $.support.cssTransform3d && !!o.animate ) {
						( that._wrapper || that.element )
							.animationComplete( complete, "transition" );
					} else {
						setTimeout( complete, 0 );
					}

					if ( that._modal ) {
						that._removeClass( that._modal, that._modalOpenClasses );
						that._modal.height( "" );
					}
				};

			that._trigger( "beforeclose" );

			_closePanel();

			that._open = false;
		}
	},

	toggle: function() {
		this[ this._open ? "close" : "open" ]();
	},

	_destroy: function() {
		var otherPanels,
			o = this.options,
			multiplePanels = ( $( "body > :mobile-panel" ).length +
				$.mobile.activePage.find( ":mobile-panel" ).length ) > 1;

		if ( o.display !== "overlay" ) {

			// Remove the wrapper if not in use by another panel
			otherPanels = $( "body > :mobile-panel" ).add(
				$.mobile.activePage.find( ":mobile-panel" ) );

			if ( otherPanels.not( ".ui-panel-display-overlay" ).not( this.element ).length === 0 ) {
				this._wrapper.children().unwrap();
			}

			if ( this._open ) {
				this._removeClass( this._fixedToolbars(), "ui-panel-page-content-open" );

				if ( $.support.cssTransform3d && !!o.animate ) {
					this._removeClass( this._fixedToolbars(), "ui-panel-animate" );
				}

				this._removeClass( this._page().parent(), "ui-panel-page-container" );

				if ( o.theme ) {
					this._removeClass( this._page().parent(),
						"ui-panel-page-container-themed ui-panel-page-container-" + o.theme );
				}
			}
		}

		if ( !multiplePanels ) {
			this.document.off( "panelopen panelclose" );
		}

		if ( this._open ) {
			this._page().jqmRemoveData( "panel" );
		}

		this._panelInner.children().unwrap();

		this._removeClass( "ui-panel ui-panel-closed ui-panel-open ui-panel-animate",
			this._getPanelClasses() );

		this.element.off( "panelbeforeopen panelhide keyup.panel updatelayout" );

		if ( this._modal ) {
			this._modal.remove();
		}

		this._superApply( arguments );
	}
} );

} );

/*!
 * jQuery Mobile Table @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Table
//>>group: Widgets
//>>description: Responsive presentation and behavior for HTML data tables
//>>docs: http://api.jquerymobile.com/table/
//>>css.structure: ../css/structure/jquery.mobile.table.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/table',[
			"jquery",
			"../widget",
			"./page" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.widget( "mobile.table", {
	version: "@VERSION",

	options: {
		classes: {
			"ui-table": ""
		},
		enhanced: false
	},

	// Expose headers and allHeaders properties on the widget headers references the THs within the
	// first TR in the table
	headers: null,

	// AllHeaders references headers, plus all THs in the thead, which may or may not include
	// several rows
	allHeaders: null,

	_create: function() {
		var options = this.options;

		if ( !options.enhanced ) {
			this._addClass( "ui-table",
				( options.disabled ? " ui-state-disabled" : "" ) );
		}

		this.refresh();
	},

	_setOptions: function( options ) {
		if ( options.disabled !== undefined ) {
			this._toggleClass( null, "ui-state-disabled", options.disabled );
		}
		return this._super( options );
	},

	_setHeaders: function() {
		this.headerRows = this.element.children( "thead" ).children( "tr" );
		this.headers = this.headerRows.first().children();
		this.allHeaders = this.headerRows.children();
		this.allRowsExceptFirst = this.element
			.children( "thead,tbody" )
				.children( "tr" )
					.not( this.headerRows.eq( 0 ) );
	},

	// Deprecated as of 1.5.0 and will be removed in 1.6.0 - use refresh() instead
	rebuild: function() {
		this.refresh();
	},

	_refreshHeaderCell: function( cellIndex, element, columnCount ) {
		var columnIndex,
			span = parseInt( element.getAttribute( "colspan" ), 10 ),
			selector = ":nth-child(" + ( columnCount + 1 ) + ")";

		if ( span ) {
			for ( columnIndex = 0; columnIndex < span - 1; columnIndex++ ) {
				columnCount++;
				selector += ", :nth-child(" + ( columnCount + 1 ) + ")";
			}
		}

		// Store "cells" data on header as a reference to all cells in the same column as this TH
		$( element ).jqmData( "cells",
			this.allRowsExceptFirst
				.not( element )
				.children( selector ) );

		return columnCount;
	},

	_refreshHeaderRow: function( rowIndex, element ) {
		var columnCount = 0;

		// Iterate over the children of the tr
		$( element ).children().each( $.proxy( function( cellIndex, element ) {
			columnCount = this._refreshHeaderCell( cellIndex, element, columnCount ) + 1;
		}, this ) );
	},

	refresh: function() {

		// Updating headers on refresh (fixes #5880)
		this._setHeaders();

		// Iterate over the header rows
		this.headerRows.each( $.proxy( this, "_refreshHeaderRow" ) );
	},

	_destroy: function() {
		var table = this.element;

		// We have to remove "cells" data even if the table was originally enhanced, because it is
		// added during refresh
		table.find( "thead tr" ).children().each( function() {
			$( this ).jqmRemoveData( "cells" );
		} );
	}
} );

} );


/*!
 * jQuery Mobile Column-toggling Table @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Table: Column Toggle
//>>group: Widgets
//>>description: Extends the table widget to a column toggle menu and responsive column visibility
//>>docs: http://api.jquerymobile.com/table-columntoggle/
//>>demos: http://demos.jquerymobile.com/@VERSION/table-column-toggle/
//>>css.structure: ../css/structure/jquery.mobile.table.columntoggle.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/table.columntoggle',[
			"jquery",
			"./table" ], factory );
	} else {

		factory( jQuery );
	}
} )( function( $, undefined ) {

return $.widget( "mobile.table", $.mobile.table, {
	options: {
		mode: "columntoggle",
		classes: {
			"ui-table-cell-hidden": "",
			"ui-table-cell-visible": "",
			"ui-table-priority-": "",
			"ui-table-columntoggle": ""
		}
	},

	_create: function() {

		// Needed because the superclass calls refresh() which needs to behave differently if
		// _create() hasn't happened yet
		this._instantiating = true;

		this._super();

		if ( this.options.mode !== "columntoggle" ) {
			return;
		}

		if ( !this.options.enhanced ) {
			this._enhanceColumnToggle();
		}

		// Cause refresh() to revert to normal operation
		this._instantiating = false;
	},

	_enhanceColumnToggle: function() {
		this._addClass( "ui-table-columntoggle" );
		this._updateHeaderPriorities();
	},

	_updateVariableColumn: function( header, cells, priority ) {
		this._addClass( cells, "ui-table-priority-" + priority );
	},

	_updateHeaderPriorities: function( state ) {
		this.headers.each( $.proxy( function( index, element ) {
			var header = $( element ),
				priority = $.mobile.getAttribute( element, "priority" );

			if ( priority ) {
				this._updateVariableColumn(
					header,
					header.add( header.jqmData( "cells" ) ),
					priority,
					state );
			}
		}, this ) );
	},

	_setColumnVisibility: function( header, visible ) {
		var cells = header.jqmData( "cells" );

		if ( cells ) {
			cells = cells.add( header );
			this._unlock( cells );
			this._addClass( cells,
				visible ? "ui-table-cell-visible" : "ui-table-cell-hidden" );
		}
	},

	setColumnVisibility: function( cell, visible ) {
		var header;

		// If cell is a number, then simply index into the headers array
		if ( $.type( cell ) === "number" ) {
			header = this.headers.eq( cell );

		// Otherwise it's assumed to be a jQuery collection object
		} else if ( cell.length > 0 ) {

			// If it's one of the headers, then we already have the header we wanted
			if ( this.headers.index( cell[ 0 ] ) >= 0 ) {
				header = cell.first();

			// Otherwise we assume it's one of the cells, so look for it in the "cells" data for
			// each header
			} else {
				this.headers.each( $.proxy( function( index, singleHeader ) {
					var possibleHeader = $( singleHeader ),
						cells = possibleHeader.jqmData( "cells" );

					if ( ( cells ? cells.index( cell[ 0 ] ) : -1 ) >= 0 ) {
						header = possibleHeader;
						return false;
					}
				}, this ) );
			}
		}

		if ( header ) {
			this._setColumnVisibility( header, visible );
		}
	},

	_unlock: function( cells ) {

		// Allow hide/show via CSS only = remove all toggle-locks
		var locked = ( cells ||
			this.element
				.children( "thead, tbody" )
					.children( "tr" )
						.children( ".ui-table-cell-hidden, .ui-table-cell-visible" ) );
		this._removeClass( locked, "ui-table-cell-hidden ui-table-cell-visible" );
	},

	_recordLockedColumns: $.noop,
	_restoreLockedColumns: $.noop,

	refresh: function() {
		var lockedColumns;

		// Calling _super() here updates this.headers
		this._super();

		if ( !this._instantiating && this.options.mode === "columntoggle" ) {

			// Record which columns are locked
			lockedColumns = this._recordLockedColumns();

			// Columns not being replaced must be cleared from input toggle-locks
			this._unlock();

			// Update priorities
			this._updateHeaderPriorities();

			// Make sure columns that were locked before this refresh, and which are still around
			// after the refresh, are restored to their locked state
			this._restoreLockedColumns( lockedColumns );
		}
	},

	_destroy: function() {
		if ( this.options.mode === "columntoggle" ) {
			if ( !this.options.enhanced ) {
				this.headers.each( $.proxy( function( index, element ) {
					var header,
						priority = $.mobile.getAttribute( element, "priority" );

					if ( priority ) {
						header = $( element );
						header
							.add( header.jqmData( "cells" ) );
					}
				}, this ) );
			}
		}
		return this._superApply( arguments );
	}
} );

} );

/*!
 * jQuery Mobile Table @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>description: Extends the table widget to a column toggle menu and responsive column visibility
//>>label: Table: Column Toggle
//>>group: Widgets
//>>css.structure: ../css/structure/jquery.mobile.table.columntoggle.popup.css

( function( factory ) {

		if ( typeof define === "function" && define.amd ) {

			// AMD. Register as an anonymous module.
			define( 'widgets/table.columntoggle.popup',[
			"jquery",
			"./table.columntoggle",
			"./popup",
			"./controlgroup",
			"./forms/button",
			"./widget.theme",
			"./forms/checkboxradio" ], factory );
		} else {

			// Browser globals
			factory( jQuery );
		}
} )( function( $, undefined ) {

return $.widget( "mobile.table", $.mobile.table, {
	options: {
		columnButton: true,
		columnButtonTheme: null,
		columnPopupTheme: null,
		columnButtonText: "Columns...",
		columnUi: true,
		classes: {
			"ui-table-columntoggle-popup": "",
			"ui-table-columntoggle-btn": "ui-corner-all ui-shadow ui-mini"
		}
	},

	_create: function() {
		var id, popup;

		this.options.columnButtonTheme =
		this.options.columnButtonTheme ? this.options.columnButtonTheme : "inherit";

		this._super();


		if ( this.options.mode !== "columntoggle" || !this.options.columnUi ) {
			return;
		}

		if ( this.options.enhanced ) {
			id = this._id();
			popup = $( this.document[ 0 ].getElementById( id + "-popup" ) );
			this._ui = {
				popup: popup,
				menu: popup.children().first(),
				button: $( this.document[ 0 ].getElementById( id + "-button" ) )
			};
			this._updateHeaderPriorities( { keep: true } );
		}
	},

	_updateVariableColumn: function( header, cells, priority, state ) {
		var input;

		if ( this.options.columnUi || ( state && state.turningOnUI ) ) {

			// Make sure the (new?) checkbox is associated with its header via .jqmData() and that,
			// vice versa, the header is also associated with the checkbox
			input = ( state.keep ? state.inputs.eq( state.checkboxIndex++ ) :
				$( "<label><input type='checkbox' checked />" +
					( header.children( "abbr" ).first().attr( "title" ) || header.text() ) +
					"</label>" )
					.appendTo( state.container )
					.children( 0 )
					.checkboxradio( {
						theme: this.options.columnPopupTheme
					} ) );

			// Associate the header with the checkbox
			input
				.jqmData( "header", header )
				.jqmData( "cells", cells );

			// Associate the checkbox with the header
			header.jqmData( "input", input );
		}

		return ( state && state.turningOnUI ) ? this : this._superApply( arguments );
	},

	_updateHeaderPriorities: function( state ) {
		var inputs, container, returnValue;

		state = state || {};

		if ( this.options.columnUi || state.turningOnUI ) {
			container = this._ui.menu.controlgroup( "container" );

			// Allow update of menu on refresh (fixes #5880)
			if ( state.keep ) {
				inputs = container.find( "input" );
			} else {
				container.empty();
			}

			returnValue = this._super( $.extend( state, {
				checkboxIndex: 0,
				container: container,
				inputs: inputs
			} ) );

			// The controlgroup can only be refreshed after having called the superclass, because
			// the superclass ultimately ends up instantiating the checkboxes inside the
			// controlgroup's container
			if ( !state.keep ) {
				this._ui.menu.controlgroup( "refresh" );
			}

			this._setupEvents();
			this._setToggleState();
		} else {
			returnValue = this._superApply( arguments );
		}

		return returnValue;
	},

	_id: function() {
		return ( this.element.attr( "id" ) || ( this.widgetName + this.uuid ) );
	},

	_themeClassFromOption: function( prefix, value ) {
		return ( value ? ( value === "none" ? "" : prefix + value ) : "" );
	},

	_removeColumnUi: function( detachOnly ) {
		var inputs = this._ui.menu.find( "input" );

		inputs.each( function() {
			var input = $( this ),
				header = input.jqmData( "header" );

			// If we're simply detaching, the checkboxes will be left alone, but the jqmData()
			// attached to them has to be removed
			if ( detachOnly ) {
				input
					.jqmRemoveData( "cells" )
					.jqmRemoveData( "header" );
			}

			// The reference from the header to the input has to be removed whether we're merely
			// detaching, or whether we're removing altogether
			header.jqmRemoveData( "input" );
		} );

		if ( !detachOnly ) {
			this._ui.menu.remove();
			this._ui.popup.remove();
			if ( this._ui.button ) {
				this._ui.button.remove();
			}
		}
	},

	_setOptions: function( options ) {
		var haveUi = this.options.columnUi;

		if ( this.options.mode === "columntoggle" ) {

			if ( options.columnUi != null ) {
				if ( this.options.columnUi && !options.columnUi ) {
					this._removeColumnUi( false );
				} else if ( !this.options.columnUi && options.columnUi ) {
					this._addColumnUI( {
						callback: this._updateHeaderPriorities,
						callbackContext: this,
						callbackArguments: [ { turningOnUI: true } ]
					} );
				}

				haveUi = options.columnUi;
			}

			if ( haveUi ) {
				if ( options.disabled != null ) {
					this._ui.popup.popup( "option", "disabled", options.disabled );
					if ( this._ui.button ) {
						this._toggleClass( this._ui.button,
							"ui-state-disabled", null, options.disabled );
						if ( options.disabled ) {
							this._ui.button.attr( "tabindex", -1 );
						} else {
							this._ui.button.removeAttr( "tabindex" );
						}
					}
				}
				if ( options.columnButtonTheme != null && this._ui.button ) {
					this._removeClass( this._ui.button, null,
						this._themeClassFromOption(
							"ui-button-",
							this.options.columnButtonTheme ) );
					this._addClass( this._ui.button, null,
						this._themeClassFromOption(
							"ui-button-",
							options.columnButtonTheme ) );
				}
				if ( options.columnPopupTheme != null ) {
					this._ui.popup.popup( "option", "theme", options.columnPopupTheme );
				}
				if ( options.columnButtonText != null && this._ui.button ) {
					this._ui.button.text( options.columnButtonText );
				}
				if ( options.columnButton != null ) {
					if ( options.columnButton ) {
						if ( !this._ui.button || this._ui.button.length === 0 ) {
							this._ui.button = this._columnsButton();
						}
						this._ui.button.insertBefore( this.element );
					} else if ( this._ui.button ) {
						this._ui.button.detach();
					}
				}
			}
		}

		return this._superApply( arguments );
	},

	_setColumnVisibility: function( header, visible, /* INTERNAL */ fromInput ) {
		var input;

		if ( !fromInput && this.options.columnUi ) {
			input = header.jqmData( "input" );

			if ( input ) {
				input.prop( "checked", visible ).checkboxradio( "refresh" );
			}
		}

		return this._superApply( arguments );
	},

	_setupEvents: function() {

		//NOTE: inputs are bound in bindToggles,
		// so it can be called on refresh, too

		// Update column toggles on resize
		this._on( this.window, {
			throttledresize: "_setToggleState"
		} );
		this._on( this._ui.menu, {
			"change input": "_menuInputChange"
		} );
	},

	_menuInputChange: function( event ) {
		var input = $( event.target );

		this._setColumnVisibility( input.jqmData( "header" ), input.prop( "checked" ), true );
	},

	_columnsButton: function() {
		var id = this._id(),
			options = this.options,
			button = $( "<a href='#" + id + "-popup' " +
				"id='" + id + "-button' " +
				"data-" + $.mobile.ns + "rel='popup' data-theme='" +
				options.columnButtonTheme + "'>" + options.columnButtonText + "</a>" );

		button.button();
		this._addClass( button, "ui-table-columntoggle-btn" );

		this._on( button, {
			click: "_handleButtonClicked"
		} );

		return button;
	},

	_addColumnUI: function( updater ) {
		var ui, id, popupId, table, options, popupThemeAttr, fragment, returnValue;

		id = this._id();
		popupId = id + "-popup";
		table = this.element;
		options = this.options;
		popupThemeAttr = options.columnPopupTheme ?
			( " data-" + $.mobile.ns + "theme='" + options.columnPopupTheme + "'" ) : "";
		fragment = this.document[ 0 ].createDocumentFragment();
		ui = this._ui = {
			button: this.options.columnButton ? this._columnsButton() : null,
			popup: $( "<div id='" + popupId + "'" +
				popupThemeAttr + "></div>" ),
			menu: $( "<fieldset></fieldset>" ).controlgroup()
		};

		this._addClass( ui.popup, "ui-table-columntoggle-popup" );

		// Call the updater before we attach the menu to the DOM, because its job is to populate
		// the menu with checkboxes, and we don't want to do that when it's already attached to
		// the DOM because we want to avoid causing reflows
		returnValue = updater.callback.apply( updater.callbackContext, updater.callbackArguments );

		ui.menu.appendTo( ui.popup );

		fragment.appendChild( ui.popup[ 0 ] );
		if ( ui.button ) {
			fragment.appendChild( ui.button[ 0 ] );
		}
		table.before( fragment );

		ui.popup.popup();

		return returnValue;
	},

	_enhanceColumnToggle: function() {
		return this.options.columnUi ?
			this._addColumnUI( {
				callback: this._superApply,
				callbackContext: this,
				callbackArguments: arguments
			} ) :
			this._superApply( arguments );
	},

	_handleButtonClicked: function( event ) {
		$.mobile.popup.handleLink( this._ui.button );
		event.preventDefault();
	},

	_setToggleState: function() {
		this._ui.menu.find( "input" ).each( function() {
			var checkbox = $( this );

			checkbox
				.prop( "checked",
					( checkbox.jqmData( "cells" ).eq( 0 ).css( "display" ) === "table-cell" ) )
				.checkboxradio( "refresh" );
		} );
	},

	// Use the .jqmData() stored on the checkboxes to determine which columns have show/hide
	// overrides, and make a list of the indices of those that have such overrides
	_recordLockedColumns: function() {
		var headers = this.headers,
			lockedColumns = [];

		// Find the index of the column header associated with each old checkbox among the
		// post-refresh headers and, if the header is still there, make sure the corresponding
		// column will be hidden if the pre-refresh checkbox indicates that the column is
		// hidden by recording its index in the array of hidden columns.
		this._ui.menu.find( "input" ).each( function() {
			var input = $( this ),
				header = input.jqmData( "header" ),
				index = -1;

			if ( header ) {
				index = headers.index( header[ 0 ] );
			}

			if ( index > -1 ) {

				// The column header associated with /this/ checkbox is still present in the
				// post-refresh table and it is locked, so the column associated with this column
				// header is also currently locked. Let's record that.
				lockedColumns = lockedColumns.concat(
					header.hasClass( "ui-table-cell-visible" ) ?
						[ { index: index, visible: true } ] :
					header.hasClass( "ui-table-cell-hidden" ) ?
						[ { index: index, visible: false } ] : [] );

				lockedColumns.push( index );
			}
		} );

		return lockedColumns;
	},

	_restoreLockedColumns: function( lockedColumns ) {
		var index, lockedStatus, input;

		// At this point all columns are visible, so programmatically check/uncheck all the
		// checkboxes that correspond to columns that were previously unlocked so as to ensure that
		// the unlocked status is restored
		for ( index = lockedColumns.length - 1 ; index > -1 ; index-- ) {
			lockedStatus = lockedColumns[ index ];
			input = this.headers.eq( lockedStatus.index ).jqmData( "input" );

			if ( input ) {
				input
					.prop( "checked", lockedStatus.visible )
					.checkboxradio( "refresh" )
					.trigger( "change" );
			}
		}
	},

	_destroy: function() {
		if ( this.options.mode === "columntoggle" && this.options.columnUi ) {
			this._removeColumnUi( this.options.enhanced );
		}
		return this._superApply( arguments );
	}
} );

} );

/*!
 * jQuery Mobile Reflow Table @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Table: reflow
//>>group: Widgets
//>>description: Extends the table widget to reflow on narrower screens
//>>docs: http://api.jquerymobile.com/table/
//>>demos: http://demos.jquerymobile.com/@VERSION/table-reflow/
//>>css.structure: ../css/structure/jquery.mobile.table.reflow.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/table.reflow',[
			"jquery",
			"./table" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.widget( "mobile.table", $.mobile.table, {
	options: {
		mode: "reflow",
		classes: {
			"ui-table-reflow": "",
			"ui-table-cell-label": "",
			"ui-table-cell-label-top": ""
		}
	},

	_create: function() {
		if ( this.options.mode === "reflow" && !this.options.enhanced ) {
			this._addClass( "ui-table-reflow" );
		}

		return this._superApply( arguments );
	},

	_refreshHeaderCell: function( cellIndex, element, columnCount ) {
		element.setAttribute( "data-" + $.mobile.ns + "colstart", columnCount + 1 );
		return this._superApply( arguments );
	},

	refresh: function() {
		this._superApply( arguments );
		if ( this.options.mode === "reflow" ) {

			// After the refresh completes, we need to iterate over the headers again, but this
			// time in reverse order so that top-level headers are visited last. This causes <b>
			// labels to be added in the correct order using a simple .prepend().
			$( this.allHeaders.get().reverse() ).each( $.proxy( this, "_updateCellsFromHeader" ) );
		}
	},

	_updateCellsFromHeader: function( index, headerCell ) {
		var iteration, cells, colstart, labelClasses,
			header = $( headerCell ),
			contents = header.clone().contents();

		if ( contents.length > 0  ) {
			labelClasses = "ui-table-cell-label";
			cells = header.jqmData( "cells" );
			colstart = $.mobile.getAttribute( headerCell, "colstart" );

			if ( cells.not( headerCell ).filter( "thead th" ).length > 0 ) {
				labelClasses = labelClasses + ( " " + "ui-table-cell-label-top" );
				iteration = parseInt( headerCell.getAttribute( "colspan" ), 10 );

				if ( iteration ) {
					cells = cells.filter( "td:nth-child(" + iteration + "n + " + colstart + ")" );
				}
			}

			this._addLabels( cells, labelClasses, contents );
		}
	},

	_addLabels: function( cells, labelClasses, contents ) {
		var b = $( "<b>" );
		if ( contents.length === 1 && contents[ 0 ].nodeName.toLowerCase() === "abbr" ) {
			contents = contents.eq( 0 ).attr( "title" );
		}

		// .not fixes #6006
		this._addClass( b, labelClasses );
		b.append( contents );
		cells
			.not( ":has(b." + labelClasses.split( " " ).join( "." ) + ")" )
				.prepend( b );
	},

	_destroy: function() {
		var colstartAttr;

		if ( this.options.mode === "reflow" ) {
			colstartAttr = "data-" + $.mobile.ns + "colstart";

			if ( !this.options.enhanced ) {
				this.element
					.children( "thead" )
						.find( "[" + colstartAttr + "]" )
							.removeAttr( colstartAttr )
						.end()
					.end()
					.children( "tbody" )
						.find( "b.ui-table-cell-label"  )
							.remove();
			}
		}

		return this._superApply( arguments );
	}
} );

} );

/*!
 * jQuery Mobile Filterable @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Filterable
//>>group: Widgets
//>>description: Renders the children of an element filterable via a callback and a textinput
//>>docs: http://api.jquerymobile.com/filterable/
//>>demos: http://demos.jquerymobile.com/@VERSION/filterable/
//>>css.structure: ../css/structure/jquery.mobile.filterable.css
//>>css.theme: ../css/themes/default/jquery.mobile.theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/filterable',[
			"jquery",
			"../widget" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

// TODO rename filterCallback/deprecate and default to the item itself as the first argument
var defaultFilterCallback = function( index, searchValue ) {
	var element,
		text = $.mobile.getAttribute( this, "filtertext" );

	if ( !text ) {
		element = $( this );
		text = element.text();

		if ( !text ) {
			text = element.val() || "";
		}
	}

	return ( ( "" + text )
			.toLowerCase().indexOf( searchValue ) === -1 );
};

return $.widget( "mobile.filterable", {
	version: "@VERSION",

	initSelector: ":jqmData(filter='true')",

	options: {
		filterReveal: false,
		filterCallback: defaultFilterCallback,
		enhanced: false,
		input: null,
		children: "> li, > option, > optgroup option, > tbody tr, > .ui-controlgroup > .ui-btn, " +
			"> .ui-controlgroup > .ui-checkbox, > .ui-controlgroup > .ui-radio"
	},

	_create: function() {
		var opts = this.options;

		$.extend( this, {
			_search: null,
			_timer: 0
		} );

		this._setInput( opts.input );
		if ( !opts.enhanced ) {
			this._filterItems( ( ( this._search && this._search.val() ) || "" ).toLowerCase() );
		}
	},

	_onKeyUp: function() {
		var val, lastval,
			search = this._search;

		if ( search ) {
			val = search.val().toLowerCase(),
			lastval = $.mobile.getAttribute( search[ 0 ], "lastval" ) + "";

			if ( lastval && lastval === val ) {
				// Execute the handler only once per value change
				return;
			}

			if ( this._timer ) {
				window.clearTimeout( this._timer );
				this._timer = 0;
			}

			this._timer = this._delay( function() {
				if ( this._trigger( "beforefilter", null, { input: search } ) === false ) {
					return false;
				}

				// Change val as lastval for next execution
				search[ 0 ].setAttribute( "data-" + $.mobile.ns + "lastval", val );

				this._filterItems( val );
				this._timer = 0;
			}, 250 );
		}
	},

	_getFilterableItems: function() {
		var elem = this.element,
			children = this.options.children,
			items = !children ? { length: 0 } :
				$.isFunction( children ) ? children() :
					children.nodeName ? $( children ) :
						children.jquery ? children :
							this.element.find( children );

		if ( items.length === 0 ) {
			items = elem.children();
		}

		return items;
	},

	_filterItems: function( val ) {
		var idx, callback, length, dst,
			show = [],
			hide = [],
			opts = this.options,
			filterItems = this._getFilterableItems();

		if ( val != null ) {
			callback = opts.filterCallback || defaultFilterCallback;
			length = filterItems.length;

			// Partition the items into those to be hidden and those to be shown
			for ( idx = 0; idx < length; idx++ ) {
				dst = ( callback.call( filterItems[ idx ], idx, val ) ) ? hide : show;
				dst.push( filterItems[ idx ] );
			}
		}

		// If nothing is hidden, then the decision whether to hide or show the items
		// is based on the "filterReveal" option.
		if ( hide.length === 0 ) {
			filterItems[ ( opts.filterReveal && val.length === 0 ) ?
				"addClass" : "removeClass" ]( "ui-screen-hidden" );
		} else {
			$( hide ).addClass( "ui-screen-hidden" );
			$( show ).removeClass( "ui-screen-hidden" );
		}

		this._refreshChildWidget();

		this._trigger( "filter", null, {
			items: filterItems
		} );
	},

	// The Default implementation of _refreshChildWidget attempts to call
	// refresh on collapsibleset, controlgroup, selectmenu, or listview
	_refreshChildWidget: function() {
		var widget, idx,
			recognizedWidgets = [ "collapsibleset", "selectmenu", "controlgroup", "listview" ];

		for ( idx = recognizedWidgets.length - 1; idx > -1; idx-- ) {
			widget = recognizedWidgets[ idx ];
			if ( $.mobile[ widget ] ) {
				widget = this.element.data( "mobile-" + widget );
				if ( widget && $.isFunction( widget.refresh ) ) {
					widget.refresh();
				}
			}
		}
	},

	// TODO: When the input is not internal, do not even store it in this._search
	_setInput: function( selector ) {
		var search = this._search;

		// Stop a pending filter operation
		if ( this._timer ) {
			window.clearTimeout( this._timer );
			this._timer = 0;
		}

		if ( search ) {
			this._off( search, "keyup keydown keypress change input" );
			search = null;
		}

		if ( selector ) {
			search = selector.jquery ? selector :
				selector.nodeName ? $( selector ) :
					this.document.find( selector );

			this._on( search, {
				keydown: "_onKeyDown",
				keypress: "_onKeyPress",
				keyup: "_onKeyUp",
				change: "_onKeyUp",
				input: "_onKeyUp"
			} );
		}

		this._search = search;
	},

	// Prevent form submission
	_onKeyDown: function( event ) {
		this._preventKeyPress = false;
		if ( event.keyCode === $.ui.keyCode.ENTER ) {
			event.preventDefault();
			this._preventKeyPress = true;
		}
	},

	_onKeyPress: function( event ) {
		if ( this._preventKeyPress ) {
			event.preventDefault();
			this._preventKeyPress = false;
		}
	},

	_setOptions: function( options ) {
		var refilter = !( ( options.filterReveal === undefined ) &&
		( options.filterCallback === undefined ) &&
		( options.children === undefined ) );

		this._super( options );

		if ( options.input !== undefined ) {
			this._setInput( options.input );
			refilter = true;
		}

		if ( refilter ) {
			this.refresh();
		}
	},

	_destroy: function() {
		var opts = this.options,
			items = this._getFilterableItems();

		if ( opts.enhanced ) {
			items.toggleClass( "ui-screen-hidden", opts.filterReveal );
		} else {
			items.removeClass( "ui-screen-hidden" );
		}
	},

	refresh: function() {
		if ( this._timer ) {
			window.clearTimeout( this._timer );
			this._timer = 0;
		}
		this._filterItems( ( ( this._search && this._search.val() ) || "" ).toLowerCase() );
	}
} );

} );

/*!
 * jQuery UI Tabs 1.12.1
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Tabs
//>>group: Widgets
//>>description: Transforms a set of container elements into a tab structure.
//>>docs: http://api.jqueryui.com/tabs/
//>>demos: http://jqueryui.com/tabs/
//>>css.structure: ../../themes/base/core.css
//>>css.structure: ../../themes/base/tabs.css
//>>css.theme: ../../themes/base/theme.css

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'jquery-ui/widgets/tabs',[
			"jquery",
			"../escape-selector",
			"../keycode",
			"../safe-active-element",
			"../unique-id",
			"../version",
			"../widget"
		], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
}( function( $ ) {

$.widget( "ui.tabs", {
	version: "1.12.1",
	delay: 300,
	options: {
		active: null,
		classes: {
			"ui-tabs": "ui-corner-all",
			"ui-tabs-nav": "ui-corner-all",
			"ui-tabs-panel": "ui-corner-bottom",
			"ui-tabs-tab": "ui-corner-top"
		},
		collapsible: false,
		event: "click",
		heightStyle: "content",
		hide: null,
		show: null,

		// Callbacks
		activate: null,
		beforeActivate: null,
		beforeLoad: null,
		load: null
	},

	_isLocal: ( function() {
		var rhash = /#.*$/;

		return function( anchor ) {
			var anchorUrl, locationUrl;

			anchorUrl = anchor.href.replace( rhash, "" );
			locationUrl = location.href.replace( rhash, "" );

			// Decoding may throw an error if the URL isn't UTF-8 (#9518)
			try {
				anchorUrl = decodeURIComponent( anchorUrl );
			} catch ( error ) {}
			try {
				locationUrl = decodeURIComponent( locationUrl );
			} catch ( error ) {}

			return anchor.hash.length > 1 && anchorUrl === locationUrl;
		};
	} )(),

	_create: function() {
		var that = this,
			options = this.options;

		this.running = false;

		this._addClass( "ui-tabs", "ui-widget ui-widget-content" );
		this._toggleClass( "ui-tabs-collapsible", null, options.collapsible );

		this._processTabs();
		options.active = this._initialActive();

		// Take disabling tabs via class attribute from HTML
		// into account and update option properly.
		if ( $.isArray( options.disabled ) ) {
			options.disabled = $.unique( options.disabled.concat(
				$.map( this.tabs.filter( ".ui-state-disabled" ), function( li ) {
					return that.tabs.index( li );
				} )
			) ).sort();
		}

		// Check for length avoids error when initializing empty list
		if ( this.options.active !== false && this.anchors.length ) {
			this.active = this._findActive( options.active );
		} else {
			this.active = $();
		}

		this._refresh();

		if ( this.active.length ) {
			this.load( options.active );
		}
	},

	_initialActive: function() {
		var active = this.options.active,
			collapsible = this.options.collapsible,
			locationHash = location.hash.substring( 1 );

		if ( active === null ) {

			// check the fragment identifier in the URL
			if ( locationHash ) {
				this.tabs.each( function( i, tab ) {
					if ( $( tab ).attr( "aria-controls" ) === locationHash ) {
						active = i;
						return false;
					}
				} );
			}

			// Check for a tab marked active via a class
			if ( active === null ) {
				active = this.tabs.index( this.tabs.filter( ".ui-tabs-active" ) );
			}

			// No active tab, set to false
			if ( active === null || active === -1 ) {
				active = this.tabs.length ? 0 : false;
			}
		}

		// Handle numbers: negative, out of range
		if ( active !== false ) {
			active = this.tabs.index( this.tabs.eq( active ) );
			if ( active === -1 ) {
				active = collapsible ? false : 0;
			}
		}

		// Don't allow collapsible: false and active: false
		if ( !collapsible && active === false && this.anchors.length ) {
			active = 0;
		}

		return active;
	},

	_getCreateEventData: function() {
		return {
			tab: this.active,
			panel: !this.active.length ? $() : this._getPanelForTab( this.active )
		};
	},

	_tabKeydown: function( event ) {
		var focusedTab = $( $.ui.safeActiveElement( this.document[ 0 ] ) ).closest( "li" ),
			selectedIndex = this.tabs.index( focusedTab ),
			goingForward = true;

		if ( this._handlePageNav( event ) ) {
			return;
		}

		switch ( event.keyCode ) {
		case $.ui.keyCode.RIGHT:
		case $.ui.keyCode.DOWN:
			selectedIndex++;
			break;
		case $.ui.keyCode.UP:
		case $.ui.keyCode.LEFT:
			goingForward = false;
			selectedIndex--;
			break;
		case $.ui.keyCode.END:
			selectedIndex = this.anchors.length - 1;
			break;
		case $.ui.keyCode.HOME:
			selectedIndex = 0;
			break;
		case $.ui.keyCode.SPACE:

			// Activate only, no collapsing
			event.preventDefault();
			clearTimeout( this.activating );
			this._activate( selectedIndex );
			return;
		case $.ui.keyCode.ENTER:

			// Toggle (cancel delayed activation, allow collapsing)
			event.preventDefault();
			clearTimeout( this.activating );

			// Determine if we should collapse or activate
			this._activate( selectedIndex === this.options.active ? false : selectedIndex );
			return;
		default:
			return;
		}

		// Focus the appropriate tab, based on which key was pressed
		event.preventDefault();
		clearTimeout( this.activating );
		selectedIndex = this._focusNextTab( selectedIndex, goingForward );

		// Navigating with control/command key will prevent automatic activation
		if ( !event.ctrlKey && !event.metaKey ) {

			// Update aria-selected immediately so that AT think the tab is already selected.
			// Otherwise AT may confuse the user by stating that they need to activate the tab,
			// but the tab will already be activated by the time the announcement finishes.
			focusedTab.attr( "aria-selected", "false" );
			this.tabs.eq( selectedIndex ).attr( "aria-selected", "true" );

			this.activating = this._delay( function() {
				this.option( "active", selectedIndex );
			}, this.delay );
		}
	},

	_panelKeydown: function( event ) {
		if ( this._handlePageNav( event ) ) {
			return;
		}

		// Ctrl+up moves focus to the current tab
		if ( event.ctrlKey && event.keyCode === $.ui.keyCode.UP ) {
			event.preventDefault();
			this.active.trigger( "focus" );
		}
	},

	// Alt+page up/down moves focus to the previous/next tab (and activates)
	_handlePageNav: function( event ) {
		if ( event.altKey && event.keyCode === $.ui.keyCode.PAGE_UP ) {
			this._activate( this._focusNextTab( this.options.active - 1, false ) );
			return true;
		}
		if ( event.altKey && event.keyCode === $.ui.keyCode.PAGE_DOWN ) {
			this._activate( this._focusNextTab( this.options.active + 1, true ) );
			return true;
		}
	},

	_findNextTab: function( index, goingForward ) {
		var lastTabIndex = this.tabs.length - 1;

		function constrain() {
			if ( index > lastTabIndex ) {
				index = 0;
			}
			if ( index < 0 ) {
				index = lastTabIndex;
			}
			return index;
		}

		while ( $.inArray( constrain(), this.options.disabled ) !== -1 ) {
			index = goingForward ? index + 1 : index - 1;
		}

		return index;
	},

	_focusNextTab: function( index, goingForward ) {
		index = this._findNextTab( index, goingForward );
		this.tabs.eq( index ).trigger( "focus" );
		return index;
	},

	_setOption: function( key, value ) {
		if ( key === "active" ) {

			// _activate() will handle invalid values and update this.options
			this._activate( value );
			return;
		}

		this._super( key, value );

		if ( key === "collapsible" ) {
			this._toggleClass( "ui-tabs-collapsible", null, value );

			// Setting collapsible: false while collapsed; open first panel
			if ( !value && this.options.active === false ) {
				this._activate( 0 );
			}
		}

		if ( key === "event" ) {
			this._setupEvents( value );
		}

		if ( key === "heightStyle" ) {
			this._setupHeightStyle( value );
		}
	},

	_sanitizeSelector: function( hash ) {
		return hash ? hash.replace( /[!"$%&'()*+,.\/:;<=>?@\[\]\^`{|}~]/g, "\\$&" ) : "";
	},

	refresh: function() {
		var options = this.options,
			lis = this.tablist.children( ":has(a[href])" );

		// Get disabled tabs from class attribute from HTML
		// this will get converted to a boolean if needed in _refresh()
		options.disabled = $.map( lis.filter( ".ui-state-disabled" ), function( tab ) {
			return lis.index( tab );
		} );

		this._processTabs();

		// Was collapsed or no tabs
		if ( options.active === false || !this.anchors.length ) {
			options.active = false;
			this.active = $();

		// was active, but active tab is gone
		} else if ( this.active.length && !$.contains( this.tablist[ 0 ], this.active[ 0 ] ) ) {

			// all remaining tabs are disabled
			if ( this.tabs.length === options.disabled.length ) {
				options.active = false;
				this.active = $();

			// activate previous tab
			} else {
				this._activate( this._findNextTab( Math.max( 0, options.active - 1 ), false ) );
			}

		// was active, active tab still exists
		} else {

			// make sure active index is correct
			options.active = this.tabs.index( this.active );
		}

		this._refresh();
	},

	_refresh: function() {
		this._setOptionDisabled( this.options.disabled );
		this._setupEvents( this.options.event );
		this._setupHeightStyle( this.options.heightStyle );

		this.tabs.not( this.active ).attr( {
			"aria-selected": "false",
			"aria-expanded": "false",
			tabIndex: -1
		} );
		this.panels.not( this._getPanelForTab( this.active ) )
			.hide()
			.attr( {
				"aria-hidden": "true"
			} );

		// Make sure one tab is in the tab order
		if ( !this.active.length ) {
			this.tabs.eq( 0 ).attr( "tabIndex", 0 );
		} else {
			this.active
				.attr( {
					"aria-selected": "true",
					"aria-expanded": "true",
					tabIndex: 0
				} );
			this._addClass( this.active, "ui-tabs-active", "ui-state-active" );
			this._getPanelForTab( this.active )
				.show()
				.attr( {
					"aria-hidden": "false"
				} );
		}
	},

	_processTabs: function() {
		var that = this,
			prevTabs = this.tabs,
			prevAnchors = this.anchors,
			prevPanels = this.panels;

		this.tablist = this._getList().attr( "role", "tablist" );
		this._addClass( this.tablist, "ui-tabs-nav",
			"ui-helper-reset ui-helper-clearfix ui-widget-header" );

		// Prevent users from focusing disabled tabs via click
		this.tablist
			.on( "mousedown" + this.eventNamespace, "> li", function( event ) {
				if ( $( this ).is( ".ui-state-disabled" ) ) {
					event.preventDefault();
				}
			} )

			// Support: IE <9
			// Preventing the default action in mousedown doesn't prevent IE
			// from focusing the element, so if the anchor gets focused, blur.
			// We don't have to worry about focusing the previously focused
			// element since clicking on a non-focusable element should focus
			// the body anyway.
			.on( "focus" + this.eventNamespace, ".ui-tabs-anchor", function() {
				if ( $( this ).closest( "li" ).is( ".ui-state-disabled" ) ) {
					this.blur();
				}
			} );

		this.tabs = this.tablist.find( "> li:has(a[href])" )
			.attr( {
				role: "tab",
				tabIndex: -1
			} );
		this._addClass( this.tabs, "ui-tabs-tab", "ui-state-default" );

		this.anchors = this.tabs.map( function() {
			return $( "a", this )[ 0 ];
		} )
			.attr( {
				role: "presentation",
				tabIndex: -1
			} );
		this._addClass( this.anchors, "ui-tabs-anchor" );

		this.panels = $();

		this.anchors.each( function( i, anchor ) {
			var selector, panel, panelId,
				anchorId = $( anchor ).uniqueId().attr( "id" ),
				tab = $( anchor ).closest( "li" ),
				originalAriaControls = tab.attr( "aria-controls" );

			// Inline tab
			if ( that._isLocal( anchor ) ) {
				selector = anchor.hash;
				panelId = selector.substring( 1 );
				panel = that.element.find( that._sanitizeSelector( selector ) );

			// remote tab
			} else {

				// If the tab doesn't already have aria-controls,
				// generate an id by using a throw-away element
				panelId = tab.attr( "aria-controls" ) || $( {} ).uniqueId()[ 0 ].id;
				selector = "#" + panelId;
				panel = that.element.find( selector );
				if ( !panel.length ) {
					panel = that._createPanel( panelId );
					panel.insertAfter( that.panels[ i - 1 ] || that.tablist );
				}
				panel.attr( "aria-live", "polite" );
			}

			if ( panel.length ) {
				that.panels = that.panels.add( panel );
			}
			if ( originalAriaControls ) {
				tab.data( "ui-tabs-aria-controls", originalAriaControls );
			}
			tab.attr( {
				"aria-controls": panelId,
				"aria-labelledby": anchorId
			} );
			panel.attr( "aria-labelledby", anchorId );
		} );

		this.panels.attr( "role", "tabpanel" );
		this._addClass( this.panels, "ui-tabs-panel", "ui-widget-content" );

		// Avoid memory leaks (#10056)
		if ( prevTabs ) {
			this._off( prevTabs.not( this.tabs ) );
			this._off( prevAnchors.not( this.anchors ) );
			this._off( prevPanels.not( this.panels ) );
		}
	},

	// Allow overriding how to find the list for rare usage scenarios (#7715)
	_getList: function() {
		return this.tablist || this.element.find( "ol, ul" ).eq( 0 );
	},

	_createPanel: function( id ) {
		return $( "<div>" )
			.attr( "id", id )
			.data( "ui-tabs-destroy", true );
	},

	_setOptionDisabled: function( disabled ) {
		var currentItem, li, i;

		if ( $.isArray( disabled ) ) {
			if ( !disabled.length ) {
				disabled = false;
			} else if ( disabled.length === this.anchors.length ) {
				disabled = true;
			}
		}

		// Disable tabs
		for ( i = 0; ( li = this.tabs[ i ] ); i++ ) {
			currentItem = $( li );
			if ( disabled === true || $.inArray( i, disabled ) !== -1 ) {
				currentItem.attr( "aria-disabled", "true" );
				this._addClass( currentItem, null, "ui-state-disabled" );
			} else {
				currentItem.removeAttr( "aria-disabled" );
				this._removeClass( currentItem, null, "ui-state-disabled" );
			}
		}

		this.options.disabled = disabled;

		this._toggleClass( this.widget(), this.widgetFullName + "-disabled", null,
			disabled === true );
	},

	_setupEvents: function( event ) {
		var events = {};
		if ( event ) {
			$.each( event.split( " " ), function( index, eventName ) {
				events[ eventName ] = "_eventHandler";
			} );
		}

		this._off( this.anchors.add( this.tabs ).add( this.panels ) );

		// Always prevent the default action, even when disabled
		this._on( true, this.anchors, {
			click: function( event ) {
				event.preventDefault();
			}
		} );
		this._on( this.anchors, events );
		this._on( this.tabs, { keydown: "_tabKeydown" } );
		this._on( this.panels, { keydown: "_panelKeydown" } );

		this._focusable( this.tabs );
		this._hoverable( this.tabs );
	},

	_setupHeightStyle: function( heightStyle ) {
		var maxHeight,
			parent = this.element.parent();

		if ( heightStyle === "fill" ) {
			maxHeight = parent.height();
			maxHeight -= this.element.outerHeight() - this.element.height();

			this.element.siblings( ":visible" ).each( function() {
				var elem = $( this ),
					position = elem.css( "position" );

				if ( position === "absolute" || position === "fixed" ) {
					return;
				}
				maxHeight -= elem.outerHeight( true );
			} );

			this.element.children().not( this.panels ).each( function() {
				maxHeight -= $( this ).outerHeight( true );
			} );

			this.panels.each( function() {
				$( this ).height( Math.max( 0, maxHeight -
					$( this ).innerHeight() + $( this ).height() ) );
			} )
				.css( "overflow", "auto" );
		} else if ( heightStyle === "auto" ) {
			maxHeight = 0;
			this.panels.each( function() {
				maxHeight = Math.max( maxHeight, $( this ).height( "" ).height() );
			} ).height( maxHeight );
		}
	},

	_eventHandler: function( event ) {
		var options = this.options,
			active = this.active,
			anchor = $( event.currentTarget ),
			tab = anchor.closest( "li" ),
			clickedIsActive = tab[ 0 ] === active[ 0 ],
			collapsing = clickedIsActive && options.collapsible,
			toShow = collapsing ? $() : this._getPanelForTab( tab ),
			toHide = !active.length ? $() : this._getPanelForTab( active ),
			eventData = {
				oldTab: active,
				oldPanel: toHide,
				newTab: collapsing ? $() : tab,
				newPanel: toShow
			};

		event.preventDefault();

		if ( tab.hasClass( "ui-state-disabled" ) ||

				// tab is already loading
				tab.hasClass( "ui-tabs-loading" ) ||

				// can't switch durning an animation
				this.running ||

				// click on active header, but not collapsible
				( clickedIsActive && !options.collapsible ) ||

				// allow canceling activation
				( this._trigger( "beforeActivate", event, eventData ) === false ) ) {
			return;
		}

		options.active = collapsing ? false : this.tabs.index( tab );

		this.active = clickedIsActive ? $() : tab;
		if ( this.xhr ) {
			this.xhr.abort();
		}

		if ( !toHide.length && !toShow.length ) {
			$.error( "jQuery UI Tabs: Mismatching fragment identifier." );
		}

		if ( toShow.length ) {
			this.load( this.tabs.index( tab ), event );
		}
		this._toggle( event, eventData );
	},

	// Handles show/hide for selecting tabs
	_toggle: function( event, eventData ) {
		var that = this,
			toShow = eventData.newPanel,
			toHide = eventData.oldPanel;

		this.running = true;

		function complete() {
			that.running = false;
			that._trigger( "activate", event, eventData );
		}

		function show() {
			that._addClass( eventData.newTab.closest( "li" ), "ui-tabs-active", "ui-state-active" );

			if ( toShow.length && that.options.show ) {
				that._show( toShow, that.options.show, complete );
			} else {
				toShow.show();
				complete();
			}
		}

		// Start out by hiding, then showing, then completing
		if ( toHide.length && this.options.hide ) {
			this._hide( toHide, this.options.hide, function() {
				that._removeClass( eventData.oldTab.closest( "li" ),
					"ui-tabs-active", "ui-state-active" );
				show();
			} );
		} else {
			this._removeClass( eventData.oldTab.closest( "li" ),
				"ui-tabs-active", "ui-state-active" );
			toHide.hide();
			show();
		}

		toHide.attr( "aria-hidden", "true" );
		eventData.oldTab.attr( {
			"aria-selected": "false",
			"aria-expanded": "false"
		} );

		// If we're switching tabs, remove the old tab from the tab order.
		// If we're opening from collapsed state, remove the previous tab from the tab order.
		// If we're collapsing, then keep the collapsing tab in the tab order.
		if ( toShow.length && toHide.length ) {
			eventData.oldTab.attr( "tabIndex", -1 );
		} else if ( toShow.length ) {
			this.tabs.filter( function() {
				return $( this ).attr( "tabIndex" ) === 0;
			} )
				.attr( "tabIndex", -1 );
		}

		toShow.attr( "aria-hidden", "false" );
		eventData.newTab.attr( {
			"aria-selected": "true",
			"aria-expanded": "true",
			tabIndex: 0
		} );
	},

	_activate: function( index ) {
		var anchor,
			active = this._findActive( index );

		// Trying to activate the already active panel
		if ( active[ 0 ] === this.active[ 0 ] ) {
			return;
		}

		// Trying to collapse, simulate a click on the current active header
		if ( !active.length ) {
			active = this.active;
		}

		anchor = active.find( ".ui-tabs-anchor" )[ 0 ];
		this._eventHandler( {
			target: anchor,
			currentTarget: anchor,
			preventDefault: $.noop
		} );
	},

	_findActive: function( index ) {
		return index === false ? $() : this.tabs.eq( index );
	},

	_getIndex: function( index ) {

		// meta-function to give users option to provide a href string instead of a numerical index.
		if ( typeof index === "string" ) {
			index = this.anchors.index( this.anchors.filter( "[href$='" +
				$.ui.escapeSelector( index ) + "']" ) );
		}

		return index;
	},

	_destroy: function() {
		if ( this.xhr ) {
			this.xhr.abort();
		}

		this.tablist
			.removeAttr( "role" )
			.off( this.eventNamespace );

		this.anchors
			.removeAttr( "role tabIndex" )
			.removeUniqueId();

		this.tabs.add( this.panels ).each( function() {
			if ( $.data( this, "ui-tabs-destroy" ) ) {
				$( this ).remove();
			} else {
				$( this ).removeAttr( "role tabIndex " +
					"aria-live aria-busy aria-selected aria-labelledby aria-hidden aria-expanded" );
			}
		} );

		this.tabs.each( function() {
			var li = $( this ),
				prev = li.data( "ui-tabs-aria-controls" );
			if ( prev ) {
				li
					.attr( "aria-controls", prev )
					.removeData( "ui-tabs-aria-controls" );
			} else {
				li.removeAttr( "aria-controls" );
			}
		} );

		this.panels.show();

		if ( this.options.heightStyle !== "content" ) {
			this.panels.css( "height", "" );
		}
	},

	enable: function( index ) {
		var disabled = this.options.disabled;
		if ( disabled === false ) {
			return;
		}

		if ( index === undefined ) {
			disabled = false;
		} else {
			index = this._getIndex( index );
			if ( $.isArray( disabled ) ) {
				disabled = $.map( disabled, function( num ) {
					return num !== index ? num : null;
				} );
			} else {
				disabled = $.map( this.tabs, function( li, num ) {
					return num !== index ? num : null;
				} );
			}
		}
		this._setOptionDisabled( disabled );
	},

	disable: function( index ) {
		var disabled = this.options.disabled;
		if ( disabled === true ) {
			return;
		}

		if ( index === undefined ) {
			disabled = true;
		} else {
			index = this._getIndex( index );
			if ( $.inArray( index, disabled ) !== -1 ) {
				return;
			}
			if ( $.isArray( disabled ) ) {
				disabled = $.merge( [ index ], disabled ).sort();
			} else {
				disabled = [ index ];
			}
		}
		this._setOptionDisabled( disabled );
	},

	load: function( index, event ) {
		index = this._getIndex( index );
		var that = this,
			tab = this.tabs.eq( index ),
			anchor = tab.find( ".ui-tabs-anchor" ),
			panel = this._getPanelForTab( tab ),
			eventData = {
				tab: tab,
				panel: panel
			},
			complete = function( jqXHR, status ) {
				if ( status === "abort" ) {
					that.panels.stop( false, true );
				}

				that._removeClass( tab, "ui-tabs-loading" );
				panel.removeAttr( "aria-busy" );

				if ( jqXHR === that.xhr ) {
					delete that.xhr;
				}
			};

		// Not remote
		if ( this._isLocal( anchor[ 0 ] ) ) {
			return;
		}

		this.xhr = $.ajax( this._ajaxSettings( anchor, event, eventData ) );

		// Support: jQuery <1.8
		// jQuery <1.8 returns false if the request is canceled in beforeSend,
		// but as of 1.8, $.ajax() always returns a jqXHR object.
		if ( this.xhr && this.xhr.statusText !== "canceled" ) {
			this._addClass( tab, "ui-tabs-loading" );
			panel.attr( "aria-busy", "true" );

			this.xhr
				.done( function( response, status, jqXHR ) {

					// support: jQuery <1.8
					// http://bugs.jquery.com/ticket/11778
					setTimeout( function() {
						panel.html( response );
						that._trigger( "load", event, eventData );

						complete( jqXHR, status );
					}, 1 );
				} )
				.fail( function( jqXHR, status ) {

					// support: jQuery <1.8
					// http://bugs.jquery.com/ticket/11778
					setTimeout( function() {
						complete( jqXHR, status );
					}, 1 );
				} );
		}
	},

	_ajaxSettings: function( anchor, event, eventData ) {
		var that = this;
		return {

			// Support: IE <11 only
			// Strip any hash that exists to prevent errors with the Ajax request
			url: anchor.attr( "href" ).replace( /#.*$/, "" ),
			beforeSend: function( jqXHR, settings ) {
				return that._trigger( "beforeLoad", event,
					$.extend( { jqXHR: jqXHR, ajaxSettings: settings }, eventData ) );
			}
		};
	},

	_getPanelForTab: function( tab ) {
		var id = $( tab ).attr( "aria-controls" );
		return this.element.find( this._sanitizeSelector( "#" + id ) );
	}
} );

// DEPRECATED
// TODO: Switch return back to widget declaration at top of file when this is removed
if ( $.uiBackCompat !== false ) {

	// Backcompat for ui-tab class (now ui-tabs-tab)
	$.widget( "ui.tabs", $.ui.tabs, {
		_processTabs: function() {
			this._superApply( arguments );
			this._addClass( this.tabs, "ui-tab" );
		}
	} );
}

return $.ui.tabs;

} ) );

/*!
 * jQuery Mobile Tabs Ajax Handling @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Tabs
//>>group: Widgets
//>>description: Extension to make Tabs widget aware of jQuery Mobile's navigation
//>>docs: http://api.jquerymobile.com/tabs/
//>>demos: http://demos.jquerymobile.com/@VERSION/tabs/

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'widgets/tabs.ajax',[
			"jquery",
			"../defaults",
			"../navigation/path",
			"../navigation/base",
			"jquery-ui/widgets/tabs" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

return $.widget( "ui.tabs", $.ui.tabs, {

	_create: function() {
		this._super();

		this.active.find( "a.ui-tabs-anchor" ).addClass( "ui-button-active" );
	},
	_isLocal: function( anchor ) {
		var path, baseUrl, absUrl;

		if ( $.mobile.ajaxEnabled ) {
			path = $.mobile.path;
			baseUrl = path.parseUrl( $.mobile.base.element().attr( "href" ) );
			absUrl = path.parseUrl( path.makeUrlAbsolute( anchor.getAttribute( "href" ),
				baseUrl ) );

			return ( path.isSameDomain( absUrl.href, baseUrl.href ) &&
				absUrl.pathname === baseUrl.pathname );
		}

		return this._superApply( arguments );
	}
} );

} );

/*!
 * jQuery Mobile iOS Orientation Fix @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: iOS Orientation Change Fix
//>>group: Utilities
//>>description: Fixes the orientation change bug in iOS when switching between landscape and portrait

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'zoom/iosorientationfix',[
			"jquery",
			"../core",
			"../zoom" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

$.mobile.iosorientationfixEnabled = true;

// This fix addresses an iOS bug, so return early if the UA claims it's something else.
var ua = navigator.userAgent,
	zoom,
	evt, x, y, z, aig;
if ( !( /iPhone|iPad|iPod/.test( navigator.platform ) && /OS [1-5]_[0-9_]* like Mac OS X/i.test( ua ) && ua.indexOf( "AppleWebKit" ) > -1 ) ) {
	$.mobile.iosorientationfixEnabled = false;
	return;
}

zoom = $.mobile.zoom;

function checkTilt( e ) {
	evt = e.originalEvent;
	aig = evt.accelerationIncludingGravity;

	x = Math.abs( aig.x );
	y = Math.abs( aig.y );
	z = Math.abs( aig.z );

	// If portrait orientation and in one of the danger zones
	if ( !window.orientation && ( x > 7 || ( ( z > 6 && y < 8 || z < 8 && y > 6 ) && x > 5 ) ) ) {
		if ( zoom.enabled ) {
			zoom.disable();
		}
	} else if ( !zoom.enabled ) {
		zoom.enable();
	}
}

$.mobile.document.on( "mobileinit", function() {
	if ( $.mobile.iosorientationfixEnabled ) {
		$.mobile.window
			.bind( "orientationchange.iosorientationfix", zoom.enable )
			.bind( "devicemotion.iosorientationfix", checkTilt );
	}
} );

} );

/*!
 * jQuery Mobile Init @VERSION
 * http://jquerymobile.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 */

//>>label: Init
//>>group: Core
//>>description: Global initialization of the library.

( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( 'init',[
			"jquery",
			"./defaults",
			"./helpers",
			"./data",
			"./support",
			"./widgets/enhancer",
			"./events/navigate",
			"./navigation/path",
			"./navigation/method",
			"./navigation",
			"./widgets/loader",
			"./vmouse" ], factory );
	} else {

		// Browser globals
		factory( jQuery );
	}
} )( function( $ ) {

var $html = $( "html" ),
	$window = $.mobile.window;

//remove initial build class (only present on first pageshow)
function hideRenderingClass() {
	$html.removeClass( "ui-mobile-rendering" );
}

// trigger mobileinit event - useful hook for configuring $.mobile settings before they're used
$.mobile.document.trigger( "mobileinit" );

// support conditions
// if device support condition(s) aren't met, leave things as they are -> a basic, usable experience,
// otherwise, proceed with the enhancements
if ( !$.mobile.gradeA() ) {
	return;
}

// override ajaxEnabled on platforms that have known conflicts with hash history updates
// or generally work better browsing in regular http for full page refreshes (BB5, Opera Mini)
if ( $.mobile.ajaxBlacklist ) {
	$.mobile.ajaxEnabled = false;
}

// Add mobile, initial load "rendering" classes to docEl
$html.addClass( "ui-mobile ui-mobile-rendering" );

// This is a fallback. If anything goes wrong (JS errors, etc), or events don't fire,
// this ensures the rendering class is removed after 5 seconds, so content is visible and accessible
setTimeout( hideRenderingClass, 5000 );

$.extend( $.mobile, {
	// find and enhance the pages in the dom and transition to the first page.
	initializePage: function() {
		// find present pages
		var pagecontainer,
			path = $.mobile.path,
			$pages = $( ":jqmData(role='page'), :jqmData(role='dialog')" ),
			hash = path.stripHash( path.stripQueryParams( path.parseLocation().hash ) ),
			theLocation = $.mobile.path.parseLocation(),
			hashPage = hash ? document.getElementById( hash ) : undefined;

		// if no pages are found, create one with body's inner html
		if ( !$pages.length ) {
			$pages = $( "body" ).wrapInner( "<div data-" + $.mobile.ns + "role='page'></div>" ).children( 0 );
		}

		// add dialogs, set data-url attrs
		$pages.each( function() {
			var $this = $( this );

			// unless the data url is already set set it to the pathname
			if ( !$this[ 0 ].getAttribute( "data-" + $.mobile.ns + "url" ) ) {
				$this.attr( "data-" + $.mobile.ns + "url", $this.attr( "id" ) ||
					path.convertUrlToDataUrl( theLocation.pathname + theLocation.search ) );
			}
		} );

		// define first page in dom case one backs out to the directory root (not always the first page visited, but defined as fallback)
		$.mobile.firstPage = $pages.first();

		// define page container
		pagecontainer = $.mobile.firstPage.parent().pagecontainer();

		// initialize navigation events now, after mobileinit has occurred and the page container
		// has been created but before the rest of the library is alerted to that fact
		$.mobile.navreadyDeferred.resolve();

		// cue page loading message
		$.mobile.loading( "show" );

		//remove initial build class (only present on first pageshow)
		hideRenderingClass();

		// if hashchange listening is disabled, there's no hash deeplink,
		// the hash is not valid (contains more than one # or does not start with #)
		// or there is no page with that hash, change to the first page in the DOM
		// Remember, however, that the hash can also be a path!
		if ( !( $.mobile.hashListeningEnabled &&
				$.mobile.path.isHashValid( location.hash ) &&
				( $( hashPage ).is( ":jqmData(role='page')" ) ||
				$.mobile.path.isPath( hash ) ||
				hash === $.mobile.dialogHashKey ) ) ) {

			// make sure to set initial popstate state if it exists
			// so that navigation back to the initial page works properly
			if ( $.event.special.navigate.isPushStateEnabled() ) {
				$.mobile.navigate.navigator.squash( path.parseLocation().href );
			}

			pagecontainer.pagecontainer( "change", $.mobile.firstPage, {
				transition: "none",
				reverse: true,
				changeUrl: false,
				fromHashChange: true
			} );
		} else {
			// trigger hashchange or navigate to squash and record the correct
			// history entry for an initial hash path
			if ( !$.event.special.navigate.isPushStateEnabled() ) {
				$window.trigger( "hashchange", [ true ] );
			} else {
				// TODO figure out how to simplify this interaction with the initial history entry
				// at the bottom js/navigate/navigate.js
				$.mobile.navigate.history.stack = [];
				$.mobile.navigate( $.mobile.path.isPath( location.hash ) ? location.hash : location.href );
			}
		}
	}
} );

$( function() {
	//Run inlineSVG support test
	$.support.inlineSVG();

	// check which scrollTop value should be used by scrolling to 1 immediately at domready
	// then check what the scroll top is. Android will report 0... others 1
	// note that this initial scroll won't hide the address bar. It's just for the check.

	// hide iOS browser chrome on load if hideUrlBar is true this is to try and do it as soon as possible
	if ( $.mobile.hideUrlBar ) {
		window.scrollTo( 0, 1 );
	}

	// if defaultHomeScroll hasn't been set yet, see if scrollTop is 1
	// it should be 1 in most browsers, but android treats 1 as 0 (for hiding addr bar)
	// so if it's 1, use 0 from now on
	$.mobile.defaultHomeScroll = ( !$.support.scrollTop || $.mobile.window.scrollTop() === 1 ) ? 0 : 1;

	//dom-ready inits
	if ( $.mobile.autoInitializePage ) {
		$.mobile.initializePage();
	}

	if ( !$.support.cssPointerEvents ) {
		// IE and Opera don't support CSS pointer-events: none that we use to disable link-based buttons
		// by adding the 'ui-disabled' class to them. Using a JavaScript workaround for those browser.
		// https://github.com/jquery/jquery-mobile/issues/3558

		// DEPRECATED as of 1.4.0 - remove ui-disabled after 1.4.0 release
		// only ui-state-disabled should be present thereafter
		$.mobile.document.delegate( ".ui-state-disabled,.ui-disabled", "vclick",
			function( e ) {
				e.preventDefault();
				e.stopImmediatePropagation();
			}
		);
	}
} );
} );

}));
