<?php

class ezcDocBlockLinkTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@link(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@link\s+(\S+)\s+(.*)$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "link", $docLine );
        }
        parent::__construct(
            array(
                "ref"  => $matches[1],
                "text" => $matches[2],
            )
        );
    }
}

?>
