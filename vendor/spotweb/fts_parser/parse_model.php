<?php
	/*
	 * Full Text Search Parser for TSearch2 and PostgreSQL
	 *
	 * Converts a human readable search string into a TSearch2 query with
	 * ILIKE phrases.
	 *
	 * Copyright (c) 2006 - 2008, Digital Stratum, Inc.
	 * All rights reserved.
	 *
	 * Redistribution and use in source and binary forms, with or without
	 * modification, are permitted provided that the following conditions
	 * are met:
	 *
	 * 1. Redistributions of source code must retain the above copyright
	 *    notice, this list of conditions and the following disclaimer.
	 *
	 * 2. Redistributions in binary form must reproduce the above copyright
	 *    notice, this list of conditions and the following disclaimer in
	 *    the documentation and/or other materials provided with the
	 *    distribution.
	 *
	 * 3. Neither the name of Digital Stratum, Inc. nor the names of its
	 *    contributors may be used to endorse or promote products derived
	 *    from this software without specific prior written permission.
	 *
	 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
	 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
	 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
	 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
	 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
	 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
	 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
	 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
	 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF
	 * THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
	 * DAMAGE.
	 */


/**
 * Parse Class.
 *
 * Accepts a string to be parsed and returns the TSearch2 and ILIKE
 * parts, as well as a list of keywords and any error messages to
 * the user if invalid input was found.
 *
 * Example:
 *

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
 */
