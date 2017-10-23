$groups = $nntp->getGroups('*.pear.*');
if (PEAR::isError($groupsummary)) {
    // handle error
}

foreach ($groups as $group) {
    echo $group['group'], ': ';
    echo $group['first'], '-', $group['last'];
    echo ' (', $group['posting'], ')', "\r\n";
}
