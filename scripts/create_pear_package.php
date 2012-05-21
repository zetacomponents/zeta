#!/usr/bin/env php
<?php
/**
 * Script for generating package.xml files for eZ Enterprise Components.
 *
 * @package Base
 * @version //autogentag//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license LGPL {@link http://www.gnu.org/copyleft/lesser.html}
 * @filesource
 */

// Disable notices as PEAR is not PHP 5 compatible
error_reporting( 2039 );

/**
 * Package file manager for package.xml 2.
 */
require_once 'PEAR/PackageFileManager2.php';

/**
 * Load the base package to boot strap the autoloading
 */
require_once 'trunk/Base/src/base.php';

/**
 * Helper file for revision files
 */
require_once 'scripts/get-packages-for-version.php';

// {{{ __autoload()

/**
 * Autoload ezc classes 
 * 
 * @param string $class_name 
 */
function __autoload( $class_name )
{
    if ( substr( $class_name, 0, 3) == 'ezc' )
    {
        ezcBase::autoload( $class_name );
    }
}

// }}}

class ezcPackageManager
{
    protected $paths = array( 
        'package' => '',
        'install' => '',
    );

    // {{{ CHANNEL

    /**
     * Channel name to use in pakage.xml files
     */
    const CHANNEL = 'components.ez.no';

    // }}}
    // {{{ LICENSE

    /**
     * License. 
     */
    const LICENSE = 'New BSD';

    // }}}

    // {{{ $output

    /**
     * ezcConsoleOutput object. 
     * 
     * @var object(ezcConsoleOutput)
     */
    protected $output;

    // }}}
    // {{{ $input

    /**
     * ezcConsoleInput object. 
     * 
     * @var object(ezcConsoleInput)
     */
    protected $input;

    // }}}
    // {{{ $validStates

    /**
     * Valid stability states. 
     * 
     * @var array
     * @access protected
     */
    protected $validStates = array( 
        'devel',
        'alpha',
        'beta',
        'stable',
        'guess',
    );

    // }}}

    // {{{ __construct()

    /**
     * Create a new package manager
     */
    public function __construct()
    {
        // Init
        $this->output = new ezcConsoleOutput();
        $this->output->formats->help->color = 'magenta';
        $this->output->formats->info->color = 'blue';
        $this->output->formats->info->style = 'bold';
        $this->output->formats->version->color = 'red';

        $this->processOptions();
    }

    // }}}
    // {{{ run()

    /**
     * Run the package manager. 
     */
    public function run()
    {
        // General info output
        $this->output->outputLine( "eZ Enterprise Components package manager.", 'info' );
        $this->output->outputText( "Version: ", 'info' );
        $this->output->outputLine( "0.1.0\n", 'version' );
        $this->output->outputLine();
        
        switch ( true )
        {
            case count( $this->input->getOptionValues() ) === 0 || $this->input->getOption( 'h' )->value === true:
                $this->showHelp();
                break;
            default:
                $version = $this->input->getOption( 'v' )->value;
                if ( !preg_match( '/([0-9]+\.[0-9]+(\.|beta|rc)[0-9]+)?|trunk/', $version ) )
                {
                    $this->raiseError( "Invalid version number <{$version}>, must be in format <x.y[state[z]]> or <trunk>." );
                }
                if ( $version == 'trunk' )
                {
                    $packageDir = 'trunk';
                }
                else
                {
                    $packageDir = 'releases';
                }
                $this->paths['package'] = realpath( "$packageDir/" . $this->input->getOption( 'p' )->value );
                $this->paths['install'] = '/tmp/ezc/' . $this->input->getOption( 'p' )->value;
                if ( is_dir( $this->paths['install'] ) )
                {
                    `rm -rf {$this->paths['install']}`;
                }
                if ( mkdir( $this->paths['install'], 0700, true ) === false )
                {
                    $this->raiseError( "Could not create installation directory <".$this->paths['install'].">.");
                }
                $this->createLinkMess( $version );
                $this->processPackage( $version );
                break;
        }
        
        $this->output->outputLine();
        $this->output->outputLine( "Operation successfully performed.", 'success' );
    }

    // }}}
        
    // protected
 
