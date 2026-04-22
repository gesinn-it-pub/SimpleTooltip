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

} );
