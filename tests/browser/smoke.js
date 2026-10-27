'use strict';

/**
 * Browser smoke test, run inside the DCI wiki container via `make browser-test`.
 *
 * The jsdom-based QUnit tests stub Tooltipster and never load MediaWiki's ResourceLoader, and
 * the PHPUnit tests never run the JavaScript. This script covers what falls between them in a
 * real Chromium on a live wiki: the module is requested by the parser functions, the real
 * Tooltipster shows the tooltip, the content has the expected markup, and nothing in the
 * content can run script.
 *
 * Every check is a concrete assertion with a failure message and a non-zero exit code; there
 * are no screenshots.
 */

const { chromium } = require( 'playwright' );
const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const zlib = require( 'zlib' );

const BASE_URL = 'http://127.0.0.1:8080';
const MW_DIR = '/var/www/html';
const FAILURES = [];

function mwRun( args, input ) {
	return execFileSync( 'php', [ 'maintenance/run.php', ...args ], { cwd: MW_DIR, input } );
}

/**
 * Creates or updates a page and invalidates its parser cache entry. An unchanged text makes no new
 * revision, and a cached parse could hide a change in the parser functions on a repeated run.
 *
 * @param {string} title
 * @param {string} wikitext
 */
function createPage( title, wikitext ) {
	mwRun( [ 'edit', '--user', 'WikiSysop', '--summary', 'browser smoke test', title ], wikitext );
	mwRun( [ 'purgePage' ], `${ title }\n` );
}

/* eslint-disable no-bitwise */
function crc32( buffer ) {
	let crc = 0xFFFFFFFF;
	for ( const byte of buffer ) {
		crc ^= byte;
		for ( let i = 0; i < 8; i++ ) {
			crc = ( crc >>> 1 ) ^ ( 0xEDB88320 & -( crc & 1 ) );
		}
	}
	return ( crc ^ 0xFFFFFFFF ) >>> 0;
}
/* eslint-enable no-bitwise */

function pngChunk( type, data ) {
	const length = Buffer.alloc( 4 );
	length.writeUInt32BE( data.length );
	const body = Buffer.concat( [ Buffer.from( type ), data ] );
	const crc = Buffer.alloc( 4 );
	crc.writeUInt32BE( crc32( body ) );
	return Buffer.concat( [ length, body, crc ] );
}

/**
 * A solid 24x24 PNG, large enough to be hovered reliably.
 *
 * @return {Buffer}
 */
function createPng() {
	const size = 24;
	const header = Buffer.alloc( 13 );
	header.writeUInt32BE( size, 0 );
	header.writeUInt32BE( size, 4 );
	header[ 8 ] = 8; // bit depth
	header[ 9 ] = 2; // RGB
	const row = Buffer.concat( [ Buffer.from( [ 0 ] ), Buffer.alloc( size * 3, 0x80 ) ] );
	const pixels = Buffer.concat( Array( size ).fill( row ) );
	return Buffer.concat( [
		Buffer.from( [ 0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A ] ),
		pngChunk( 'IHDR', header ),
		pngChunk( 'IDAT', zlib.deflateSync( pixels ) ),
		pngChunk( 'IEND', Buffer.alloc( 0 ) )
	] );
}

async function fileExists( name ) {
	const url = `${ BASE_URL }/api.php?action=query&prop=imageinfo&format=json&titles=File:${ name }`;
	const data = await ( await fetch( url ) ).json();
	return Object.values( data.query.pages ).some( ( page ) => !!page.imageinfo );
}

/**
 * Imports a generated image unless it is already in the wiki: importing the same file twice fails,
 * and the test must be repeatable on a running wiki.
 *
 * @param {string} name
 */
async function importImage( name ) {
	if ( await fileExists( name ) ) {
		return;
	}
	const dir = '/tmp/smoke-images';
	fs.mkdirSync( dir, { recursive: true } );
	// eslint-disable-next-line security/detect-non-literal-fs-filename
	fs.writeFileSync( `${ dir }/${ name }`, createPng() );
	mwRun( [ 'importImages', '--user', 'WikiSysop', dir ] );
}

