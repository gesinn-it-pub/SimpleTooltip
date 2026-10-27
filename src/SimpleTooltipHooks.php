<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\SimpleTooltip;

use Html;
use MediaWiki\MediaWikiServices;
use OutputPage;
use Parser;
use Skin;

class SimpleTooltipHooks {
	/**
	 * Add libraries to resource loader
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ): void {
		// Add as ResourceLoader Module
		$out->addModules( 'ext.SimpleTooltip' );
	}

	/**
	 * Register parser hooks
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ): void {
		// Register parser functions
		$parser->setFunctionHook( 'simple-tooltip', [ __CLASS__, 'inlineTooltip' ] );
		$parser->setFunctionHook( 'tip-text', [ __CLASS__, 'inlineTooltip' ] );

		$parser->setFunctionHook( 'simple-tooltip-info', [ __CLASS__, 'infoTooltip' ] );
		$parser->setFunctionHook( 'tip-info', [ __CLASS__, 'infoTooltip' ] );

		$parser->setFunctionHook( 'simple-tooltip-img', [ __CLASS__, 'imgTooltip' ] );
		$parser->setFunctionHook( 'tip-img', [ __CLASS__, 'imgTooltip' ] );
	}

	/**
	 * Parser function handler for {{#tip-text: inline-text | tooltip-text }}
	 *
	 * Both texts may contain wikitext.
	 *
	 * @param Parser $parser
	 * @param string $value
	 * @param string $title
	 * @return array
	 */
	public static function inlineTooltip( Parser $parser, string $value, string $title = '' ): array {
		if ( trim( $title ) === '' ) {
			return [];
		}

		$html = Html::rawElement(
			'span',
			[
				'class' => 'simple-tooltip simple-tooltip-inline',
				'data-simple-tooltip' => self::parseTooltipText( $parser, $title ),
			],
			$parser->recursiveTagParse( $value )
		);

		return self::htmlResult( $html );
	}

	/**
	 * Parser function handler for {{#tip-info: tooltip-text }}
	 *
	 * @param Parser $parser
	 * @param string $value
	 * @return array
	 */
	public static function infoTooltip( Parser $parser, string $value ): array {
		if ( trim( $value ) === '' ) {
			return [];
		}

		$html = Html::element(
			'span',
			[
				'class' => 'simple-tooltip simple-tooltip-info',
				'data-simple-tooltip' => self::parseTooltipText( $parser, $value ),
			]
		);

		return self::htmlResult( $html );
	}

	/**
	 * Parser function handler for {{#tip-img: image | tooltip-text }}
	 *
	 * The image is a file name on this wiki (with or without the "File:" prefix) or, if the wiki
	 * allows external images, an http(s) URL.
	 *
	 * @param Parser $parser
	 * @param string $value
	 * @param string $title
	 * @return array
	 */
	public static function imgTooltip( Parser $parser, string $value, string $title = '' ): array {
		if ( trim( $title ) === '' ) {
			return [];
		}

		$imgUrl = self::resolveImageUrl( $parser, trim( $value ) );
		if ( $imgUrl === null ) {
			return self::htmlResult( Html::element(
				'span',
				[ 'class' => 'error simple-tooltip-error' ],
				wfMessage( 'simpletooltip-img-invalid', trim( $value ) )->plain()
			) );
		}

		$html = Html::element(
			'img',
			[
				'class' => 'simple-tooltip simple-tooltip-img',
				'data-simple-tooltip' => self::parseTooltipText( $parser, $title ),
				'src' => $imgUrl,
				'alt' => '',
			]
		);

		return self::htmlResult( $html );
	}

	/**
	 * Parse the wikitext of a tooltip. The result is HTML that ends up in an escaped attribute.
	 */
	private static function parseTooltipText( Parser $parser, string $wikitext ): string {
		return trim( $parser->recursiveTagParseFully( $wikitext ) );
	}

	/**
	 * Resolve a local file name or an allowed external URL to an image URL.
	 *
	 * @return string|null null if the image is missing or not allowed
	 */
	private static function resolveImageUrl( Parser $parser, string $image ): ?string {
		if ( preg_match( '!^(https?:)?//!i', $image ) ) {
			return self::isExternalImageAllowed( $parser, $image ) ? $image : null;
		}

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $image, NS_FILE );
		if ( $title === null ) {
			return null;
		}

		// Also records the file as used by the page, so the parser cache is invalidated with it
		[ $file ] = $parser->fetchFileAndTitle( $title );
		return $file && $file->exists() ? $file->getUrl() : null;
	}

	private static function isExternalImageAllowed( Parser $parser, string $url ): bool {
		$options = $parser->getOptions();
		if ( $options->getAllowExternalImages() ) {
			return true;
		}

		foreach ( (array)$options->getAllowExternalImagesFrom() as $prefix ) {
			if ( $prefix !== '' && strpos( $url, $prefix ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $html Already safe HTML (built with Html or from parser output)
	 * @return array
	 */
	private static function htmlResult( string $html ): array {
		return [
			$html,
			'noparse' => true,
			'isHTML' => true,
			'markerType' => 'nowiki'
		];
	}
}
