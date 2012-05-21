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

// }}} __autoload()

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

$outputFilenameOption = new ezcConsoleOption( 'o', 'outputfilename', ezcConsoleInput::TYPE_STRING );
$outputFilenameOption->shorthelp = "The name of the file to render to.";
$params->registerOption( $outputFilenameOption );

$filenameOption = new ezcConsoleOption( 'f', 'filename', ezcConsoleInput::TYPE_STRING );
$filenameOption->mandatory = true;
$filenameOption->shorthelp = "The name of the file to render.";
$params->registerOption( $filenameOption );

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
$filename = $params->getOption( 'filename' )->value;
$version = $params->getOption( 'version' )->value;
$outputFilename = $params->getOption( 'outputfilename' )->value;

$output = getRstOutput( $filename );
if ( $output === false )
{
    echo "  FAILED\n";
    exit( 1 );
}
$output = removeHeaderFooter( $output );
$output = addNewHeader( $component, $output, $filename );
$output = addExampleLineNumbers( $output );
$output = addLinks( $component, $output, $version );
$output = addNewFooter( $output );

$targetDir = $params->getOption( 'target' )->value;

if ( $outputFilename )
{
    $filename = $outputFilename;
}
else
{
    $filenameParts = explode( '/', $filename );
    $filename = array_splice( $filenameParts, -1 );
    $filename = basename( $filename[0], '.txt' );
    $filename = "{$component}_$filename.html";
}
//echo "$targetDir/{$component}_$filename.html\n";
file_put_contents( "$targetDir/$filename", $output );
echo " OK\n";

function getRstOutput( $fileName )
{
    exec( "rst2html $fileName", $output, $returnCode );
    return $returnCode == 0 ? join( "\n", $output ) : false;
}

function removeHeaderFooter( $output )
{
    $output = substr( $output, strpos( $output, '<body>' ) + 7 );
    $output = preg_replace( '@<h1 class="title">eZ components - [A-Za-z]+</h1>@', '', $output );
    $output = preg_replace( '@<\/body>.*@ms', '', $output );
    return $output;
}

function addNewFooter( $output )
{
    return $output . "\n". "<div style=\"color: #959fa8; text-align: right; font-size: 0.85em;\">Last updated: ". date( 'D, d M Y' ) . "</div>";
}

function addNewHeader( $component, $output, $filename )
{
    $exploded = explode( '/', $filename );
    $filename = array_splice( $exploded, -1 );
    $filename = basename( $filename[0], '.txt' );
    $title = ucfirst( $filename );
    $outputHeader = <<<FOO
<div class="attribute-heading"><h1>$component: $title</h1></div>


<b>[ <a href="introduction_$component.html" class="menu">Tutorial</a> ]</b>
<!-- EXTRA DOCS GO HERE! -->
<b>[ <a href="classtrees_$component.html" class="menu">Class tree</a> ]</b>
<b>[ <a href="elementindex_$component.html" class="menu">Element index</a> ]</b>
<b>[ <a href="changelog_$component.html" class="menu">ChangeLog</a> ]</b>
<b>[ <a href="credits_$component.html" class="menu">Credits</a> ]</b>
<hr class="separator" />
FOO;
    return $outputHeader . $output;
}

function addLinks( $component, $output, $version )
{
//    $base = "http://ez.no/doc/components/view/$version/(file)/$component/";
    $base = "$component/";

    $output = preg_replace( '@(ezc[A-Z][a-zA-Z0-9]+)::\$([A-Za-z0-9]+)@', "<a href='{$base}\\1.html#\$\\2'>\\0</a>", $output );
    $output = preg_replace( "@(ezc[A-Z][a-zA-Z0-9]+)::([A-Za-z0-9_]+)(?=\()@", "<a href='{$base}\\1.html#\\2'>\\0</a>", $output );
    $output = preg_replace( "@(ezc[A-Z][a-zA-Z0-9]+)-(>|\&gt;)([A-Za-z0-9_]+)(?=\()@", "<a href='{$base}\\1.html#\\3'>\\0</a>", $output );
    $output = preg_replace( "@(ezc[A-Z][a-zA-Z0-9]+)::([A-Z_]+)\\b@", "<a href='{$base}\\1.html#const\\2'>\\0</a>", $output );
    $output = preg_replace( "@(?<![/>])(ezc[A-Z][a-zA-Z0-9]+)@", "<a href='{$base}\\1.html'>\\0</a>", $output );
    $output = preg_replace( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z0-9]+)(</span><span style=\"color: #[0-9A-F]+\">\()@", "\\1<a href='{$base}\\2.html'>\\2</a>\\3", $output );
    $output = preg_replace( "@(ezc[A-Z][a-zA-Z]+)(</span><span style=\"color: #[0-9A-F]+\">::</span><span style=\"color: #[0-9A-F]+\">)([A-Z_]+)@", "<a href='{$base}\\1.html#const\\3'>\\1::\\3</a>", $output );
    $output = preg_replace( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z0-9]+)(</li>)@", "\\1<a href='{$base}\\2.html'>\\2</a>\\3", $output );
    $output = preg_replace( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z0-9]+)(</span><span style=\"color: #[0-9A-Z]+\">::</span><span style=\"color: #[0-9A-F]+\">)([A-Za-z]+)(</span>)@", "\\1<a href='{$base}\\2.html#\\4'>\\2::\\4</a>\\5", $output );
    $output = preg_replace( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z0-9]+Exception)(\&nbsp;\\$)@", "\\1<a href='{$base}\\2.html'>\\2</a>\\3", $output );
    return $output;
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
        $listing = '<pre class="listing">';
        $highlighted = highlight_string( html_entity_decode( $args[1] ), true );
        $highlighted = preg_replace( '@^<code><span style="color: #000000">.<br />@ms', '<code><br />', $highlighted );
        $highlighted = preg_replace( '@(<span style="color: #[0-9A-F]+">)(.*?)((<br />)+)(.*?)(</span>)@ms', '\1\2\6\3\1\5\6', $highlighted );
        $highlighted = preg_replace( '@(<span style="color: #[0-9A-F]+">)(.+?)(<br />)(</span>)@ms', '\1\2\4\3', $highlighted );
        $highlighted = preg_replace( '@<span style="color: #[0-9A-F]+"></span>@', '', $highlighted );
        $highlighted = preg_replace( '@</span><br />.</code>$@ms', "</code>", $highlighted );
        $highlighted = preg_replace_callback( '@(.*?)<br />@', "callbackAddLineNr", $highlighted );
        $listing .= $highlighted . '</pre>';
        return $listing;
    } else {
        return $args[0];
    }
}