/**
 * Opens a page and records script errors and dialogs, which a tooltip must never cause.
 *
 * @param {Object} browser Playwright browser
 * @param {string} title
 * @return {Promise<Object>} Playwright page
 */
async function openPage( browser, title ) {
	const page = await browser.newPage();
	page.on( 'pageerror', ( err ) => FAILURES.push( `${ title }: unhandled page error: ${ err.message }` ) );
	page.on( 'dialog', ( dialog ) => {
		FAILURES.push( `${ title }: a script opened a dialog ("${ dialog.message() }")` );
		dialog.dismiss();
	} );
	await page.goto( `${ BASE_URL }/index.php/${ title }`, { waitUntil: 'networkidle' } );
	return page;
}

/**
 * Hovers an element and returns what the single visible tooltip shows.
 *
 * @param {Object} page Playwright page
 * @param {string} selector
 * @return {Promise<Object|null>} count, html and text of the tooltip, null if none appears
 */
async function hoverTooltip( page, selector ) {
	await page.mouse.move( 0, 0 );
	await page.waitForSelector( '.tooltipster-base:visible', { state: 'detached', timeout: 3000 } )
		.catch( () => {} );
	try {
		await page.locator( selector ).first().hover( { timeout: 5000 } );
		await page.waitForSelector( '.tooltipster-base:visible', { timeout: 3000 } );
	} catch ( e ) {
		return null;
	}
	return page.evaluate( () => {
		const visible = Array.from( document.querySelectorAll( '.tooltipster-base' ) )
			.filter( ( el ) => el.offsetParent !== null || getComputedStyle( el ).display !== 'none' );
		const base = visible[ 0 ].getBoundingClientRect();
		const content = visible[ 0 ].querySelector( '.tooltipster-content' );
		const range = document.createRange();
		range.selectNodeContents( content );
		const text = range.getBoundingClientRect();
		return {
			count: visible.length,
			html: content.innerHTML,
			text: content.textContent.trim(),
			// Space between the box and its text; a paragraph margin adds to it
			gapTop: Math.round( text.top - base.top ),
			gapBottom: Math.round( base.bottom - text.bottom )
		};
	} );
}

async function checkModuleIsLoadedOnlyWhereATooltipIs( browser ) {
	const withTooltip = await openPage( browser, 'SmokeTestTooltips' );
	const stateWith = await withTooltip.evaluate( () => mw.loader.getState( 'ext.SimpleTooltip' ) );
	if ( stateWith !== 'ready' ) {
		FAILURES.push(
			`ext.SimpleTooltip is "${ stateWith }" on a page with tooltips, expected "ready" - the parser ` +
			'functions must request the module through ParserOutput::addModules.'
		);
	}
	await withTooltip.close();

	const plain = await openPage( browser, 'SmokeTestPlain' );
	const statePlain = await plain.evaluate( () => mw.loader.getState( 'ext.SimpleTooltip' ) );
	if ( statePlain === 'ready' || statePlain === 'loading' ) {
		FAILURES.push(
			`ext.SimpleTooltip is "${ statePlain }" on a page without tooltips - the module must only be ` +
			'loaded where a parser function is used.'
		);
	}
	await plain.close();
}

async function checkInlineTooltipShowsFormattedContent( browser ) {
	const page = await openPage( browser, 'SmokeTestTooltips' );

	const shown = await page.locator( '.simple-tooltip-inline b' ).count();
	if ( shown !== 1 ) {
		FAILURES.push( `the hovered text of #tip-text should keep its bold wikitext, found ${ shown } <b>.` );
	}

	const tooltip = await hoverTooltip( page, '.simple-tooltip-inline' );
	if ( !tooltip ) {
		FAILURES.push( 'hovering the #tip-text text did not show a tooltip.' );
	} else {
		if ( tooltip.count !== 1 ) {
			FAILURES.push( `hovering shows ${ tooltip.count } tooltips, expected exactly 1 (double init?).` );
		}
		if ( !tooltip.html.includes( '<i>italic</i>' ) || !tooltip.html.includes( 'a link' ) ) {
			FAILURES.push( `the tooltip should render wikitext (italic and a link), got: ${ tooltip.html }` );
		}
		if ( Math.max( tooltip.gapTop, tooltip.gapBottom ) > 8 ) {
			FAILURES.push(
				`there is extra space around the text in the tooltip (above ${ tooltip.gapTop }px, below ` +
				`${ tooltip.gapBottom }px, expected only the padding of about 6px) - a paragraph margin inside ` +
				'the tooltip adds space.'
			);
		}
		if ( !tooltip.html.includes( 'simple-tooltip__content' ) || /<p[ >]/.test( tooltip.html ) ) {
			FAILURES.push( `the tooltip content should be a div without an outer paragraph, got: ${ tooltip.html }` );
		}
	}

	await page.close();
}

