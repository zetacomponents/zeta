<?php

class ezcDocBlockInternalTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@internal(\s*|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@internal\s*(.*)$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "internal", $docLine );
        }
        parent::__construct(
            array(
                'desc' => ( isset( $matches[1] ) ? $matches[1] : "" )
            )
        );
    }
}

?>
