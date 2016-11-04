<h2 class="stdtitle">Full Text Search Parser Try Out</h2>

<p>Back to the <a href="<?= URL;?>oss/fts_parser">Full Text Parser</a> page.</p>

<?php
	$slash_gpc_set = (bool) ini_get('magic_quotes_gpc');

	$upper_op_only = isset($_POST['upper_op_only']);
	$use_prepared_sql = isset($_POST['use_prepared_sql']);

	$op = 'and';
	if ( isset($_POST['op']) )
		$op = ($_POST['op'] == 'and' || $_POST['op'] == 'or' ? $_POST['op'] : '');

	$keyword = '';
	if ( isset($_POST['keyword']) )
		$keyword = ($slash_gpc_set == true ? stripslashes($_POST['keyword']) : $_POST['keyword']);

	$field_name = 'fulltext';
	if ( isset($_POST['field_name']) )
		$field_name = ($slash_gpc_set == true ? stripslashes($_POST['field_name']) : $_POST['field_name']);
?>

<ul>
	<li>Operators: AND &amp; OR | NOT ! and escaped characters with \</li>
	<li>Exact phrases can be ensured by using double or single quotes "like this" or 'exact phrase'</li>
	<li>Nested operator order is supported using ( and )</li>
	<li>Spaces between keywords can default to AND, OR, or none</li>
	<li>Only considering uppercase AND, OR, and NOT as operators can be selected</li>
	<li>Supports generating prepared statments or traditional SQL</li>
</ul>

<form action="<?= URL;?>oss/fts_parser_test" method="post">

<p>Some examples might be:</p>
<ul>
	<li><pre>black and white</pre></li>
	<li><pre>("black and white" or "cut 'n dry") and not (gray | grey)</pre></li>
	<li><pre>sales and not "Management Assistant"</pre></li>
</ul>

<p><input name="upper_op_only" type="checkbox" value="t"<?php echo ($upper_op_only ? ' checked="checked"' : ''); ?>" /> Operators AND, OR, and NOT must be uppercase to be considered as operators.</p>

<p><input name="use_prepared_sql" type="checkbox" value="t"<?php echo ($use_prepared_sql ? ' checked="checked"' : ''); ?>" /> Generate Prepared Statement ILIKE Data</p>

<p><select name="op">
	<option value="none"<?php echo ($op == '' ? ' selected="selected"' : ''); ?>" />None</option>
	<option value="and"<?php echo ($op == 'and' ? ' selected="selected"' : ''); ?>" />AND</option>
	<option value="or"<?php echo ($op == 'or' ? ' selected="selected"' : ''); ?>" />OR</option>
</select> Default operator between words</p>

<p>Database Field Name: <input name="field_name" type="text" value="<?php echo htmlspecialchars($field_name); ?>" size="80" /></p>

<p>Test Search Phrase: <input name="keyword" type="text" value="<?php echo htmlspecialchars($keyword); ?>" size="80" /></p>
<input type="submit" />
</form>

<pre>
<?php

	require_once 'parse_model.php';

	// Required for the PDO::quote() function.
	$o_db = new PDO('pgsql:host=localhost;dbname=mydb', 'user', 'pass');

	$o_parse = new parse_model();
	$o_parse->debug = true;
	$o_parse->upper_op_only = $upper_op_only;
	$o_parse->use_prepared_sql = $use_prepared_sql;
	$o_parse->set_default_op($op);

	if ( $keyword != '' )
	{
		if ( $o_parse->parse($keyword, $field_name) == false )
			echo "Message to user: [$o_parse->error_msg]\n\n";
		else
		{
			$query = "SELECT * FROM some_table WHERE\n";

			if ( $o_parse->tsearch != '' )
				$query .= "fulltext @@ to_tsquery(" . $o_db->quote($o_parse->tsearch) . ")\n";

			if ( $o_parse->ilike != '' )
				$query .= ($o_parse->tsearch != '' ? "AND " : '') . "\n($o_parse->ilike)";

			echo "\nSAMPLE QUERY:\n\n$query\n\n";
		}
	}

?>
</pre>