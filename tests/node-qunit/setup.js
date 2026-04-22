'use strict';

const { JSDOM } = require( 'jsdom' );

const dom = new JSDOM( '<!DOCTYPE html><html><body></body></html>' );
const window = dom.window;

// Must set globals before requiring jQuery so it initialises directly
global.document = window.document;
global.window = window;

// With global.document set, require('jquery') returns jQuery directly
const jQuery = require( 'jquery' );

// Stub tooltipster — overridden per test as needed
jQuery.fn.tooltipster = function () {
	return this;
};

global.jQuery = jQuery;
global.$ = jQuery;

global.mw = {
	libs: {},
	hook: () => ( { add: () => {} } )
};

require( '../../lib/SimpleTooltip.js' );
