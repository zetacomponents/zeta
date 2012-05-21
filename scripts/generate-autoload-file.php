<?php
/**
 * Script to automatically generate autoload files.
 *
 * To run this for one specific component, go to the parent directory of
 * scripts/ and trunk/ and run the following the lines below. Make sure you
 * install the PEAR package "Structures_Graph" and the graphviz binaries first.
 * 
 * php -derror_reporting=E_ALL scripts/generate-autoload-file.php
 *  -c $componentname -t trunk/$componentname
 *
 * Substitute $componentname with the name of your component (two times). This
 * creates the correct autoload file(s) in trunk/component/src and also a new
 * class diagram in trunk/component/design (with graphviz's 'neato').
 *
 * In order to re-generate autoload files for all components, run the
 * scripts/generate-autoload-files.sh script instead.
 */

/**
 * Load the base package to boot strap the autoloading
 */
require_once dirname( __FILE__ ) . '/../trunk/Base/src/base.php';

/**
 * Pear classes
 */
require_once 'Structures/Graph.php';
require_once 'Structures/Graph/Node.php';
require_once 'Structures/Graph/Manipulator/TopologicalSorter.php';

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

ini_set( 'highlight.string', '#335533' );
ini_set( 'highlight.keyword', '#0000FF' );
ini_set( 'highlight.default', '#000000' );
ini_set( 'highlight.comment', '#007700' );

// Setup console parameters
$params = new ezcConsoleInput();
$componentOption = new ezcConsoleOption( 'c', 'component', ezcConsoleInput::TYPE_STRING );
$componentOption->mandatory = true;
$componentOption->shorthelp = "The name of the component.";
$params->registerOption( $componentOption );

$targetOption = new ezcConsoleOption( 't', 'target', ezcConsoleInput::TYPE_STRING );
$targetOption->mandatory = false;
$targetOption->shorthelp = "The directory to where the generated autoload file should be written.";
$params->registerOption( $targetOption );

$noGraphOption = new ezcConsoleOption( 'n', 'no-graph' );
$noGraphOption->shorthelp = "Do not generate a new class diagram.";
$params->registerOption( $noGraphOption );

// Process console parameters
try
{
    $params->process();
}
catch ( ezcConsoleOptionException $e )
{
    print( $e->getMessage(). "\n" );
    print( "\n" );

    echo $params->getHelpText( 'Autoload file generator.' ) . "\n";

    echo "\n";
    exit();
}

$component = $params->getOption( 'component' )->value;
$targetOption = $params->getOption( 'target' )->value;
if ( $targetOption )
{
    $targetDir = $targetOption;
}
else
{
    $targetDir = "trunk/{$component}";
}
$noGraph   = $params->getOption( 'no-graph' )->value;

$files = fetchExceptionFiles( $component, true );
$depData = generateDependencyData( $files, $component );
$maxClassNameLength1 = checkMaxClassLength( $depData );
$sorted1 = sortDependencyData( $depData );

$files = fetchNormalFiles( $component, true );
$depData = generateDependencyData( $files, $component );
$maxClassNameLength2 = checkMaxClassLength( $depData );
$sorted2 = sortDependencyData( $depData );

$maxClassNameLength = max( $maxClassNameLength1, $maxClassNameLength2 );

/* Do create dot file and PNG for it */
ob_start();
dumpDotHeader( $component );
foreach ( $sorted1 as $prefix => $sorted )
{
    dumpSortedDotArray( $sorted, $maxClassNameLength + 2 );
}
foreach ( $sorted2 as $prefix => $sorted )
{
    dumpSortedDotArray( $sorted, $maxClassNameLength + 2 );
}
dumpDotFooter();
$data = ob_get_contents();
ob_end_clean();
file_put_contents( '/tmp/dot-gen.dot', $data );

if ( $noGraph !== true )
{
    `neato -Tpng -o $targetDir/design/class_diagram.png /tmp/dot-gen.dot`;
}
unlink( '/tmp/dot-gen.dot' );

/* Do create autoload files */
$autoloadData = $preloadData = array();
foreach ( $sorted1 as $prefix => $sorted )
{
    if ( !isset( $autoloadData[$prefix] ) ) $autoloadData[$prefix] = '';
    if ( !isset( $preloadData[$prefix] ) ) $preloadData[$prefix] = '';
    $autoloadData[$prefix] .= dumpSortedArray( $sorted, $maxClassNameLength + 2 );
    $preloadData[$prefix] .= dumpSortedPreloadArray( $sorted, $maxClassNameLength + 2 );
}
foreach ( $sorted2 as $prefix => $sorted )
{
    if ( !isset( $autoloadData[$prefix] ) ) $autoloadData[$prefix] = '';
    if ( !isset( $preloadData[$prefix] ) ) $preloadData[$prefix] = '';
    $autoloadData[$prefix] .= dumpSortedArray( $sorted, $maxClassNameLength + 2 );
    $preloadData[$prefix] .= dumpSortedPreloadArray( $sorted, $maxClassNameLength + 2 );
}
$footer = dumpFooter();

