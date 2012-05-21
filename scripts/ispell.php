<?php
/*
 * TODO:
 * - To check a sentence in the spell checker, it should not start with @*-+&, etc Insert a space there. 
 * - @package, @return, etc can be checked because either: @ is ignored, or @package can be inserted in the personal dictionary.
 * - A star (*) saves the word in the personal dictionary. This behavior is not documented. (Update the user interface)
 * - If <code> or <pre> is used, is should search for the closing tag, not until the end of the docblock.
 * - Sometimes the script hangs (after the first sentence).
 */ 

include ( "packages/Base/trunk/src/base.php" );
function __autoload( $class_name )
{
    if ( ezcBase::autoload( $class_name ) )
    {
        return;
    }
}

// Setup console parameters
$params = new ezcConsoleInput();
$file = new ezcConsoleOption( 'f', 'file', ezcConsoleInput::TYPE_STRING );
$file->shorthelp = "File that should be checked with ispell.";
$file->mandatory = true;
$params->registerOption( $file );

$noBackup = new ezcConsoleOption( 'B', 'nobackup', ezcConsoleInput::TYPE_NONE );
$noBackup->shorthelp = "Set this flag if no backup should be created.";
$params->registerOption( $noBackup );

$checkComponentWords = new ezcConsoleOption( 'c', 'check_component_words', ezcConsoleInput::TYPE_NONE );
$checkComponentWords->shorthelp = "Set this flag if the component words (like ezcArchive, ezcConsoleTools, etc) should be checked.";
$params->registerOption( $checkComponentWords );

$personalDictionary = new ezcConsoleOption( 'p', 'personal_dictionary', ezcConsoleInput::TYPE_STRING );
$personalDictionary->shorthelp = "Set a personal dictionary";
$params->registerOption( $personalDictionary );


try
{
    $params->process();
}
catch ( ezcConsoleOptionException $e )
{
    echo $e->getMessage(). "\n\n";
    echo $params->getSynopsis() . "\n\n";

    foreach ( $params->getOptions() as $option )
    {
        echo "-{$option->short}, --{$option->long}\t    {$option->shorthelp}\n";
    }

    echo "\n";
    exit();
}

// We should have a file name.
$file = $params->getOption( "file" )->value;
$checkComponentWords = $params->getOption( "check_component_words" )->value;
$personalDictionary = $params->getOption( "personal_dictionary" )->value;
if( $personalDictionary == false ) $personalDictionary = null;

$fp = fopen( $file, "r" );
if( $fp === false )
{
    exit( "Couldn't open the file: $file" );
}

$ispell = new ISpell( $personalDictionary );
$i = 0;

$inDocBlock = false;
$skip = false;


while( $sentence = fgets( $fp ) )
{
    if( preg_match( "@^\s*/\*\*\s*$@", $sentence ) )
    {
        $inDocBlock = true;
    }
    else if( $inDocBlock && preg_match( "@\s*\*/\s*$@", $sentence ) )
    {
        $inDocBlock = false;
        $skip = false;
    }
    else if( $inDocBlock && !$skip)
    {
        // If something contains an @, skip the rest until the new docblock.
        if( preg_match( "|@|", $sentence ) ) 
        {
            $skip = true;
        }
        else if( preg_match( "|<code>|", $sentence ) ) 
        {
            $skip = true;
        }
        else
        {
            $pos = strpos( $sentence, "*" ) + 1;

            $testForSpelling = substr( $sentence, $pos );
            $correct = $ispell->check( $testForSpelling, !$checkComponentWords ); 

            $sentence = substr( $sentence, 0, $pos ) . $correct;
        }
    }

    $correctedSentences[$i] = $sentence;
    $i++;
}

fclose( $fp );

// Backup
if ( !$params->getOption( "nobackup" )->value )
{
    copy( $file, $file.".bak" );
}

$fp = fopen( "$file", "w" );

if( $fp === false )
{
    exit ( "Cannot open the file <$file> for writing." );
}

// Write the changes
for( $i = 0; $i < sizeof( $correctedSentences ); $i++)
{
    fwrite( $fp, $correctedSentences[$i] );
}

// And close.
fclose( $fp);

   

class ISpell
{
    private $stdin = null;

    private $pipes;

    private $ispell;


    public function __construct( $personalDictionary = null )
    {
        $this->stdin = fopen("php://stdin","r");

        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
        );

        $personalDictionary = ( $personalDictionary === null ? "" : "-p $personalDictionary" ); 

        $this->ispell = proc_open( "/usr/bin/ispell -a $personalDictionary", $descriptorspec, $this->pipes ); 

        if ( !is_resource( $this->ispell ) ) 
        {
            die ("Cannot open Ispell\n");
        }
    }

    public function __destruct()
    {
        fclose( $this->stdin );
    }


    /**
     * Returns the corrected sentence.
     * 
     * This function requires input from the user.
     */
    public function check( $sentence, $skipComponentWords = true )
    {
        $newSentence = "";

        $read = fread( $this->pipes[1] , 1024); // read introduction, or anything and ignore.
        fwrite( $this->pipes[0], "$sentence\n" ); // write the sentence to ispell.

        $prefPos = 0;

        // Read the output
        while ( ($result = fgets( $this->pipes[1], 1024 ) ) && $result != "\n" )
        {
            // Each word is on a new line.
            if( !$this->isOk( $result ) )
            {
                list( $word, $position, $suggestions ) = $this->parseResult( $result );

                if( !( $skipComponentWords && preg_match( "@^ezc[A-Z]@", $word ) ) )
                {
                    $this->showHelp( $sentence, $word, $position, $suggestions );
                    $line = $this->getCorrection();

                    // Update the word, if something is filled in.
                    if( $line != "" )
                    {
                        // Add to the private dictionary
                        if( $line == "*" )
                        {
                            fwrite( $this->pipes[0], "*$word\n" ); // write the sentence to ispell.
                            echo ("WORD APPENDED");
                        }
                        else
                        {
                            $newSentence .= substr( $sentence, $prefPos, $position - $prefPos ) .  $line;
                            $prefPos = $position + strlen( $word );
                        }
                    }
                }
            }
        }

        $position = strlen( $sentence );
        $newSentence .= substr( $sentence, $prefPos, $position - $prefPos );

        return $newSentence;
    }

    private function showHelp( $sentence, $word, $position, $suggestions )
    {
        echo ("\n");
        echo ( $sentence . "\n" );

        echo ("Word not recognized: " . $word . "\n\n" );
        echo $suggestions;
    }
    
    private function getCorrection()
    {
        echo ("\nType replacement (return to accept): ");
        $line = rtrim( fgets($this->stdin, 1024) );
        return $line;
    }


    private function isOk( $result )
    {
        if( $result[0] == "*" ) return true;
        if( $result[0] == "+" ) return true;
        if( $result[0] == "-" ) return true;
        if( $result[0] == "\n" ) return true;

        return false;
    }

    private function parseResult( $result )
    {
        if( $result[0] == "&" )
        {
            $split =  split( " ", $result );
            $position = substr( $split[3], 0, -1 );

            return array( $split[1], $position, $result ); // $result should be suggestions.
        }
        else if( $result[0] == "#" )
        {
            $split =  split( " ", $result );

            return array( $split[1], $split[2], false );

        }
    }

}



?>