async function checkInfoIconShowsATooltip( browser ) {
	const page = await openPage( browser, 'SmokeTestTooltips' );

	const box = await page.locator( '.simple-tooltip-info' ).first().boundingBox();
	if ( !box || box.width < 4 || box.height < 4 ) {
		FAILURES.push(
			`the info icon has no visible size (${ JSON.stringify( box ) }) - check lib/SimpleTooltip.css ` +
			'and that the style module is loaded.'
		);
	}

	const tooltip = await hoverTooltip( page, '.simple-tooltip-info' );
	if ( !tooltip || tooltip.text !== 'Info text' ) {
		FAILURES.push( `hovering the info icon should show "Info text", got: ${ JSON.stringify( tooltip ) }` );
	}

	await page.close();
}

async function checkImageTooltipUsesTheLocalFile( browser ) {
	const page = await openPage( browser, 'SmokeTestTooltips' );

	const src = await page.locator( 'img.simple-tooltip-img' ).first().getAttribute( 'src' );
	if ( !src || !src.includes( 'SmokeTestTip.png' ) ) {
		FAILURES.push( `#tip-img with a file name should use the URL of the local file, got src="${ src }".` );
	} else {
		const loaded = await page.evaluate(
			() => document.querySelector( 'img.simple-tooltip-img' ).naturalWidth
		);
		if ( loaded !== 24 ) {
			FAILURES.push( `the image of #tip-img did not load (naturalWidth ${ loaded }, expected 24).` );
		}
	}

	const tooltip = await hoverTooltip( page, 'img.simple-tooltip-img' );
	if ( !tooltip || tooltip.text !== 'Image tip' ) {
		FAILURES.push( `hovering the image should show "Image tip", got: ${ JSON.stringify( tooltip ) }` );
	}

	await page.close();
}

async function checkMissingAndExternalImagesShowAnError( browser ) {
	const page = await openPage( browser, 'SmokeTestTooltips' );

	const errors = await page.locator( '.simple-tooltip-error' ).count();
	const images = await page.locator( 'img.simple-tooltip-img' ).count();
	if ( errors !== 2 ) {
		FAILURES.push(
			`a missing file and an external URL (external images are off) should give 2 error spans, got ${ errors }.`
		);
	}
	if ( images !== 1 ) {
		FAILURES.push( `only the existing local file should produce an image, got ${ images } img elements.` );
	}

	await page.close();
}

async function checkEscapedMarkupStaysText( browser ) {
	const page = await openPage( browser, 'SmokeTestXss' );

	for ( const selector of [ '.simple-tooltip-info', '.simple-tooltip-inline' ] ) {
		const tooltip = await hoverTooltip( page, selector );
		if ( !tooltip ) {
			FAILURES.push( `hovering ${ selector } on the XSS page did not show a tooltip.` );
		} else if ( !tooltip.text.includes( '<img' ) ) {
			FAILURES.push( `escaped markup in ${ selector } should be shown as text, got: ${ tooltip.html }` );
		}
	}

	const executed = await page.evaluate( () => window.smokeXss );
	const images = await page.locator( '.tooltipster-base img' ).count();
	if ( executed !== undefined || images !== 0 ) {
		FAILURES.push(
			`escaped markup in a tooltip became live (smokeXss=${ executed }, img elements: ${ images }).`
		);
	}

	await page.close();
}

