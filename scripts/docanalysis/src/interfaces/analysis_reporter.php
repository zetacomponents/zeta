<?php

abstract class ezcDocAnalysisReporter
{
    protected $properties = array(
        'options' => null,
    );
    
    public abstract function output( ezcDocAnalysisElement $analysisElement, $level = 0 );

    public function __construct( ezcDocAnalysisReporterOptions $options = null )
    {
        if ( $options === null )
        {
            $options = new ezcDocAnalysisReporterOptions();
        }
        $this->properties['options'] = $options;
    }

    public function __get( $propertyName )
    {
        if ( $this->__isset( $propertyName ) )
        {
            return $this->properties[$propertyName];
        }
        throw new ezcBasePropertyNotFoundException( $propertyName );
    }

    public function __isset( $propertyName )
    {
        return array_key_exists( $propertyName, $this->properties );
    }
}

?>
