<?php
/**
 * Load the base package to boot strap the autoloading
 */
require_once 'trunk/Base/src/base.php';

// {{{ __autoload()

/**
 * Autoload ezc classes 
 * 
 * @param string $class_name 
 */
function __autoload( $class_name )
{
    if ( ezcBase::autoload( $class_name ) )
    {
        return;
    }
}

class ezctutBase extends ezcBase
{
    public static function getClassLocation( $class )
    {
        class_exists( $class, true );
        return ezcBase::$autoloadArray[$class];
    }

    public static function getClassComponent( $class )
    {
        $location = self::getClassLocation( $class );
        if ( $location )
        {
            return substr( $location, 0, strpos( $location, '/' ) );
        }
        else
        {
            echo "Wrong class '{$class}', using current component: {$GLOBALS['component']}\n";
            return $GLOBALS['component'];
        }
    }
}

ini_set( 'highlight.string', '#335533' );
ini_set( 'highlight.keyword', '#0000FF' );
ini_set( 'highlight.default', '#000000' );
ini_set( 'highlight.comment', '#007700' );

// Setup console parameters
$params = new ezcConsoleInput();
$componentOption = new ezcConsoleOption( 'c', 'component', ezcConsoleInput::TYPE_STRING );
$componentOption->mandatory = true;
$componentOption->shorthelp = "The name of the component.";
$params->registerOption( $componentOption );

$targetOption = new ezcConsoleOption( 't', 'target', ezcConsoleInput::TYPE_STRING );
$targetOption->mandatory = true;
$targetOption->shorthelp = "The directory to where the generated documentation should be written.";
$params->registerOption( $targetOption );

$versionOption = new ezcConsoleOption( 'v', 'version', ezcConsoleInput::TYPE_STRING );
$versionOption->mandatory = true;
$versionOption->shorthelp = "The version of the component that should be read. E.g. trunk, 1.0rc1, etc.";
$params->registerOption( $versionOption );

$versionOption = new ezcConsoleOption( 'r', 'release', ezcConsoleInput::TYPE_STRING );
$versionOption->mandatory = true;
$versionOption->shorthelp = "The release of the components we're building this tutorial for.";
$params->registerOption( $versionOption );

// Process console parameters
try
{
    $params->process();
}
catch ( ezcConsoleOptionException $e )
{
    print( $e->getMessage(). "\n" );
    print( "\n" );

    echo $params->getSynopsis() . "\n";
    foreach ( $params->getOptions() as $option )
    {
        echo "-{$option->short}, --{$option->long}\t    {$option->shorthelp}\n";
    }

    echo "\n";
    exit();
}

$component = $params->getOption( 'component' )->value;
$version = $params->getOption( 'version' )->value;
$release = $params->getOption( 'release' )->value;

if ( $version == 'trunk' )
{
    $componentDir = "trunk/$component";
}
else
{
    $componentDir = "releases/$component/$version";
}

$output = getRstOutput( $componentDir );
$output = removeHeaderFooter( $output );
$output = addNewHeader( $component, $output, $release );
$output = addExampleLineNumbers( $output );
$output = addLinks( $component, $output, $version );
$output = addNewFooter( $output );

$targetDir = $params->getOption( 'target' )->value;
file_put_contents( "$targetDir/introduction_$component.html", $output );
// Copying images and files
`mkdir -p $targetDir/img`;
`cp $componentDir/docs/img/*.* $targetDir/img/ 2>/dev/null`;
`mkdir -p $targetDir/files`;
`cp $componentDir/docs/files/*.* $targetDir/files/ 2>/dev/null`;

function getRstOutput( $componentDir )
{
    $fileName = "$componentDir/docs/tutorial.txt";
    $output = shell_exec( "rst2html $fileName" );
    return $output;
}

function removeHeaderFooter( $output )
{
    ini_set( 'pcre.backtrack_limit', 10000000 );
    $output = preg_replace( '@.*?<body>@ms', '', $output );
    $output = preg_replace( '@<h1 class="title">eZ components - [A-Za-z]+</h1>@i', '', $output );
    $output = preg_replace( '@<\/body>.*@ms', '', $output );
    return $output;
}

