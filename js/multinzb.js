(function(root) {
    'use strict';

    var multiNzb = {};

    multiNzb.endpoint = '?';
    multiNzb.selectedCheckboxSelector = 'td.multinzb input[type=checkbox]:checked, span.multi input[type=checkbox]:checked';
    multiNzb.allCheckboxSelector = 'td.multinzb input[type=checkbox], span.multi input[type=checkbox]';

    multiNzb.normalizeAction = function(dltype) {
        if (dltype == 'client-sabnzbd' || dltype == 'disable') {
            return 'display';
        } // if

        return dltype || 'display';
    };

    multiNzb.collectMessageIds = function($) {
        var messageIds = [];

        $(multiNzb.selectedCheckboxSelector).each(function() {
            messageIds.push($(this).val());
        });

        return messageIds;
    };

    multiNzb.clearSelection = function($) {
        $(multiNzb.allCheckboxSelector).attr('checked', false);
    };

    multiNzb.buildFields = function(dltype, messageIds) {
        var fields = [
            {name: 'page', value: 'getnzb'},
            {name: 'action', value: multiNzb.normalizeAction(dltype)}
        ];

        for (var i = 0; i < messageIds.length; i++) {
            fields.push({name: 'messageid[]', value: messageIds[i]});
        } // for

        return fields;
    };

    multiNzb.encodeFields = function(fields) {
        var encoded = [];

        for (var i = 0; i < fields.length; i++) {
            encoded.push(encodeURIComponent(fields[i].name) + '=' + encodeURIComponent(fields[i].value));
        } // for

        return encoded.join('&');
    };

    multiNzb.buildRequestBody = function(dltype, messageIds) {
        return multiNzb.encodeFields(multiNzb.buildFields(dltype, messageIds));
    };

    multiNzb.submitDisplay = function(dltype, messageIds, documentRef) {
        var documentObject = documentRef || root.document;
        var form = documentObject.createElement('form');
        var fields = multiNzb.buildFields(dltype, messageIds);

        form.method = 'post';
        form.action = multiNzb.endpoint;
        form.style.display = 'none';

        for (var i = 0; i < fields.length; i++) {
            var input = documentObject.createElement('input');
            input.type = 'hidden';
            input.name = fields[i].name;
            input.value = fields[i].value;
            form.appendChild(input);
        } // for

        documentObject.body.appendChild(form);
        form.submit();

        root.setTimeout(function() {
            if (form.parentNode) {
                form.parentNode.removeChild(form);
            } // if
        }, 1000);
    };

    root.spotwebMultiNzb = multiNzb;
}(typeof window !== 'undefined' ? window : this));
