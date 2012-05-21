<?php
/**
 * Autoload ezc classes 
 * 
 * @param string $class_name 
 */

function __autoload( $className )
{
	require_once("trunk/Base/src/base.php");
	if ( strpos( $className, "_" ) !== false )
	{
		$file = str_replace( "_", "/", $className ) . ".php";
		$val = require_once( $file );
		if ( $val == 0 )
			return true;
		return false;
	}
	ezcBase::autoload( $className );
}

// Parse options
function getInputOptions()
{
	$parameters = new ezcConsoleInput();
   
    $parameters->registerOption( $helpOption = new ezcConsoleOption( 'h', 'help' ) );
    $helpOption->shorthelp = "This help.";
    $helpOption->longhelp = "This help.";

	$parameters->registerOption( new ezcConsoleOption( 
        't', 
        'target', 
		ezcConsoleInput::TYPE_STRING,
        'java_classes',
        false,
        'Target directory.',
        'Target directory where the to java converted classes should be stored. Default is \'java_classes\'.'
	) );

	$parameters->registerOption( new ezcConsoleOption( 
        's', 
        'source', 
		ezcConsoleInput::TYPE_STRING,
        "trunk",
        true,
        'Source directory.',
        'Source component directory. By default it will process \'trunk\'.'
	) );

	try 
	{
		$parameters->process();
	}
	catch ( ezcConsoleParameterException $e )
	{
		echo $e->getMessage(), "\n";
	}

    if( $helpOption->value )
    {
          echo $parameters->getSynopsis() . "\n";
          foreach ( $parameters->getOptions() as $option )
          {
              echo "-{$option->short}/{$option->long}: \t\t\t {$option->longhelp}\n";
          }

          exit();
    }

    return $parameters;

/*
    echo "\n";
    echo <<<AAA
Update this description when the console tools are finished.

To let this script work, you should have performed the following steps:
- Install PEAR (Or compile PHP with PEAR support).
- Make sure that the PHP executables are in the PATH.
- Make sure that autoconf (preferable version 2.13) is installed.
- pecl install docblock-alpha
AAA;
 */
} 
function findRecursive( $sourceDir, $filters )
{
	$elements = array();
	$dir = glob( "$sourceDir/*" );
	foreach( $dir as $entry )
	{
		if ( is_dir( $entry ) )
		{
			$subList = findRecursive( $entry, $filters );
			$elements = array_merge( $elements, $subList );
		}
		else
		{
			$ok = true;
			foreach( $filters as $filter )
			{
				if ( !preg_match( $filter, $entry ) )
				{
					$ok = false;
					break;
				}
			}
			if ( $ok )
			{
				$elements[] = $entry;
			}
		}
	}
	return $elements;
}

