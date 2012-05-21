<?php

class ezcDocAnalysisCheck
{
    protected $rules = array(
        "ezcDocComponentReflection" => array(),
        "ezcDocFileReflection"      => array(),
        "ReflextionClass"           => array(),
        "ReflectionProperty"        => array(),
        "ReflectionMethod"          => array(),
    );

    public function addRule( ezcDocAnalysisRule $rule )
    {
        foreach ( $rule->getCheckableElements() as $element )
        {
            $this->rules[$element][] = $rule;
        }
    }

    public function check( ezcDocAnalysisElement $analysisElement )
    {
        if ( $analysisElement->docBlockValid === true )
        {
            foreach ( $this->rules[get_class( $analysisElement->element )] as $rule )
            {
                $rule->check( $analysisElement );
            }
        }
        foreach ( $analysisElement->children as $child )
        {
            $this->check( $child );
        }
    }
}

?>
