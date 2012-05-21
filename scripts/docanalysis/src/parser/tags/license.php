<?php

class ezcDocBlockLicenseTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@license(\s*|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@license\s+(.*)\s*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "license", $docLine );
        }
        parent::__construct(
            array( "text" => $matches[1] )
        );
    }
}

?>
