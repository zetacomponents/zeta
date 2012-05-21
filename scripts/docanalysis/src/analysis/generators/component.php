<?php

class ezcDocComponentAnalysisGenerator extends ezcDocAnalysisElementGenerator
{
    protected $files = array();

    protected $component;

    public function __construct( Reflector $component )
    {
        if ( ( $component instanceof ezcDocComponentReflection ) === false )
        {
            throw new ezcBaseValueException( "component", $component, "ezcDocComponentReflection" );
        }
        $this->files     = $component->getFiles();
        $this->component = $component;
    }

    public function generate()
    {
        $analysis = ezcDocAnalysisElement::get( $this->component );
        foreach ( $this->files as $file )
        {
            $analyser = new ezcDocFileAnalysisGenerator( $file );
            $analysis->addChild( $analyser->generate() );
        }
        return $analysis;
    }
}

?>