class
parse_model
{
	// Scanner states
	const START		= 0;
	const INID		= 1;
	const INSQUOTE	= 2;
	const INDQUOTE	= 3;
	const INESC		= 4;
	const DONE		= 5;

	/// End of File (input data) indicator.
	const EOF		= "\0";

	// TOKEN TYPES
	// Book keeping tokens
	const BEGINFILE	= 0;
	const ENDFILE	= 1;
	const ERROR		= 2;
	// Reserved words
	const ANDWORD	= 3;
	const ORWORD	= 4;
	const NOTWORD	= 5;
	// Multicharacter tokens
	const ID		= 6;
	const QSTRING	= 7;
	// Special symbols
	const ANDOP		= 8;
	const OROP		= 9;
	const NOTOP		= 10;
	const LPAREN	= 11;
	const RPAREN	= 12;

	private $token_name_lookup = array(
		self::BEGINFILE	=> 'BEGINFILE',
		self::ENDFILE	=> 'ENDFILE',
		self::ERROR		=> 'ERROR',
		self::ANDWORD	=> 'AND',
		self::ORWORD	=> 'OR',
		self::NOTWORD	=> 'NOT',
		self::ID		=> 'ID',
		self::QSTRING	=> 'QSTRING',
		self::ANDOP		=> 'ANDOP',
		self::OROP		=> 'OROP',
		self::NOTOP		=> 'NOTOP',
		self::LPAREN	=> 'LPAREN',
		self::RPAREN	=> 'RPAREN');

	/// Array of reserved words.
	private $reserved_words = array(
		'AND'	=> self::ANDWORD,
		'OR'	=> self::ORWORD,
		'NOT'	=> self::NOTWORD);

	/// Logical operators and grouping characters.
	private $operators = '&|!()';

	/// Last two logical tokens.
	private $expr_cache = array('last' => '', 'not' => '');

	private $token;			///> Holds the current token
	private $prev_token;	///> Holds the previous token for back reference
	private $token_string;	///> Lexeme of identifier or reserved word
	private $token_tsearch;	///> Lexeme of identifier or reserved word for tsearch
	private $syntax_error;	///> True if there was a syntax error
	private $stopwords;		///> Array of Stop Words
	private $lookahead;		///> Look ahead array to support supplying a default operator

	private $lparen_count;	///> Counter for open parens
	private $ilike_paren;	///> Counter to track the paren depth of ilike phrases

	// Input text and current position in text.
	private $linebuf;		///> Current input line buffer
	private $linepos;		///> Current position in current line
	private $bufsize;		///> Length of current line
	private $lineno;		///> Current line number

	/// Set to a value to add as a default operator between ID's if an operator is missing.
	private $use_default_op;
	private $default_op_str;

	/// The full field name of the database table to use in the ILIKE clause.
	private $db_field;

	/// The character used to escape single quotes.
	private $esc_char;

	/// Counter for generating db field referneces when generating a prepared SQL statement.
	private $db_field_count;

	/// Array of field data for each ILIKE clause when generating a prepared SQL statement.
	public $db_ilike_data;

	// Output search strings.
	public $keywords;		///> Array of keywords found
	public $tsearch;		///> tsearch2 component of query
	public $ilike;			///> ILIKE component of query
	public $error_msg;		///> Human readable error message
	public $error_tok;		///> Token where error detected
	public $error_pos;		///> Character position where error detected

	// Enable debug information.
	public $debug;			///> Set to true to enable debug messges

	/// Set to true to only consider operators AND, OR, NOT that are written in upper case.
	public $upper_op_only;

	/// Set to true to have the ILIKE component generated for use with a prepared SQL statement.
	public $use_prepared_sql;


	/**
	 * Constructor - initialize the scanner and parser.
	 *
	 * @param[in] $ilike_db_field The name of the database field that contains the full text data.
	 */
	public function
	__construct()
	{
		$this->stop_words();
		$this->set_default_op('and');

		$this->debug = false;
		$this->upper_op_only = false;
		$this->use_prepared_sql = true;
	}
	// __construct()


	/**
	 * Initialize the parser.
	 */
	private function
	init_parser()
	{
		$this->token = self::BEGINFILE;
		$this->prev_token = self::BEGINFILE;
		$this->syntax_error = false;
		$this->lookahead = array();

		$this->ilike_paren = 0;
		$this->lparen_count = 0;
		$this->db_field_count = 0;

		$this->db_ilike_data = array();
		$this->keywords = array();
		$this->tsearch = '';
		$this->ilike = '';
		$this->error_msg = '';
		$this->error_tok = '';
		$this->error_pos = 0;
	}
	// init_parser()


	/**
	 * Set the default operator.
	 *
	 * @param[in] $op Can be 'AND' or 'OR'.  Any other value disables the default operator.
	 */
	public function
	set_default_op($op)
	{
		if ( strtolower($op) == 'and' )
		{
			$this->use_default_op = self::ANDOP;
			$this->default_op_str = '&';
		}

		else if ( strtolower($op) == 'or' )
		{
			$this->use_default_op = self::OROP;
			$this->default_op_str = '|';
		}

		else
		{
			$this->use_default_op = false;
			$this->default_op_str = '';
		}
	}
	// set_default_op()


	/**
	 * Parser
	 *
	 * @param[in] $text Search phrase to parse.
	 * @param[in] $db_field The field name in the database table to use in the ILIKE clause.
	 */
	public function
	parse($text, $db_field, $esc_char = "'")
	{
		$this->init_parser();

		$this->db_field = $db_field;
		$this->esc_char = $esc_char;

		$this->linebuf = $text;
		$this->linepos = 0;
		$this->bufsize = strlen($text);
		$this->lineno = 1;

		if ( $this->debug )
			echo "\nINPUT: [$text]\n\n";


		$this->token = $this->get_token();

		// Possible starting tokens.
		switch ( $this->token )
		{
		case self::ID :
		case self::QSTRING :
		case self::NOTOP :
		case self::LPAREN :
			$this->expr();
			break;
		}


		if ( $this->token != self::ENDFILE )
		{
			$this->syntax_error();
			return false;
		}

		else if ( $this->syntax_error == true )
			return false;


		if ( $this->debug )
		{
			echo "\nRESULTS:\n\n";

			echo "Keywords:        ";
			foreach ( $this->keywords as $word )
				echo "[$word] ";
			echo "\n\n";

			echo "TSearch2 Clause: " . $this->tsearch . "\n\n";

			echo "ILIKE Clause:    " . $this->ilike . "\n\n";

			if ( $this->use_prepared_sql == true )
			{
				echo "ILIKE Prepared Statement Variables: " . count($this->db_ilike_data) . "\n";
				foreach ( $this->db_ilike_data as $varname => $value )
					echo "    $varname == $value\n";

				echo "\n";
			}

		}

		return true;
	}
	// parse()


	/**
	 * Match an expected token and read the next token.
	 */
	private function
	match($expected)
	{
		$this->prev_token = $this->token;

		if ( $this->token != $expected )
			$this->syntax_error();

		else
		{
			// If there is a look ahead value, use that instead of
			// a new token from the scanner.
			if ( count($this->lookahead) > 0 )
			{
				$a = array_shift($this->lookahead);
				$this->token = $a['tok'];
				$this->token_string = $a['str'];
			}
			else
				$this->token = $this->get_token();
		}
	}
	// match()


	/**
	 * Display a syntax error.
	 */
	private function
	syntax_error()
	{
		$this->syntax_error = true;

		if ( $this->debug && $this->error_msg == '' )
		{
			echo "\nSyntax Error at Character " . ($this->linepos - strlen($this->token_string) + 1) .
				": Unexpected Token -> [" . $this->token_name_lookup[$this->token] . "] " .
				"Token String: [$this->token_string]\n";
			echo "\n$this->linebuf\n" . str_repeat('-', $this->linepos - strlen($this->token_string)) . "^\n";
		}

		// Only save the first error, since most other errors will be caused by this one.
		if ( $this->error_msg == '' )
		{
			$this->error_tok = $this->token_string;
			$this->error_pos = $this->linepos - strlen($this->token_string) + 1;

			$this->error_msg = "Syntax Error in search phrase at character " .
				$this->error_pos . ": Unexpected [" . $this->token_string . "]";
		}

		return;
	}
	// syntax_error()


	/**
	 * Match an expression.
	 */
	private function
	expr()
	{
		$this->simple_expr();

		// Prevent double NOT.
		while ( $this->token == self::NOTOP && (
		$this->prev_token == self::BEGINFILE ||
		$this->prev_token == self::ANDOP ||
		$this->prev_token == self::OROP ||
		$this->prev_token == self::LPAREN) )
		{
			$this->tsearch .= '!';
			$this->expr_cache['not'] = 'NOT';

			$this->match(self::NOTOP);

			$this->simple_expr();
		}
	}
	// expr()


	/**
	 * Match a simple expression.
	 */
	private function
	simple_expr()
	{
		$this->factor();

		while ( ($this->token == self::ANDOP) || ($this->token == self::OROP) )
		{
			$this->expr_cache['not'] = '';

			switch ( $this->token )
			{
			case self::ANDOP :
				$this->tsearch .= '&';
				$this->expr_cache['last'] = 'AND';
				break ;

			case self::OROP :
				$this->tsearch .= '|';
				$this->expr_cache['last'] = 'OR';
				break ;
			}

			$this->match($this->token);
			$this->factor();
		}
	}
	// simple_expr()


	/**
	 * Match a factor.
	 */
	private function
	factor()
	{
		switch ( $this->token )
		{
		case self::ID :

			$this->tsearch .= $this->token_tsearch;
			$this->keywords[] = $this->token_string;

			if ( $this->ilike_paren > 0 )
			{
				$this->ilike .= ($this->expr_cache['last'] != '' && $this->ilike != ''
				? " {$this->expr_cache['last']} " : '') .
				( $this->lparen_count > 0 ? str_repeat('(', $this->lparen_count - $this->ilike_paren) : '' ) .
				$this->db_field . ($this->expr_cache['not'] != '' ? " {$this->expr_cache['not']}" : '') . " ILIKE ";

				$this->db_field_count++;
				$this->db_ilike_data[":ilike_data" . $this->db_field_count] = "%" . $this->token_string . "%";

				if ( $this->use_prepared_sql == false )
					$this->ilike .= "'%" . str_replace("'", $this->esc_char . "'", $this->token_string) . "%'";
				else
					$this->ilike .= ":ilike_data" . $this->db_field_count;
			}

			$this->match(self::ID);

			break;

		case self::QSTRING :

			$this->quoted_to_tsearch();

            // we cannot use empty() because 0 would also return true
            if (strlen($this->token_string) <> 0) {
                $this->keywords[] = $this->token_string;

                $this->ilike .= ($this->expr_cache['last'] != '' && $this->ilike != ''
                ? " {$this->expr_cache['last']} " : '') .
                ( $this->lparen_count > 0 ? str_repeat('(', $this->lparen_count - $this->ilike_paren) : '' ) .
                $this->db_field . ($this->expr_cache['not'] != '' ? " {$this->expr_cache['not']}" : '') . " ILIKE ";

                $this->db_field_count++;
                $this->db_ilike_data[":ilike_data" . $this->db_field_count] = "%" . $this->token_string . "%";

                if ( $this->use_prepared_sql == false )
                    $this->ilike .= "'%" . str_replace("'", $this->esc_char . "'", $this->token_string) . "%'";
                else
                    $this->ilike .= ":ilike_data" . $this->db_field_count;
            }

			// Remember how many paren levels deep the ILIKE clause is.
			if ( $this->lparen_count > $this->ilike_paren )
				$this->ilike_paren = $this->lparen_count;

			$this->match(self::QSTRING);
			break;

		case self::LPAREN :

			$this->lparen_count++;
			$this->tsearch .= '(';
			$this->match(self::LPAREN);

			$this->expr();

			$this->tsearch .= ')';

			// If there were no quoted phrases in the expression, prevent and empty
			// paren set () in the ILIKE string.
			if ( $this->ilike_paren > 0 )
			{
				$this->ilike_paren--;
				$this->ilike .= ')';
			}

			$this->lparen_count--;
			$this->match(self::RPAREN);

			break;

		case self::NOTOP :

			break;

		default :
			$this->syntax_error();
			$this->token = $this->get_token();
			break;
		}

		// Check if using a default operator between ID's and/or QSTRING's, or between an
		// ID or QSTRING and the start of an expression.
		if ( $this->use_default_op !== false &&
		($this->token == self::ID || $this->token == self::QSTRING || $this->token == self::LPAREN) )
		{
			// Save the ID and inject the default operator.
			$this->lookahead[] = array('tok' => $this->token, 'str' => $this->token_string);
			$this->token = $this->use_default_op;
			$this->token_string = $this->default_op_str;

			if ( $this->debug )
				echo sprintf("%-10s %-10s %s\n", 'Inject:', $this->token_name_lookup[$this->token], $this->token_string);
		}
	}
	// factor()


	/**
	 * Adds a quoted string's keywords to the tsearch.
	 */
	private function
	quoted_to_tsearch()
	{
		$op = '';
		$save = '&';
		// If quoted string has NOT applied to it, invert the tsearch operator.
		if ( $this->expr_cache['not'] == 'NOT' )
			$save = '|';

        $tmpBuild = '(';

		$a = explode(' ', $this->token_tsearch);

		foreach ( $a as $str )
		{
			if ( strlen($str) < 2 || isset($this->stopwords[$str]) )
				continue;

			$tmpBuild .= $op . $str;
			$op = $save;
		}

        // dont add empty strings (eg: search for ""Whee"")
        if ($tmpBuild <> '(') {
            $this->tsearch .= $tmpBuild . ')';
        }
	}
	// quoted_to_tsearch()


	/*
	 *
	 * SCANNER
	 *
	 */


	/**
	 * Fetches the next character from the input data.
	 */
	private function
	get_next_char()
	{
		if ( $this->linepos >= $this->bufsize )
		{
			// End of the buffer.
			if ( $this->bufsize != -1 )
			{
				$this->bufsize = -1;
				$this->lineno++;
			}

			$c = self::EOF;
		}

		else
			$c = $this->linebuf[$this->linepos++];

		return $c;
	}
	// get_next_char()


	/**
	 * Backtrack one character of the input data.
	 */
	private function
	unget_next_char()
	{
		// Sanity check.
		if ( $this->linepos > 0 )
			$this->linepos--;
	}
	// unget_next_char()


	/**
	 * Lookup and identifier to see if it is a reserved word.
	 */
	private function
	reserved_lookup($word)
	{
		if ( isset($this->reserved_words[$word]) )
			return $this->reserved_words[$word];

		return self::ID;
	}
	// reserved_lookup()


	/**
	 * Returns the next token from the input data.
	 */
	private function
	get_token()
	{
		$this->token_string = '';
		$this->token_tsearch = '';
		$state = self::START;
		$c = 0;

		while ( $state != self::DONE )
		{
			$pre = $c;
			$c = $this->get_next_char();
			$save = true;

			switch ( $state )
			{
			case self::START :

				if ( $c == "'" )
				{
					$save = false;
					$state = self::INSQUOTE;
				}

				else if ( $c == '"' )
				{
					$save = false;
					$state = self::INDQUOTE;
				}

				else if ( $c == "\\" )
				{
					$save = false;
					$state = self::INESC;
				}

				else if ( $this->isspace($c) )
					$save = false;

				else if ( ord($c) > 32 && strpos($this->operators, $c) === false )
					$state = self::INID;

				else
				{
					$state = self::DONE;

					switch ( $c )
					{
					case self::EOF :
						$save = false;
						$current_token = self::ENDFILE;
						break;

					case '&' :
						$current_token = self::ANDOP;
						break;

					case '|' :
						$current_token = self::OROP;
						break;

					case '!' :
						$current_token = self::NOTOP;
						break;

					case '(' :
						$current_token = self::LPAREN;
						break;

					case ')' :
						$current_token = self::RPAREN;
						break;

					default :
						$current_token = self::ERROR;
						break;
					}
				}

				break;

			case self::INESC :

				$state = self::INID;
				break;

			case self::INSQUOTE :
			case self::INDQUOTE :

				if ( ord($c) < 32 )
				{
					$this->unget_next_char();
					$save = false;
					$state = self::DONE;
					$current_token = self::ID;
				}

				else if ( $c == "\\" && $pre != "\\" )
					$save = false;

				else if ( $c == ($state == self::INDQUOTE ? '"' : "'") && $pre != "\\" )
				{
					$save = false;
					$state = self::DONE;
					$current_token = self::QSTRING;
				}

				break;

			case self::INID :

				if ( $c == "\\" && $pre != "\\" )
					$save = false;

				else if ( ord($c) < 32 ||
				($pre != "\\" && ($this->isspace($c) || strpos($this->operators, $c) !== false)) )
				{
					// Backup in the input.
					$this->unget_next_char();
					$save = false;
					$state = self::DONE;
					$current_token = self::ID;
				}

				break;

			case self::DONE :
			default :
				// Should never happen
				if ( $this->debug )
					echo "Scanner Bug: state=[$state]\n";

				$state = self::DONE;
				$current_token = self::ERROR;
				break;
			}

			if ( $save == true )
			{
				$this->token_string .= $c;

				// tsearch gets its own token string because anything other than alpha-numeric
				// will not work with tsearch, but will work with the ILIKE phrase.
				if ( $this->isalnum($c) || $c == '*' || $this->isspace($c) || $c == "'" || $c == '"' ) {
					$this->token_tsearch .= $c;
                } else {
					$this->token_tsearch .= '=';
                } # else
			}

			if ( $state == self::DONE && $current_token == self::ID )
			{
				if ( $this->upper_op_only == true )
					$current_token = $this->reserved_lookup($this->token_string);
				else
					$current_token = $this->reserved_lookup(strtoupper($this->token_string));
			}
		}

		if ( $this->debug )
			echo sprintf("%-10s %-10s %s\n", 'Token:', $this->token_name_lookup[$current_token], $this->token_string);

        /**
         * Replace a trailing wildcard char with a valid operator,
         * and replace all others with an =.
         *
         * we have to make sure we don't replace our own wildcard operator
         */
        if (substr($this->token_tsearch, -1) == '*') {
            $this->token_tsearch = substr($this->token_tsearch, 0, -1) . ':*';
        } # if
        if (!empty($strpos) && (strpos('*', $this->token_tsearch) !== false)) {
            $this->token_tsearch = str_replace('*', '=', substr($this->token_tsearch, 0, -1));
        } # if


// Consolidate 'AND', 'OR', and 'NOT' into '&',  '|',  and '!' respectively.
		if ( $current_token == self::NOTWORD )
			$current_token = self::NOTOP;

		else if ( $current_token == self::ANDWORD )
			$current_token = self::ANDOP;

		else if ( $current_token == self::ORWORD )
			$current_token = self::OROP;

		return $current_token;
	}
	// get_token()


	/*
	 * Build an array of stop words.
	 *
	 * Must be the same list as in /usr/local/pgsql/share/contrib/english.stop
	 */
	private function
	stop_words()
	{
		$this->stopwords = array(
		'i'				=> 'i',
		'me'			=> 'me',
		'my'			=> 'my',
		'myself'		=> 'myself',
		'we'			=> 'we',
		'our'			=> 'our',
		'ours'			=> 'ours',
		'ourselves'		=> 'ourselves',
		'you'			=> 'you',
		'your'			=> 'your',
		'yours'			=> 'yours',
		'yourself'		=> 'yourself',
		'yourselves'	=> 'yourselves',
		'he'			=> 'he',
		'him'			=> 'him',
		'his'			=> 'his',
		'himself'		=> 'himself',
		'she'			=> 'she',
		'her'			=> 'her',
		'hers'			=> 'hers',
		'herself'		=> 'herself',
		'it'			=> 'it',
		'its'			=> 'its',
		'itself'		=> 'itself',
		'they'			=> 'they',
		'them'			=> 'them',
		'their'			=> 'their',
		'theirs'		=> 'theirs',
		'themselves'	=> 'themselves',
		'what'			=> 'what',
		'which'			=> 'which',
		'who'			=> 'who',
		'whom'			=> 'whom',
		'this'			=> 'this',
		'that'			=> 'that',
		'these'			=> 'these',
		'those'			=> 'those',
		'am'			=> 'am',
		'is'			=> 'is',
		'are'			=> 'are',
		'was'			=> 'was',
		'were'			=> 'were',
		'be'			=> 'be',
		'been'			=> 'been',
		'being'			=> 'being',
		'have'			=> 'have',
		'has'			=> 'has',
		'had'			=> 'had',
		'having'		=> 'having',
		'do'			=> 'do',
		'does'			=> 'does',
		'did'			=> 'did',
		'doing'			=> 'doing',
		'a'				=> 'a',
		'an'			=> 'an',
		'the'			=> 'the',
		'and'			=> 'and',
		'but'			=> 'but',
		'if'			=> 'if',
		'or'			=> 'or',
		'because'		=> 'because',
		'as'			=> 'as',
		'until'			=> 'until',
		'while'			=> 'while',
		'of'			=> 'of',
		'at'			=> 'at',
		'by'			=> 'by',
		'for'			=> 'for',
		'with'			=> 'with',
		'about'			=> 'about',
		'against'		=> 'against',
		'between'		=> 'between',
		'into'			=> 'into',
		'through'		=> 'through',
		'during'		=> 'during',
		'before'		=> 'before',
		'after'			=> 'after',
		'above'			=> 'above',
		'below'			=> 'below',
		'to'			=> 'to',
		'from'			=> 'from',
		'up'			=> 'up',
		'down'			=> 'down',
		'in'			=> 'in',
		'out'			=> 'out',
		'on'			=> 'on',
		'off'			=> 'off',
		'over'			=> 'over',
		'under'			=> 'under',
		'again'			=> 'again',
		'further'		=> 'further',
		'then'			=> 'then',
		'once'			=> 'once',
		'here'			=> 'here',
		'there'			=> 'there',
		'when'			=> 'when',
		'where'			=> 'where',
		'why'			=> 'why',
		'how'			=> 'how',
		'all'			=> 'all',
		'any'			=> 'any',
		'both'			=> 'both',
		'each'			=> 'each',
		'few'			=> 'few',
		'more'			=> 'more',
		'most'			=> 'most',
		'other'			=> 'other',
		'some'			=> 'some',
		'such'			=> 'such',
		'no'			=> 'no',
		'nor'			=> 'nor',
		'not'			=> 'not',
		'only'			=> 'only',
		'own'			=> 'own',
		'same'			=> 'same',
		'so'			=> 'so',
		'than'			=> 'than',
		'too'			=> 'too',
		'very'			=> 'very',
		's'				=> 's',
		't'				=> 't',
		'can'			=> 'can',
		'will'			=> 'will',
		'just'			=> 'just',
		'don'			=> 'don',
		'should'		=> 'should',
		'now'			=> 'now');
	}
	// stop_words()


	/* Functions: Converted from <ctype.h>.
	 * Author: John Millaway
	 *
	 * Note: These functions expect a character,
	 * such as 'a', or '?', not an integer.
	 * If you want to use integers, first convert
	 * the integer using the chr() function.
	 *
	 * Examples:
	 *
	 * isalpha('a'); // returns 1
	 * isalpha(chr(97)); // same thing
	 *
	 * isdigit(1); // NO!
	 * isdigit('1'); // yes.
	 */
	private function isalnum ($c){ return ((($this->ctype__[( ord($c) )]&(01 | 02 | 04 )) != 0)?1:0);}
	private function isalpha ($c){ return ((($this->ctype__[( ord($c) )]&(01 | 02 )) != 0)?1:0);}
	private function isascii ($c){ return (((( ord($c) )<=0177) != 0)?1:0);}
	private function iscntrl ($c){ return ((($this->ctype__[( ord($c) )]& 040 ) != 0)?1:0);}
	private function isdigit ($c){ return ((($this->ctype__[( ord($c) )]& 04 ) != 0)?1:0);}
	private function isgraph ($c){ return ((($this->ctype__[( ord($c) )]&(020 | 01 | 02 | 04 )) != 0)?1:0);}
	private function islower ($c){ return ((($this->ctype__[( ord($c) )]& 02 ) != 0)?1:0);}
	private function isprint ($c){ return ((($this->ctype__[( ord($c) )]&(020 | 01 | 02 | 04 | 0200 )) != 0)?1:0);}
	private function ispunct ($c){ return ((($this->ctype__[( ord($c) )]& 020 ) != 0)?1:0);}
	private function isspace ($c){ return ((($this->ctype__[( ord($c) )]& 010 ) != 0)?1:0);}
	private function isupper ($c){ return ((($this->ctype__[( ord($c) )]& 01 ) != 0)?1:0);}
	private function isxdigit ($c){ return ((($this->ctype__[( ord($c) )]&(0100 | 04 )) != 0)?1:0);}
	private $ctype__ = array(
	32,32,32,32,32,32,32,32,32,40,40,40,40,40,32,32,32,32,32,32,32,32,32,32,32,32,32,32,32,32,32,32,
	-120,16,16,16,16,16,16,16,16,16,16,16,16,16,16,16,4,4,4,4,4,4,4,4,4,4,16,16,16,16,16,16,
	16,65,65,65,65,65,65,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,16,16,16,16,16,
	16,66,66,66,66,66,66,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,16,16,16,16,32,
	0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
	0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
	0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
	0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);


}
// parse_model
?>