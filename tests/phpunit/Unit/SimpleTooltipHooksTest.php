<?php

declare( strict_types=1 );

/**
 * MediaWiki SimpleTooltip Extension
 *
 *
 * @link https://github.com/gesinn-it-pub/SimpleTooltip
 *
 * @author gesinn.it GmbH & Co. KG
 * @license MIT
 */

namespace Tests\Unit;

use MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks;
use Parser;
use ParserOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @group SimpleTooltip
 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks
 */
class SimpleTooltipHooksTest extends TestCase {

	/**
	 * The tooltip HTML as it appears, escaped, in the data attribute
	 *
	 * @param string $escapedInner already escaped inner HTML
	 * @return string
	 */
	private static function wrapped( string $escapedInner ): string {
		return '&lt;div class=&quot;simple-tooltip__content&quot;&gt;' . $escapedInner . '&lt;/div&gt;';
	}

	/**
	 * Parser whose parse methods return their input unchanged
	 *
	 * @param ParserOutput|null $output returned by getOutput(), a mock by default
	 * @param string|null $fullParse result of recursiveTagParseFully(), the input by default
	 * @return Parser&MockObject
	 */
	private function createParserMock( $output = null, ?string $fullParse = null ): Parser {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();
		$parserMock->method( 'getOutput' )->willReturn( $output ?? $this->createParserOutputMock() );
		$parserMock->method( 'recursiveTagParse' )->willReturnArgument( 0 );
		if ( $fullParse === null ) {
			$parserMock->method( 'recursiveTagParseFully' )->willReturnArgument( 0 );
		} else {
			$parserMock->method( 'recursiveTagParseFully' )->willReturn( $fullParse );
		}
		return $parserMock;
	}

	/**
	 * @return ParserOutput&MockObject
	 */
	private function createParserOutputMock() {
		// The class moved to MediaWiki\Parser in MW 1.42; remove the fallback with MW 1.39 support
		$class = class_exists( 'MediaWiki\\Parser\\ParserOutput' ) ? 'MediaWiki\\Parser\\ParserOutput' : 'ParserOutput';
		return $this->createMock( $class );
	}

