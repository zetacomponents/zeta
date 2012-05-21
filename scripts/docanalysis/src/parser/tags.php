<?php

class ezcDocBlockTags implements ArrayAccess, Iterator, Countable
{
    protected $tags = array();

    public function offsetGet( $offset )
    {
        if ( !isset( $this->tags[$offset] ) )
        {
            throw new ezcBasePropertyNotFoundException( $offset );
        }
        return $this->tags[$offset];
    }

    public function offsetSet( $offset, $value )
    {
        if ( ( $value instanceof ezcDocBlockTag ) === false )
        {
            throw new ezcBaseValueException( "value", $value, "ezcDocBlockTag" );
        }
        $offset = get_class( $value );

        if ( !isset( $this->tags[$offset] ) )
        {
            $this->tags[$offset] = array();
        }
        $this->tags[$offset][] = $value;
    }

    public function offsetUnset( $offset )
    {
        throw new RuntimeException( "Unsetting offset not permitted!" );
    }

    public function offsetExists( $offset )
    {
        return isset( $this->tags[$offset] );
    }

    public function current()
    {
        return current( $this->tags );
    }

    public function next()
    {
        return next( $this->tags );
    }

    public function key()
    {
        return key( $this->tags );
    }

    public function rewind()
    {
        return reset( $this->tags );
    }

    public function valid()
    {
        return ( current( $this->tags ) !== false );
    }

    public function count()
    {
        return count( $this->tags );
    }
}

?>
