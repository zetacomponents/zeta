<?php

class ezcDocBlockSubsubpackageTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@subpackage(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@subpackage\s+(\S+)\s*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "subpackage", $docLine );
        }
        parent::__construct(
            array( "name" => $matches[1] )
        );
    }
}

?>
