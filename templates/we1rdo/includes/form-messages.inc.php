<?php

    /*
     * Render the form results in the appropriate way, either
     * in JSON or HTML, depending on variables given by the system
     */
    function showResults(Dto_FormResult $result, array $data = null)
    {
        /*
         * First make sure the user actually tried
         * to submit this form, if so, return our
         * JSON output and nothing else.
         */
        if ($result->isSubmitted()) {
            /*
             * Determine what type of renderer for the form submission
             * result we should use?
             *
             * We render either as HTML, if not default to render
             * as JSON
             */
            if (isset($data['renderhtml'])) {
                renderResultMessagesHtml($result);
            } else {
                echo $result->toJSON();
            } // else

            return true;
        } // if

        /*
         * If there was no submit of the form, just show placeholders
         * for the errors and information
         */
        echo "<ul class='formerrors'></ul><ul class='forminformation'></ul>";

        return false;
    } // showResults

/*
 * Render a Dto_FormResult as a HTML error message box
 */
function renderResultMessagesHtml(Dto_FormResult $result)
{
    echo PHP_EOL.'<ul class="formerrors">'.PHP_EOL;
    foreach ($result->getErrors() as $formError) {
        echo '<script type="text/javascript">alert(JSON.stringify("'.$formError.'"))</script>';
    } // foreach
    echo '</ul>'.PHP_EOL;

    echo PHP_EOL.'<ul class="forminformation">'.PHP_EOL;
    foreach ($result->getInfo() as $formInfo) {
        echo "<script type='text/javascript'>alert('".$formInfo."'); </script>";
    } // foreach
    echo '</ul>'.PHP_EOL;
} // renderResultMessagesHtml()
