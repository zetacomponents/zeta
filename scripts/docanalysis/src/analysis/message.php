<?php

class ezcDocAnalysisMessage
{
    protected $message;

    protected $level;

    public function __construct( $message, $level = E_ERROR )
    {
        $this->message = $message;
        $this->level   = $level;
    }

    public function __get( $propertyName )
    {
        if ( isset( $this->$propertyName ) )
        {
            return $this->$propertyName;
        }
        throw new ezcBasePropertyNotFoundException( $propertyName );
    }
}

?>
