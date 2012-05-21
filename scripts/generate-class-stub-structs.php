<?php
/**
 * Creates a struct class from a definition file. Can also be used to
 * re-process a generated file. 
 *
 * The script picks up on the following items, as well as comments for those
 * items. The rest will be stripped:
 * 
 * @package test
 * class test
 * 
 * @var string
 * public $foo;
 * 
 * @var int
 * public $bar;
*/

$file = file( $argv[1] );

$args = array();
$description = '<description>';

foreach( $file as $line )
{
    if ( preg_match( '/.* \* ([A-Za-z-].*)/', $line, $m ) )
    {
        if ( $description == '<description>' )
        {
            $description = '';
        }
        $description .= $m[0] . "\n";
    }
    if ( preg_match( '/.* \*$/', $line, $m ) )
    {
        $description .= $m[0] . "\n";
    }
    if ( preg_match( '/.* \* $/', $line, $m ) )
    {
        $description .= $m[0] . "\n";
    }

    if ( preg_match( '/@package ([a-z]+)/i', $line, $m ) )
    {
        $package = $m[1];
    }

    if ( preg_match( '/^class ([a-z0-9]+)/i', $line, $m ) )
    {
        $classDescription = trim( $description );
        $description = '';
        $class = $m[1];
    }

    if ( preg_match( '/@var ([a-zA-Z()]+)/i', $line, $m ) )
    {
        $type = $m[1];
    }

    if ( preg_match( '/public\ \$([A-Za-z]+)/', $line, $m ) )
    {
        $descriptions[$m[1]] = '<description>';
        $description = trim( $description );
        if ( $description !== '' )
        {
            $descriptions[$m[1]] = $description;
            $description = '';
        }
        $args[$m[1]] = $type;
    }
}

$year = date( 'Y' );

// add file header and licenses
echo <<<ENDH
<?php
/**
 * @copyright Copyright (C) 2005-$year eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package $package
 */

/**
 $classDescription
 * @package $package
 * @version //autogentag//
 */
class $class extends ezcBaseStruct
{

ENDH;

foreach( $args as $name => $type )
{
echo <<<ENDP
    /**
     {$descriptions[$name]}
     * @var $type
     */
    public \$$name;


ENDP;
}

echo "    /**\n     * Constructs a new {$class}.\n     *\n";

foreach( $args as $name => $type )
{
    echo "     * @param $type \$$name\n";
}

echo "     */\n";

echo "    public function __construct( ";

$elems = array();
$length = 24;
foreach( $args as $name => $type )
{
    $elem = "\$$name = ";
    switch ( $type )
    {
        case 'int':     $elem .= '0'; break;
        case 'double':  $elem .= '0'; break;
        case 'bool':    $elem .= 'true'; break;
        case 'string':  $elem .= "''"; break;
        case 'array':   $elem .= 'array()'; break;
        default:        $elem .= 'null'; break;
    }
    $length += strlen( $elem );
    if ( $length > 56 )
    {
        $length = 8;
        $elem = "\n        $elem";
    }

    $elems[] = $elem;
}
echo join( ', ', $elems );
echo " )\n    {\n";
foreach( $args as $name => $type )
{
    echo "        \$this->{$name} = \${$name};\n";
}
echo "    }\n\n";

echo <<<END
    /**
     * Returns a new instance of this class with the data specified by \$array.
     *
     * \$array contains all the data members of this class in the form:
     * array('member_name'=>value).
     *
     * __set_state makes this class exportable with var_export.
     * var_export() generates code, that calls this method when it
     * is parsed with PHP.
     *
     * @param array(string=>mixed) \$array
     * @return {$class}
     */

END;

echo "    static public function __set_state( array \$array )\n    {\n    ";

echo "    return new {$class}( ";

$elems = array();
$length = 25;
foreach( $args as $name => $type )
{
    $elem = "\$array['$name']";
    $length += 10 + strlen( $name );
    if ( $length > 60 )
    {
        $length = 20;
        $elem = "\n            $elem";
    }
    $elems[] = $elem;
}
echo join( ', ', $elems );

echo " );\n    }\n";

echo <<<ENDF
}
?>

ENDF;
?>
