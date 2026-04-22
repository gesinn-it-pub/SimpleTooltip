/**
 * SimpleTooltip Extension
 *
 * For more info see http://mediawiki.org/wiki/Extension:SimpleTooltip
 *
 * @author  Simon Heimler
 * @param {Object} mw MediaWiki object
 * @param {jQuery} $ jQuery object
 */
( function ( mw, $ ) {

	'use strict';

	/**
	 * Decodes html-encoded string
	 *
	 * @param {string} input
	 * @return {string}
	 */
	function htmlDecode( input ) {
		const e = document.createElement( 'textarea' );
		e.innerHTML = input;
		// handle case of empty input
		return e.childNodes.length === 0 ? '' : e.childNodes[ 0 ].nodeValue;
	}

	/** @type {Object} namespace */
	mw.libs.SimpleTooltip = {};

	/**
	 * Default Tooltip Options
	 *
	 * @see http://iamceege.github.io/tooltipster/
	 */
	mw.libs.SimpleTooltip.defaultOptions = {
		animation: 'fade',
		delay: 0,
		speed: 0,
		maxWidth: 400,
		theme: 'tooltipster-default',
		touchDevices: true,
		trigger: 'hover'
	};

	/**
	 * Triggers the execution of the tooltip Plugin
	 *
	 * @param {jQuery|boolean} context jQuery object (context) or DOM selection, false for global
	 * @param {Object|boolean} customOptions tooltip options to replace defaults, false for defaults
	 */
	mw.libs.SimpleTooltip.trigger = function ( context, customOptions ) {

		let $context;
		const options = customOptions || mw.libs.SimpleTooltip.defaultOptions;

		// If a context is given, search only for tooltips within
		if ( context ) {
			$context = $( context ).find( '.simple-tooltip' );
		} else {
			// eslint-disable-next-line no-jquery/no-global-selector
			$context = $( '.simple-tooltip' );
		}

		// Iterate each tooltip element, extract the tooltip text from
		// the data-simple-tooltip attribute and set the tooltip text manually
		// Note: function() required here (not arrow) because $(this) refers to each element
		$context.each( function () {
			const $el = $( this );
			let text = $el.attr( 'data-simple-tooltip' );
			text = htmlDecode( text );
			const $content = $( '<span>' + text + '</span>' );
			// eslint-disable-next-line no-jquery/variable-pattern
			options.content = $content;
			$el.tooltipster( options );
		} );

	};

	$( () => {

		// Trigger Tooltips on DOM Ready
		// Use no specific context and use no custom options
		mw.libs.SimpleTooltip.trigger( false, false );

		// Uses sf.initializeJSElements Hook triggered when a new form instance is added
		mw.hook( 'pf.addTemplateInstance' ).add( ( $elements ) => {

			$elements.find( '.simple-tooltip' ).each( ( i, el ) => {
				const $el = $( el );
				$el.removeClass( 'tooltipstered' );
			} );

			mw.libs.SimpleTooltip.trigger( $elements, false );

		} );

	} );

}( mw, jQuery ) );
