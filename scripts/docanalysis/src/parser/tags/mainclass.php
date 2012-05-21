<?php

class ezcDocBlockMainclassTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@mainclass(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@mainclass\s*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "mainclass", $docLine );
        }
        parent::__construct(
            array()
        );
    }
}

?>