    // {{{ raiseError()

    /**
     * An error occured. Output it and die().
     * 
     * @param mixed $err Exception or string error message. 
     */
    protected function raiseError( $err )
    {
        if ( is_a( $err, 'Exception' ) )
        {
            $err = $err->getMessage();
        }
        $this->output->outputText( $err . "\n", 'failure' );
        die();
    }

    // }}}
    // {{{ processOptions()

    /**
     * Process expected parameters. 
     */
    protected function processOptions()
    {
        $this->input = new ezcConsoleInput();

        $p = $this->input->registerOption( 
            new ezcConsoleOption( 
                'p', 
                'package', 
                ezcConsoleInput::TYPE_STRING,
                null,
                null,
                'Package name.',
                'Name of the package to generate the package.xml files for. The package name must reflect the directory structure and you must be in the <packages/> directory of your SVN checkout.'
            )
        );

        $v = $this->input->registerOption( 
            new ezcConsoleOption( 
                'v', 
                'version', 
                ezcConsoleInput::TYPE_STRING,
                null,
                null,
                'Package version.',
                'Version of the release to generate a package.xml files for.',
                array( new ezcConsoleOptionRule( $p ) )
            )
        );
        $p->addDependency( new ezcConsoleOptionRule( $v ) );

        $b = $this->input->registerOption( 
            new ezcConsoleOption( 
                'b', 
                'base-version', 
                ezcConsoleInput::TYPE_STRING,
                null,
                null,
                'Base version dependency.',
                'Base version this package depends on.',
                array( new ezcConsoleOptionRule( $p ) )
            )
        );
        $p->addDependency( new ezcConsoleOptionRule( $b ) );

        $this->input->registerOption( 
            new ezcConsoleOption( 
                's', 
                'stability', 
                ezcConsoleInput::TYPE_STRING,
                'guess',
                null,
                'Stability of the package.',
                'Stability status of the release to package: devel, alpha, beta, or stable (default is guess from version string).',
                array( new ezcConsoleOptionRule( $v ), new ezcConsoleOptionRule( $p ) )
            ) 
        );
        $this->input->registerOption( 
            new ezcConsoleOption( 
                'h', 
                'help', 
                ezcConsoleInput::TYPE_NONE,
                null,
                null,
                'Display help. Use "-h <OptionName>" to display detailed info on a parameter.',
                'Display help information in general or for a specific parameter. Use as "-h <OptionName> to receive help for a specific parameter".'
            ) 
        );
        $this->input->registerOption( 
            new ezcConsoleOption( 
                'd', 
                'debug', 
                null,
                null,
                null,
                'Display debugging output.',
                'Display debugging output on the console instead of writing it to the package file. The installation infrastructure will be created anyway.'
            ) 
        );
        
        // Process parameters
        try 
        {
            $this->input->process();
        }
        catch ( ezcConsoleOptionException $e )
        {
            $this->raiseError( $e );
        }
    }

    // }}} 
    // {{{ grabReadme()

    /**
     * Returns package information from README file. 
     * Extracts information from the packages README file. Returns an array of
     * short description (index 0) and long description (index 1).
     * 
     * @param string $path Path to package base directory.
     * @return array Array with package descriptions (0=>short, 1=>long).
     */
    protected function grabReadme( $version = null )
    {
        $readmePath = $this->paths['package'] . "$version/DESCRIPTION";
        if ( !is_file( $readmePath ) || !is_readable( $readmePath ) )
        {
            $this->raiseError( 'Could not find README file <'.$readmePath.'>.' );
        }
        $readme = file( $readmePath );
        return array( 
            $readme[0],
            implode( '', $readme ),
        );
    }

    // }}}
    // {{{ grabChangelog()

