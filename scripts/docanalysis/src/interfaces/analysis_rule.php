<?php

interface ezcDocAnalysisRule
{
    public function getCheckableElements();

    public function check( ezcDocAnalysisElement $elementAnalysis );
}

?>
