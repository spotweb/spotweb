$descriptions = $nntp->getDescriptions('*.pear.*');
if (PEAR::isError($descriptions)) {
    // handle error
}

foreach ($descriptions as $group => $description) {
    echo $group, ': ', $description, "\r\n";
}
