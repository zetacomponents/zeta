<?php

class ezcDocClassAnalysisGenerator extends ezcDocAnalysisElementGenerator
{

    private $properties = array();

    private $methods = array();

    private $class;

    public function __construct( Reflector $class )
    {
        if ( ( $class instanceof ReflectionClass ) === false )
        {
            throw new ezcBaseValueException( "class", $class, "ReflectionClass" );
        }
        $this->class = $class;
        $this->properties = $class->getProperties();
        $this->methods = $class->getMethods();
    }

    public function generate()
    {
        $analysis = ezcDocAnalysisElement::get( $this->class );
        $this->parseDocBlock( $analysis, $this->class->getDocComment() );
        foreach ( $this->properties as $property )
        {
            $declaringClass = $property->getDeclaringClass();
            if ( $declaringClass->isUserDefined() && $declaringClass == $this->class )
            {
                $analyser = new ezcDocPropertyAnalysisGenerator( $property );
                $analysis->addChild( $analyser->generate() );
            }
        }
        foreach ( $this->methods as $method )
        {
            if ( $method->isUserDefined() && $method->getDeclaringClass() == $this->class )
            {
                $analyser = new ezcDocMethodAnalysisGenerator( $method );
                $analysis->addChild( $analyser->generate() );
            }
        }
        return $analysis;
    }
}


?>
