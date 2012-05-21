<?php

require_once dirname( __FILE__ ) . '/../../trunk/Base/src/ezc_bootstrap.php';

$debug = !( isset( $argv[1] ) && $argv[1] === 'go' );

define( 'BASE_PATH', '/local/ezctest' );

define( 'PHP_BASE_PATH', BASE_PATH . '/php' );

define( 'CC_PATH', BASE_PATH . '/opt/cruisecontrol' );
define( 'CC_PROJECT_PATH', CC_PATH . '/projects' );

define( 'PHP_CC_VERSION', '5.3-dev' );

$ignoreComponents = array(
    'UnitTest',
);

// Dependencies not reflected in DEPS files (mainly test deps)
$additionalDependenecies = array(
    'Base' => array(
        'File',
        'Translation',
    ),
    'Debug' => array(
        'EventLog',
    ),
    'EventLog' => array(
        'Database',
        'EventLogDatabaseTiein',
    ),
    'PersistentObject' => array(
        'DatabaseSchema',
    ),
    'TreePersistentObjectTiein' => array(
        'Database',
        'TreeDatabaseTiein',
    ),
    'WorkflowEventLogTiein' => array(
        'Database',
        'WorkflowDatabaseTiein',
    ),
    'Webdav' => array(
        'File'
    ),
);

// Data source names
$dsns = array(
    'mysql'  => 'mysql://ezctest@localhost/ezctest',
    // @TODO: An SQLite on disc DB should also be provided!
    // 'sqlite' => 'sqlite://$\{$basedir}/build/tmp/test.sqlite',
    'sqlite' => 'sqlite://:memory:',
);

// Generate list of PHP installations (5.* ensures to not grab the pear/ dir)
$phps = glob( PHP_BASE_PATH . '/5.*', GLOB_ONLYDIR );

array_walk(
    $phps,
    function ( &$value, $key )
    {
        $value = basename( $value );
    }
);

/*
 *
 * Do not change anything below this line!
 *
 */

$ignoreDirs = array_merge(
    array(
        'autoload',
        'extract',
        'PHPUnit',
        'run-test-tmp',
        '.svn',
    ),
    $ignoreComponents
);

$dirsToCreate = array(
    '',
    '/source',
    '/trunk',
    '/build',
    '/build/api',
    '/build/coverage',
    '/build/logs',
    '/build/tmp',
);

$config               = ezcTemplateConfiguration::getInstance();
$config->templatePath = dirname( __FILE__ ) . '/templates';
$config->compilePath  = dirname( __FILE__ ) . '/templates_c';

$dirsToCreate = array(
    '',
    '/source',
    '/build',
    '/build/api',
    '/build/coverage',
    '/build/logs',
    '/build/tmp',
);

$componentPaths = glob( CC_PROJECT_PATH . '/ezc/source/trunk/*', GLOB_ONLYDIR );

$componentNames = array();

foreach ( $componentPaths as $componentPath )
{
    $componentName = basename( $componentPath );
    if ( in_array( $componentName, $ignoreDirs ) )
    {
        // Skip non-components
        continue;
    }
    $componentNames[] = $componentName;

    $componentDepsFile = $componentPath . '/DEPS';
    $componentDeps = is_file( $componentDepsFile ) ? file( $componentDepsFile ) : array();
    foreach ( $componentDeps as $id => $depEntry )
    {
        $depParts = explode( ':', $depEntry );
        $componentDeps[$id] = trim( $depParts[0] );
    }

    if ( isset( $additionalDependenecies[$componentName] ) )
    {
        $componentDeps = array_merge( $componentDeps, $additionalDependenecies[$componentName] );
    }

    // Add additional deps
    if ( strpos( $componentName, 'Database' ) !== false || in_array( 'Database', $componentDeps )  )
    {
        $componentDeps[] = 'Database';
        $componentDeps[] = 'DatabaseSchema';
        $componentDeps[] = 'PersistentObject';
    }
    $componentDeps[] = 'Base';
    $componentDeps[] = 'UnitTest';
    $componentDeps[] = 'ConsoleTools';

    // Remove duplicate deps
    $componentDeps = array_unique( $componentDeps );
    
    $ccComponentPath = CC_PROJECT_PATH . "/ezc$componentName";

    foreach ( $dirsToCreate as $createPath )
    {
        $path = $ccComponentPath . $createPath;
        if ( !is_dir( $path ) )
        {
            mkdir( $path ) || die( "Coulnd not create component path $path.\n" );
            // echo "Would create dir '$path'\n";
        }
    }

    // Symlink scripts/
    $ccLinkPath = $ccComponentPath . '/source/scripts';
    if ( !is_link( $ccLinkPath ) )
    {
        symlink( CC_PROJECT_PATH . '/ezc/source/scripts', $ccLinkPath ) || die( "Could not create link $ccLinkPath.\n" );
    }

    foreach ( $componentDeps as $dep )
    {
        $depPath = "$ccComponentPath/source/trunk/$dep";
        $depSvn  = "http://svn.ez.no/svn/ezcomponents/trunk/$dep";

        if ( !is_dir( $depPath ) )
        {
            echo "New checkout $depPath\n";
            `svn co $depSvn $depPath`;
        }
        else
        {
            echo "Found dep $depPath\n";
        }
    }
    $coPath  = "$ccComponentPath/source/trunk/$componentName";
    $svnPath = "http://svn.ez.no/svn/ezcomponents/trunk/$componentName";
    if ( !is_dir( $coPath ) )
    {
        `svn co $svnPath $coPath`;
    }

    $tpl                      = new ezcTemplate();
    $tpl->send->componentName = $componentName;
    $tpl->send->componentDeps = $componentDeps;
    $tpl->send->needsDatabase = in_array( 'Database', $componentDeps ) || strpos( $componentName, 'Database' );
    $tpl->send->phps          = $phps;
    $tpl->send->phpBasePath   = PHP_BASE_PATH;
    $tpl->send->dsns          = $dsns;
    $tpl->send->phpCcVersion  = PHP_CC_VERSION;
   
    if ( $debug )
    { 
        echo " Would put the following to $ccComponentPath/build.xml:\n";
        echo $tpl->process( 'build.xml.tpl' ) . "\n\n";
    }
    else
    {
        file_put_contents( $ccComponentPath . '/build.xml', $tpl->process( 'build.xml.tpl' ) ) || die( "Could not write build.xml for component $componentName.\n" );
    }

    $componentsDeps[$componentName] = $componentDeps;
}

$tpl = new ezcTemplate();
$tpl->send->components = $componentNames;
$tpl->send->deps = $componentsDeps;

if ( $debug )
{
    echo CC_PATH  . '/config.xml' . "\n";
    echo $tpl->process( 'config.xml.tpl' ) . "\n\n";
}
else
{
    file_put_contents( CC_PATH  . '/config.xml', $tpl->process( 'config.xml.tpl' ) ) || die( "Could not write config.xml\n" );
}

?>
