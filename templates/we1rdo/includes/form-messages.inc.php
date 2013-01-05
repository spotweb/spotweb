<?php
    function showResults($result) {
        /*
         * First make sure the user actually tried
         * to submit this form, if so, return our
         * JSON output and nothing else.
         */
        if ($result->isSubmitted()) {
            echo $result->toJSON();

            return true;
        } # if

        /*
         * If there was no submit of the form, just show placeholders
         * for the errors and information
         */
        echo "<ul class='formerrors'></ul><ul class='forminformation'></ul>";

        return false;
    } # showResults

