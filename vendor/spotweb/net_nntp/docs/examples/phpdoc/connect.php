$posting = $nntp->connect('news.php.net');
if (PEAR::isError($posting)) {
    // handle error
}
