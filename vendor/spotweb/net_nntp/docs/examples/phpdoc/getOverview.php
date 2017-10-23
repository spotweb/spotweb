// Detches single article
$overview = $nntp->getOverview(5);
$overview = $nntp->getOverview('<Message-ID>');
if (PEAR::isError($overview)) {
    // handle error
}

echo 'Number: ',  $overview['Number'],  "\r\n";
echo 'Subject: ', $overview['Subject'], "\r\n";
echo 'From: ',    $overview['From'],    "\r\n";
echo 'Date: ',    $overview['Date'],    "\r\n";


// Fetches multiple articles
$overview = $nntp->getOverview();
$overview = $nntp->getOverview('5-');
$overview = $nntp->getOverview('5-9');
if (PEAR::isError($overview)) {
    // handle error
}

foreach ($overview as $name => $content) {
    echo 'Number: ',  $content['Number'],  "\r\n";
    echo 'Subject: ', $content['Subject'], "\r\n";
    echo 'From: ',    $content['From'],    "\r\n";
    echo 'Date: ',    $content['Date'],    "\r\n";
}