foreach( $autoloadData as $prefix => $autoloadLines )
{
    $f = fopen( "{$targetDir}/src/{$prefix}_autoload.php", 'w' );
    fwrite( $f, dumpHeader( $component ) );
    fwrite( $f, $autoloadLines );
    fwrite( $f, $footer );
    fclose( $f );
}
/*
foreach( $preloadData as $prefix => $preloadLines )
{
    $f = fopen( "{$targetDir}/src/{$prefix}_preload.php", 'w' );
    fwrite( $f, dumpLicense( $component ) );
    fwrite( $f, $preloadLines );
    fclose( $f );
}
*/
function dumpLicense( $component )
{
    $year = date( "Y" );
    return <<<ENDL
<?php
/**
 * Autoloader definition for the $component component.
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @version //autogentag//
 * @filesource
 * @package $component
 */

ENDL;
}

function dumpHeader( $component )
{
    return dumpLicense( $component ) . <<<END

return array(

END;
}

function dumpDotHeader( $component )
{
    echo <<<END
digraph "{$component}.xml" {
    node [ fontname=Arial, shape=plaintext ];
    edge [ fontname=Arial ];
    mindist = 0.4;
    splines  = true;
    overlap=false;

END;
}

function dumpFooter()
{
    return <<<END
);
?>

END;
}
function dumpDotFooter()
{
    echo <<<END
}

END;
}
?>
<?php
function dumpSortedArray( $sorted, $length )
{
    $ret = '';
    for ( $i = count( $sorted ) - 1; $i >= 0; $i-- )
    {
        usort( $sorted[$i], 'sortByClassName' );
        foreach( $sorted[$i] as $node )
        {
            $data = $node->getData();

            if ( !class_exists( $data['class'], false ) && !interface_exists( $data['class'], false ) )
            {
//                require $data['file'];
            }

            $file = preg_replace( '@.*trunk/@', '', $data['file'] );
            $fileParts = explode( '/', $file );
            unset($fileParts[1]);
            $file = implode( '/', $fileParts );

            $ret .= sprintf( "    %-{$length}s => '%s',\n", "'{$data['class']}'", $file );
        }
    }
    return $ret;
}

function dumpSortedPreloadArray( $sorted, $length )
{
    $ret = '';
    for ( $i = count( $sorted ) - 1; $i >= 0; $i-- )
    {
        usort( $sorted[$i], 'sortByClassName' );
        foreach( $sorted[$i] as $node )
        {
            $data = $node->getData();

            if ( !class_exists( $data['class'], false ) && !interface_exists( $data['class'], false ) )
            {
//                require $data['file'];
            }

            $fileParts = explode( '/', $data['file'] );
            unset($fileParts[0]);
            $file = implode( '/', $fileParts );

            $ret .= "require '{$file}';\n";
        }
    }
    return $ret;
}

function dumpSortedDotArray( $sorted, $length )
{
    for ( $i = count( $sorted ) - 1; $i >= 0; $i-- )
    {
        usort( $sorted[$i], 'sortByClassName' );
        foreach( $sorted[$i] as $node )
        {
            $data = $node->getData();

            if ( !class_exists( $data['class'], false ) )
            {
//                require $data['file'];
            }

            if ( !preg_match( '@Exception$@', $data['class'] ) )
            {
                $bgcolor = $data['type'] == "interface" ? '#dddddd' : '#ffffff';
                echo <<<ENDL
    {$data['class']} [label=<<TABLE BGCOLOR="{$bgcolor}">
    <TR>
        <TD><FONT COLOR="black" POINT-SIZE="18">{$data['class']}</FONT></TD>
    </TR>

ENDL;
                sort( $data['functions'] );
                foreach ( $data['functions'] as $func )
                {
                    echo <<<ENDL
    <TR>
        <TD ALIGN="LEFT"><FONT COLOR="#333333" POINT-SIZE="14">{$func}()</FONT></TD>
    </TR>

ENDL;
                }
                echo <<<ENDL
</TABLE>>];

ENDL;
                foreach( $data['extends'] as $dep )
                {
                    echo <<<ENDL
    {$data['class']} -> {$dep} [ label="extends", arrowhead="empty" ];

ENDL;
                }

                foreach( $data['implements'] as $dep )
                {
                    echo <<<ENDL
    {$data['class']} -> {$dep} [ label="implements", arrowhead="empty", style="dashed" ];

ENDL;
                }
            }
        }
    }
}

function checkMaxClassLength( $depData )
{
    foreach ( $depData as $data )
    {
        $max = 0;
        foreach ( $data as $classInfo )
        {
            if ( strlen( $classInfo['class'] ) > $max )
            {
                $max = strlen( $classInfo['class'] );
            }
        }
        return $max;
    }
}

function sortByClassName( $a, $b )
{
    $aa = $a->getData();
    $bb = $b->getData();
    return strcmp( $aa['class'], $bb['class'] );
}

