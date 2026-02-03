/* Persist mobile filter selection and apply it to the Search form. */
(function () {
    "use strict";

    var STORAGE_KEY = "spotweb.mobile.filters";

    function parseQuery(queryString) {
        var params = {};
        if (!queryString) {
            return params;
        }
        queryString.split("&").forEach(function (part) {
            if (!part) {
                return;
            }
            var pieces = part.split("=");
            var rawKey = pieces[0] || "";
            var rawValue = pieces.length > 1 ? pieces.slice(1).join("=") : "";
            var key = decodeURIComponent(rawKey.replace(/\+/g, " "));
            var value = decodeURIComponent(rawValue.replace(/\+/g, " "));

            if (key === "search[value][]") {
                if (!params[key]) {
                    params[key] = [];
                }
                params[key].push(value);
            } else {
                params[key] = value;
            }
        });
        return params;
    }

    function extractSearchParamsFromUrl(url) {
        if (!url) {
            return {};
        }
        var queryIndex = url.indexOf("?");
        if (queryIndex === -1) {
            return {};
        }
        var query = url.slice(queryIndex + 1);
        var hashIndex = query.indexOf("#");
        if (hashIndex !== -1) {
            query = query.slice(0, hashIndex);
        }
        return parseQuery(query);
    }

    function hasFilterParams(params) {
        return Boolean(params["search[tree]"] || params["search[value][]"] || params["search[value]"]);
    }

    function storeFilterParams(params) {
        if (!hasFilterParams(params)) {
            return;
        }
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(params));
        } catch (e) {
            /* ignore storage errors */
        }
    }

    function loadStoredFilters() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function clearExistingFilterInputs(form) {
        var existing = form.querySelectorAll("input[data-mobile-filter='1']");
        for (var i = 0; i < existing.length; i++) {
            existing[i].parentNode.removeChild(existing[i]);
        }
    }

    function addHiddenInput(form, name, value) {
        var input = document.createElement("input");
        input.type = "hidden";
        input.name = name;
        input.value = value;
        input.setAttribute("data-mobile-filter", "1");
        form.appendChild(input);
    }

    function applyFiltersToSearchForm() {
        var form = document.getElementById("filterform");
        if (!form) {
            return;
        }

        clearExistingFilterInputs(form);

        var stored = loadStoredFilters();
        if (!stored || !hasFilterParams(stored)) {
            return;
        }

        if (stored["search[tree]"]) {
            addHiddenInput(form, "search[tree]", stored["search[tree]"]);
        }

        if (stored["search[value][]"]) {
            var values = stored["search[value][]"];
            if (!Array.isArray(values)) {
                values = [values];
            }
            for (var i = 0; i < values.length; i++) {
                addHiddenInput(form, "search[value][]", values[i]);
            }
        } else if (stored["search[value]"]) {
            addHiddenInput(form, "search[value]", stored["search[value]"]);
        }
    }

    function captureFiltersFromLocation() {
        var params = extractSearchParamsFromUrl(window.location.href);
        storeFilterParams(params);
    }

    $(document).on("click", "a.js-mobile-filter", function () {
        var params = extractSearchParamsFromUrl(this.href);
        storeFilterParams(params);
    });

    $(document).on("pageshow", "#search", function () {
        applyFiltersToSearchForm();
    });

    $(document).ready(function () {
        captureFiltersFromLocation();
        applyFiltersToSearchForm();
    });
}());
