<?php

abstract class ezcDocAnalysisElementGenerator
{
    public abstract function __construct( Reflector $element );

    public abstract function generate();

    protected function parseDocBlock( $analysis, $docBlock )
    {
        try 
        {
            $analysis->docBlock = ezcDocBlockParser::parse( $docBlock );
        }
        catch ( ezcDocInvalidDocBlockException $e )
        {
            $analysis->addMessage( new ezcDocAnalysisMessage( $e->getMessage() ) );
            $analysis->docBlockValid = false;
        }
        catch ( ezcDocException $e )
        {
            $analysis->addMessage( new ezcDocAnalysisMessage( $e->getMessage() ) );
        }
    }
}

?>
