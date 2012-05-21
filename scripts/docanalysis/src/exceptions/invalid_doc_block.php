<?php

class ezcDocInvalidDocBlockException extends ezcDocException
{
    public function __construct( $docLine, $msg = null )
    {
        parent::__construct(
            "Invalid doc block. Reason: '$docLine'." .
                ( $msg !== null ? " $msg" : "" )
        );
    }
}

?>
