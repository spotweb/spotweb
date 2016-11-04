Full Text Search Parser for TSearch2 and PostgreSQL
===================================================

Homepage: http://digitalstratum.com/oss/fts_parser

[Try out the parser](http://digitalstratum.com/oss/fts_parser_test)


Latest News
-----------

March 31, 2008: **Version 2.0 Release**  
Version 2.0 released after a full rewrite to correct some bugs found in version
1, in addition to making it possible to add new features. I only wrote version 2
for PHP5, so if you are still running PHP4 (you should really consider
upgrading) then you will have to make the appropriate modifications yourself.
Really the only thing non-PHP4 is the use of PHP5's class constants instead of
global defines.

July 14, 2006: **Initial Release**  
The Full Text Search Parser for TSearch2 and PostgreSQL is officially released
with little fanfare.


Description
-----------

This is a full text search parser written in PHP for use with the
[TSearch2](http://www.sai.msu.su/~megera/postgres/gist/tsearch/V2/) extension
to [PostgreSQL](http://www.postgresql.org/). TSearch2 adds a full-featured,
integrated, and fast full text search for any Postgres database, but the search
syntax uses & for AND, | for OR, and ! for NOT, which are more suited to
programmers than the users who will generally be using the search. Most people
just want to type words, in which case there is generally an assumed "AND"
operation between the words. Last, when the search is complete, users generally
like to see the search words highlighted in the result set. I wrote this parser
to solve all of the above problems and it is currently deployed in a real-world
application. I also wanted to get into writing languages and parsers, so this
was a good way to introduce myself to the topic without getting overly complex.


### What does it cost?

I'm releasing the PHP source under the BSD Open Source License, so basically
it's free. However, if you use this code in a commercial project, a donation
would be greatly appreciated.


### Requirements

System requirements are as follows:

1. A PostgreSQL database installation with the TSearch2 extension.
1. PHP 5.x


### Features

These are the primary features of the parser:

* Simple to use.
* Entirely written in PHP with no dependencies.
* Allows the user to enter search strings in a natural way and can apply a
  default "AND" or "OR" between keywords.
* Provides error information suitable for user consumption.
* Supports unlimited depth of expression nesting using parenthesis.
* Supports exact phrase searches using double or single quotes.
* Will generate results for use as a normal SQL query or for creating prepared
  statements.
* Returns an array of individual keywords for highlighting the result sets.


Installation and Configuration
------------------------------

### Install

Extract the .tgz and you will have three files:

* **parse_model.php** - Main parser code. I design and write my code using a
  MVC (model, view, control) paradigm, and the parser is a model. In this case
  it simply defines a class so it should work as a drop-in for any project.
* **parse_test.php** - A simple test program that can be run from a browser.
* **parse_test_cmd.php** - Same as the test program above, but runs from the
  command line.

Put the parse_model.php file in your project directory and include it where ever
you need the parser. Here is a very simple example:

```php
require_once 'parse_model.php';

// Get input into $text via form post or command line...
$text = '("black and white" or "cut \'n dry") and not (gray | grey)';

$o_parse = new parse_model();
$o_parse->debug = true;
$o_parse->use_prepared_sql = true;

$o_db = new PDO('pgsql:host=localhost;dbname=mydb', 'user', 'pass');

if ( $text != '' )
{
    if ( $o_parse->parse($text, 'fulltext') == false )
        echo "Message to user: [$o_parse->error_msg]\n\n";
    else
    {
        $query = "SELECT * FROM some_table WHERE ";

        // The tsearch clause does NOT come back escaped.
        if ( $o_parse->tsearch != '' )
            $query .= "fulltext @@ to_tsquery(" . $o_db->quote($o_parse->tsearch) . ") ";

        // When $o_parse->use_prepared_sql is true, the values for the ILIKE are NOT
        // escaped, otherwise the clause that comes back will have single quotes
        // escaped with the character passed to the parse() function (which uses a
        // single quote as the default).  Because of how the ILIKE statement has to
        // be built, the escaping must be performed at parse time.
        if ( $o_parse->ilike != '' )
            $query .= ($o_parse->tsearch != '' ? "AND " : '') . "($o_parse->ilike)";

        echo "\nSample Query: $query\n\n";

        $o_q = $o_db->prepare($query);

        // Bind the ILIKE clause variables because $o_parse->use_prepared_sql was
        // set to true.  PDO will ensure the values are escaped properly (one of
        // the many reasons for using PDO).
        foreach ( $o_parse->db_ilike_data as $varname => $value )
            $o_q->bindValue($varname, $value);

        $o_q->execute();
    }
}
```

### Configuration

Make sure the array of stop-words in the stop_words() function matches the same
list defined for your installation of TSearch2, usually found at:
/usr/local/pgsql/share/contrib/english.stop


Using the Parser
----------------

Include the parse_model.php file and create a parse object:

```php
require_once 'parse_model.php';
$o_parse = new parse_model();
```

Class parse_model API:

Input Variable Members:

* **$debug** is a boolean to enable or disable debug information. Defaults to
  false. You will certainly want to keep this set to false in a production
  environment. When in debug mode the parser outputs debug information with echo
  statements which will certainly cause output to the browser or command shell.

* **$upper_op_only** is a boolean that determines if the word-based operators
  "AND", "OR", and "NOT" must be typed in uppercase by the user to be considered
  as operators. Defaults to false.

* **$use_prepared_sql** is a boolean that determines if the generated ILIKE
  statments will be in Prepared Statement format (recommended) or regular SQL
  format. When false and output is regular SQL, any single quotes are escaped in
  the PostgreSQL specific way of doubling the single quote. Defaults to true.

* **set_default_op($op)** is a boolean that determines if the generated ILIKE
  statments will be in Prepared Statement format (recommended) or regular SQL
  format. When false (output is regular SQL) any single quotes are escaped in
  the PostgreSQL specific way of doubling the single quote. Defaults to true.


Function Members:

* **set_default_op($op)** is used to set the default operator to be injected
  between search words and quoted phrases that do not have an operator between
  them. Can be set to "and", "or", or "none". Defaults to "and".

* **parse($text, $db_field)** parses the input $text into TSearch2 and ILIKE
  parts and returns false if there was an error, otherwise true. If the return
  value is false the $error_msg variable will contain a human readable error
  message, and the contents of $keywords, $tsearch, $ilike, and $db_ilike_data
  are undefined and should not be used. The $db_field parameter should be set to
  the database table field name to be used in the TSsearch2 and ILIKE parts of
  the results.


Output Variable Members:

* **$keywords** is an array that is filled with all keywords found.

* **$tsearch** is a string containing the TSearch2 part of the SQL WHERE clause.

* **$ilike** is a string containing the ILIKE part of the SQL WHERE clause in
  either Prepared Statement format or regular SQL.

* **$db_ilike_data** is an array containing the ILIKE Prepared Statement
  references as keys, and the search strings as values.

* **$error_msg** is a string containing a human readable error message if
  parse() returned false.

* **$error_tok** is a string containing the token where the error occurred.

* **$error_pos** is an integer of the character offset in the original string
  where the error occurred.


Notes
-----

TSearch2 looks hard on the surface, but is really very easy to install, set up,
and even add to an existing database! I suggest reading through the introduction
page to get started with TSearch2 if you have not already.

One of the main reasons for writing a full blown parser for this, instead of
trying a bunch of fancy regex, has to do with dealing with searching on phrases
(text in double or single quotes). TSearch2 is a word indexer, and therefore
cannot perform true phrase searches. For example, if you searched on 'computer
& programmer', TSearch2 would return all matches that contained both words, but
they would not necessarily be next to each other, which may not matter. However,
if you searched for '"computer programmer"', indicating you wanted that phrases,
i.e. those two words have to appear in the text and they must be consecutive,
TSearch2 cannot guarantee such a match.

The way to include phrase searches with TSearch2 is to include a LIKE or ILIKE
(case insensitive LIKE) in the WHERE clause of the SQL statement. The problem
gets a little complex though, when trying to extract any phrases from the user
supplied search string since full nested parenthesis are supported. The ILIKE
clause also has to retain the logic (AND, OR, and NOT) between possible multiple
phrases in a single search. For example, is someone searched on: 'computer
("c++" or "visual basic")' then the ILIKE would be: 'AND (text ILIKE '%c++%' OR
text ILIKE '%visual basic%')' The attachment of the ILIKE expression to the
WHERE clause is with AND, but the two phrases are an OR condition which must be
retained.

In light of the complexity, I decided early on that this would be much easier to
do with a real parser instead of trying to use regex. However, it could also be
that my regex knowledge is not up to the task since I know regex does support
recursive back-references, but I never dug into regex that deeply. I wanted to
write a parser anyway... :-)

I have a few more notes about highlighting the keywords and phrases in the
result set, but it's late and I need to sleep. Those notes will be forth coming,
but briefly it has to do with the fact that TSearch2 "stemms" the words it
indexes and searches with.
