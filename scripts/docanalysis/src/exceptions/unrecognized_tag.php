<?php

class ezcDocUnrecognizedDocTagException extends ezcDocException
{

    public function __construct( $docLine )
    {
        parent::__construct( "The docline '$docLine' could not be parsed because the tag was not recognized." );
    }

}

?>
