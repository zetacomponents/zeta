<?php

class ezcDocBlockFilesourceTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@filesource(\s*|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@filesource\s*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "filesource", $docLine );
        }
        parent::__construct(
            array()
        );
    }
}

?>
