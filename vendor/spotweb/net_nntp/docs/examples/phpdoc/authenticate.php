$authenticated = $nntp->authenticate('somebody', 'secret');
if (PEAR::isError($authenticated)) {
    // handle error
}

if ($authenticated) {
    // success
} else {
    // failure
}