    /**
     * Extract latest changes from changelog. 
     * 
     * @param string $path Package path.
     * @return string Latest changes.
     */
    protected function grabChangelog( $path, $version, &$preVersion )
    {
        if ( $version == 'trunk' )
        {
            $changelogPath = $this->paths['package'] . '/ChangeLog';
            $versionString = "[012]\.[0-9](.*)";
        }
        else
        {
            $changelogPath = $this->paths['package'] . "/$version/ChangeLog";
            $versionString = preg_quote( $version );
        }
        if ( !is_file( $changelogPath ) || !is_readable( $changelogPath ) )
        {
            $this->raiseError( 'Could not find ChangeLog file <'.$changelogPath.'>.' );
        }
        $data = array();
        $data = file( $changelogPath );
        $changelogData = array();
        $versionFound = false;
        foreach ( $data as $line )
        {
            if ( $versionFound && preg_match( "@^[012]\.[0-9](.*)\s-\s([A-Z][a-z]+)|(\[RELEASEDATE\])@", $line ) )
            {
                $versionFound = false;
            }
            if ( preg_match( "@^$versionString\s-\s@", $line ) )
            {
                $versionFound = true;
            }
            if ( $versionFound )
            {
                $changelogData[] = $line;
            }
        }
        // Find previous version number, we need this for unstable packages (from trunk)
        $preVersion = false;
        $found = 0;
        if ( $version == 'trunk' )
        {
            $preVersion = "0.9.9";
            foreach ( $data as $line )
            {
                if ( preg_match( "@^([012]\.[0-9](.*))\s-\s([A-Z][a-z]+)|(\[RELEASEDATE\])@", $line, $matches ) )
                {
                    $found++;
                }
                if ( $found == 2 )
                {
                    $preVersion = $matches[1];
                    $found++;
                }
            }
            $preVersion = $preVersion . '.' . date( "YmdHi" );
        }
        // Remove version string from text itself
        unset( $changelogData[0] );
        unset( $changelogData[1] );
        return "\n" . trim( implode( '', $changelogData ) ) . "\n";
    }

    // }}}
    // {{{ showHelp()

    /**
     * Print help information. 
     * 
     * @access protected
     * @return void
     */
    protected function showHelp()
    {
        $helpTopic = $this->input->getOption( 'h' )->value;
        
        $this->output->outputLine( $this->input->getSynopsis() );
        $this->output->outputLine( "Usage: $ generate_package_xml.php -p <PackageName> -v <PackageVersion> -s <PackageStatus>", 'help' );
        $this->output->outputLine( "Must be run from within /your/svn/co/ezcomponents/packages .", 'help' );
        
        if ( $helpTopic !== '' && !is_bool( $helpTopic ) )
        {
            try
            {
                $option = $this->input->getOption( $helpTopic );
            }
            catch ( ezcConsoleOptionException $e )
            {
                $this->raiseError( 'Invalid help topic: <' . $helpTopic . '>.' );
            }
            $this->output->outputLine();
            $this->output->outputLine( "Usage of $ generate_package_xml.php parameter $helpTopic:", 'help' );
            $this->output->outputText( $option->longhelp, 'help' );
            if( is_array( $option->depends ) && count( $option->depends ) > 0 ) 
            {
                $this->output->outputLine();
                $this->output->outputText( "Must be used together with parameters: ", 'help' );
                foreach( $option->depends as $dependency )
                {
                    $this->output->outputText( "-{$dependency->option}, ", 'help' );
                }
                $this->output->outputLine( "...", 'help' );
            }
        }
        else
        {
            $help = $this->input->getHelp();
            $table = new ezcConsoleTable( $this->output, 78 );
            $table->options->defaultFormat = 'help';
            foreach ( $help as $rowId => $row )
            {
                foreach ( $row as $cellId => $cell )
                {
                    $table[$rowId][$cellId]->content = $cell;
                }
            }
            $table->outputTable();
        }
        $this->output->outputText( "\n", 'help' );
    }

    // }}}
     // {{{  createLinkMess()