async function checkListStaysIntact( browser ) {
	const page = await openPage( browser, 'SmokeTestList' );

	const result = await page.evaluate( () => ( {
		dl: document.querySelectorAll( '.mw-parser-output dl' ).length,
		dd: document.querySelectorAll( '.mw-parser-output dd' ).length,
		inside: !!document.querySelector( '.mw-parser-output dd .simple-tooltip' )
	} ) );
	if ( result.dl !== 2 || result.dd !== 3 || !result.inside ) {
		FAILURES.push( `the indented list around a tooltip is broken: ${ JSON.stringify( result ) }` );
	}

	await page.close();
}

async function checkContentAddedLaterIsInitialisedOnce( browser ) {
	const page = await openPage( browser, 'SmokeTestTooltips' );

	// The same call MediaWiki makes after a live preview or other Ajax content.
	await page.evaluate( () => {
		const $box = jQuery( '<div>' ).attr( 'id', 'smoke-injected' ).appendTo( document.body );
		$box.append(
			jQuery( '<span>' )
				.addClass( 'simple-tooltip simple-tooltip-inline' )
				.attr(
					'data-simple-tooltip',
					'<div class="simple-tooltip__content">Injected tip</div>'
				)
				.text( 'Injected' )
		);
		mw.hook( 'wikipage.content' ).fire( $box );
		mw.hook( 'wikipage.content' ).fire( $box );
	} );

	const initialised = await page.evaluate(
		() => document.querySelector( '#smoke-injected .simple-tooltip' ).classList.contains( 'tooltipstered' )
	);
	if ( !initialised ) {
		FAILURES.push( 'a tooltip added later through the wikipage.content hook was not initialised.' );
	}

	const tooltip = await hoverTooltip( page, '#smoke-injected .simple-tooltip' );
	if ( !tooltip || tooltip.text !== 'Injected tip' || tooltip.count !== 1 ) {
		FAILURES.push(
			'the later added tooltip should show "Injected tip" exactly once, got: ' + JSON.stringify( tooltip )
		);
	}

	await page.close();
}

( async () => {
	await importImage( 'SmokeTestTip.png' );

	createPage( 'SmokeTestPlain', 'Plain text without a tooltip.' );
	createPage(
		'SmokeTestTooltips',
		[
			'Inline: {{#tip-text: \'\'\'bold\'\'\' shown | \'\'italic\'\' tip with [[Main Page|a link]]}}',
			'',
			'Info: {{#tip-info: Info text}}',
			'',
			'Image: {{#tip-img: SmokeTestTip.png | Image tip}}',
			'',
			'Missing: {{#tip-img: DoesNotExist12345.png | Tip}}',
			'',
			'External: {{#tip-img: https://example.com/a.png | Tip}}'
		].join( '\n' )
	);
	createPage(
		'SmokeTestXss',
		[
			'{{#tip-info: &lt;img src=x onerror="window.smokeXss=1"&gt;}}',
			'',
			'{{#tip-text: shown | <nowiki><img src=x onerror="window.smokeXss=1"></nowiki>}}'
		].join( '\n' )
	);
	createPage( 'SmokeTestList', ': Item 1\n:: Item 2 {{#tip-text: (Test) | Test }}\n:: Item 3' );

	const browser = await chromium.launch();
	try {
		const checks = [
			checkModuleIsLoadedOnlyWhereATooltipIs,
			checkInlineTooltipShowsFormattedContent,
			checkInfoIconShowsATooltip,
			checkImageTooltipUsesTheLocalFile,
			checkMissingAndExternalImagesShowAnError,
			checkEscapedMarkupStaysText,
			checkListStaysIntact,
			checkContentAddedLaterIsInitialisedOnce
		];
		for ( const check of checks ) {
			// A failing check must not hide the messages of the others
			try {
				await check( browser );
			} catch ( err ) {
				FAILURES.push( `${ check.name } crashed: ${ err.message.split( '\n' )[ 0 ] }` );
			}
		}
	} finally {
		await browser.close();
	}

	if ( FAILURES.length > 0 ) {
		console.error( 'Browser smoke test FAILED:\n' );
		FAILURES.forEach( ( msg ) => console.error( `  - ${ msg }` ) );
		process.exit( 1 );
	}

	console.log( 'Browser smoke test passed.' );
} )().catch( ( err ) => {
	console.error( 'Browser smoke test crashed:', err );
	process.exit( 1 );
} );
