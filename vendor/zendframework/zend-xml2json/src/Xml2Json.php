<?php
/**
 * @see       https://github.com/zendframework/zend-xml2json for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-xml2json/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Xml2Json;

use SimpleXMLElement;
use Zend\Json\Json;
use ZendXml\Security as XmlSecurity;

/**
 * Class for translating XML to JSON.
 */
class Xml2Json
{
     /**
      * Maximum allowed nesting depth when performing xml2json conversion.
      *
      * @var int
      */
    public static $maxRecursionDepthAllowed = 25;

    /**
     * Converts XML to JSON.
     *
     * Converts an XML formatted string into a JSON formatted string.
     *
     * The caller of this function needs to provide only the first parameter,
     * which is an XML formatted string.
     *
     * The second parameter, also optional, allows the user to select if the
     * XML attributes in the input XML string should be included or ignored
     * during the conversion.
     *
     * This function converts the XML formatted string into a PHP array via a
     * recursive function; it then converts that array to json via
     * Json::encode().
     *
     * NOTE: Encoding native javascript expressions via Zend\Json\Expr is not
     * possible.
     *
     * @param string $xmlStringContents XML String to be converted.
     * @param  bool $ignoreXmlAttributes Include or exclude XML attributes in
     *     the conversion process.
     * @return string JSON formatted string on success.
     * @throws Exception\RuntimeException If the input not a XML formatted string.
     */
    public static function fromXml($xmlStringContents, $ignoreXmlAttributes = true)
    {
        // Load the XML formatted string into a Simple XML Element object.
        $simpleXmlElementObject = XmlSecurity::scan($xmlStringContents);

        // If it is not a valid XML content, throw an exception.
        if (! $simpleXmlElementObject) {
            throw new Exception\RuntimeException('Function fromXml was called with invalid XML');
        }

        // Call the recursive function to convert the XML into a PHP array.
        $resultArray = static::processXml($simpleXmlElementObject, $ignoreXmlAttributes);

        // Convert the PHP array to JSON using Json::encode.
        return Json::encode($resultArray);
    }

    /**
     * Return the value of an XML attribute text or the text between the XML tags.
     *
     * In order to allow Zend\Json\Expr from XML, we check if the node matches
     * the pattern, and, if so, we return a new Zend\Json\Expr instead of a
     * text node.
     *
     * @param SimpleXMLElement $simpleXmlElementObject
     * @return Expr|string
     */
    protected static function getXmlValue($simpleXmlElementObject)
    {
        $pattern   = '/^[\s]*new Zend[_\\]Json[_\\]Expr[\s]*\([\s]*[\"\']{1}(.*)[\"\']{1}[\s]*\)[\s]*$/';
        $matchings = [];
        $match     = preg_match($pattern, $simpleXmlElementObject, $matchings);

        if ($match) {
            return new Expr($matchings[1]);
        }

        return (trim(strval($simpleXmlElementObject)));
    }

    /**
     * processXml - Contains the logic for fromJson()
     *
     * The logic in this function is a recursive one.
     *
     * The main caller of this function (fromXml) needs to provide only the
     * first two parameters (the SimpleXMLElement object and the flag for
     * indicating whether or not to ignore XML attributes).
     *
     * The third parameter will be used internally within this function during
     * the recursive calls.
     *
     * This function converts a SimpleXMLElement object into a PHP array by
     * calling a recursive function in this class; once all XML elements are
     * stored to a PHP array, it is returned to the caller.
     *
     * @param SimpleXMLElement $simpleXmlElementObject
     * @param bool $ignoreXmlAttributes
     * @param int $recursionDepth
     * @return array
     * @throws Exception\RecursionException if the XML tree is deeper than the
     *     allowed limit.
     */
    protected static function processXml($simpleXmlElementObject, $ignoreXmlAttributes, $recursionDepth = 0)
    {
        // Keep an eye on how deeply we are involved in recursion.
        if ($recursionDepth > static::$maxRecursionDepthAllowed) {
            // XML tree is too deep. Exit now by throwing an exception.
            throw new Exception\RecursionException(sprintf(
                'Function processXml exceeded the allowed recursion depth of %d',
                static::$maxRecursionDepthAllowed
            ));
        }

        $children   = $simpleXmlElementObject->children();
        $name       = $simpleXmlElementObject->getName();
        $value      = static::getXmlValue($simpleXmlElementObject);
        $attributes = (array) $simpleXmlElementObject->attributes();

        if (! count($children)) {
            if (! empty($attributes) && ! $ignoreXmlAttributes) {
                foreach ($attributes['@attributes'] as $k => $v) {
                    $attributes['@attributes'][$k] = static::getXmlValue($v);
                }

                if (! empty($value)) {
                    $attributes['@text'] = $value;
                }

                return [$name => $attributes];
            }

            return [$name => $value];
        }

        $childArray = [];
        foreach ($children as $child) {
            $childname = $child->getName();
            $element   = static::processXml($child, $ignoreXmlAttributes, $recursionDepth + 1);

            if (! array_key_exists($childname, $childArray)) {
                $childArray[$childname] = $element[$childname];
                continue;
            }

            if (empty($subChild[$childname])) {
                $childArray[$childname] = [$childArray[$childname]];
                $subChild[$childname]   = true;
            }

            $childArray[$childname][] = $element[$childname];
        }

        if (! empty($attributes) && ! $ignoreXmlAttributes) {
            foreach ($attributes['@attributes'] as $k => $v) {
                $attributes['@attributes'][$k] = static::getXmlValue($v);
            }
            $childArray['@attributes'] = $attributes['@attributes'];
        }

        if (! empty($value)) {
            $childArray['@text'] = $value;
        }

        return [$name => $childArray];
    }
}