function processDocComment( $rc, $type, $class = null )
{
    $comment = $rc->getDocComment(); 

    // Add the @class <CLASSNAME> to the docblock.
    if( $type == "class" )
    {
        $n = substr( $comment, 0, 4);
        $n .= " * @class $class\n *\n";
        $n .= substr( $comment, 4);
        $comment = $n;
    }

    // Replace <note:> with <@note >
    $comment = str_ireplace( "note:", "@note ", $comment );


    $tokens = docblock_tokenize( $comment );

    $new = ""; 

/*    for( $i = 0; $i < 100; $i++)
    {
        echo docblock_token_name( $i ) . "\n";
    }
    exit();
*/

    $insideCode = false;
    for ( $i = 0; $i < sizeof( $tokens ); $i++ )
    {

        if ( docblock_token_name( $tokens[$i][0] ) == 'DOCBLOCK_CODEOPEN' )
        {
            $tokens[$i][1] = "@code"; 
            $insideCode = true;
        } 
        elseif ( docblock_token_name( $tokens[$i][0] ) == 'DOCBLOCK_CODECLOSE' )
        {
            $tokens[$i][1] = "@endcode";
            $insideCode = false;
        }
        elseif( docblock_token_name( $tokens[$i][0] ) == 'DOCBLOCK_TAG' )
        {
            if( $tokens[$i][1] == "@param" || $tokens[$i][1] == "@return")
            {
                $tokens[$i + 1][1] = ltrim( $tokens[$i + 1][1] );
                $a = explode( " ", $tokens[$i + 1][1] );


                if( sizeof( $a ) <= 2)
                {
                    $i += 3;
                    continue;
                }
                else
                {
                    if( strlen( $a[1] ) > 0 &&
                        $a[1][0] == '$' )
                    {
                        $a[1] = substr( $a[1], 1 );
                    }
                    
                    unset ($a[0] );
                    $tokens[$i + 1][1] = " " . implode( " ", $a );
 
                    $new .= $tokens[$i++][1];
                    $new .= $tokens[$i][1];
                    continue;
                }


            }
            elseif( $tokens[$i][1] == "@var" || $tokens[$i][1] == "@access" )
            {
                // Skip the @var, <type>, tab
                $i += 3;
                //echo "SKIP: <".$tokens[$i + 2][1].">";
                continue;
            }
            else
            {
//                    echo "Token:", docblock_token_name( $tokens[$i][0] ), $tokens[$i][1], "\n";
            }
  

            //$tokens[$i + 1][1] = substr( $tokens[$i + 1][1], );

            //echo "SKIPPING: <" . $tokens[$i][1]   . ">";
            //continue;
/*
            $tokens[$i][1]
            if( $tokens[$i][1] == "@param" )
            {
                echo "next token: " . $tokens[$i][1];
            }
 */
        }

        if( !$insideCode )
        {
//            $new .= str_replace( '$', '\a ', $tokens[$i][1] );
            $new .= $tokens[$i][1];
        }
        else
        {
            $new .= $tokens[$i][1];
        }
    }


    $new = preg_replace( "#[*]([ \t]+[*])+#", "*", $new );

    return $new;


/*
        // Found a tag?
        if ( docblock_token_name( $tokens[$i][0] ) == 'DOCBLOCK_TAG' )
        {
            var_dump( $tokens[$i] );

           //$result[$tokens[$i][1]][] = trim( $tokens[$i + 1][1] );

        }
 */


//    var_dump( $new );




} 

