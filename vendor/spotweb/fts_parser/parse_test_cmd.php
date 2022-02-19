
Full Text Search Parser - Version 2.0 - Test

Operators: AND &amp; OR | NOT ! and escaped characters with \
Exact phrases can be ensured by using double or single quotes "like this" or 'exact phrase'
Nested operator order is supported using ( and )
Spaces between keywords can default to AND, OR, or none
Only considering uppercase AND, OR, and NOT as operators can be selected
Supports generating prepared statments or traditional SQL

Test Search Phrase: <?php
	$text = trim(fgets(STDIN));

	require_once 'parse_model.php';

	$o_parse = new parse_model();
	$o_parse->debug = true;
	$o_parse->use_prepared_sql = false;

	// Required for the PDO::quote() function.
	//$o_db = new PDO('pgsql:host=localhost;dbname=mydb', 'user', 'pass');

	if ( $text != '' )
	{
		if ( $o_parse->parse($text, 'fulltext') == false )
			echo "Message to user: [$o_parse->error_msg]\n\n";
		else
		{
			$query = "SELECT * FROM some_table WHERE ";

			if ( $o_parse->tsearch != '' )
				$query .= "fulltext @@ to_tsquery('$o_parse->tsearch') ";
				//$query .= "fulltext @@ to_tsquery(" . $o_db->quote($o_parse->tsearch) . ")\n";

			if ( $o_parse->ilike != '' )
				$query .= ($o_parse->tsearch != '' ? "AND " : '') . "($o_parse->ilike)";

			echo "\nSample Query: $query\n\n";

			/*
			$o_q = $o_db->prepare($query);

			foreach ( $o_parse->db_ilike_data as $varname => $value )
				$o_q->bindValue($varname, $value);

			$o_q->execute();
			*/
		}
	}
?>