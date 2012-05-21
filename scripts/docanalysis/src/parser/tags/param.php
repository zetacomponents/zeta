<?php

class ezcDocBlockParamTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@param(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@param\s+(\S+)\s+(&?\$\S+)\s*(.*)$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "param", $docLine );
        }
        parent::__construct(
            array(
                "type"        => $matches[1],
                "name"        => $matches[2],
                "description" => $matches[3],
            )
        );
    }
}

?>
