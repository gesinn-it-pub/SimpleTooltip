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

use Parser;
use PHPUnit\Framework\TestCase;
use SimpleTooltipHooks;

/**
 * @group SimpleTooltip
 *
 */
class SimpleTooltipHooksTest extends TestCase {

	/**
	 * @covers SimpleTooltipHooks::inlineTooltip
	 */
	public function testInlineTooltip() {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();

		$parserMock->expects( $this->once() )
					->method( 'recursiveTagParseFully' )
					->willReturn( 'Tooltip content' );

		$tooltip = SimpleTooltipHooks::inlineTooltip( $parserMock, 'Inline text', 'Tooltip text' );

		$expectedHtml = '<span class="simple-tooltip simple-tooltip-inline" ' .
						'data-simple-tooltip="Tooltip content">Inline text</span>';
		$this->assertEquals( $expectedHtml, $tooltip[0] );
	}

	/**
	 * @covers SimpleTooltipHooks::inlineTooltip
	 */
	public function testInlineTooltipEmptyTooltipTextReturnsEmptyArray(): void {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();

		$parserMock->expects( $this->never() )
					->method( 'recursiveTagParseFully' );

		$result = SimpleTooltipHooks::inlineTooltip( $parserMock, 'Display text', '' );
		$this->assertSame( [], $result );
	}

	/**
	 * @covers SimpleTooltipHooks::inlineTooltip
	 */
	public function testInlineTooltipConvertsDoubleQuotesToSingleQuotes(): void {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();

		$parserMock->expects( $this->once() )
					->method( 'recursiveTagParseFully' )
					->willReturn( 'Say "hello"' );

		$result = SimpleTooltipHooks::inlineTooltip( $parserMock, 'Display text', 'Say "hello"' );
		// Double quotes are converted to single quotes, which htmlspecialchars then encodes as &#039;
		$this->assertStringContainsString( 'data-simple-tooltip="Say &#039;hello&#039;"', $result[0] );
	}

	/**
	 * @covers SimpleTooltipHooks::inlineTooltip
	 */
	public function testInlineTooltipEscapesHtmlInDisplayText(): void {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();

		$parserMock->expects( $this->once() )
					->method( 'recursiveTagParseFully' )
					->willReturn( 'Tooltip text' );

		$result = SimpleTooltipHooks::inlineTooltip( $parserMock, '<b>Bold text</b>', 'Tooltip text' );
		$this->assertStringContainsString( '&lt;b&gt;Bold text&lt;/b&gt;', $result[0] );
	}

	/**
	 * @covers SimpleTooltipHooks::inlineTooltip
	 */
	public function testInlineTooltipReturnsExpectedFlags(): void {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();

		$parserMock->method( 'recursiveTagParseFully' )
					->willReturn( 'Tooltip' );

		$result = SimpleTooltipHooks::inlineTooltip( $parserMock, 'Text', 'Tooltip' );
		$this->assertTrue( $result['noparse'] );
		$this->assertTrue( $result['isHTML'] );
		$this->assertSame( 'nowiki', $result['markerType'] );
	}

	/**
	 * @covers SimpleTooltipHooks::infoTooltip
	 */
	public function testInfoTooltip() {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();

		$tooltip = SimpleTooltipHooks::infoTooltip( $parserMock, 'Tooltip text' );

		$expectedHtml = '<span class="simple-tooltip simple-tooltip-info" data-simple-tooltip="Tooltip text"></span>';
		$this->assertEquals( $expectedHtml, $tooltip[0] );
	}

	/**
	 * @covers SimpleTooltipHooks::infoTooltip
	 */
	public function testInfoTooltipEmptyValueReturnsEmptyArray(): void {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();

		$result = SimpleTooltipHooks::infoTooltip( $parserMock, '' );
		$this->assertSame( [], $result );
	}

	/**
	 * @covers SimpleTooltipHooks::infoTooltip
	 */
	public function testInfoTooltipEscapesHtmlSpecialChars(): void {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();

		$result = SimpleTooltipHooks::infoTooltip( $parserMock, 'A &gt; B' );
		$this->assertStringContainsString( 'data-simple-tooltip="A &amp;gt; B"', $result[0] );
	}

	/**
	 * @covers SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltip() {
		$parserMock = $this->getMockBuilder( Parser::class )
						   ->disableOriginalConstructor()
						   ->getMock();

		$tooltip = SimpleTooltipHooks::imgTooltip( $parserMock, 'Image URL', 'Tooltip text' );

		$expectedHtml = '<img class="simple-tooltip simple-tooltip-img" ' .
						'data-simple-tooltip="Tooltip text" src="Image URL"></img>';
		$this->assertEquals( $expectedHtml, $tooltip[0] );
	}

	/**
	 * @covers SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipEmptyTooltipTextReturnsEmptyArray(): void {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();

		$result = SimpleTooltipHooks::imgTooltip( $parserMock, 'http://example.com/image.png', '' );
		$this->assertSame( [], $result );
	}

	/**
	 * @covers SimpleTooltipHooks::imgTooltip
	 */
	public function testImgTooltipEscapesHtmlInSrc(): void {
		$parserMock = $this->getMockBuilder( Parser::class )
							->disableOriginalConstructor()
							->getMock();

		$result = SimpleTooltipHooks::imgTooltip( $parserMock, '<script>alert(1)</script>', 'Tooltip text' );
		$this->assertStringContainsString( 'src="&lt;script&gt;alert(1)&lt;/script&gt;"', $result[0] );
	}
}
