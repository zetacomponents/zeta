<?php
include 'scripts/get-packages-for-version.php';

if ( $argc != 2 )
{
    echo "Usage:\n\tscripts/package.php <version>\n\tscripts/package.php 1.0beta1\n\n";
    die();
}
$version = $argv[1];
$fileName = "release-info/$version";
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
        echo "releases/$component/$componentVersion\n";
    }
    else
    {
        echo "trunk/$component\n";
    }
}

?>
