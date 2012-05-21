<?php

class ezcDocBlockParser
{
    protected static $docBlock;

    public static function parse( $docBlockString )
    {
        $docLines = explode( "\n", $docBlockString );

        if ( count( $docLines ) < 3 )
        {
            throw new ezcDocInvalidDocBlockException( "Docblock is missing." );
        }
        
        self::$docBlock = new ezcDocBlock();
        self::$docBlock->orginialBlock = $docBlockString;

        self::stripFraming( $docLines );

        self::$docBlock->heading     = ( isset( $docLines[0] ) && preg_match( '(\* +@)', $docLines[0] ) === 0 ? self::stripCommentLine( array_shift( $docLines ), true ) : null );
        self::$docBlock->description = ( isset( $docLines[0] ) && preg_match( '(\* +@)', $docLines[0] ) === 0 ? self::stripCommentLine( array_shift( $docLines ), true ) : null );

        while ( ( $line = array_shift( $docLines ) ) !== null )
        {
            if ( preg_match( '/\s+\*\s+@[^ ]+\s+/', $line ) !== 0 )
            {
                // We found the first tag!
                array_unshift( $docLines, $line );
                break;
            }
            self::$docBlock->description .= " " . self::stripCommentLine( $line, true );
        }

        if ( count( $docLines ) > 0 )
        {
            self::$docBlock->tags = self::parseTags( $docLines );
        }

        return self::$docBlock;
    }

    public static function parseToken( ezcDocBlockTagToken $token )
    {


    }

    protected static function parseTags( array &$docLines )
    {
        $tags = array();
        while ( count( $docLines ) !== 0 )
        {
            $tag = self::parseTag( $docLines );
            $tags[get_class( $tag )][] = $tag;
        }
        return $tags;
    }

    protected static function parseTag( array &$docLines )
    {
        $tagLine = self::stripCommentLine( array_shift( $docLines ), true );
        // Fetch lines of the tag
        while ( ( $line = array_shift( $docLines ) ) !== null )
        {
            // If next tag is found, unshift the line and go ahead parsing the found tag
            if ( preg_match( '/^@[^ ]+/', self::stripCommentLine( $line, true ) ) !== 0 )
            {
                // Next tag
                array_unshift( $docLines, $line );
                break;
            }
            // Else add
            $tagLine .= ' ' . self::stripCommentLine( $line, true );
        }
        return ezcDocBlockTagDispatcher::parse( $tagLine );
    }

    protected static function stripFraming( array &$docLines )
    {
        $firstLine = array_shift( $docLines );
        if ( preg_match( '@^\s*/\*\*\s*$@', $firstLine ) === 0 )
        {
            throw new ezcDocInvalidDocBlockException( $firstLine, "Doc block start invalid. Must be '/**'." );
        }
        $lastLine = array_pop( $docLines );
        if ( preg_match( '@\s*\*/\s*$@', $lastLine ) === 0 )
        {
            throw new ezcDocInvalidDocBlockException( $lastLine, "Doc block end invalid. Must be '*/'." );
        }
    }

    protected static function stripCommentLine( $line, $trim = false )
    {
        $line = preg_replace( '@(\s)\* ?(.*)$@', '\1\2', $line );
        return ( $trim === true ? trim( $line ) : $line );
    }
}

?>
