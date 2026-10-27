<?php

declare( strict_types=1 );

/**
 * @link https://github.com/gesinn-it-pub/SimpleTooltip
 *
 * @author gesinn.it GmbH & Co. KG
 * @license MIT
 */

namespace MediaWiki\Extension\SimpleTooltip\Tests\Integration;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use ParserOptions;

/**
 * Runs the parser functions through the real parser.
 *
 * @group SimpleTooltip
 * @group Database
 */
class ParserFunctionsTest extends MediaWikiIntegrationTestCase {

	private function parse( string $wikitext ): string {
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$options = ParserOptions::newFromAnon();
		$title = Title::makeTitle( NS_MAIN, 'SimpleTooltipTest' );
		$output = $parser->parse( $wikitext, $title, $options );

		if ( version_compare( MW_VERSION, '1.42', '>=' ) ) {
			return $output->getRawText();
		}
		// MW < 1.42: getRawText() did not exist; getText() was the only option (remove with MW 1.39 support)
		return $output->getText( [ 'unwrap' => true ] );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 */
	public function testWikitextInDisplayAndTooltipTextIsParsed(): void {
		$html = $this->parse( "{{#tip-text: '''bold''' text | ''italic'' tip}}" );

		$this->assertStringContainsString( '<b>bold</b> text</span>', $html );
		$this->assertStringContainsString( 'data-simple-tooltip="', $html );
		$this->assertStringContainsString( '&lt;i&gt;italic&lt;/i&gt; tip', $html );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::infoTooltip
	 */
	public function testInfoTooltipParsesWikitext(): void {
		$html = $this->parse( "{{#tip-info: ''italic''}}" );

		$this->assertStringContainsString( 'simple-tooltip-info', $html );
		$this->assertStringContainsString( '&lt;i&gt;italic&lt;/i&gt;', $html );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::infoTooltip
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 */
	public function testScriptAndHandlersNeverReachTheOutput(): void {
		$html = $this->parse(
			'{{#tip-info: <script>alert(1)</script><img src=x onerror=alert(2)>}}' .
			'{{#tip-text: x | <script>alert(3)</script>}}'
		);

		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringNotContainsString( '<img src=x', $html );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipRefusesExternalUrlWhenExternalImagesAreDisabled(): void {
		$this->overrideConfigValue( 'AllowExternalImages', false );

		$html = $this->parse( '{{#tip-img: https://example.com/a.png | Tip}}' );

		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringContainsString( 'simple-tooltip-error', $html );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipAllowsExternalUrlWhenExternalImagesAreEnabled(): void {
		$this->overrideConfigValue( 'AllowExternalImages', true );

		$html = $this->parse( '{{#tip-img: https://example.com/a.png | Tip}}' );

		$this->assertStringContainsString( 'src="https://example.com/a.png"', $html );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipRefusesNonHttpSchemes(): void {
		$this->overrideConfigValue( 'AllowExternalImages', true );

		foreach ( [ 'javascript:alert(1)', 'data:image/png;base64,AAAA', 'ftp://example.com/a.png' ] as $url ) {
			$html = $this->parse( '{{#tip-img: ' . $url . ' | Tip}}' );
			$this->assertStringNotContainsString( '<img', $html, $url );
		}
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipReportsMissingLocalFile(): void {
		$html = $this->parse( '{{#tip-img: DoesNotExist12345.png | Tip}}' );

		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringContainsString( 'simple-tooltip-error', $html );
	}
}
