#!/usr/bin/php
<?php

define( 'CHANNEL_URI',          'components.ez.no' );
define( 'PACKAGE_NAME',         'eZComponents' );
define( 'PACKAGE_SUMMARY',      'Super package to install a complete release of eZ Components.' );
define( 'PACKAGE_DESCRIPTION',  'This super package provides dependencies to every other eZ Component to install those all at once. To perform this, simply do <$ pear install -a ' . PACKAGE_NAME . '>.');
define( 'PACKAGE_LICENSE',      'New BSD');

$releasesPath = realpath( '.' . DIRECTORY_SEPARATOR . 'release-info' );

/**
 * Package file manager for package.xml 2.
 */
require_once 'PEAR/PackageFileManager2.php';

/**
 * Autoload ezc classes 
 * 
 * @param string $class_name 
 */
function __autoload( $class_name )
{
    require_once("trunk/Base/src/base.php");

    if ( strpos( $class_name, "_" ) !== false )
    {
        $file = str_replace( "_", "/", $class_name ) . ".php";
        $val = require_once( $file );
        if ( $val == 0 )
            return true;
        return false;
    }

    ezcBase::autoload( $class_name );
}

$output = new ezcConsoleOutput();
$output->formats->help->color = 'magenta';
$output->formats->info->color = 'blue';
$output->formats->info->style = 'bold';
$output->formats->version->color = 'red';

// Standard text
$output->outputLine();
$output->outputLine( "eZ Components super-package creator", 'info' );
$output->outputText( "Version: ", 'info' );
$output->outputLine( "0.1.0\n", 'version' );
$output->outputLine();

// Input handling
$input = new ezcConsoleInput();
$input->registerOption(
    new ezcConsoleOption(
        'v', 
        'version', 
        ezcConsoleInput::TYPE_STRING,
        null,
        null,
        'Version number of the release version to create.',
        'Version number of the release version to create. The number must reflect a release file with the named version number below <svn/releases/>.'
    )
);
$input->registerOption(
    new ezcConsoleOption(
        'h', 
        'help', 
        ezcConsoleInput::TYPE_NONE,
        null,
        null,
        'Create a super-package package.xml file for the given version number.',
        'This tool can reate a super-package package.xml file that has dependencies to every other component package. Provide the current releases version number to the -v parameter to run the script.'
    )
);
$input->registerOption(
    new ezcConsoleOption(
        'd',
        'debug', 
        ezcConsoleInput::TYPE_NONE,
        null,
        null,
        'Switch tool into debugging mode.',
        'Sets the tool to debugging mode. Instead of writing the package.xml file it will be dumped to stdout.'
    )
);

// Attempt to process parameters
try
{
    $input->process();
}
catch ( ezcConsoleInputException $e )
{
    die( $options->formatText( $e->getMessage(), 'failure' ) );
}

// Output help
if ($input->getOption( 'h' )->value !== false || $input->getOption( 'v' )->value === false ) 
{
    $output->outputLine( "Usage:", 'help' );
    $output->outputLine();
    $output->outputLine( "$ " . __FILE__ . " -v <version> -s <state>", 'help' );
    $output->outputLine( "Creates a super-package package file for the named release version and stability.", 'help' );
    $output->outputLine();
    $help = $input->getHelp( true );
    $table = new ezcConsoleTable( $output, 80,  2 );
    $table->options->defaultFormat = 'help';
    $table->options->defaultBorderFormat = 'help';
    foreach ( $help as $rowId => $row)
    {
        foreach ( $row as $cellId => $cell )
        {
            $table[$rowId][$cellId]->content = $cell;
        }
    }
    $table->outputTable();
    die( "\n\n" );
}

// Grab releases info
$releasePath = $releasesPath . DIRECTORY_SEPARATOR . $input->getOption( 'v' )->value;

if ( !file_exists( $releasePath ) || !is_readable( $releasePath ) )
{
    die( $output->formatText( "Release file <$releasePath> is not readable or does not exist.\n", 'failure' ) );
}

if ( ( $releaseDef = file( $releasePath ) ) === false )
{
    die( $output->formatText( "Release file <$releasePath> could not be read.", 'failure' ) );
}

// Create release dir, if not exists
$packagePath = DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'Components';
if ( !is_dir( $packagePath ) && mkdir( $packagePath, 0700, true ) === false )
{
    die( $output->formatText( "Error creating packaging directory <$packagePath>.", 'failure' ) );
}
$packagePath = realpath( $packagePath );
// Add dummy file
file_put_contents( $packagePath . DIRECTORY_SEPARATOR . 'DUMMY', 'ezc' );

// Package file manager
$pkg = new PEAR_PackageFileManager2;
$e = $pkg->setOptions(
    array(
        'packagedirectory'  => $packagePath,
        'baseinstalldir'    => 'ezc',
        'simpleoutput'      => true,
        'filelistgenerator' => 'file',
    )
);
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error creating file manager: <" . $e->getMessage() . ">.\n", 'failure' ) );

$foundPackageTag = false;
$notes = array();
foreach ( $releaseDef as $release )
{
    if ( substr( $release, 0, 1 ) === '#' ) 
    {
        continue;    
    }
    if ( !trim( $release ) == '' &&  $foundPackageTag )
    {
        $releaseData = array_map( 'trim', explode( ': ', $release ) );
        $version = str_replace( 'rc', 'RC', $releaseData[1] );
        $e = $pkg->addPackageDepWithChannel( 'required', $releaseData[0], CHANNEL_URI, $version );
        if ( PEAR::isError( $e ) )
            die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
    }
    if ( substr( $release, 0, 8 ) === 'PACKAGES' )
    {
        $foundPackageTag = true;
    }
    if ( !$foundPackageTag && trim( $release ) !== 'NOTES' )
    {
        $notes[] = $release;
    }
}

$e = $pkg->setPackage( PACKAGE_NAME );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setSummary( PACKAGE_SUMMARY );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setDescription( PACKAGE_DESCRIPTION );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setChannel( CHANNEL_URI );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$version   = $input->getOption( 'v' )->value;
$version   = str_replace( 'rc', 'RC', $version );
if ( strpos( $version, 'alpha' ) !== false )
{
    $stability = 'alpha';
}
else if ( strpos( $version, 'beta' ) !== false || strpos( $version, 'RC' ) !== false )
{
    $stability = 'beta';
}
else
{
    $stability = 'stable';
}

$e = $pkg->setReleaseStability( $stability );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setAPIStability( $stability );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setReleaseVersion( $version );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setAPIVersion( $version );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->setLicense( PACKAGE_LICENSE );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->setNotes( trim( join( '', $notes ) ) . "\n" );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->setPackageType( 'php' );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->setPhpDep( '5.2.1' );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setPearinstallerDep( '1.4.2' );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->addGlobalReplacement( 'pear-config', '@php_dir@', 'php_dir' );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->addRelease();
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->addMaintainer( 'lead', 'ezc', 'eZ components team', 'ezc@ez.no' );
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->generateContents();
if ( PEAR::isError( $e ) )
    die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$debug = $input->getOption( 'd' )->value !== false ? true : false;
if ( $debug )
{
    $e = $pkg->debugPackageFile();
    if ( PEAR::isError( $e ) )
        die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
}
else 
{
    $e = $pkg->writePackageFile();
    if ( PEAR::isError( $e ) )
        die( $output->formatText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
}

// Output success
$output->outputText( "\nSuccesfully finished operation. Thanks for using this hacky script!\n\n", 'success' );

?>