function cloneFile( $file, $targetDir )
{
	$dir = dirname( $file );
	if ( !is_dir( $targetDir . "/" . $dir ) )
	{
		mkdir ( $targetDir . "/" . $dir, 0777, true );
	}
	$f = fopen( $targetDir . "/" . str_replace(".php", ".java", $file ), "w" );
	ob_start();
	$found = false;
	$lines = file( $file );
	foreach ( $lines as $line )
	{
		if ( preg_match( '@(class|interface)(\s+)(ezc[a-z_0-9]+)(\s+(extends)\s+(\w+))?(\s+(implements)\s+(\w+(\s*,\s*\w+)*))?@i', $line, $match ) )
		{
			$class = $match[3];
			$found = true;
			break;
		}
	}
	if ( !$found )
	{
		return;
	}

    if ( isset( $match[8] ) && ( $match[8] == "implements" ) )
    {
        $implements = "implements " . $match[9];
    }
    else
    {
        $implements = "";
    }


	$rc = new ReflectionClass( $class );
    
    $classTags = getTags( $rc );
    
    // Create the namespace
    echo "package ".( isset( $classTags["@package"] ) ? $classTags["@package"][0] : "PACKAGE_NOT_SET" ).";\n\n";

    $classBlock = processDocComment($rc, "class", $class );
    echo $classBlock;
    echo ("\n");

    // Set the access type of the class.
    echo ( isset( $classTags[ "@access" ] ) ? $classTags["@access"][0] : "public" ) ." ";

    if ( $rc->isInterface() )
    {
        echo
            $rc->isFinal() ? 'final ' : '',
            'interface ';
    }
    else
    {
        echo
            $rc->isAbstract() ? 'abstract ' : '',
            $rc->isFinal() ? 'final ' : '',
            'class ';
    }
	echo "$class";

    $c = $rc->getParentClass();
    if( is_object( $c ) )
    {
      echo " extends " . $c->getName();
    }

    echo " " . $implements;

    echo "\n{\n";

    $ignoreProps = array();
    $ignoreConsts = array();
    $prc = $rc->getParentClass();
    while ( $prc )
    {
        foreach ( $prc->getProperties() as $property )
        {
            $ignoreProps[] = $property->getName();
        }
        foreach ( $prc->getConstants() as $constantName => $constant )
        {
            $ignoreConsts[] = $constantName;
        }
        $prc = $prc->getParentClass();
    }
    $ignoreProps = array_unique( $ignoreProps );
    $ignoreConsts = array_unique( $ignoreConsts );

	foreach ( $rc->getConstants() as $constantName => $constant )
	{
        // Skip constants of parents, PHP 5.1.x bug
        if ( in_array( $constantName, $ignoreConsts ) )
        {
            continue;
        }
        $constantType = "UNKNOWN";
        if ( is_float( $constant ) )
        {
            $constantType = "float";
        }
        elseif ( is_int( $constant ) )
        {
            $constantType = "int";
        }
        elseif ( is_bool( $constant ) )
        {
            $constantType = "bool";
        }
        elseif ( is_string( $constant ) )
        {
            $constantType = "string";
        }
        echo "    public static final $constantType $constantName = ", var_export( $constant, true ), ";\n";
	}
	echo "\n";

    foreach ( $rc->getProperties() as $property )
	{
        // Skip properties of parents, PHP 5.1.x bug
        if ( in_array( $property->getName(), $ignoreProps ) )
        {
            continue;
        }
        // Don't show the parent property methods.
        if( $property->getDeclaringClass()->getName() ==  $class )
        {
            echo "";

            echo "    ", processDocComment($property, "property");
            echo ("\n");

            $propertyTags = getTags( $property );

            if ( isset( $propertyTag["@access"] ) )
            {
                echo "    ", $propertyTag["@access"];
            }
            else
            {
                echo "    ",
                    $property->isPublic() ? 'public ' : '',
                    $property->isPrivate() ? 'private ' : '',
                    $property->isProtected() ? 'protected ' : '',
                    $property->isStatic() ? 'static ' : '';
            }

            if ( isset( $propertyTags["@var"][0] ) )
            {
                $var = fixType( $propertyTags["@var"][0] );
                echo $var . " "; 
            }
            else
            {
                echo "PROPERTY_TYPE_MISSING ";
            }

            //$propertyType = getPropertyType( $property );
            //echo $propertyType ? $propertyType : "PROPERTY_TYPE_MISSING", " ";
            
            echo $property->getName();
            echo ";\n";
        }
	}
	echo "\n";

    $o = 0;
    while ( true )
    {
        $text = substr( $classBlock, $o );
        if ( preg_match( "#[@]property(-read|-write|)[ \t\r\n*]+([^ \t\r\n]*)[ \t\r\n*]+(?:[$]?([^ \t\r\n]*))[ \t\r\n*]+([^@]+)#s",
                         $text, $matches, PREG_OFFSET_CAPTURE ) )
        {
            $propType = $matches[1][0];
            $type = $matches[2][0];
            $name = $matches[3][0];
            $desc = $matches[4][0];
            $desc = preg_replace( "#^[ \t]+[*]#m", " ", $desc );
            $desc = preg_replace( "#[\r\n]#s", " ", $desc );
            //echo "Got property ", $type, ":", $name, ":", $desc, "\n";
            $extra = "";
            if ( $propType == "-read" )
            {
                $extra = "\n * @note Read only.";
            }
            elseif ( $propType == "-write" )
            {
                $extra = "\n * @note Write only.";
            }
            echo "    /**\n * $desc$extra\n */\n";
            echo "    public $type $name;\n";
            $o += $matches[0][1] + strlen( $matches[0][0] );
        }
        else
        {
            break;
        }
    }

	foreach ( $rc->getMethods() as $method )
	{
        // Don't show the parent class methods.
        if( $method->getDeclaringClass()->getName() ==  $class )
        {

            echo "    ", processDocComment($method, "method");
            echo ("\n\t");

            $methodTags = getTags( $method );
            if ( $rc->isInterface() )
            {
                echo
                    $method->isFinal() ? 'final ' : '',
                    $method->isPublic() ? 'public ' : '',
                    $method->isPrivate() ? 'private ' : '',
                    $method->isProtected() ? 'protected ' : '';
            }
            else
            {
                echo
                    $method->isAbstract() ? 'abstract ' : '',
                    $method->isFinal() ? 'final ' : '',
                    $method->isPublic() ? 'public ' : '',
                    $method->isPrivate() ? 'private ' : '',
                    $method->isProtected() ? 'protected ' : '',
                    $method->isStatic() ? 'static ' : '';
            }
            
            $returnType = getReturnValue( $method );

            if ( strcmp( $method->name, "__construct" ) == 0 )
            {
                // Constructor has no return type.
                // Replace the method name.
                echo "$class ( ";
            }
            else if ( strcmp( $method->name, "__destruct" ) == 0 )
            {
                // Destructor has no return type.
                // Replace the method name.
                echo "~$class( ";
            }
            else
            {
                echo $returnType ? fixType( $returnType ) . ' ' : 'RETURN_TYPE_MISSING ';
                echo "{$method->name}( ";
            }


            $parameterTypes = getParameterTypes( $method );
            foreach ( $method->getParameters() as $i => $param )
            {
                if ( $i != 0 )
                {
                    echo ", ";
                }
                $paramClass = $param->getClass();
                if ( $paramClass )
                {
                    echo $paramClass->getName(), " ";
                }
                elseif ( isset( $parameterTypes[$param->getName()] ) )
                { 
                    echo fixType( $parameterTypes[$param->getName()] ), " ";
                }
                else
                {
                    echo "PARAM_TYPE_MISSING ";
                }
                echo $param->getName();
                if ( $param->isDefaultValueAvailable() )
                {
                    echo " = ", var_export( $param->getDefaultValue(), true );
                }
                /*
                if ( $param->isDefaultValueAvailable() )
                {
                    echo ' = ';
                    switch( strtolower( gettype( $param->getDefaultValue() ) ) )
                    {
                        case 'boolean':
                            echo $param->getDefaultValue() ? 'true' : 'false';
                            break;
                        case 'null':
                            echo 'null';
                            break;
                        default:
                            echo $param->getDefaultValue();
                    }
                }
                */
            }

            echo " ) ";  

            echo ( isset( $methodTags["@throws"] ) ? getThrowsString( $methodTags["@throws"] ) : "" );
            
            
            echo ($method->isAbstract() ? ";" : " {}" ) . "\n";
        }
	}
	
	echo "}\n";
	fwrite( $f, ob_get_contents() );
	ob_end_clean();
}

