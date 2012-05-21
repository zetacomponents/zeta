<?php

class ezcDocAnalysisRuleFileHeaderCheck implements ezcDocAnalysisRule
{
    protected static $level = E_ERROR;

    protected $elements = array(
        "ezcDocFileReflection"
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

        $componentAnalysisElement = $analysisElement->parent;

        $this->checkPackageTag( $analysisElement, $componentAnalysisElement );
        $this->checkLicenseTag( $analysisElement );
        $this->checkCopyrightTag( $analysisElement );
        $this->checkVersionTag( $analysisElement );

        $this->checkShortDesc( $analysisElement );
    }

    protected function checkShortDesc( ezcDocAnalysisElement $analysisElement )
    {
        $heading = $analysisElement->docBlock->heading;
        $className = '';
        foreach ( $analysisElement->children as $child )
        {
            if ( $child->element instanceof ReflectionClass )
            {
                $className = $child->element->getName();
                break;
            }
        }

        if ( preg_match( '(' . preg_quote( $className ) . ')', $heading ) === 0 )
        {
            $analysisElement->addMessage(
                new ezcDocAnalysisMessage(
                    "File document heading misses class name '$className'."
                ),
                self::$level
            );
        }
    }

    protected function checkVersionTag( ezcDocAnalysisElement $analysisElement )
    {
        if ( !isset( $analysisElement->docBlock->tags['ezcDocBlockVersionTag'] ) )
        {
            $analysisElement->addMessage(
                new ezcDocAnalysisMessage(
                    'Missing file level @package tag.'
                ),
                self::$level
            );
        }
        else
        {
            $versionTag = $analysisElement->docBlock->tags['ezcDocBlockVersionTag'];
            if ( !is_array( $versionTag ) || count( $versionTag ) === 0 )
            {
                $analysisElement->addMessage(
                    new ezcDocAnalysisMessage(
                        "Missing @version tag in file level doc block."
                    ),
                    self::$level
                );
            }
            else if ( count( $versionTag ) > 1 )
            {
                $analysisElement->addMessage(
                    new ezcDocAnalysisMessage(
                        "Ambigious @version tag in file level doc block."
                    ),
                    self::$level
                );
            }
            else
            {
                $version = trim( $versionTag[0]->number );
                if ( $version !== '//autogen//' && $version !== '//autogentag//' )
                {
                    $analysisElement->addMessage(
                        new ezcDocAnalysisMessage(
                            "Invalid @version tag. Expected '//autogen//' or '//autogentag//', got '$version'."
                        ),
                        self::$level
                    );
                }
            }
        }
    }

    protected function checkCopyrightTag( ezcDocAnalysisElement $analysisElement )
    {
        if ( !isset( $analysisElement->docBlock->tags['ezcDocBlockCopyrightTag'] ) )
        {
            $analysisElement->addMessage(
                new ezcDocAnalysisMessage(
                    'Missing file level @package tag.'
                ),
                self::$level
            );
        }
        else
        {
            $copyrightTag = $analysisElement->docBlock->tags['ezcDocBlockCopyrightTag'];
            if ( !is_array( $copyrightTag ) || count( $copyrightTag ) === 0 )
            {
                $analysisElement->addMessage(
                    new ezcDocAnalysisMessage(
                        "Missing @copyright tag in file level doc block."
                    ),
                    self::$level
                );
            }
            else if ( count( $copyrightTag ) > 1 )
            {
                $analysisElement->addMessage(
                    new ezcDocAnalysisMessage(
                        "Ambigious @copyright tag in file level doc block."
                    ),
                    self::$level
                );
            }
            else
            {
                $copyright = trim( $copyrightTag[0]->text );
                $expected = 'Copyright (C) 2005-' . date( 'Y' ) .  ' eZ Systems AS. All rights reserved.';
                if ( $copyright !== $expected )
                {
                    $analysisElement->addMessage(
                        new ezcDocAnalysisMessage(
                            "Invalid @copyright tag. Expected '$expected', got '$copyright'."
                        ),
                        self::$level
                    );
                }
            }
        }
    }

    protected function checkLicenseTag( ezcDocAnalysisElement $analysisElement )
    {
        if ( !isset( $analysisElement->docBlock->tags['ezcDocBlockLicenseTag'] ) )
        {
            $analysisElement->addMessage(
                new ezcDocAnalysisMessage(
                    'Missing file level @package tag.'
                ),
                self::$level
            );
        }
        else
        {
            $licenseTag = $analysisElement->docBlock->tags['ezcDocBlockLicenseTag'];
            if ( !is_array( $licenseTag ) || count( $licenseTag ) === 0 )
            {
                $analysisElement->addMessage(
                    new ezcDocAnalysisMessage(
                        "Missing @license tag in file level doc block."
                    ),
                    self::$level
                );
            }
            else if ( count( $licenseTag ) > 1 )
            {
                $analysisElement->addMessage(
                    new ezcDocAnalysisMessage(
                        "Ambigious @license tag in file level doc block."
                    ),
                    self::$level
                );
            }
            else
            {
                $license = trim( $licenseTag[0]->text );
                if ( $license !== 'http://ez.no/licenses/new_bsd New BSD License' )
                {
                    $analysisElement->addMessage(
                        new ezcDocAnalysisMessage(
                            "Invalid @license tag. Expected 'http://ez.no/licenses/new_bsd New BSD License', got '$license'."
                        ),
                        self::$level
                    );
                }
            }
        }
    }

    protected function checkPackageTag( ezcDocAnalysisElement $analysisElement, ezcDocAnalysisElement $componentAnalysisElement )
    {
        if ( !isset( $analysisElement->docBlock->tags['ezcDocBlockPackageTag'] ) )
        {
            $analysisElement->addMessage(
                new ezcDocAnalysisMessage(
                    'Missing file level @package tag.'
                ),
                self::$level
            );
        }
        else
        {
            $packageTag = $analysisElement->docBlock->tags['ezcDocBlockPackageTag'];
            if ( !is_array( $packageTag ) || count( $packageTag ) === 0 )
            {
                $analysisElement->addMessage(
                    new ezcDocAnalysisMessage(
                        "Missing @package tag in file level doc block."
                    ),
                    self::$level
                );
            }
            else if ( count( $packageTag ) > 1 )
            {
                $analysisElement->addMessage(
                    new ezcDocAnalysisMessage(
                        "Ambigious @package tag in file level doc block."
                    ),
                    self::$level
                );
            }
            else
            {
                if ( $packageTag[0]->name !== $componentAnalysisElement->name )
                {
                    $analysisElement->addMessage(
                        new ezcDocAnalysisMessage(
                            "Invalid @package tag. Component name is '{$componentAnalysisElement->name}' but tag indicates '{$packageTag[0]->name}'."
                        ),
                        self::$level
                    );
                }
            }
        }
    }
}

?>
