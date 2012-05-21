<?php

/**
 * ezcDocBlockTagToken 
 * Represents a token which is part of a doc block tag definition.
 *
 * 
 * @package Documentation
 * @version //autogen//
 * @copyright Copyright (C) 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
interface ezcDocBlockTagToken
{
    const DELIMITER = '%';

    /**
     * Match this token and replace it if matched successfully.
     * Trys to match this token at the start of the given string and removes
     * it, if matched correctly. If the token does not match, an
     * ezcDocBlockTagTokenNotMatchedException is thrown.
     * 
     * @param string $string 
     * @return ezcDocBlockTagTokenValue The value of the token.
     * @throws ezcDocBlockTagTokenNotMatchedException
     *         if the token could not be matched.
     */
    public function matchToken( &$string );

    /**
     * Checks wether the token matches
     * 
     * @param string $string 
     * @return bool
     */
    public function tokenMatches( $string );

}
?>
