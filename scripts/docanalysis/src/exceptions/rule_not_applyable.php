<?php

class ezcDocRuleNotApplyableException extends ezcDocException
{
    public function __construct( $class, $type )
    {
        parent::__construct( "Rule class '$class' can not check element of type '$type'." );
    }
}

?>
