<?php

class ezcDocBlockVarTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@var(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@var\s+(\S+)\s*(.*)$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "var", $docLine );
        }
        parent::__construct(
            array(
                "type" => $matches[1],
                "description" => $matches[2],
            )
        );
    }
}

?>
