<?php

class ezcDocAnalysisRuleTagAvailable implements ezcDocAnalysisRule
{
    protected static $level = E_ERROR;

    protected $tag;

    protected $allowEmptyString = false;

    protected $elements = array();

    public function __construct( $tag, array $elements, $allowEmptyString = false )
    {
        $this->tag              = $tag;
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
            throw new ezcDocRuleNotApplyableException( __CLASS__, get_class( $analysisElement ) );
        }
        if ( !isset( $analysisElement->docBlock->tags[$this->tag] ) || !isset( $analysisElement->docBlock->tags[$this->tag][0] ) )
        {
            $analysisElement->addMessage( new ezcDocAnalysisMessage( "Tag '{$this->tag}' is not set or empty for element '{$analysisElement->name}'.", self::$level ) );
        }
    }
}

?>
