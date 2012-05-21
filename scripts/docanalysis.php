#!/usr/bin/env php
<?php

// For help, please run 
// $ scripts/docanalysis.php --help

ini_set(
    'include_path',
    dirname( __FILE__ ) . '/../trunk' . PATH_SEPARATOR . ini_get( 'include_path' )
);

require_once dirname( __FILE__ ) . '/../trunk/Base/src/base.php';

ezcBase::addClassRepository(
    dirname( __FILE__ ) . '/docanalysis/src', 
    dirname( __FILE__ ) . '/docanalysis/autoload',
    'ezc'
);

function __autoload( $className )
{
    ezcBase::autoload( $className );
}

$input = new ezcConsoleInput();
$input->registerOption( 
    new ezcConsoleOption(
        'h',
        'help',
        ezcConsoleInput::TYPE_NONE,
        null,
        false,
        'Retrieve help.',
        'Shows this help information',
        array(),
        array(),
        false,
        false,
        true
    )
);
$input->registerOption( 
    new ezcConsoleOption(
        'c',
        'color',
        ezcConsoleInput::TYPE_NONE,
        null,
        false,
        'Activate color output.',
        'If this option is activated, the output messages are colorized.'
    )
);

$input->argumentDefinition = new ezcConsoleArguments();
$input->argumentDefinition[] = new ezcConsoleArgument(
    "component",
    ezcConsoleInput::TYPE_STRING,
    "Component name.",
    "Name of the component to analyse."
);

try
{
    $input->process();
}
catch ( ezcConsoleException $e )
{
    die( $e->getMessage() . "\n" );
}

if ( $input->helpOptionSet() )
{
    echo $input->getHelpText(
       "eZ Components Documentation Analyser"
    );
}


$ref = new ezcDocComponentReflection( $input->argumentDefinition["component"]->value );

$cov = new ezcDocComponentAnalysisGenerator( $ref );
$analysis = $cov->generate();

$check = new ezcDocAnalysisCheck();
$check->addRule(
    new ezcDocAnalysisRuleHeadingAvailable(
        array( "ReflectionClass", "ReflectionMethod", "ReflectionProperty" )
    )
);
$check->addRule(
    new ezcDocAnalysisRuleTagsAvailable(
        array( "ezcDocBlockPackageTag", "ezcDocBlockVersionTag" ), 
        array( "ReflectionClass" )
    )
);
$check->addRule(
    new ezcDocAnalysisRuleParamCheck()
);
$check->addRule(
    new ezcDocAnalysisRuleFileHeaderCheck()
);
$check->addRule(
    new ezcDocAnalysisRuleClassHeaderCheck()
);

$check->check( $analysis );

$reporter = new ezcDocAnalysisPlainReporter();
$reporter->options->useColors = $input->getOption( 'color' )->value;
$reporter->output( $analysis );

?>
