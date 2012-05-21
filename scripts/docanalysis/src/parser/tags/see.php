<?php

class ezcDocBlockSeeTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@see(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@see\s+(\S+)\s*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "see", $docLine );
        }
        parent::__construct(
            array(
                "ref"  => $matches[1],
            )
        );
    }
}

?>
