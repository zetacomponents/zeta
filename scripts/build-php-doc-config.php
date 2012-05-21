<?php
include 'scripts/get-packages-for-version.php';

if ( $argc < 4 )
{
    echo "Usage:\n\tscripts/build-php-doc-config.php <sourcedir> <targetdir> <releaseversion> <source:on off>\n\tscripts/package.php /home/derick/dev/ezcomponents 1.0beta1 trunk on\n\n";
    die();
}
$sourcedir = $argv[1];
$target = $argv[2];
$releaseversion = $argv[3];
$source = $argv[4];
$fileName = "release-info/$releaseversion";
if ( !file_exists( "$fileName" ) )
{
    echo "The releases file <$fileName> does not exist!\n\n";
    die();
}

$directories = '';

$elements = fetchVersionsFromReleaseFile( $fileName );

foreach ( $elements as $component => $componentVersion )
{
    if ( $componentVersion != 'trunk' )
    {
        $componentVersion = "releases/$component/$componentVersion";
    }
    else
    {
        $componentVersion = "trunk/$component";
    }
    $directories .= "$sourcedir/$componentVersion,";
}

// strip last ,
$directories = substr( $directories, 0, -1 );

echo <<<ECHOEND
[Parse Data]
title = eZ Components Manual
hidden = false
parseprivate = off
javadocdesc = off
defaultcategoryname = NoCategoryName
defaultpackagename = NoPackageName

target = $target
directory = $directories

ignore = autoload/,*autoload.php,tests/,docs/,design/
output=HTML:ezComp:ezdocs
sourcecode = $source

ECHOEND;

?>
