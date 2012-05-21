<?php

class ezcDocAnalysisRuleParamCheck implements ezcDocAnalysisRule
{
    protected static $level = E_ERROR;

    protected $elements = array(
        "ReflectionMethod"
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
        $realParams = $analysisElement->element->getParameters();
        $docParams  = isset( $analysisElement->docBlock->tags["ezcDocBlockParamTag"] ) ? $analysisElement->docBlock->tags["ezcDocBlockParamTag"] : array();

        // Compare real parameters to documented ones
        // Workaround, $realParamPos should work with $realParam->getPosition()
        foreach ( $realParams as $realParamPos => $realParam )
        {
            $realParamName = '$' . $realParam->getName();
            foreach ( $docParams as $docParamPos => $docParam )
            {
                $docTypes = explode( '|', $docParam->type );
                // Sanitize types ala array(Something) and ArrayObject(Something)
                foreach ( $docTypes as $id => $docType )
                {
                    if ( preg_match( '/(.+)(\(.*\))/', $docType, $matches ) > 0 )
                    {
                        $docTypes[$id] = $matches[1];
                    }
                }

                if ( $realParamName == $docParam->name )
                {
                    // Found parameter documentation, now checking different stuff
                    
                    if ( $docParamPos != $realParamPos )
                    {
                        // Position missmatch
                        $analysisElement->addMessage(
                            new ezcDocAnalysisMessage(
                                "Parameter '$realParamName' documented at position {$docParamPos} but is at {$realParamPos}."
                            ), 
                            self::$level 
                        );
                    }
                    if ( ( $class = $realParam->getClass() ) !== null ) 
                    {
                        try
                        {
                            $refClass = new ReflectionClass( $docTypes[0] );
                        }
                        catch( Exception $e )
                        {
                            $analysisElement->addMessage(
                                new ezcDocAnalysisMessage(
                                    "Parameter '$realParamName' documented to be of non existent class {$docTypes[0]}.",
                                    self::$level
                                )
                            );
                            $refClass = false;
                        }

                        // Has class type hint
                        if ( $class->getName() != $docTypes[0] && ( !$refClass || ( !$class->isSubclassOf( $refClass ) && !$refClass->isSubclassOf( $class ) ) ) )
                        {
                            $analysisElement->addMessage(
                                new ezcDocAnalysisMessage(
                                    "Parameter '$realParamName' documented to be of type {$docParam->type} but is a {$class->getName()}.",
                                    self::$level
                                )
                            );
                        }
                    }
                    if ( $realParam->isArray() && substr( $docParam->type, 0, 5 ) !== 'array' && $docParam->type !== 'mixed' )
                    {
                        // Has array type hint
                        $analysisElement->addMessage(
                            new ezcDocAnalysisMessage(
                                "Parameter '$realParamName' documented to be of type {$docParam->type} but is an array.",
                                self::$level
                            )
                        );
                    }
                    if ( $realParam->isDefaultValueAvailable() )
                    {
                        $defaultValue = $realParam->getDefaultValue();
                        switch( true )
                        {
                            case ( $docParam->type == "mixed" ):
                                // Mixed is ok everywhere
                                break;
                            case ( is_object( $defaultValue ) ):
                                if ( !in_array( get_class( $defaultValue ), $docTypes ) )
                                {
                                    $analysisElement->addMessage(
                                        new ezcDocAnalysisMessage(
                                            "Parameter '$realParamName' documented to be of type {$docParam->type} but is '" . get_class( $defaultValue ) . "' or mixed.",
                                            self::$level
                                        )
                                    );
                                }
                                break;
                            case ( is_array( $defaultValue ) ):
                                if ( substr( $docParam->type, 0, 5 ) !== "array" )
                                {
                                    $analysisElement->addMessage(
                                        new ezcDocAnalysisMessage(
                                            "Parameter '$realParamName' documented to be of type {$docParam->type} but is 'array' or mixed.",
                                            self::$level
                                        )
                                    );
                                }
                                break;
                            case ( is_resource( $defaultValue ) ):
                                if ( substr( $docParam->type, 0, 8 ) !== "resource" )
                                {
                                    $analysisElement->addMessage(
                                        new ezcDocAnalysisMessage(
                                            "Parameter '$realParamName' documented to be of type {$docParam->type} but is 'resource' or mixed.",
                                            self::$level
                                        )
                                    );
                                }
                                break;
                            case ( is_scalar( $defaultValue ) ):
                                switch ( ( $typeName = gettype( $defaultValue ) ) )
                                {
                                    case 'integer':
                                        $typeName = 'int';
                                        break;
                                    case 'boolean':
                                        $typeName = 'bool';
                                        break;
                                    case 'double':
                                        $typeName = 'float';
                                        break;
                                }
                                if ( $typeName === 'string' && !in_array( substr( $defaultValue, 0, 1 ), array( '"', "'" ) ) )
                                {
                                    // Value is a constant, may be any scalar documented, ignore by adding string
                                    $docTypes[] = 'string';
                                }
                                if ( !in_array( $typeName, $docTypes ) )
                                {
                                    $analysisElement->addMessage(
                                        new ezcDocAnalysisMessage(
                                            "Parameter '$realParamName' documented to be of type {$docParam->type} but is '$typeName' or mixed.",
                                            self::$level
                                        )
                                    );
                                }
                                break;
                        }
                    }
                    // We found the fitting doc and checked it, so continue
                    continue 2;
                }
            }
            $analysisElement->addMessage(
                new ezcDocAnalysisMessage(
                    "Parameter '$realParamName' seems not to be documented or is documented incorrectly.",
                    self::$level
                )
            );
        }
    }
}

?>
