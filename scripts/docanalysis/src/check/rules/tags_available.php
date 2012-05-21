<?php

class ezcDocAnalysisRuleTagsAvailable implements ezcDocAnalysisRule
{
    protected static $level = E_ERROR;

    protected $tagCheks;

    protected $allowEmptyString = false;

    protected $elements = array();

    public function __construct( array $tags, array $elements, $allowEmptyString = false )
    {
        foreach ( $tags as $tag )
        {
            $this->tagChecks[] = new ezcDocAnalysisRuleTagAvailable(
                $tag,
                $elements,
                $allowEmptyString
            );
        }
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
        foreach ( $this->tagChecks as $tagCheck )
        {
            $tagCheck->check( $analysisElement );
        }
    }
}

?>