function getParameterTypes( $method )
{
	$types = array();
	$db = $method->getDocComment();
	$nextTextParamType = false;
	foreach ( docblock_tokenize( $db ) as $docItem )
	{
		if ( $nextTextParamType )
		{
			if ( docblock_token_name( $docItem[0] ) == 'DOCBLOCK_TEXT' )
			{
				if ( preg_match( '@\s([^\s]+)\s+\$([^\s]+)@', $docItem[1], $match ) )
				{
					$types[$match[2]] = $match[1];
				}
			}
			$nextTextParamType = false;
		}
		if ( docblock_token_name( $docItem[0] ) == 'DOCBLOCK_TAG' && $docItem[1] == '@param' )
		{
			$nextTextParamType = true;
		}
		else
		{
			$nextTextParamType = false;
		}
	}
	return $types;
}

function getReturnValue( $method )
{
	$types = array();
	$db = $method->getDocComment();
	$nextTextParamType = false;
	foreach ( docblock_tokenize( $db ) as $docItem )
	{
		if ( $nextTextParamType )
		{
			if ( docblock_token_name( $docItem[0] ) == 'DOCBLOCK_TEXT' )
			{
				if ( preg_match( '@\s([^\s]+)@', $docItem[1], $match ) )
				{
					return trim( $match[1] );
				}
			}
			$nextTextParamType = false;
		}
		if ( docblock_token_name( $docItem[0] ) == 'DOCBLOCK_TAG' && $docItem[1] == '@return' )
		{
			$nextTextParamType = true;
		}
		else
		{
			$nextTextParamType = false;
		}
	}
	return false;
}

