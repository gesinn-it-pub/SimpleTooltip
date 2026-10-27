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

	const SCRIPT_URL = /^\s*javascript:/i;

	/**
	 * Parses the tooltip HTML into an inert fragment and removes anything executable.
	 *
	 * The HTML comes from the server-side parser and is already sanitized there;
	 * this is a second line of defence. A <template> is used so that nothing is
	 * loaded or run while parsing.
	 *
	 * @param {string} html
	 * @return {jQuery}
	 */
	function buildContent( html ) {
		const template = document.createElement( 'template' );
		template.innerHTML = html;
		const $content = $( '<span>' ).append( template.content );

		$content.find( 'script, iframe, object, embed' ).remove();
		$content.find( '*' ).each( ( i, el ) => {
			Array.from( el.attributes ).forEach( ( attr ) => {
				const isHandler = /^on/i.test( attr.name );
				const isScriptUrl = SCRIPT_URL.test( attr.value );
				if ( isHandler || isScriptUrl ) {
					el.removeAttribute( attr.name );
				}
			} );
		} );
		return $content;
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
			$context = $( context ).find( '.simple-tooltip' ).not( '.tooltipstered' );
		} else {
			// eslint-disable-next-line no-jquery/no-global-selector
			$context = $( '.simple-tooltip' ).not( '.tooltipstered' );
		}

		// Iterate each tooltip element, extract the tooltip text from
		// the data-simple-tooltip attribute and set the tooltip text manually
		// Note: function() required here (not arrow) because $(this) refers to each element
		$context.each( function () {
			const $el = $( this );
			// attr() already returns the decoded value; decoding again would
			// turn escaped text into markup
			const $content = buildContent( $el.attr( 'data-simple-tooltip' ) );
			$el.tooltipster( Object.assign( {}, options, { content: $content } ) );
		} );

	};

	// Fires on page load and again whenever new content is added (live preview, AJAX, VisualEditor)
	mw.hook( 'wikipage.content' ).add( ( $content ) => {
		mw.libs.SimpleTooltip.trigger( $content, false );
	} );

	// Fired by PageForms when a new template instance is added to a form
	mw.hook( 'pf.addTemplateInstance' ).add( ( $elements ) => {
		// The instance is a clone and still carries the marker of the original tooltip
		$elements.find( '.simple-tooltip' ).removeClass( 'tooltipstered' );
		mw.libs.SimpleTooltip.trigger( $elements, false );
	} );

}( mw, jQuery ) );
