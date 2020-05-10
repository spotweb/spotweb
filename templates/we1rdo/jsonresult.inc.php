<?php

    require __DIR__.'/includes/form-messages.inc.php';

    if ((isset($result) && ($result->isSubmitted()))) {
        showResults($result);
    } // if
