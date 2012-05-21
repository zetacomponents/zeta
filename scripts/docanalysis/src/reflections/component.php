<?php

class ezcDocComponentReflection implements Reflector
{
    private $name;

    private $path;

    public function __construct( $name )
    {
        foreach ( explode( PATH_SEPARATOR, ini_get( "include_path" ) ) as $path )
        {
            if ( file_exists( "$path/$name" ) && is_dir( "$path/$name" ) )
            {
                $this->path = "$path/$name";
                break;
            }
        }
        if ( $this->path === null )
        {
            throw new ReflectionException( "Component '$name' could not be found in include path '" . ini_get( "include_path" ) . "'." );
        }
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFiles()
    {
        $srcPath = ( file_exists( "{$this->path}/src" ) ) ? "{$this->path}/src" : $this->path;

        $files = array();
        foreach ( glob( "$srcPath/*autoload.php" ) as $autloadFile )
        {
            $autoload = require( $autloadFile );
            foreach( $autoload as $class => $file )
            {
                $file = strtr( $file, array( $this->name => $srcPath ) );
                $files[] = new ezcDocFileReflection( $file, new ReflectionClass( $class ) );
            }
        }
        return $files;
    }

    public static function export( $component )
    {
        $ref = new ezcDocComponentReflection( $component );
        return $ref->dump();
    }

    public function __toString()
    {
        return $this->dump();
    }

    protected function dump()
    {
        return var_export( $this->getFiles(), true );
    }
}
?>
