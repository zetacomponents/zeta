<?php

class ezcDocAnalysisRuleClassHeaderCheck extends ezcDocAnalysisRuleFileHeaderCheck
{
    protected static $level = E_ERROR;

    protected $elements = array(
        "ReflectionClass"
    );

    public function __construct()
    {
    }

    public function getCheckableElements()
    {
        return $this->elements;
    }

    public function check( ezcDocAnalysisElement $analysisElement )
    {
        if ( in_array( get_class( $analysisElement->element ), $this->getCheckableElements() ) === false )
        {
            throw new ezcDocRuleNotApplyableException( __CLASS__, get_class( $analysisElement ) );
        }

        if ( isset( $analysisElement->docBlock->tags['ezcDocBlockLicense'] ) )
        {
            $analysisElement->addMessage(
                new ezcDocAnalysisMessage(
                    'Found @license tag in class doc block.'
                ),
                self::$level
            );
        }
        if ( isset( $analysisElement->docBlock->tags['ezcDocBlockCopyrightTag'] ) )
        {
            $analysisElement->addMessage(
                new ezcDocAnalysisMessage(
                    'Found @copyright tag in class doc block.'
                ),
                self::$level
            );
        }

        $componentAnalysisElement = $analysisElement->parent->parent;

        $this->checkVersionTag( $analysisElement );
        $this->checkPackageTag( $analysisElement, $componentAnalysisElement );
    }
}

?>
