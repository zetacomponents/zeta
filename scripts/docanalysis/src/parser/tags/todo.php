<?php

class ezcDocBlockTodoTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@todo(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@todo\s+(.+)$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "todo", $docLine );
        }
        parent::__construct(
            array(
                "text"  => $matches[1],
            )
        );
    }
}

?>
