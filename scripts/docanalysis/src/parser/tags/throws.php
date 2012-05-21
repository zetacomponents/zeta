<?php

class ezcDocBlockThrowsTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@throws(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@throws\s+(\S+)\s*(.*)\s*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "return", $docLine );
        }
        parent::__construct(
            array(
                "type"  => $matches[1],
                "doc"   => trim( $matches[2] ),
            )
        );
    }
}

?>
