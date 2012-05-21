<?php

interface ezcDocBlockTag
{
    public function __construct( $docLine );
    
    public static function getPattern();
}

?>
