<?php

class ezcDocBlockTagDispatcher
{
    private static $tagClasses;

    public static function parse( $docLine )
    {
        if ( self::$tagClasses === null )
        {
            self::initTagClasses();
        }
        foreach( self::$tagClasses as $tagClass )
        {
            if ( preg_match( call_user_func( array( $tagClass, 'getPattern' ) ), $docLine, $matches ) > 0 )
            {
                return new $tagClass( $docLine );
            }
        }
        throw new ezcDocUnrecognizedDocTagException( $docLine );
    }

    private static function initTagClasses()
    {
        self::$tagClasses = array();
        foreach ( require( dirname( __FILE__ ) . "/../../autoload/doc_autoload.php" ) as $class => $file )
        {
            if ( is_subclass_of( $class, "ezcDocBlockBaseTag" ) )
            {
                self::$tagClasses[] = $class;
            }
        }
    }
}

?>