function sortDependencyData( $depDataArray )
{
    $return = array();
    foreach ( $depDataArray as $prefix => $depData )
    {
        $nodes = array();
        $graph = new Structures_Graph();

        /* Create all nodes and add them to the graph */
        foreach ( $depData as $classInfo )
        {
            $nodes[$classInfo['class']] = new Structures_Graph_Node();
            $nodes[$classInfo['class']]->setData( $classInfo );
            $graph->addNode( $nodes[$classInfo['class']] );
        }

        /* Add arcs */
        foreach( $depData as $classInfo )
        {
            if ( array_key_exists( 'deps', $classInfo ) )
            {
                foreach( $classInfo['deps'] as $dependency )
                {
                    if ( array_key_exists( $dependency, $nodes ) )
                    {
                        $nodes[$classInfo['class']]->connectTo( $nodes[$dependency] );
                    }
                }
            }
        }

        /* Sort */
        $m = new Structures_Graph_Manipulator_TopologicalSorter();
        $sorted = $m->sort( $graph );
        $return[$prefix] = $sorted;
    }
    return $return;
}

function fetchExceptionFiles( $component )
{
    return ezcFile::findRecursive( dirname( __FILE__ ) . "/../trunk/{$component}/src", array( '@\.php$@', '@/exceptions/@' ) );
}

function fetchNormalFiles( $component )
{
    return ezcFile::findRecursive( dirname( __FILE__ ) . "/../trunk/{$component}/src", array( '@\.php$@' ), array ( '@/exceptions/@' ) );
}

function generateDependencyData( $files, $component )
{
    $depArray = array();
    foreach ( $files as $file )
    {
        $name = '';
        $depData = getClassDependencies( $file, $name, $extends, $implements, $functions, $type, $relation );
        if ( $name )
        {
            // Figure out prefix
            $prefix = figureOutPrefix( $name, $component );
            $depArray[$prefix][$name] = array( 'file' => $file, 'class' =>
                    $name, 'deps' => $depData, 'functions' => $functions,
                    'type' => $type, 'extends' => $extends, 'implements' => $implements );

        }
    }
    return $depArray;
}

function figureOutPrefix( $className, $component )
{
    if ( $className == 'XMLWriter' )
    {
        return 'db_schema';
    }
    if ( preg_match( "/^([a-z]*)([A-Z][a-z0-9]*)([A-Z][a-z0-9]*)?/", $className, $matches ) !== false )
    {
        if ( in_array( $component, array( 'AuthenticationDatabaseTiein', 'DatabaseSchema',
                        'EventLogDatabaseTiein', 'GraphDatabaseTiein', 'ImageAnalysis',
                        'MvcFeedTiein', 'MvcMailTiein', 'MvcTemplateTiein', 'MvcAuthenticationTiein',
                        'PersistentObjectDatabaseSchemaTiein', 'PhpGenerator',
                        'TemplateTranslationTiein', 
                        'TranslationCacheTiein', 'TreeDatabaseTiein', 'TreePersistentObjectTiein',
                        'WorkflowDatabaseTiein', 'WorkflowEventLogTiein' ) ) )
        {
            return strtolower( "{$matches[2]}_{$matches[3]}" );
        }
        return strtolower( $matches[2] );
    }
    return '';
}

function getClassDependencies( $file, &$name, &$extends, &$implements, &$functions, &$type, &$relation )
{
    $extends = $implements = array();
    $info = $functions = array();
    $visibility = "public";

    $tokens = token_get_all( file_get_contents( $file ) );
    $lastKeyword = null;
    foreach( $tokens as $token )
    {
        if ( $lastKeyword === null && is_array( $token ) )
        {
            switch( $token[0] )
            {
                case T_CLASS:
                case T_INTERFACE:
                    $type = $token[1];
                    $lastKeyword = $token[1];
                    break;
                case T_EXTENDS:
                case T_IMPLEMENTS:
                    $lastKeyword = $token[1];
                    $relation = $token[1];
                    break;
                case T_FUNCTION:
                    $lastKeyword = $token[1];
                    break;
                case T_PROTECTED:
                    $visibility = "protected";
                    break;
                case T_PRIVATE:
                    $visibility = "private";
                    break;
            }

        }
        else if ( is_array( $token ) && $token[0] == T_WHITESPACE )
        {
            continue;
        }
        else if ( !is_array( $token ) && $token == ',' )
        {
            continue;
        }
        else if ( is_array( $token ) && $token[0] == T_STRING )
        {
            if ( $lastKeyword === 'extends' )
            {
                $extends[] = $token[1];
                $info[] = $token[1];
                $lastKeyword = null;
            }
            else if ( $lastKeyword === 'implements' )
            {
                $implements[] = $token[1];
                $info[] = $token[1];
            }
            else if ( $lastKeyword === 'function' )
            {
                switch( $visibility )
                {
                    case 'public':
                        $char = "+";
                        break;
                    case 'protected':
                        $char = "#";
                        break;
                    case 'private':
                        $char = "-";
                        break;
                }
                $functions[] = $char . $token[1];
                $visibility = "public";
            }
            else
            {
                $name = $token[1];

                $lastKeyword = null;
            }
        }
        else
        {
            $lastKeyword = null;
        }
    }
    return $info;
}