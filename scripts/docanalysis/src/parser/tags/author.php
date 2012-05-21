<?php

class ezcDocBlockAuthorTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@author(\s*|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@author\s+(.*)\s*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "author", $docLine );
        }
        parent::__construct(
            array( "text" => $matches[1] )
        );
    }
}

?>
