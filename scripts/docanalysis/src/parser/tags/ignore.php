<?php

class ezcDocBlockIgnoreTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@ignore(\s*|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@ignore\s*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "ignore", $docLine );
        }
        parent::__construct(
            array()
        );
    }
}

?>