function fixType( $type )
{
    $type = trim( $type );

    // Only one word allowed.
    if ( strpos( $type, " " ) !== false )
    {
        $type = substr( $type, 0, strpos( $type,  " " ) );
    }
   
    // Pick the first type if it can have multiple values: int|bool.
    if ( ( $pos = strpos( $type, "|" ) ) !== false )
    {
//        $type = substr( $type, 0, $pos );
        $type = "RETURN_TYPE_MIXED";
    }
    elseif ( ( $pos = strpos( $type, "/" ) ) !== false )
    {
//        $type = substr( $type, 0, $pos );
        $type = "RETURN_TYPE_MIXED";
    }

    if ( strncmp( $type, "array(", 6 ) == 0 )
    {
        $type = substr( $type, 6, -1) . "[]";
        $type = str_replace( "=>", "_", $type );
    }

    return $type;
}

/** 
 * Returns an array with tags and value.
 */
function getTags( $reflectionItem )
{
    $result = array();
	$dc = $reflectionItem->getDocComment();
    
    // Go through the comment block.
    $tokens = docblock_tokenize( $dc );

    for ( $i = 0; $i < sizeof( $tokens ); $i++ )
    {
        // Found a tag?
        if ( docblock_token_name( $tokens[$i][0] ) == 'DOCBLOCK_TAG' )
        {
           $result[$tokens[$i][1]][] = trim( $tokens[$i + 1][1] );

        }
    }
    return $result;
}

function getThrowsString(  $tags )
{
        if ( isset( $tags ) )
        {
            $str = "throws ";

            for( $i = 0; $i < sizeof( $tags ); $i++ )
            {
                $tags[$i] = trim( $tags[$i] );

                // Only one word allowed.
                if ( ( $pos = strpos( $tags[$i], " " ) ) !== false )
                {
                    $tags[$i] = substr( $tags[$i], 0, $pos );
                }

                // Sometimes: myException::MyConstType is used. Remove the second part.
                if( ( $pos = strpos( $tags[$i], ":" ) ) !== false )
                {
                    $tags[$i] = substr( $tags[$i], 0, $pos );
                }
            }

            $str .= implode( $tags, ", " );

            return $str;
        }

        return false;
}

function status( $str )
{
    echo $str . "\n";
}

$consoleInput = getInputOptions();
$directory = $consoleInput->getOption("target");
$source = $consoleInput->getOption("source");

// If source is not set, read all the components.

if( !is_array( $source->value ) ) 
{
    $source->value = array( $source->value );
}

$files = array();
foreach( $source->value as $s )
{
    if( is_file( $s ) )
    {
        $files = array_merge( $files, array($s));
    }
    else
    {
        $files = array_merge( $files, findRecursive( $s, array( '/\.php$/', '/src/' ) ) );
    }
}

if( count( $files ) == 0 )
{
    status("Could not find any source files");
    exit( -1 );
}

status( "Processing files ");
foreach ( $files as $file )
{
    echo (".");
	cloneFile( $file, $directory->value );
}
?>
