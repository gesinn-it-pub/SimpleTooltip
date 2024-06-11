<?php
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

use PHPUnit\Framework\TestCase;
use Parser;
use SimpleTooltipHooks;

/**
 * @group SimpleTooltip
 * 
 */
class SimpleTooltipHooksTest extends TestCase {

	public function testInlineTooltip() {
		// Create a mock for the Parser class
		$parserMock = $this->getMockBuilder(Parser::class)
							->disableOriginalConstructor()
							->getMock();

		// Set up the mock behavior for recursiveTagParseFully
		$parserMock->expects($this->once())
					->method('recursiveTagParseFully')
					->willReturn('Tooltip content');

		// Call the inlineTooltip method with some test values
		$tooltip = SimpleTooltipHooks::inlineTooltip($parserMock, 'Inline text', 'Tooltip text');

		// Assert that the returned HTML contains the expected tooltip content
		$expectedHtml = '<span class="simple-tooltip simple-tooltip-inline" data-simple-tooltip="Tooltip content">Inline text</span>';
		$this->assertEquals($expectedHtml, $tooltip[0]);

	}

	public function testInfoTooltip() {
		// Create a mock for the Parser class
		$parserMock = $this->getMockBuilder(Parser::class)
							->disableOriginalConstructor()
							->getMock();

		// Call the infoTooltip method with some test values
		$tooltip = SimpleTooltipHooks::infoTooltip($parserMock, 'Tooltip text');

		// Assert that the returned HTML contains the expected tooltip content
		$expectedHtml = '<span class="simple-tooltip simple-tooltip-info" data-simple-tooltip="Tooltip text"></span>';
		$this->assertEquals($expectedHtml, $tooltip[0]);
	}

	public function testImgTooltip() {
		// Create a mock for the Parser class
		$parserMock = $this->getMockBuilder(Parser::class)
						   ->disableOriginalConstructor()
						   ->getMock();

		// Call the imgTooltip method with some test values
		$tooltip = SimpleTooltipHooks::imgTooltip($parserMock, 'Image URL', 'Tooltip text');

		// Assert that the returned HTML contains the expected tooltip content
		$expectedHtml = '<img class="simple-tooltip simple-tooltip-img" data-simple-tooltip="Tooltip text" src="Image URL"></img>';
		$this->assertEquals($expectedHtml, $tooltip[0]);
	}
}
