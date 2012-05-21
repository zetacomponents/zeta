<?php

class ezcDocBlockTagTokenNotMatchedException extends ezcDocException
{
    public function __construct( ezcDocBlockTagToken $token, $string )
    {
        parent::__construct(
            "The token '{$token->name}' did not match the string '$string'."
        );
    }
}

?>