function addNewFooter( $output )
{
    return $output . "\n". "<div style=\"color: #959fa8; text-align: right; font-size: 0.85em;\">Last updated: ". date( 'D, d M Y' ) . "</div>";
}

function addNewHeader( $component, $output, $version )
{
    $outputHeader = <<<FOO
<h1>$component</h1>
<b>[ <a href="/docs/api/$version/introduction_$component.html" class="menu">Tutorial</a> ]</b>
<!-- EXTRA DOCS GO HERE! -->
<b>[ <a href="/docs/api/$version/classtrees_$component.html" class="menu">Class tree</a> ]</b>
<b>[ <a href="/docs/api/$version/elementindex_$component.html" class="menu">Element index</a> ]</b>
<b>[ <a href="/docs/api/$version/changelog_$component.html" class="menu">ChangeLog</a> ]</b>
<b>[ <a href="/docs/api/$version/credits_$component.html" class="menu">Credits</a> ]</b>
<hr class="separator" />
FOO;
    return $outputHeader . $output;
}

function addLinks( $component, $output, $version )
{
//    $base = "http://ez.no/doc/components/view/$version/(file)/$component/";
    $base = "$component/";

    $output = preg_replace_callback( '@<span class="nx">(ezc[A-Z][a-zA-Z0-9]+)</span><span class="o">::</span><span class="na">([A-Za-z0-9_]+)</span>@', 'callBackFormatClassStaticMethodLink', $output );
    $output = preg_replace_callback( '@(?<!>)<span class="nx">(ezc[A-Z][a-zA-Z0-9]+)</span>@', 'callBackFormatClassLink', $output );
    $output = preg_replace_callback( '@(ezc[A-Z][a-zA-Z0-9]+)::\$([A-Za-z0-9]+)@', 'callBackFormatClassVarLink', $output );
    $output = preg_replace_callback( "@(ezc[A-Z][a-zA-Z0-9]+)::([A-Za-z0-9_]+)(?=\()@", 'callBackFormatClassStaticMethodLink', $output );
    $output = preg_replace_callback( "@(ezc[A-Z][a-zA-Z0-9]+)-(>|\&gt;)([A-Za-z0-9_]+)(?=\()@", 'callBackFormatClassDynamicMethodLink', $output );
    $output = preg_replace_callback( "@(ezc[A-Z][a-zA-Z0-9]+)::([A-Z_]+)\\b@", 'callBackFormatClassConstantLink', $output );
    $output = preg_replace_callback( "@(?<![/>])(ezc[A-Z][a-zA-Z0-9]+)@", 'callBackFormatClassLink', $output );
    $output = preg_replace_callback( "@(?<=<td>)(ezc[A-Z][a-zA-Z0-9]+)@", 'callBackFormatClassLink', $output );
    $output = preg_replace_callback( "@(?<=<dt>)(ezc[A-Z][a-zA-Z0-9]+)@", 'callBackFormatClassLink', $output );
    $output = preg_replace_callback( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z0-9]+)(</span><span style=\"color: #[0-9A-F]+\">\()@", 'callbackFormatClassDynamicMethodCodeLink', $output );
    $output = preg_replace_callback( "@(ezc[A-Z][a-zA-Z]+)(</span><span style=\"color: #[0-9A-F]+\">::</span><span style=\"color: #[0-9A-F]+\">)([A-Z_]+)@", 'callBackFormatClassConstantCodeLink', $output );
    $output = preg_replace_callback( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z0-9]+)(</li>)@", 'callBackFormatClassCodeLink', $output );
    $output = preg_replace_callback( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z0-9]+)(</span><span style=\"color: #[0-9A-Z]+\">::</span><span style=\"color: #[0-9A-F]+\">)([A-Za-z]+)(</span>)@", 'callBackFormatClassStaticMethodCodeLink', $output );
    $output = preg_replace_callback( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z0-9]+Exception)(\&nbsp;\\$)@", 'callBackFormatExceptionClassCodeLink', $output );
    return $output;
}

