$groupsummary = $nntp->selectGroup('php.pear.general');
if (PEAR::isError($groupsummary)) {
    // handle error
}

$article = $nntp->selectArticle(5);
if (PEAR::isError($article)) {
    // handle error
}

if ($article === false) {
    // article does not exist
} else {
    // success
}
