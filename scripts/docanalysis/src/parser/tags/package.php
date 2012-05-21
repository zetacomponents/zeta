<?php

class ezcDocBlockPackageTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@package(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@package\s+(\S+)\s*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "package", $docLine );
        }
        parent::__construct(
            array( "name" => $matches[1] )
        );
    }
}

?>
