<?php

class ezcDocFileAnalysisGenerator extends ezcDocAnalysisElementGenerator
{
    protected $class;

    protected $file;

    public function __construct( Reflector $file )
    {
        if ( ( $file instanceof ezcDocFileReflection ) === false )
        {
            throw new ezcBaseValueException( "file", $file, "ezcDocFileReflection" );
        }
        $this->class = $file->getClass();
        $this->file  = $file;
    }

    public function generate()
    {
        $analysis = ezcDocAnalysisElement::get( $this->file );

        // Receive first doc block in file as file doc block
        $tokens = token_get_all( file_get_contents( $this->file->getPath() ) );
        $docBlock = null;
        foreach ( $tokens as $token )
        {
            if ( is_array( $token ) && $token[0] === T_DOC_COMMENT )
            {
                $docBlock = $token[1];
                break;
            }
        }

        $this->parseDocBlock( $analysis, $docBlock );
        $analyser = new ezcDocClassAnalysisGenerator( $this->class );
        $analysis->addChild( $analyser->generate() );
        return $analysis;
    }
}

?>
