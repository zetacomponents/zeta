#!/usr/bin/php
<?php
if ( $argc < 2 )
{
    print 'usage: scripts/generate-struct-test.php struct-class-name component-name > trunk/MvcTools/tests/structs/routing_information.php';
    print 'example usage: scripts/generate-struct-test.php ezcMvcRoutingInformation MvcTools > trunk/MvcTools/tests/structs/routing_information.php';
    die( "\n" );
}

/**
 * Load the base package to boot strap the autoloading
 */
require_once dirname( __FILE__ ) . '/../trunk/Base/src/base.php';

$fixtures = array(
    'php',
    'ezc',
    'ezp',
    'buddymiles',
    'buddyguy',
    'django',
    'satchmo',
    'vim',
    'linux',
    'gentoo',
    'debian',
    'oop',
    'random',
);

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

// }}}

$class = $argv[1];

if ( !class_exists( $class ) )
{
    die( "not a class: $class" );
}


$component = $argv[2];

if ( !is_dir( 'trunk' . DIRECTORY_SEPARATOR . $component ) )
{
    die( "not a component in trunk: $component" );
}

$structRc = new ReflectionClass( $class );
$baseStructRc = new ReflectionClass( 'ezcBaseStruct' );

if ( !$structRc->isSubclassOf( $baseStructRc ) )
{
    die( "Not a struct: $class" );
}

$structPropertiesRc = $structRc->getProperties();
$ctorRc = $structRc->getConstructor();
$ctorArgs = $ctorRc->getParameters();

// Make sure that there are as much number of properties in the ctor
// than in the class
if ( count( $ctorArgs ) != count( $structPropertiesRc  ) )
{
    die( "Not the same number of arguments in the constructor of $class than properties." );
}
foreach( $structPropertiesRc as $property )
{
    $found = false;
    foreach( $ctorArgs as $arg )
    {
        if ( $arg->getName() == $property->getName() )
        {
            $found = true;
            break;
        }
    }
    if ( !$found )
    {
        die( "Struct constructor lacks argument: " . $property->getName() );
    }
}

$instanciateStruct = sprintf( '$struct = new %s();', $class );

$tests = array();

$test['getset'] = array();
$test['getset'][] = $instanciateStruct;

$test['state'] = array();
$test['state'][] = '$state = array(';

for( $i=0; $i < count( $structPropertiesRc ); $i++ )
{
    $property = $structPropertiesRc[$i];
    $test['state'][] = sprintf( '\'%s\' => \'%s\',', $property->name, $fixtures[$i] );
}
$test['state'][] = ');';
$test['state'][] = sprintf( '$struct = %s::__set_state( $state );', $class );
for( $i=0; $i < count( $structPropertiesRc ); $i++ )
{
    $property = $structPropertiesRc[$i];
    $assertEquals = sprintf( '$this->assertEquals( \'%s\', $struct->%s, \'Property %s does not have the expected value\' );', $fixtures[$i], $property->getName(), $property->getName() );
    $test['getset'][] = sprintf( '$struct->%s = \'%s\';', $property->name, $fixtures[$i] );
    $test['getset'][] = $assertEquals;;
    $test['state'][] = $assertEquals;
}

?>
<?php echo '<?php'; ?>

/**
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package <?php echo $component . "\n"; ?>
 * @subpackage Tests
 */

/**
 * Test the struct <?php echo $class; ?>.
 *
 * @package <?php echo $component . "\n"; ?>
 * @subpackage Tests
 */
class <?php echo $class; ?>Test extends ezcTestCase
{
    public function testIsStruct()
    {
        $struct = new <?php echo $class; ?>();
        $this->assertTrue( $struct instanceof ezcBaseStruct );
    }

    public function testGetSet()
    {
        <?php echo join( "\n" . str_repeat( ' ', 8 ), $test['getset'] ); ?>

    }

    public function testSetState()
    {
        <?php echo join( "\n" . str_repeat( ' ', 8 ), $test['state'] ); ?>

    }

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( "<?php echo $class; ?>Test" );
    }
}
<?php echo '?>'; ?>
