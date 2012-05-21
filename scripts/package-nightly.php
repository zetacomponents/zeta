#!/usr/bin/env php
<?php
include 'scripts/get-packages-for-version.php';

if ( $argc != 2 )
{
    echo "Usage:\n\tscripts/package-nightly.php <version>\n\tscripts/package-nightly.php nightly\n\n";
    die();
}
$version = $argv[1];
$fileName = "release-info/latest";
if ( !file_exists( "$fileName" ) )
{
    echo "The releases file <$fileName> does not exist!\n\n";
    die();
}

define( 'EZC_NIGHTLY_VERSION_APPEND', '.0.1-' . $version );

$basePackageDir = "/tmp/ezc". md5( time() );
$packageDir = $basePackageDir . "/ezcomponents-$version";
$packageList = array();

mkdir( $packageDir, 0777, true );

grabChangelog( $fileName, $packageDir );
createReleaseInfoFile( $packageDir, $fileName, $version );
addPackages( $fileName, $packageDir );
setupAutoload( $packageDir, $packageList );
addAditionalFiles( $packageDir, $packageList );
setBaseNonDevel( $packageDir );

echo "Creating Archives: ";
`cd $basePackageDir; tar cvjf /tmp/ezcomponents-$version.tar.bz2 ezcomponents-$version`;
echo "tar.bz2 ";
`cd $basePackageDir; zip -r /tmp/ezcomponents-$version.zip ezcomponents-$version`;
echo "zip ";
echo "Done\n";

echo "Creating Lite Archives: ";
`cd $basePackageDir; tar cvjf /tmp/ezcomponents-$version-lite.tar.bz2 --exclude-tag-under=suite.php --exclude-tag-under=class_diagram.png --exclude-tag-all=suite.php --exclude-tag-all=class_diagram.png --exclude=docs ezcomponents-$version`;
echo "tar.bz2 ";
`cd $basePackageDir; zip -r /tmp/ezcomponents-$version-lite.zip ezcomponents-$version -xi \*/docs/\* \ \*/design/\* \ \*/tests/\*`;
echo "zip ";
echo "Done\n";

echo "Generating HTML version of changelog: ";
`cd $basePackageDir; rst2html ezcomponents-$version/ChangeLog > /tmp/ezcomponents-$version.changelog.html`;
echo "Done\n";

echo "Copying release files to nightly: ";
mkdir( 'nightly' );
`mv -v /tmp/ezcomponents-$version* nightly/`;
echo "Done\n\n";
`rm -rf $basePackageDir`;

function grabChangelog( $fileName, $packageDir )
{
    // Open ChangeLog file
    $fp = fopen( "$packageDir/ChangeLog", "w" );
    fwrite( $fp, "Nightly release.\n" );
    fclose( $fp );
}

function createReleaseInfoFile( $packageDir, $fileName, $versionNr )
{
    $elements = fetchVersionsFromReleaseFile( $fileName );

    $xw = new XmlWriter;
    $xw->openUri( "$packageDir/release-info.xml" );
    $xw->setIndent( true );
    $xw->startDocument( '1.0', 'utf-8' );
    $xw->startElement( 'release-info' );
    $xw->writeElement( 'version', $versionNr );
    $xw->startElement( 'deps' );
    $xw->writeElement( 'php', trim( file_get_contents( 'scripts/php-version' ) ) );
    $xw->startElement( 'packages' );

    foreach ( $elements as $component => $versionNr )
    {
        $dependencies = fetchVersionsFromReleaseFile( $versionNr == 'trunk' ? "trunk/$component/DEPS" : "releases/$component/$versionNr/DEPS" );

        $xw->startElement( 'package' );
        $xw->writeAttribute( 'version', $versionNr . EZC_NIGHTLY_VERSION_APPEND );
        $xw->writeAttribute( 'name', $component );
        if ( count( $dependencies ) > 0 )
        {
            $xw->startElement( 'deps' );
            foreach( $dependencies as $dependency => $depVerNr )
            {
                $xw->startElement( 'package' );
                $xw->writeAttribute( 'version', $depVerNr );
                $xw->writeAttribute( 'name', $dependency );
                $xw->endElement();
            }
            $xw->endElement();
        }
        $xw->endElement();
    }
    $xw->endElement();
    $xw->endElement();
    $xw->endElement();
    $xw->endDocument();
}

function addPackages( $fileName, $packageDir )
{
    $elements = fetchVersionsFromReleaseFile( $fileName );

    echo "Exporting packages from SVN: \n";
    foreach ( $elements as $component => $versionNr )
    {
        addPackage( $packageDir, $component, $versionNr . EZC_NIGHTLY_VERSION_APPEND );
    }
}

function addPackage( $packageDir, $name, $version )
{
    echo sprintf( '* %-40s %-12s: ', $name, $version );

    $dirName = "trunk/$name";

    if ( !is_dir( $dirName ) )
    {
        echo "release directory not found\n";
        return false;
    }
    $GLOBALS['packageList'][] = $name;

    /* exporting */
    echo "E ";
    `svn export http://svn.ez.no/svn/ezcomponents/$dirName $packageDir/$name`;

    /* remove crappy files */
    echo "RR ";
    @unlink( "$packageDir/$name/review.txt" );
    
    echo "Done\n";
}

function setupAutoload( $packageDir, $packageList )
{
    echo "Setting up autoload structure: ";
    mkdir( "$packageDir/autoload" );
    foreach ( $packageList as $packageName )
    {
        echo "$packageName ";
        $glob = glob( "$packageDir/$packageName/src/*_autoload.php" );
        foreach( $glob as $fileName )
        {
            $targetName = basename( $fileName );
            copy( $fileName, "$packageDir/autoload/$targetName" );
            unlink( $fileName );
        }
    }
    echo "\n";
}

function addAditionalFiles( $packageDir, $packageList )
{
    echo "Adding additional files: ";
    echo "LICENSE ";
    copy( "LICENSE", "$packageDir/LICENSE" );

    echo "descriptions.txt ";
    $f = fopen( "$packageDir/descriptions.txt", "w" );
    foreach ( $packageList as $packageName )
    {
        $descFileName = "$packageDir/$packageName/DESCRIPTION";
        if ( file_exists( $descFileName ) )
        {
            fwrite( $f, "$packageName\n" . str_repeat( '-', strlen( $packageName ) ) . "\n" );
            $desc = file_get_contents( $descFileName );
            fwrite( $f, "$desc\n" );
        }
    }
    fclose( $f );

    echo "\n";
}

function setBaseNonDevel( $packageDir )
{
    echo "Configuring Base package in release mode: ";
    file_put_contents( "$packageDir/Base/src/base.php", str_replace( "libraryMode = \"devel\"", "libraryMode = \"tarball\"", file_get_contents( "$packageDir/Base/src/base.php" ) ) );
    echo "Done\n";
}

?>