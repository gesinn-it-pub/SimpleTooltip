<?php
/**
 * SimpleTooltip Extension
 * Provides basic tooltips, supporting inline text and info icons
 *
 * For more info see http://mediawiki.org/wiki/Extension:SimpleTooltip
 *
 * @file
 * @ingroup Extensions
 * @author Simon Heimler, 2015
 * @license GNU General Public Licence 2.0 or later
 */

//////////////////////////////////////////
// VARIABLES                            //
//////////////////////////////////////////

$dir         = dirname( __FILE__ );
$dirbasename = basename( $dir );


//////////////////////////////////////////
// CONFIGURATION                        //
//////////////////////////////////////////

$wgSimpleTooltipSubmitText = 'NEW';


//////////////////////////////////////////
// CREDITS                              //
//////////////////////////////////////////

$wgExtensionCredits['other'][] = array(
   'path'           => __FILE__,
   'name'           => 'SimpleTooltip',
   'author'         => array('Simon Heimler'),
   'version'        => '0.1.0',
   'url'            => 'https://www.mediawiki.org/wiki/Extension:SimpleTooltip'
);


//////////////////////////////////////////
// RESOURCE LOADER                      //
//////////////////////////////////////////

$wgResourceModules['ext.SimpleTooltip'] = array(
   'scripts' => array(
      'lib/jquery.tooltipster.js',
      'lib/SimpleTooltip.js',
   ),
   'styles' => array(
      'lib/tooltipster.css',
      'lib/SimpleTooltip.css',
   ),
   'dependencies' => array(
      // No dependencies
   ),
   'localBasePath' => __DIR__,
   'remoteExtPath' => 'SimpleTooltip',
);


//////////////////////////////////////////
// LOAD FILES                           //
//////////////////////////////////////////

// Register i18n
$wgExtensionMessagesFiles['SimpleTooltipMagic'] = $dir . '/SimpleTooltip.i18n.magic.php';

// Register files
$wgAutoloadClasses['SimpleTooltipParserFunction'] = $dir . '/modules/SimpleTooltipParserFunction.php';

// Register hooks
$wgHooks['BeforePageDisplay'][] = 'SimpleTooltipOnBeforePageDisplay';
$wgHooks['ParserFirstCallInit'][] = 'SimpleTooltipOnParserFirstCallInit';



//////////////////////////////////////////
// HOOK CALLBACKS                       //
//////////////////////////////////////////

/**
* Add libraries to resource loader
*/
function SimpleTooltipOnBeforePageDisplay( OutputPage &$out, Skin &$skin ) {

  // Add as ResourceLoader Module
  $out->addModules('ext.SimpleTooltip');

  return true;
}

/**
* Register parser hooks
*
* See also http://www.mediawiki.org/wiki/Manual:Parser_functions
*/
function SimpleTooltipOnParserFirstCallInit( &$parser ) {

  // Register parser functions
  $parser->setFunctionHook('simple-tooltip', 'SimpleTooltipParserFunction::inlineTooltip');
  $parser->setFunctionHook('tip-text', 'SimpleTooltipParserFunction::inlineTooltip');

  $parser->setFunctionHook('simple-tooltip-info', 'SimpleTooltipParserFunction::infoTooltip');
  $parser->setFunctionHook('tip-info', 'SimpleTooltipParserFunction::infoTooltip');

  return true;
}

