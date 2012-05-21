#!/usr/local/bin/php
<?php
include 'scripts/get-packages-for-version.php';

if ( $argc != 3 )
{
    echo "Usage:\n\tscripts/package.php <oldversion> <newversion>\n\tscripts/package.php 1.1beta2 1.1rc1\n\n";
    die();
}
$oldVersion = $argv[1];
$newVersion = $argv[2];
$fileName = "release-info/$oldVersion";
if ( !file_exists( "$fileName" ) )
{
    echo "The releases file <$fileName> does not exist!\n\n";
    die();
}
$oldPackageVersions = fetchVersionsFromReleaseFile( $fileName );

$fileName = "release-info/$newVersion";
if ( !file_exists( "$fileName" ) )
{
    echo "The releases file <$fileName> does not exist!\n\n";
    die();
}
$newPackageVersions = fetchVersionsFromReleaseFile( $fileName );

$output = '';
foreach ( $newPackageVersions as $package => $newVersion )
{
    if ( !isset( $oldPackageVersions[$package] ) )
    {
        $output .= "$package\n". str_repeat( "=", strlen( $package ) ). "\n\n";
        $output .= grabFullChangelog( $package, $newVersion );
        continue;
    }
    $oldVersion = $oldPackageVersions[$package];
    if ( $oldVersion == $newVersion )
    {
        continue;
    }
    
    $output .= "\n";
    $output .= "$package\n". str_repeat( "=", strlen( $package ) ). "\n\n";
    $output .= grabPartialChangelog( $package, $oldVersion, $newVersion );
}
echo $output;

function grabFullChangelog( $package, $version )
{
    return file_get_contents( "releases/$package/$version/ChangeLog" );
}

function grabPartialChangelog( $package, $oldVersion, $newVersion )
{
    $data = array();
    $data = file( "releases/$package/$newVersion/ChangeLog" );
    $changelogData = array();
    $versionFound = false;
    foreach ( $data as $line )
    {
        if ( $versionFound && preg_match( "@^$oldVersion\s-\s@", $line ) )
        {
            $versionFound = false;
        }
        if ( preg_match( "@^$newVersion\s-\s@", $line ) )
        {
            $versionFound = true;
        }
        if ( $versionFound )
        {
            $changelogData[] = $line;
        }
    }
    $data = trim( implode( '', $changelogData ) ) . "\n\n\n";
    return $data;
}
?>
