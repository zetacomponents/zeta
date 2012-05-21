<?php

class ezcDocAnalysisRuleHeadingAvailable implements ezcDocAnalysisRule
{
    protected static $level = E_ERROR;

    protected $allowEmptyString = false;

    protected $elements = array();

    public function __construct( array $elements, $allowEmptyString = false )
    {
        $this->elements         = $elements;
        $this->allowEmptyString = $allowEmptyString;
    }

    public function getCheckableElements()
    {
        return $this->elements;
    }

    public function check( ezcDocAnalysisElement $analysisElement )
    {
        if ( in_array( get_class( $analysisElement->element ), self::getCheckableElements() ) === false )
        {
            throw new ezcDocRuleNotApplyableException( get_class( $analysisElement ) );
        }
        if ( !isset( $analysisElement->docBlock->heading ) 
             || ( $analysisElement->docBlock->heading === "" && $this->allowEmptyString === false ) 
           )
        {
            $analysisElement->addMessage(
                new ezcDocAnalysisMessage(
                    "Heading is not set or empty for element '{$analysisElement->name}'.",
                    self::$level
                )
            );
        }
    }
}

?>
