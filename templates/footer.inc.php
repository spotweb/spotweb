
		</div>
			
		<div class="span-24">
			<!-- The Footer -->
			<br>			
		</div>
<?php
echo "<table cellpadding='10' cellspacing='100%'><tr><td><a href=?";
if (!empty($filter['tree']) && !empty($filter['text']))
{
	echo "search[tree]=" . $filter['tree'] . "&amp;search[type]=" . $filter['type'] ."&amp;search[text]=" . $filter['text'] ."&amp;";
} else {
	if (!empty($filter['tree']))
	{
		echo "search[tree]=" . $filter['tree'] . "&amp;";
	}

	if (!empty($filter['text']))
	{
		echo "search[type]=" . $filter['type'] ."&amp;search[text]=" . $filter['text'] . "&amp;";
	}
}

echo "offset=" . $prevoffset . ">&lt;&lt;  Vorige</a></td><td>         </td>";

echo "<td><a href=?";
if (!empty($filter['tree']) && !empty($filter['text']))
{
	echo "search[tree]=" . $filter['tree'] . "&amp;search[type]=" . $filter['type'] ."&amp;search[text]=" . $filter['text'] ."&amp;";
} else {
	if (!empty($filter['tree']))
	{
		echo "search[tree]=" . $filter['tree'] . "&amp;";
	}

	if (!empty($filter['text']))
	{
		echo "search[type]=" . $filter['type'] ."&amp;search[text]=" . $filter['text'] . "&amp;";
	}
}
echo "offset=" . $nextoffset . ">Volgende &gt;&gt;</a></td></tr></table>";

?>
	</body>
</html>	
