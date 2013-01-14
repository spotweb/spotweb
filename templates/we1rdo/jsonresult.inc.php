<?php
    require "includes/form-messages.inc.php";

    if ((isset($result) && ($result->isSubmitted()))) {
        showResults($result);
    } // if
