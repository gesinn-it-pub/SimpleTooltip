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
 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks
 */
class ParserFunctionsTest extends MediaWikiIntegrationTestCase {

	private function parseToOutput( string $wikitext ) {
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$options = ParserOptions::newFromAnon();
		$title = Title::makeTitle( NS_MAIN, 'SimpleTooltipTest' );
		return $parser->parse( $wikitext, $title, $options );
	}

	private function parse( string $wikitext ): string {
		$output = $this->parseToOutput( $wikitext );

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

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipUsesTheUrlOfALocalFile(): void {
		// The class moved to MediaWiki\FileRepo in MW 1.43 and MW 1.46 drops the global name
		$fileClass = class_exists( 'MediaWiki\\FileRepo\\File\\File' ) ? 'MediaWiki\\FileRepo\\File\\File' : 'File';
		$repoClass = class_exists( 'MediaWiki\\FileRepo\\RepoGroup' ) ? 'MediaWiki\\FileRepo\\RepoGroup' : 'RepoGroup';

		$file = $this->createMock( $fileClass );
		$file->method( 'exists' )->willReturn( true );
		$file->method( 'getUrl' )->willReturn( '/images/a/ab/Tip_icon.png' );
		// The parser compares the title of the file with the requested one
		$file->method( 'getTitle' )->willReturn( Title::makeTitle( NS_FILE, 'Tip_icon.png' ) );
		$repoGroup = $this->createMock( $repoClass );
		$repoGroup->method( 'findFile' )->willReturn( $file );
		$this->setService( 'RepoGroup', $repoGroup );

		foreach ( [ 'Tip icon.png', 'File:Tip icon.png' ] as $name ) {
			$html = $this->parse( '{{#tip-img: ' . $name . ' | Tip}}' );

			$this->assertStringContainsString( 'src="/images/a/ab/Tip_icon.png"', $html, $name );
			$this->assertStringNotContainsString( 'simple-tooltip-error', $html, $name );
		}
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipRefusesAnInvalidFileName(): void {
		$html = $this->parse( '{{#tip-img: Bad[name | Tip}}' );

		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringContainsString( 'simple-tooltip-error', $html );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipAllowsOnlyUrlsFromTheExternalImageWhitelist(): void {
		$this->overrideConfigValues( [
			'AllowExternalImages' => false,
			'AllowExternalImagesFrom' => [ 'https://cdn.example.org/' ],
		] );

		$allowed = $this->parse( '{{#tip-img: https://cdn.example.org/a.png | Tip}}' );
		$this->assertStringContainsString( 'src="https://cdn.example.org/a.png"', $allowed );

		$other = $this->parse( '{{#tip-img: https://evil.example.com/a.png | Tip}}' );
		$this->assertStringNotContainsString( '<img', $other );
		$this->assertStringContainsString( 'simple-tooltip-error', $other );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks
	 */
	public function testModuleIsRequestedOnlyByPagesThatUseAParserFunction(): void {
		foreach ( [ '{{#tip-text: a | b}}', '{{#tip-info: b}}' ] as $wikitext ) {
			$this->assertContains( 'ext.SimpleTooltip', $this->parseToOutput( $wikitext )->getModules(), $wikitext );
		}

		$this->assertNotContains( 'ext.SimpleTooltip', $this->parseToOutput( 'Plain text' )->getModules() );
	}

	/**
	 * Issue 14: a tooltip inside a list item must not break the list, and its content is a div, not a paragraph.
	 *
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 */
	public function testTooltipInsideAnIndentedListKeepsTheListIntact(): void {
		$html = $this->parse( ": Item 1\n:: Item 2 {{#tip-text: (Test) | Test }}\n:: Item 3" );

		foreach ( [ 'dl', 'dd' ] as $tag ) {
			$this->assertSame(
				substr_count( $html, '<' . $tag ),
				substr_count( $html, '</' . $tag . '>' ),
				"<$tag> and </$tag> are balanced in: $html"
			);
		}
		$this->assertStringContainsString(
			'data-simple-tooltip="&lt;div class=&quot;simple-tooltip__content&quot;&gt;Test&lt;/div&gt;"',
			$html
		);
		$this->assertStringNotContainsString( '&lt;p&gt;', $html, 'no paragraph in the tooltip text' );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::infoTooltip
	 */
	public function testSeveralParagraphsInATooltipStayInsideTheDiv(): void {
		$html = $this->parse( "{{#tip-info: One\n\nTwo}}" );

		$this->assertStringContainsString( 'simple-tooltip__content&quot;&gt;&lt;p&gt;One', $html );
		$this->assertStringContainsString( '&lt;p&gt;Two', $html );
	}
}
