<?php

class ezcDocFileReflection implements Reflector
{
    private $path;

    private $class;

    public function __construct( $path, ReflectionClass $class )
    {
        if ( is_file( $path ) === false || is_readable( $path ) === false )
        {
            throw new ReflectionException( "File '$path' cannot be reflected. Does not exist or is not readable." );
        }
        $this->path  = $path;
        $this->class = $class;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getClass()
    {
        return $this->class;
    }

    public static function export( $path, ClassReflection $class )
    {
        $ref = new ezcDocFileReflection( $path, $class );
        return $ref->dump();
    }

    public function __toString()
    {
        return $this->dump();
    }

    public function getName()
    {
        return $this->path;
    }

    protected function dump()
    {
        return "{$this->path}\n" . var_export( $this->getClass(), true );
    }
}
?>
