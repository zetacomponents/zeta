<?php

class ezcDocBlockPropertyTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@property(-read|-write|)(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@property(-read|-write|)\s+(\S+)\s+(\$\S+)\s*(.*)$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "property", $docLine );
        }
        parent::__construct(
            array(
                "read"  => ( $matches[1] !== "-write" ),
                "write" => ( $matches[1] !== "read" ),
                "type"  => $matches[2],
                "name"  => $matches[3],
                "doc"   => $matches[4],
            )
        );
    }
}

?>
