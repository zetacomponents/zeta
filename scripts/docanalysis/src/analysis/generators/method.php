<?php

class ezcDocMethodAnalysisGenerator extends ezcDocAnalysisElementGenerator
{
    private $method;

    public function __construct( Reflector $method )
    {
        if ( ( $method instanceof ReflectionMethod ) === false )
        {
            throw new ezcBaseValueException( "method", $method, "ReflectionMethod" );
        }
        $this->method = $method;
    }

    public function generate()
    {
        $analysis = ezcDocAnalysisElement::get( $this->method );
        $this->parseDocBlock( $analysis, $this->method->getDocComment() );
        return $analysis;
    }
}

?>
