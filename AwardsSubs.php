<?php

/**
 * @name      Awards Modification
 * @license   Mozilla Public License version 2.0 http://mozilla.org/MPL/2.0/.
 *
 * @version   3.0 Alpha
 *
 * Original Software by:           Juan "JayBachatero" Hernandez
 * Copyright (c) 2006-2009:        YodaOfDarkness (Fustrate)
 * Copyright (c) 2010:             Jason "JBlaze" Clemons
 *
 */

/**
 * Converts a php array to a JS object
 *
 * @param array $array
 * @param string $objName
 */
function AwardsBuildJavascriptObject($array, $objName)
{
    return 'var ' . $objName . ' = ' . AwardsBuildJavascriptObject_Recurse($array) . ";\n";
}

/**
 * Main function to do the array to JS object conversion
 *
 * @param array $array
 */
function AwardsBuildJavascriptObject_Recurse($array)
{
	// Not an array so just output it.
	if (!is_array($array))
	{
		// Handle null correctly
		if ($array === null)
			return 'null';

		return '"' . $array . '"';
	}

	// Start of this JS object.
	$retVal = "{";

	// Output all key/value pairs as "$key" : $value
	$first = true;
	foreach ($array as $key => $value)
	{
		// Add a comma before all but the first pair.
		if (!$first)
			$retVal .= ', ';

		$first = false;

		// Quote $key if it's a string.
		if (is_string($key))
			$key = '"' . $key . '"';

		$retVal .= $key . ' : ' . AwardsBuildJavascriptObject_Recurse($value);
	}

	// Close and return the JS object.
	return $retVal . "}";
}

function AwardsLoadAward()
{
	// @todo
};

function AwardsValidateImage()
{
	// @todo
};

function AwardsGetGroups()
{
	// @todo
};