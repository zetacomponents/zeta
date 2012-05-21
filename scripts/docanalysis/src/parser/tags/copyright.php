<?php

class ezcDocBlockCopyrightTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@copyright(\s*|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@copyright\s+(.*)\s*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "copyright", $docLine );
        }
        parent::__construct( array( "text" => $matches[1] ) );
    }
}

?>
