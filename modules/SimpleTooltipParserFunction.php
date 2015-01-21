<?php

/**
 * Hooks for PlasticMW extension
 *
 * @file
 * @ingroup Extensions
 */

class SimpleTooltipParserFunction {

    /**
     * Parser function handler for {{#simple-tooltip: .. | .. }}
     *
     * @param Parser $parser
     * @param string $arg
     *
     * @return string: HTML to insert in the page.
     */
    public static function inlineTooltip( $parser, $value /* arg2, arg3, */ ) {

        $args = array_slice( func_get_args(), 2 );
        $title = $args[0] || '';

        //////////////////////////////////////////
        // BUILD HTML                           //
        //////////////////////////////////////////

        $html = '<span class="simple-tooltip simple-tooltip-inline" title="' . htmlspecialchars($title) . '">' . htmlspecialchars($value) . '</span>';

        return array(
            $html,
            'noparse' => true,
            'isHTML' => true,
            "markerType" => 'nowiki'
        );
    }

    /**
     * Parser function handler for {{#simple-tooltip: .. | .. }}
     *
     * @param Parser $parser
     * @param string $arg
     *
     * @return string: HTML to insert in the page.
     */
    public static function infoTooltip( $parser, $value /* arg2, arg3, */ ) {

        //////////////////////////////////////////
        // BUILD HTML                           //
        //////////////////////////////////////////

        $html = '<span class="simple-tooltip simple-tooltip-info" title="' . htmlspecialchars($value) . '" src=""></span>';

        return array(
            $html,
            'noparse' => true,
            'isHTML' => true,
            "markerType" => 'nowiki'
        );
    }

}


/**
 * Helper Logging Function that outputs an object as pretty JSON and kills the PHP process
 *
 * @param  [type] $object [description]
 * @return [type]         [description]
 */
function jlog($object) {
    header('Content-Type: application/json');
    print(json_encode($object, JSON_PRETTY_PRINT));
    die();
}
