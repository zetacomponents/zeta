<?php

class ezcDocAnalysisPlainReporter extends ezcDocAnalysisReporter
{
    protected $messageLevelMap = array(
        E_ERROR  => 'messageError',
        E_NOTICE => 'messageNotice',
    );

    protected $output;

    public function __construct( ezcDocAnalysisReporterOptions $options = null)
    {
        parent::__construct( $options );

        $this->output = new ezcConsoleOutput();

        $this->output->formats->messageError->color = 'red';
        $this->output->formats->messageError->style = array( 'bold' );
        
        $this->output->formats->messageNotice->color = 'yellow';
        $this->output->formats->messageNotice->style = array( 'bold' );
    }

    public function output( ezcDocAnalysisElement $analysisElement, $level = 0 )
    {
        $this->output->options->useFormats = $this->options->useColors;
        
        if ( count( $analysisElement->messages ) !== 0 )
        {
            $this->output->outputLine( $this->indent( "{$analysisElement->name} (" . count( $analysisElement->messages ) . " messages), file: {$analysisElement->file} line: {$analysisElement->line}", $level ) );
        }
        foreach( $analysisElement->messages as $message )
        {
            $this->output->outputText( $this->indent( "- ", $level + 2 ) );
            $this->output->outputLine( $message->message, $this->messageLevelMap[$message->level] );
        }
        foreach( $analysisElement->children as $child )
        {
            $this->output( $child, $level + 4 );
        }
    }

    protected function indent( $text, $level )
    {
        return str_repeat( " ", $level ) . $text;
    }
}

?>
