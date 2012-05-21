<?php

abstract class ezcDocBlockBaseTag
{
    protected $params;

    public function __construct( array $params )
    {
        $this->params = $params;
    }

    public function __get( $propertyName )
    {
        if ( !isset( $this->params[$propertyName] ) )
        {
            throw new ezcBasePropertyNotFoundException( $propertyName );
        }
        return $this->params[$propertyName];
    }
}

?>
