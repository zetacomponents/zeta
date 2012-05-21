<?php

class ezcDocAnalysisReporterOptions extends ezcBaseOptions
{
    protected $properties = array(
        'useColors' => true,
    );

    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'useColors':
                if ( is_bool( $propertyValue ) !== true )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'bool' );
                }
                break;
            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
        }
        $this->properties[$propertyName] = $propertyValue;
    }
}

?>
