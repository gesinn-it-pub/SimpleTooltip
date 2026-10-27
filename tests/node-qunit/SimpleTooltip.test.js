'use strict';

QUnit.module( 'SimpleTooltip', ( hooks ) => {

	hooks.afterEach( () => {
		jQuery.fn.tooltipster = function () {
			return this;
		};
	} );

	QUnit.test( 'mw.libs.SimpleTooltip is defined after module load', ( assert ) => {
		assert.ok( mw.libs.SimpleTooltip, 'SimpleTooltip namespace is defined' );
		assert.strictEqual( typeof mw.libs.SimpleTooltip.trigger, 'function', 'trigger is a function' );
	} );

	QUnit.test( 'defaultOptions has expected values', ( assert ) => {
		const opts = mw.libs.SimpleTooltip.defaultOptions;
		assert.strictEqual( opts.animation, 'fade', 'animation is fade' );
		assert.strictEqual( opts.delay, 0, 'delay is 0' );
		assert.strictEqual( opts.speed, 0, 'speed is 0' );
		assert.strictEqual( opts.maxWidth, 400, 'maxWidth is 400' );
		assert.strictEqual( opts.theme, 'tooltipster-default', 'theme is tooltipster-default' );
		assert.strictEqual( opts.touchDevices, true, 'touchDevices is true' );
		assert.strictEqual( opts.trigger, 'hover', 'trigger is hover' );
	} );

	QUnit.test( 'trigger calls tooltipster on tooltip elements within context', ( assert ) => {
		const container = document.createElement( 'div' );
		const span = document.createElement( 'span' );
		span.className = 'simple-tooltip';
		span.setAttribute( 'data-simple-tooltip', 'Test tooltip text' );
		container.appendChild( span );
		document.body.appendChild( container );

		const calledWithOptions = [];
		jQuery.fn.tooltipster = function ( opts ) {
			calledWithOptions.push( opts );
			return this;
		};

		mw.libs.SimpleTooltip.trigger( jQuery( container ), false );

		assert.strictEqual( calledWithOptions.length, 1, 'tooltipster called once per tooltip element' );
		assert.ok( calledWithOptions[ 0 ].content, 'options.content is set from data-simple-tooltip' );

		document.body.removeChild( container );
	} );

	QUnit.test( 'trigger does not call tooltipster when context has no tooltip elements', ( assert ) => {
		const container = document.createElement( 'div' );
		document.body.appendChild( container );

		let called = false;
		jQuery.fn.tooltipster = function () {
			called = true;
			return this;
		};

		mw.libs.SimpleTooltip.trigger( jQuery( container ), false );

		assert.false( called, 'tooltipster was not called' );

		document.body.removeChild( container );
	} );

	QUnit.test( 'trigger uses custom options when provided', ( assert ) => {
		const container = document.createElement( 'div' );
		const span = document.createElement( 'span' );
		span.className = 'simple-tooltip';
		span.setAttribute( 'data-simple-tooltip', 'Custom options test' );
		container.appendChild( span );
		document.body.appendChild( container );

		const calledWithOptions = [];
		jQuery.fn.tooltipster = function ( opts ) {
			calledWithOptions.push( opts );
			return this;
		};

		const customOptions = { animation: 'grow', delay: 200, maxWidth: 600 };
		mw.libs.SimpleTooltip.trigger( jQuery( container ), customOptions );

		assert.strictEqual( calledWithOptions[ 0 ].animation, 'grow', 'custom animation applied' );
		assert.strictEqual( calledWithOptions[ 0 ].delay, 200, 'custom delay applied' );

		document.body.removeChild( container );
	} );

	/**
	 * Runs trigger() on one tooltip element and returns the options passed to tooltipster.
	 *
	 * @param {string} attrValue data-simple-tooltip value (already decoded by the browser)
	 * @param {Object|boolean} customOptions
	 * @return {Object}
	 */
	function triggerWith( attrValue, customOptions ) {
		const container = document.createElement( 'div' );
		const span = document.createElement( 'span' );
		span.className = 'simple-tooltip';
		span.setAttribute( 'data-simple-tooltip', attrValue );
		container.appendChild( span );
		document.body.appendChild( container );

		let received = null;
		jQuery.fn.tooltipster = function ( opts ) {
			received = opts;
			return this;
		};
		mw.libs.SimpleTooltip.trigger( jQuery( container ), customOptions );
		document.body.removeChild( container );
		return received;
	}

	QUnit.test( 'trigger does not modify defaultOptions', ( assert ) => {
		triggerWith( 'Text', false );
		assert.false( 'content' in mw.libs.SimpleTooltip.defaultOptions, 'defaultOptions has no content' );
	} );

	QUnit.test( 'trigger does not modify custom options passed by the caller', ( assert ) => {
		const custom = { animation: 'grow' };
		triggerWith( 'Text', custom );
		assert.false( 'content' in custom, 'custom options object is untouched' );
	} );

	QUnit.test( 'trigger renders server-side HTML in the tooltip content', ( assert ) => {
		const opts = triggerWith( 'a <b>bold</b> word', false );
		assert.strictEqual( opts.content.find( 'b' ).length, 1, 'b element is present' );
		assert.strictEqual( opts.content.text(), 'a bold word', 'text content is correct' );
	} );

	QUnit.test( 'trigger does not decode entities a second time', ( assert ) => {
		// The browser has already decoded the attribute once; this is what <nowiki> yields.
		const opts = triggerWith( '&lt;img src=x onerror=alert(1)&gt;', false );
		assert.strictEqual( opts.content.find( 'img' ).length, 0, 'no img element is created' );
		assert.strictEqual( opts.content.text(), '<img src=x onerror=alert(1)>', 'markup is shown as text' );
	} );

	QUnit.test( 'trigger strips scripts, event handlers and script URLs', ( assert ) => {
		const link = '<a href="javascript:alert(3)" onclick="x()">l</a>';
		const opts = triggerWith(
			'<img src="x" onerror="alert(1)"><script>alert(2)</script>' + link,
			false
		);
		assert.strictEqual( opts.content.find( 'script' ).length, 0, 'script removed' );
		assert.strictEqual( opts.content.find( '[onerror], [onclick]' ).length, 0, 'event handlers removed' );
		assert.strictEqual( opts.content.find( 'a' ).attr( 'href' ), undefined, 'script URL removed' );
	} );

	/**
	 * @param {string} text value of data-simple-tooltip
	 * @param {string} [className]
	 * @return {jQuery} a div that contains one tooltip element
	 */
	function createContainer( text, className = 'simple-tooltip' ) {
		const container = document.createElement( 'div' );
		const span = document.createElement( 'span' );
		span.className = className;
		span.setAttribute( 'data-simple-tooltip', text );
		container.appendChild( span );
		return jQuery( container );
	}

	QUnit.test( 'wikipage.content hook initialises only the tooltips inside its content', ( assert ) => {
		assert.true( mw.registeredHooks().includes( 'wikipage.content' ), 'wikipage.content handler registered' );

		const inside = createContainer( 'in' );
		const outside = createContainer( 'out' );
		jQuery( document.body ).append( inside, outside );

		const initialised = [];
		jQuery.fn.tooltipster = function () {
			initialised.push( this.attr( 'data-simple-tooltip' ) );
			return this;
		};
		mw.fireHook( 'wikipage.content', inside );

		assert.deepEqual( initialised, [ 'in' ], 'only elements inside the hook content are initialised' );
		inside.remove();
		outside.remove();
	} );

	QUnit.test( 'already initialised tooltips are not initialised again', ( assert ) => {
		const $container = createContainer( 'done', 'simple-tooltip tooltipstered' );
		let calls = 0;
		jQuery.fn.tooltipster = function () {
			calls++;
			return this;
		};
		mw.libs.SimpleTooltip.trigger( $container, false );
		assert.strictEqual( calls, 0, 'tooltipster not called' );
	} );

	QUnit.test( 'pf.addTemplateInstance re-initialises the tooltips of a cloned instance', ( assert ) => {
		const $instance = createContainer( 'clone', 'simple-tooltip tooltipstered' );
		let calls = 0;
		jQuery.fn.tooltipster = function () {
			calls++;
			return this;
		};
		mw.fireHook( 'pf.addTemplateInstance', $instance );
		assert.strictEqual( calls, 1, 'tooltipster called for the cloned element' );
	} );

	QUnit.test( 'trigger without a context searches the whole document', ( assert ) => {
		const $container = createContainer( 'global' );
		jQuery( document.body ).append( $container );

		const initialised = [];
		jQuery.fn.tooltipster = function () {
			initialised.push( this.attr( 'data-simple-tooltip' ) );
			return this;
		};
		mw.libs.SimpleTooltip.trigger( false, false );

		assert.true( initialised.includes( 'global' ), 'tooltip in the document is initialised' );
		$container.remove();
	} );

	QUnit.test( 'trigger puts the tooltip content in a div, not in an inline element', ( assert ) => {
		const opts = triggerWith( '<p>block content</p>', false );
		assert.true( opts.content.is( 'div' ), 'content root is a div' );
		assert.strictEqual( opts.content.find( 'p' ).length, 1, 'block elements inside are kept' );
	} );

} );
