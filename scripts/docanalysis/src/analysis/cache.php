<?php

class ezcDocAnalysisCache
{
    protected static $elements = array();

    public static function add( ezcDocAnalysisElement $analysisElement )
    {
        self::$elements[self::generateId( $analysisElement->element )] = $analysisElement;
    }

    public static function get( Reflector $element )
    {
        if ( isset( self::$elements[self::generateId( $element )] ) )
        {
            return self::$elements[self::generateId( $element )];
        }
        return false;
    }

    protected static function generateId( Reflector $element )
    {
        switch( get_class( $element ) )
        {
            case 'ReflectionClass':
                return $element->getName();
            case 'ReflectionProperty':
                return self::generateId( $element->getDeclaringClass() ) . "::" . "$" . $element->getName();
            case 'ReflectionMethod':
                return self::generateId( $element->getDeclaringClass() ) . "::" . $element->getName() . "()";
            case 'ReflectionComponent':
                return "__component__" . $element->getName();
            case 'ReflectionFile':
                return "__file__" . $element->getName();
        }
    }
}

?>
