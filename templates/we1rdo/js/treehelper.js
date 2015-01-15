    function initializeCategoryTree() {
        // Attach the dynatree widget to an existing <div id="tree"> element
        // and pass the tree options as an argument to the dynatree() function:
        $("div#tree").hide();
        $("div#tree").dynatree({
            // we set async to false, because else the dynatree steals the focus
            initAjax: { url: "?page=catsjson" + spotweb_currentfilter_params, async: false },
            checkbox: true, // Show checkboxes.
            persist: false, // Persist expand-status to a cookie
            selectMode: 3, //  1:single, 2:multi, 3:multi-hier
            clickFolderMode: 2, // 1:activate, 2:expand, 3:activate and expand
            ajaxDefaults: { // Used by initAjax option
                cache: false // false: Append random '_' argument to the request url to prevent caching.
            },
            onPostInit: function() {
                $("div#tree").show();
            },
            onQuerySelect: function(flag, node) {
                if (!flag) {
                    if (!node.data.strongnot) {
                        node.visit(function (node) {
                            node.data.strongnot = true;
                            node.data.addClass = 'strongnotnode';
                        }, true);
                    } else {
                        node.visit(function (node) {
                            node.data.strongnot = false;
                            node.data.addClass = node.data.addClass.replace('strongnotnode', '');
                        }, true);
                    } // else

                    node.render(true);
                    return (!node.data.strongnot);
                } else {
                    return true;
                } // else
            }
        });

        $("#filterform").submit(function() {
            var formField = $("#search-tree");

            // then append Dynatree selected 'checkboxes':
            var selectedNodes = $("#tree").dynatree("getTree").getSelectedNodes();
            var tmp = $.map(selectedNodes, function(node){
                if (node.data.strongnot) {
                    /* If our parent node is selected, don't bother selecting this one */
                    if (node.parent) {
                        if ((node.parent.data.strongnot) && (node.parent.isSelected())) {
                            return ;
                        } // if
                    } // if

                    return '~' + node.data.key;
                } else {
                    /* If our parent node is selected, don't bother selecting this one */
                    if (node.parent) {
                        if ((!node.parent.data.strongnot) && (node.parent.isSelected())) {
                            return ;
                        } // if
                    } // if

                    return node.data.key;
                } // else
            }); // map

            formField[0].value = tmp.join(',');

            if (formField[0].value.length == 0) {
                $(formField[0]).remove();
            } // if

            return true;
        });
    } // initializeCategoryTree

