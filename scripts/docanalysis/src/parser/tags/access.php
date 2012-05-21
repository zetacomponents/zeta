<?php

class ezcDocBlockAccessTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@access(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@access\s+(\S+)\s*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "access", $docLine );
        }
        parent::__construct(
            array(
                "public"    => strtolower( $matches[1] ) === "public",
                "protected" => strtolower( $matches[1] ) === "public",
                "private"   => strtolower( $matches[1] ) === "public",
            )
        );
    }
}

?>