    /**
     * Check for and create/update installation dir structure.
     * This method creates a new directory 'install/' below the packages
     * 'trunk/'. This dir reflects the latter installation dir structure,
     * needed by the installer. The method additionally ckecks if the necessary
     * links are in place, creates the if needed, and renews the autoload
     * links.
     * 
     * @access protected
     * @return void
     */
    protected function createLinkMess( $version )
    {
        // prepare mess of links and dirs to create
        $installDir = $this->paths['install'];
        if ( $version == 'trunk' )
        {
            $packageDir = $this->paths['package'];
        }
        else
        {
            $packageDir = $this->paths['package'] . "/$version";
        }

        // directory paths which have to be really created
        $realPaths              = array();
        $realPaths['ezc']       = $installDir . '/ezc';
        $realPaths['autoload']  = $realPaths['ezc'] . '/autoload';

        // paths which have to be linked from their original source
        $linkPaths = array(
            $realPaths['ezc'] . '/' . $this->input->getOption( 'p' )->value 
            => $packageDir . '/src',
        );
        if ( is_dir( $packageDir . '/docs' ) )
        {
            $linkPaths[$installDir . '/docs'] = $packageDir . '/docs';
        }
        if ( is_dir( $packageDir . '/design' ) )
        {
            $linkPaths[$installDir . '/design'] = $packageDir . '/design';
        }

        // autoload files must be linked
        foreach( glob( $packageDir . '/src/*autoload*' ) as $autoloadFile ) 
        {
            $linkPaths[$realPaths['autoload'] . '/' . basename( $autoloadFile )] = $autoloadFile;
        }

        // add license and CREDITS files
        if ( $version == 'trunk' )
        {
            $linkPaths[$installDir . '/docs/LICENSE'] = $packageDir . '/../../../LICENSE';
        }
        else
        {
            $linkPaths[$installDir . '/docs/LICENSE'] = $packageDir . '/../../../LICENSE';
        }
        $linkPaths[$installDir . '/docs/CREDITS'] = $packageDir . '/CREDITS';
        
        // create real dir structure
        foreach ( $realPaths as $path ) 
        {
            if( !is_dir( $path ) ) 
            {
                if( mkdir( $path, 0777, true ) === false ) 
                {
                    $this->raiseError( 'Could not create basic install directory infrastructure in <' . $path . '>.' );
                }
            }
        }

        // clean up autoload links, if necessary
        foreach( glob( $realPaths['autoload'] . '/*' ) as $autoloadFile ) {
            if (!file_exists( $autoloadFile ) || !is_writeable( $autoloadFile ) || !unlink( $autoloadFile ) ) {
                $this->raiseError( 'Cannot remove former autoload link: <' . $autoloadFile . '>.' );
            }
        }

        // create linked dir structure
        foreach ( $linkPaths as $link => $target ) 
        {
            if( !is_link( $link ) ) 
            {
                // unfortunately we have to copy here, as we need to modify files sometimes :S
                `cp -vfR $target $link`;
                $basePhp = $link . '/' . 'base.php';
                if ( file_exists( $basePhp ) )
                {
                    file_put_contents( "$basePhp", str_replace( "libraryMode = \"devel\"", "libraryMode = \"pear\"", file_get_contents( $basePhp ) ) );
                }
                /*
                if( symlink( $target, $link ) === false ) 
                {
                    $this->raiseError( 'Could not create basic install link infrastructure <' . $link . '> to <' . $target . '>.' );
                }
                */
            }
        }
    }

    // }}}

    private function guessFromVersion( $version )
    {
        if ( preg_match( '@alpha@', $version ) )
        {
            return 'alpha';
        }
        if ( preg_match( '@beta|rc|trunk@', $version ) )
        {
            return 'beta';
        }
        return 'stable';
    }
    // {{{ processPackage()

    /**
     * Process the package given.
     * Processes the given package and creates a package.xml.
     */
    protected function processPackage( $version )
    {
        $baseVersion = $this->input->getOption( 'b' )->value;

        $packageName = $this->input->getOption( 'p' )->value;
        $packageDir  = $this->paths['install'];
        
        if ( !is_dir( $packageDir ) )
            $this->raiseError( "Package dir <' . $packageDir . '> is invalid.");
        
        $state = $this->input->getOption( 's' )->value !== false ? $this->input->getOption( 's' )->value : $this->guessFromVersion( $version );
        
        if ( $state == 'guess' )
        {
            $state = $this->guessFromVersion( $version );
        }
        
        if ( !in_array( $state, $this->validStates ) )
        {
            $this->raiseError( 'Invalid package state: <'.$state.'>.' );
        }
        
        $info = $this->grabReadme( $version == 'trunk' ? '' : "/$version" );

        $descShort = $info[0];
        $descLong  = $info[1];

        $changelog = $this->grabChangelog( $packageDir, $version, $preVersion );
        $dependencies = fetchVersionsFromReleaseFile( $this->paths['package'] . ( $version == 'trunk' ? "/trunk/DEPS" : "/$version/DEPS" ) );

        $installDir = $packageDir . '/' . 'install';

        $this->generatePackageXml( $packageName, $packageDir, $state, $preVersion ? $preVersion : $version, $descShort, $descLong, $changelog, $baseVersion, $dependencies );
    }