	/**
	 * @param bool $allowExternal
	 * @param string[] $allowedFrom
	 * @param ParserOutput|null $output returned by getOutput(), a mock by default
	 * @param string|null $fullParse result of recursiveTagParseFully(), the input by default
	 * @return Parser&MockObject
	 */
	private function createParserMockWithExternalImages(
		bool $allowExternal,
		array $allowedFrom = [],
		$output = null,
		?string $fullParse = null
	): Parser {
		$options = $this->getMockBuilder( ParserOptions::class )
							->disableOriginalConstructor()
							->getMock();
		$options->method( 'getAllowExternalImages' )->willReturn( $allowExternal );
		$options->method( 'getAllowExternalImagesFrom' )->willReturn( $allowedFrom );

		$parserMock = $this->createParserMock( $output, $fullParse );
		$parserMock->method( 'getOptions' )->willReturn( $options );
		return $parserMock;
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 */
	public function testInlineTooltip(): void {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();
		$parserMock->method( 'getOutput' )->willReturn( $this->createParserOutputMock() );

		$parserMock->expects( $this->once() )
					->method( 'recursiveTagParseFully' )
					->with( 'Tooltip text' )
					->willReturn( 'Tooltip content' );
		$parserMock->method( 'recursiveTagParse' )->willReturnArgument( 0 );

		$tooltip = SimpleTooltipHooks::inlineTooltip( $parserMock, 'Inline text', 'Tooltip text' );

		$expectedHtml = '<span class="simple-tooltip simple-tooltip-inline" ' .
						'data-simple-tooltip="' . self::wrapped( 'Tooltip content' ) . '">Inline text</span>';
		$this->assertEquals( $expectedHtml, $tooltip[0] );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 */
	public function testInlineTooltipEmptyTooltipTextReturnsEmptyArray(): void {
		$parserMock = $this->createParserMock();
		$parserMock->expects( $this->never() )->method( 'recursiveTagParseFully' );

		$result = SimpleTooltipHooks::inlineTooltip( $parserMock, 'Display text', '' );
		$this->assertSame( [], $result );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 */
	public function testInlineTooltipKeepsQuotesAndEscapesThemInTheAttribute(): void {
		$parserMock = $this->createParserMock();

		$result = SimpleTooltipHooks::inlineTooltip( $parserMock, 'Display text', 'Say "hello"' );
		$this->assertStringContainsString(
			'data-simple-tooltip="' . self::wrapped( 'Say &quot;hello&quot;' ) . '"',
			$result[0]
		);
		$this->assertStringNotContainsString( 'Say "hello"', $result[0] );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 */
	public function testInlineTooltipDisplayTextIsParsedAsWikitext(): void {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();
		$parserMock->method( 'getOutput' )->willReturn( $this->createParserOutputMock() );
		$parserMock->expects( $this->once() )
					->method( 'recursiveTagParse' )
					->with( "'''Bold''' text" )
					->willReturn( '<b>Bold</b> text' );
		$parserMock->method( 'recursiveTagParseFully' )->willReturn( 'Tip' );

		$result = SimpleTooltipHooks::inlineTooltip( $parserMock, "'''Bold''' text", 'Tip' );
		$this->assertStringContainsString( '><b>Bold</b> text</span>', $result[0] );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 */
	public function testInlineTooltipEscapesMarkupInTooltipText(): void {
		$parserMock = $this->createParserMock();

		$result = SimpleTooltipHooks::inlineTooltip( $parserMock, 'Text', '<b>x</b>' );
		$this->assertStringContainsString(
			'data-simple-tooltip="' . self::wrapped( '&lt;b&gt;x&lt;/b&gt;' ) . '"',
			$result[0]
		);
		$this->assertStringNotContainsString( '<b>x</b>', $result[0] );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 */
	public function testInlineTooltipReturnsExpectedFlags(): void {
		$result = SimpleTooltipHooks::inlineTooltip( $this->createParserMock(), 'Text', 'Tooltip' );
		$this->assertTrue( $result['noparse'] );
		$this->assertTrue( $result['isHTML'] );
		$this->assertSame( 'nowiki', $result['markerType'] );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testMissingTooltipArgumentReturnsEmptyArrayWithoutWarning(): void {
		$parserMock = $this->createParserMock();

		$this->assertSame( [], SimpleTooltipHooks::inlineTooltip( $parserMock, 'Display text' ) );
		$this->assertSame( [], SimpleTooltipHooks::imgTooltip( $parserMock, 'Image.png' ) );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::infoTooltip
	 */
	public function testZeroIsValidTooltipText(): void {
		$parserMock = $this->createParserMock();

		$inline = SimpleTooltipHooks::inlineTooltip( $parserMock, 'Text', '0' );
		$this->assertStringContainsString( 'data-simple-tooltip="' . self::wrapped( '0' ) . '"', $inline[0] );

		$info = SimpleTooltipHooks::infoTooltip( $parserMock, '0' );
		$this->assertStringContainsString( 'data-simple-tooltip="' . self::wrapped( '0' ) . '"', $info[0] );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::infoTooltip
	 */
	public function testInfoTooltip(): void {
		$parserMock = $this->createParserMock();

		$tooltip = SimpleTooltipHooks::infoTooltip( $parserMock, 'Tooltip text' );

		$expectedHtml = '<span class="simple-tooltip simple-tooltip-info" data-simple-tooltip="' .
			self::wrapped( 'Tooltip text' ) . '"></span>';
		$this->assertEquals( $expectedHtml, $tooltip[0] );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::infoTooltip
	 */
	public function testInfoTooltipEmptyValueReturnsEmptyArray(): void {
		$result = SimpleTooltipHooks::infoTooltip( $this->createParserMock(), '' );
		$this->assertSame( [], $result );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::infoTooltip
	 */
	public function testInfoTooltipIsParsedAsWikitext(): void {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();
		$parserMock->method( 'getOutput' )->willReturn( $this->createParserOutputMock() );
		$parserMock->expects( $this->once() )
					->method( 'recursiveTagParseFully' )
					->with( "''Hi''" )
					->willReturn( '<i>Hi</i>' );

		$result = SimpleTooltipHooks::infoTooltip( $parserMock, "''Hi''" );
		$this->assertStringContainsString(
			'data-simple-tooltip="' . self::wrapped( '&lt;i&gt;Hi&lt;/i&gt;' ) . '"',
			$result[0]
		);
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::infoTooltip
	 */
	public function testInfoTooltipEscapesHtmlSpecialChars(): void {
		$result = SimpleTooltipHooks::infoTooltip( $this->createParserMock(), 'A &gt; B' );
		$this->assertStringContainsString(
			'data-simple-tooltip="' . self::wrapped( 'A &amp;gt; B' ) . '"',
			$result[0]
		);
		$this->assertStringNotContainsString( 'A &gt; B', $result[0] );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipWithAllowedExternalUrl(): void {
		$parserMock = $this->createParserMockWithExternalImages( true );

		$tooltip = SimpleTooltipHooks::imgTooltip( $parserMock, 'https://example.com/a.png', 'Tooltip text' );

		$this->assertStringContainsString( 'class="simple-tooltip simple-tooltip-img"', $tooltip[0] );
		$this->assertStringContainsString(
			'data-simple-tooltip="' . self::wrapped( 'Tooltip text' ) . '"',
			$tooltip[0]
		);
		$this->assertStringContainsString( 'src="https://example.com/a.png"', $tooltip[0] );
		$this->assertTrue( $tooltip['isHTML'] );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipEmptyTooltipTextReturnsEmptyArray(): void {
		$result = SimpleTooltipHooks::imgTooltip( $this->createParserMock(), 'http://example.com/image.png', '' );
		$this->assertSame( [], $result );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipTooltipIsParsedAsWikitext(): void {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();
		$parserMock->method( 'getOutput' )->willReturn( $this->createParserOutputMock() );
		$options = $this->getMockBuilder( ParserOptions::class )->disableOriginalConstructor()->getMock();
		$options->method( 'getAllowExternalImages' )->willReturn( true );
		$parserMock->method( 'getOptions' )->willReturn( $options );
		$parserMock->expects( $this->once() )
					->method( 'recursiveTagParseFully' )
					->with( "'''Bold'''" )
					->willReturn( '<b>Bold</b>' );

		$result = SimpleTooltipHooks::imgTooltip( $parserMock, 'https://example.com/a.png', "'''Bold'''" );
		$this->assertStringContainsString(
			'data-simple-tooltip="' . self::wrapped( '&lt;b&gt;Bold&lt;/b&gt;' ) . '"',
			$result[0]
		);
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipAllowsUrlsFromTheExternalImageWhitelist(): void {
		$parserMock = $this->createParserMockWithExternalImages( false, [ 'https://cdn.example.org/' ] );

		$allowed = SimpleTooltipHooks::imgTooltip( $parserMock, 'https://cdn.example.org/a.png', 'Tip' );
		$this->assertStringContainsString( 'src="https://cdn.example.org/a.png"', $allowed[0] );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipEscapesQuotesInUrl(): void {
		$parserMock = $this->createParserMockWithExternalImages( true );

		$result = SimpleTooltipHooks::imgTooltip( $parserMock, 'https://example.com/a.png" onerror="x', 'Tip' );

		$this->assertStringContainsString( 'src="https://example.com/a.png&quot; onerror=&quot;x"', $result[0] );
		$this->assertStringNotContainsString( '" onerror="', $result[0] );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::onParserFirstCallInit
	 */
	public function testParserFirstCallInitRegistersAllParserFunctions(): void {
		$registered = [];
		$parserMock = $this->getMockBuilder( Parser::class )->disableOriginalConstructor()->getMock();
		$parserMock->expects( $this->exactly( 6 ) )
			->method( 'setFunctionHook' )
			->willReturnCallback( static function ( $name, $callback ) use ( &$registered ) {
				$registered[$name] = $callback[1];
			} );

		SimpleTooltipHooks::onParserFirstCallInit( $parserMock );

		$this->assertSame( [
			'simple-tooltip' => 'inlineTooltip',
			'tip-text' => 'inlineTooltip',
			'simple-tooltip-info' => 'infoTooltip',
			'tip-info' => 'infoTooltip',
			'simple-tooltip-img' => 'imgTooltip',
			'tip-img' => 'imgTooltip',
		], $registered );
	}

	/**
	 * @param callable $call receives the parser and returns the result of a parser function
	 * @param bool $expectModule
	 */
	private function assertModuleRequested( callable $call, bool $expectModule ): void {
		$output = $this->createParserOutputMock();
		if ( $expectModule ) {
			$output->expects( $this->once() )->method( 'addModules' )->with( [ 'ext.SimpleTooltip' ] );
		} else {
			$output->expects( $this->never() )->method( 'addModules' );
		}
		$call( $this->createParserMockWithExternalImages( true, [], $output ) );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::infoTooltip
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testParserFunctionsRequestTheModuleWhenTheyRenderATooltip(): void {
		$this->assertModuleRequested(
			static fn ( $parser ) => SimpleTooltipHooks::inlineTooltip( $parser, 'Text', 'Tip' ), true );
		$this->assertModuleRequested(
			static fn ( $parser ) => SimpleTooltipHooks::infoTooltip( $parser, 'Tip' ), true );
		$this->assertModuleRequested(
			static fn ( $parser ) => SimpleTooltipHooks::imgTooltip( $parser, 'https://example.com/a.png', 'Tip' ),
			true );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::infoTooltip
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testParserFunctionsDoNotRequestTheModuleWhenTheyRenderNothing(): void {
		$this->assertModuleRequested(
			static fn ( $parser ) => SimpleTooltipHooks::inlineTooltip( $parser, 'Text', '' ), false );
		$this->assertModuleRequested(
			static fn ( $parser ) => SimpleTooltipHooks::infoTooltip( $parser, '' ), false );
		$this->assertModuleRequested(
			static fn ( $parser ) => SimpleTooltipHooks::imgTooltip( $parser, 'https://example.com/a.png', '' ),
			false );
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::inlineTooltip
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::infoTooltip
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::imgTooltip
	 */
	public function testTooltipContentHasNoOuterParagraphAndIsWrappedInADiv(): void {
		$parserMock = $this->createParserMockWithExternalImages( true, [], null, "<p>Text with <b>bold</b>\n</p>" );

		$results = [
			SimpleTooltipHooks::inlineTooltip( $parserMock, 'Shown', 'Tip' ),
			SimpleTooltipHooks::infoTooltip( $parserMock, 'Tip' ),
			SimpleTooltipHooks::imgTooltip( $parserMock, 'https://example.com/a.png', 'Tip' ),
		];

		foreach ( $results as $result ) {
			$this->assertStringContainsString(
				'data-simple-tooltip="' . self::wrapped( 'Text with &lt;b&gt;bold&lt;/b&gt;' ) . '"',
				$result[0]
			);
			$this->assertStringNotContainsString( '&lt;p&gt;', $result[0] );
		}
	}

	/**
	 * @covers \MediaWiki\Extension\SimpleTooltip\SimpleTooltipHooks::infoTooltip
	 */
	public function testSeveralParagraphsKeepTheirParagraphsInsideTheDiv(): void {
		$parserMock = $this->createParserMock( null, "<p>One\n</p>\n<p>Two\n</p>" );

		$result = SimpleTooltipHooks::infoTooltip( $parserMock, 'Tip' );

		$this->assertStringContainsString( '&lt;p&gt;One', $result[0] );
		$this->assertStringContainsString( '&lt;p&gt;Two', $result[0] );
		$this->assertStringContainsString(
			'data-simple-tooltip="&lt;div class=&quot;simple-tooltip__content&quot;&gt;&lt;p&gt;One',
			$result[0]
		);
	}
}