function callBackFormatExceptionClassCodeLink( $args )
{
    $component = ezctutBase::getClassComponent( $args[2] );
    return "{$args[1]}<a href='{$component}/{$args[2]}.html'>{$args[2]}</a>{$args[3]}";
}

function callBackFormatClassStaticMethodCodeLink( $args)
{
    $component = ezctutBase::getClassComponent( $args[2] );
    return "{$args[1]}<a href='{$component}/{$args[2]}.html#{$args[4]}'>{$args[2]}::{$args[4]}</a>{$args[5]}";
}

function callBackFormatClassCodeLink( $args )
{
    $component = ezctutBase::getClassComponent( $args[2] );
    return "{$args[1]}<a href='{$component}/{$args[2]}.html'>{$args[2]}</a>{$args[3]}";
}

function callBackFormatClassConstantCodeLink( $args )
{
    $component = ezctutBase::getClassComponent( $args[1] );
    return "<a href='{$component}/{$args[1]}.html#const{$args[3]}'>{$args[1]}::{$args[3]}</a>";
}

function callbackFormatClassDynamicMethodCodeLink( $args )
{
    $component = ezctutBase::getClassComponent( $args[2] );
    return "{$args[1]}<a href='{$component}/{$args[2]}.html'>{$args[2]}</a>{$args[3]}";
}

function callBackFormatClassVarLink( $args )
{
    $component = ezctutBase::getClassComponent( $args[1] );
    return "<a href='{$component}/{$args[1]}.html#\${$args[2]}'>{$args[0]}</a>";
}

function callBackFormatClassStaticMethodLink( $args )
{
    $component = ezctutBase::getClassComponent( $args[1] );
    return "<a href='{$component}/{$args[1]}.html#{$args[2]}'>{$args[0]}</a>";
}

function callBackFormatClassDynamicMethodLink( $args )
{
    $component = ezctutBase::getClassComponent( $args[1] );
    return "<a href='{$component}/{$args[1]}.html#{$args[3]}'>{$args[0]}</a>";
}

function callBackFormatClassConstantLink( $args )
{
    $component = ezctutBase::getClassComponent( $args[1] );
    return "<a href='{$component}/{$args[1]}.html#const{$args[2]}'>{$args[0]}</a>";
}

function callBackFormatClassLink( $args )
{
    $component = ezctutBase::getClassComponent( $args[1] );
    return "<a href='{$component}/{$args[1]}.html'>{$args[0]}</a>";
}

function addExampleLineNumbers( $output )
{
    return preg_replace_callback( '@<pre class=\"literal-block\">(.+?)<\/pre>@ms', 'callbackAddLineNumbers', $output );
}

$lineNr = 0;

function callbackAddLineNr( $args )
{
    global $lineNr;

    $nrString = str_replace( ' ', '&nbsp;', sprintf( '%3d', $lineNr ) );
    if ( $lineNr == 0 )
    {
        $val = '';
    }
    else
    {
        $val = $nrString . ". {$args[1]}\n";
    }
    $lineNr++;
    return $val;
}

function callbackAddLineNumbers( $args )
{
    global $lineNr;

    $lineNr = 0;
    
    if ( strstr( $args[1], '&lt;?php' ) !== false )
    {
        file_put_contents( "/tmp/foo2.php", trim( html_entity_decode( $args[1] ) ) );
        `pygmentize -o /tmp/foo3.html /tmp/foo2.php`;
        $contents = file( "/tmp/foo3.html" );
        // strip out BS at start and end
        $contents[0] = str_replace( '<div class="highlight"><pre>', '', $contents[0] );
        unset( $contents[count( $contents ) - 1] );
        $listing = <<<ENDT
<ol class="example">
 
ENDT;
        foreach ( $contents as $line )
        {
            $listing .= "<li>{$line}</li>";
        }

        $listing .= "</ol>\n";
        return $listing;
    } else {
        return $args[0];
    }
}