    // }}}
    // {{{ generatePackageXml()

    /**
     * Generate the final package.xml. 
     * 
     * @param string $name      Name of the package.
     * @param string $path      Path to the packages base directory.
     * @param string $state     Stability state.
     * @param string $version   Version number.
     * @param string $short     Short description.
     * @param string $long      Long description.
     * @param string $changelog Changelog information 
     */
    protected function generatePackageXml( $name, $path, $state, $version, $short, $long, $changelog, $baseVersion, $dependencies )
    {
        $version = str_replace( 'rc', 'RC', $version );
        $baseVersion = str_replace( 'rc', 'RC', $baseVersion );
        $autoloadDir = $this->paths['install'] . '/ezc/autoload';
        if ( !is_dir( $path ) )
        {
            $this->raiseError( 'Package source directory <'.$path.'> invalid.' );
        }

        $pkg = new PEAR_PackageFileManager2;
        $e = $pkg->setOptions(
            array(
                'packagedirectory'  => $path,
                'pathtopackagefile' => $path,
                'baseinstalldir'    => '/',
                'simpleoutput'      => true,
                'filelistgenerator' => 'file',
                'dir_roles' => array( 
                    'docs'   => 'doc',
                ),
                'roles' => array(
                    'php'    => 'php',
                    'css'    => 'php',
                    'xsd'    => 'php',
                    'rng'    => 'php',
                ),
            )
        );

        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $e = $pkg->setPackage( $name );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setSummary( $short );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setDescription( $long );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setChannel( self::CHANNEL );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        
        $e = $pkg->setReleaseStability( $state );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setAPIStability( $state );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setReleaseVersion( $version );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setAPIVersion( $version );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $e = $pkg->setLicense( self::LICENSE );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setNotes( $changelog );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $e = $pkg->setPackageType( 'php' );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        if ( isset( $dependencies['php'] ) )
        {
            $e = $pkg->setPhpDep( $dependencies['php'] );
        }
        else
        {
            $e = $pkg->setPhpDep( trim( file_get_contents( 'scripts/php-version' ) ) );
        }
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $e = $pkg->setPearinstallerDep( '1.4.2' );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        if ( $name !== 'Base' )
        {
            $e = $pkg->addPackageDepWithChannel( 'required', 'Base', self::CHANNEL, $baseVersion );
            if ( PEAR::isError( $e ) )
                $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        }
        foreach ( $dependencies as $depComponent => $depVersion )
        {
            if ( $depComponent == 'php' )
            {
                continue;
            }
            $e = $pkg->addPackageDepWithChannel( 'required', $depComponent, self::CHANNEL, $depVersion );
            if ( PEAR::isError( $e ) )
                $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        }

        $pkg->addGlobalReplacement( 'php-const', 'libraryMode = "devel"', 'libraryMode = "pear"' );

        $e = $pkg->addRelease();
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $e = $pkg->addMaintainer( 'lead', 'ezc', 'eZ systems', 'ezc@ez.no' );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $e = $pkg->generateContents();
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        
        $debug = $this->input->getOption( 'd' )->value !== false ? true : false;
        if ( $debug )
        {
            $e = $pkg->debugPackageFile();
            if ( PEAR::isError( $e ) )
                $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        }
        else 
        {
            $e = $pkg->writePackageFile();
            if ( PEAR::isError( $e ) )
                $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        }

        `cd /tmp; pear package {$path}/package.xml; cd -`;
        $this->output->outputLine();
        $this->output->outputLine( "Finished processing. You can find the release in /tmp.", 'success' );
        $this->output->outputLine();
        $this->output->outputLine();
    }

    // }}}

}

$manager = new ezcPackageManager();
$manager->run();

?>
