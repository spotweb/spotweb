/**
 * Helper function to open a dialog, a couple of parameters are required.
 *
 * @param divid id of a dummy div which should be used to create a dialog
 * @param title title of the dialogbox
 * @param url URL of the HTML content to load into the dialog
 * @param presubmitHook Function to be called when the submit button is pressed
 * @param successAction choice of 'autoclose', 'showresultonly', 'reload'
 * @param closeCb Function which should be called when the dialog is closed
 * @param openCb Function which should be called when the HTML content of the dialog is loaded
 */
function openDialog(divid, title, url, buttonClick, successAction, closeCb, openCb) {
    var $dialdiv = $("#" + divid);

    /*
     * Test whether we need to 'dialog'-ify the
     * dialog again, if not, we can just reshow the
     * normal dialog after we clear its' content
     */
    if (!$dialdiv.is(".ui-dialog-content")) {
        // Show the dialog
        $dialdiv.dialog( {
            title: title,
            autoOpen: false,
            resizable: false,
            position: 'center',
            stack: true,
            closeOnEscape: true,
            height: 'auto',
            width: 'auto',
            modal: true
        } );
    } // if

    /*
     * Update de dialogs' title, we use two seperate methods because the first one because we
     * the first one doesn't always work correctly.
     */
    $dialdiv.dialog("option", 'title', title);
    $("span.ui-dialog-title").text(title);

    /* submit button handler */
    if (!buttonClick) {
        var buttonClick = function() {
            /*
             * The current 'this' is where the submitt button has been pushed,
             * this means we can assume we are in the form itselve.
             */
            var formdata = $(this).attr("name") + "=" + $(this).val();
            formdata = $(this.form).serialize() + "&" + formdata;

            // actually post the data, use JSON as a result type
            $.ajax({
                type: "POST",
                url: this.form.action,
                dataType: "json",
                data: formdata,
                success: function(data) {
                    /*
                     * Upon success (of the HTTP call), we try to find the
                     * dialog again and act upon the chosen action
                     */
                    var $dialdiv = $("#"+divid);

                    if ((data.result == 'success') && (successAction == 'autoclose')) {
                        $dialdiv.dialog('close');
                        $dialdiv.empty();

                        if (closeCb) {
                            closeCb();
                        } // if
                    } else {
                        /* If we need to reload the content, reload it, else just show the results */
                        if (successAction == 'reload') {
                            loadDialogContent(false);
                        } // if

                        if ((successAction == 'showresultsonly') && (data.result == 'success')) {
                            $dialdiv.empty();

                            /* Create the empty elements to show the errors/information in */
                            $dialdiv.html("<ul class='formerrors'></ul><ul class='forminformation'></ul>");
                        } // if

                        var $formerrors = $dialdiv.find("ul.formerrors");
                        $formerrors.empty();
                        $.each(data.errors, function(key, val) {
                            $formerrors.append("<li>" + val + "</li>");
                        }); // each

                        // Add the information items to the form
                        var $forminfo = $dialdiv.find("ul.forminformation");
                        $forminfo.empty();
                        $.each(data.info, function(key, val) {
                            $forminfo.append("<li>" + val + "</li>");
                        }); // each
                    } // if post was not successful
                } // success()
            }); // ajax call to submit the form

            return false; // supress standard submit button
        }; // buttonClick
    } // if not defined

    /*
     * Define a dialog loader function which also attaches the
     * submit buttons. This will be called when the dialog submit
     * is succesful but the dialog shouldn't be closed.
     */
    function loadDialogContent(async) {
        /* actually load the content */
        $.ajax({
            type: "GET",
            dataType: "html",
            async: async,
            url: url,
            data: {},
            success: function(response) {
                /*
                 * Replace the current HTML content of the dialog with this new content
                 */
                var $dialdiv = $("#" + divid);
                $dialdiv.empty().html(response);

                // Save the actual DialogUrl, so we can refresh it later if required
                $dialdiv.data('dialogurl', url);

                /*
                 * Loop through all submit buttons, and attach a form submit handler to
                 * it. We need this to make sure we know which button was pressed, else
                 * we cannot differentiate between different 'submit' buttons which is
                 * rather limitting.
                 */
                var $buttons = $("#" + divid + " input[type='submit']");
                $buttons.click(buttonClick);

                // Call the open callback
                if (openCb) {
                    openCb();
                } // if

                // actually show the dialogs content
                $dialdiv.dialog('open');

                return false; // supress the default action of the link which opens this dialog
            } // success function
        }); // ajax call
    } // loadDialogContent

    // load contents on first run
    loadDialogContent(true);
    return false;
} // openDialog
