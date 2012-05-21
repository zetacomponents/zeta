<?php

class ezcDocInvalidDocTagException extends ezcDocException
{

    public function __construct( $tag, $docLine )
    {
        parent::__construct( "The tag '$tag' could not be parsed on doc line '$docLine'." );
    }

}

?>
