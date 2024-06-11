<?php

class SimpleTooltipHooks {
	/**
	 * Add libraries to resource loader
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		// Add as ResourceLoader Module
		$out->addModules( 'ext.SimpleTooltip' );
	}

	/**
	 * Register parser hooks
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
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
	 * @param Parser $parser
	 * @param string $value
	 * @return array
	 */
	public static function inlineTooltip( Parser $parser, string $value ) {
		$args = array_slice( func_get_args(), 2 );
		$title = $args[0];

		if ( !$title ) {
			return [];
		}

		$content = Sanitizer::removeSomeTags( $title );
		$content = $parser->recursiveTagParseFully( $content );
		$content = str_replace( '"', "'", $content );
		$content = trim( $content );
		$content = htmlspecialchars( $content );

		$html = '<span class="simple-tooltip simple-tooltip-inline"';

		$html .= ' data-simple-tooltip="' . $content . '"';
		$html .= '>' . htmlspecialchars( $value ) . '</span>';

		return [
			$html,
			'noparse' => true,
			'isHTML' => true,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * Parser function handler for {{#tip-info: tooltip-text }}
	 *
	 * @param Parser $parser
	 * @param string $value
	 * @return array
	 */
	public static function infoTooltip( Parser $parser, string $value ) {
		if ( !$value ) {
			return [];
		}

		$html = '<span class="simple-tooltip simple-tooltip-info"';

		$html .= ' data-simple-tooltip="' . htmlspecialchars( Sanitizer::removeSomeTags( $value ) ) . '"></span>';

		return [
			$html,
			'noparse' => true,
			'isHTML' => true,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * Parser function handler for {{#tip-img: image-url | tooltip-text }}
	 *
	 * @param Parser $parser
	 * @param string $value
	 * @return array
	 */
	public static function imgTooltip( Parser $parser, string $value ) {
		$args = array_slice( func_get_args(), 2 );
		$title = $args[0];

		if ( !$title ) {
			return [];
		}

		$imgUrl = htmlspecialchars( $value );

		$html = '<img class="simple-tooltip simple-tooltip-img"';

		$html .= ' data-simple-tooltip="' . htmlspecialchars( Sanitizer::removeSomeTags( $title ) ) . '"';
		$html .= ' src="' . $imgUrl . '"></img>';

		return [
			$html,
			'noparse' => true,
			'isHTML' => true,
			'markerType' => 'nowiki'
		];
	}
}
