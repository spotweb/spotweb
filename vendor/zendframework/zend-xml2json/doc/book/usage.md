# XML to JSON conversion

zend-xml2json provides a convenience method for transforming XML formatted data
into JSON format.  This feature was inspired from an [IBM developerWorks
article](http://www.ibm.com/developerworks/xml/library/x-xml2jsonphp/).

zend-xml2json provides the static method `Zend\Xml2Json\Xml2Json::fromXml()`.
This method will generate JSON from a given XML input. This method takes any
arbitrary XML string as an input parameter, and optionally a boolean parameter
to instruct the conversion logic as to whether or not to ignore XML attributes
during the conversion process. If the optional flag for converting attributes is
not provided, then the default behavior is to ignore the XML attributes.

A basic example follows:

```php
$jsonContents = Zend\Xml2Json\Xml2Json::fromXml($xmlStringContents, true);
```

`Zend\Xml2Json\Xml2Json::fromXml()` converts the XML formatted string input
parameter and returns the equivalent JSON formatted string output. In case of an
XML input format error or conversion logic error, it raises an exception.

The conversion logic uses recursive techniques to traverse the XML tree,
supporting up to 25 levels deep. Beyond that depth, it raises a
`Zend\Xml2Json\Exception\RecursionException`.

## Example

The following example demonstrates both the XML input string passed to and the JSON
output string returned from `Zend\Xml2Json\Xml2Json::fromXml()`. This example
passes a boolean false to the second argument in order to receive a
representation of the XML attributes in the returned JSON.

First, let's look at the XML:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<books>
    <book id="1">
        <title>Code Generation in Action</title>
        <author><first>Jack</first><last>Herrington</last></author>
        <publisher>Manning</publisher>
    </book>

    <book id="2">
        <title>PHP Hacks</title>
        <author><first>Jack</first><last>Herrington</last></author>
        <publisher>O'Reilly</publisher>
    </book>

    <book id="3">
        <title>Podcasting Hacks</title>
        <author><first>Jack</first><last>Herrington</last></author>
        <publisher>O'Reilly</publisher>
    </book>
</books>
```

Assuming that the above is captured in the variable `$xml`, we'll now pass it to
the following code:

```php
// Passing the second parameter to ensure we get attributes as well.
$json = Zend\Xml2Json\Xml2Json::fromXml($xml, false);
```

This results in the following JSON:

```json
{
   "books" : {
      "book" : [ {
         "@attributes" : {
            "id" : "1"
         },
         "title" : "Code Generation in Action",
         "author" : {
            "first" : "Jack", "last" : "Herrington"
         },
         "publisher" : "Manning"
      }, {
         "@attributes" : {
            "id" : "2"
         },
         "title" : "PHP Hacks", "author" : {
            "first" : "Jack", "last" : "Herrington"
         },
         "publisher" : "O'Reilly"
      }, {
         "@attributes" : {
            "id" : "3"
         },
         "title" : "Podcasting Hacks", "author" : {
            "first" : "Jack", "last" : "Herrington"
         },
         "publisher" : "O'Reilly"
      }
   ]}
}
```
