<?php

class ezcDocBlockVersionTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@version(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@version\s+(.*)\s*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "version", $docLine );
        }
        parent::__construct(
            array( "number" => $matches[1] )
        );
    }
}

?>
