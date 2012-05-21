<?php

class ezcDocPropertyAnalysisGenerator extends ezcDocAnalysisElementGenerator
{
    private $property;

    public function __construct( Reflector $property )
    {
        if ( ( $property instanceof ReflectionProperty ) === false )
        {
            throw new ezcBaseValueException( "property", $property, "ReflectionProperty" );
        }
        $this->property = $property;
    }

    public function generate()
    {
        $analysis = ezcDocAnalysisElement::get( $this->property );
        $this->parseDocBlock( $analysis, $this->property->getDocComment() );
        return $analysis;
    }
}

?>
