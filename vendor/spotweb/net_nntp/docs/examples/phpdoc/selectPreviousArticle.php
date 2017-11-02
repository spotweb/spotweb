$article = $nntp->selectPreviousArticle();
if (PEAR::isError($article)) {
    // handle error
}

if ($article === false) {
    // article does not exist
} else {
    // success
}
