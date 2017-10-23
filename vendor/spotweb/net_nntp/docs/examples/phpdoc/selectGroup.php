$groupsummary = $nntp->selectGroup('php.pear.general');
if (PEAR::isError($groupsummary)) {
    // handle error
}

$group = $groupsummary['group'];
$count = $groupsummary['count'];
$first = $groupsummary['first'];
$last  = $groupsummary['last'];
