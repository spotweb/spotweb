<?php

class SpotPage_catsjson extends SpotPage_Abs
{
    private $_params;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);

        $this->sendContentTypeHeader('json');

        $this->_params = $params;
    }

    // ctor

    /*
     * render a page
     */
    public function render()
    {
        if ($this->_params['rendertype'] == 'tree') {
            $this->categoriesToJson();
        } else {
            $this->renderSelectBox();
        } // else
    }

    // render

    /*
     * Render the JSON specifically for one selectbox, no
     * logic whatsoever
     */
    public function renderSelectBox()
    {
        /*
         * The categorylisting is very static, so ask the user to always cache
         */
        $this->sendExpireHeaders(false);

        $category = $this->_params['category'];
        $genre = $this->_params['subcatz'];
        if (strlen($genre) == 0) {
            $genre = 'z';
        } // if

        /* Validate the selected category */
        if (!isset(SpotCategories::$_head_categories[$category])) {
            return;
        } // if

        $returnArray = [];
        $scType = 'z';

        switch ($this->_params['rendertype']) {
            case 'subcatz':
                    $scType = $this->_params['rendertype'][6];

                    foreach (SpotCategories::$_categories[$category]['z'] as $key => $value) {
                        $returnArray[$key] = $value;
                    } // foreach

                     break;
             // case subcatz

            case 'subcata':
            case 'subcatb':
            case 'subcatc':
            case 'subcatd':
                    $scType = $this->_params['rendertype'][6];

                    if (isset(SpotCategories::$_categories[$category][$scType])) {
                        foreach (SpotCategories::$_categories[$category][$scType] as $key => $value) {
                            if (in_array('z'.$genre, $value[1])) {
                                $returnArray['cat'.$category.'_z'.$genre.'_'.$scType.$key] = $value[0];
                            } // if
                        } // foreach
                    } // if

                    break;
             // case subcata, subcatb, subcatc, subcatd

            // Used to show all (including deprecated) categories. This is required when editing
            // a spot since some spots still use deprecated categories which we don't want to lose.
            // Depreicated categories will be marked as such.
            case 'subcata_old':
            case 'subcatb_old':
            case 'subcatc_old':
            case 'subcatd_old':
                    $scType = $this->_params['rendertype'][6];

                    if (isset(SpotCategories::$_categories[$category][$scType])) {
                        foreach (SpotCategories::$_categories[$category][$scType] as $key => $value) {
                            if (in_array('z'.$genre, $value[1])) {
                                $returnArray['cat'.$category.'_z'.$genre.'_'.$scType.$key] = $value[0];
                            } // if
                            elseif (in_array('z'.$genre, $value[2])) {
                                $returnArray['cat'.$category.'_z'.$genre.'_'.$scType.$key] = $value[0].' ('._('deprecated').')';
                            } // elseif
                        } // foreach
                    } // if

                    break;
                 // case subcata_old, subcatb_old, subcatc_old, subcatd_old
        } // switch

        if (isset(SpotCategories::$_subcat_descriptions[$category][$scType])) {
            echo json_encode(
                ['title'    => SpotCategories::$_subcat_descriptions[$category][$scType],
                    'items' => $returnArray, ]
            );
        } else {
            echo json_encode(
                ['title'    => '',
                    'items' => [], ]
            );
        } // if
    }

    // renderSelectBox

    /*
     * Returns a JSON back for DynaTree so it can render the categorylist as a tree
     */
    public function categoriesToJson()
    {
        /*
         * Don't allow the tree to be cached, it contains the current state of the
         * tree
         */
        $this->sendExpireHeaders(true);

        /* First parse the search string so we know which items to select and which not */
        $svcUserFilter = new Services_User_Filters($this->_daoFactory, $this->_settings);
        $svcSearchQp = new Services_Search_QueryParser($this->_daoFactory->getConnection());
        $parsedSearch = $svcSearchQp->filterToQuery(
            $this->_params['search'],
            [],
            $this->_currentSession,
            $svcUserFilter->getIndexFilter($this->_currentSession['user']['userid'])
        );
        if ($this->_params['disallowstrongnot']) {
            $parsedSearch['strongNotList'] = '';
        } // if
        $compressedCatList = ','.$svcSearchQp->compressCategorySelection($parsedSearch['categoryList'], $parsedSearch['strongNotList']);
        //error_log($this->_params['search']['tree']);
        //var_dump($parsedSearch);
        //var_dump($compressedCatList);
        //die();

        echo '[';

        $hcatList = [];
        $typeCatTmp = '';
        $hcatTmp = '';
        foreach (SpotCategories::$_head_categories as $hcat_key => $hcat_val) {
            // The uer can opt to only show a specific category, if so, skip all others
            if (($hcat_key != $this->_params['category']) && ($this->_params['category'] != '*')) {
                continue;
            } // if

            // If the user choose to show only one category, we dont want the category item itself
            if ($this->_params['category'] == '*') {
                $hcatTmp = '{"title": "'.$hcat_val.'", "isFolder": true, "key": "cat'.$hcat_key.'",	"children": [';
            } // if
            $typeCatDesc = [];

            if (isset(SpotCategories::$_categories[$hcat_key]['z'])) {
                foreach (SpotCategories::$_categories[$hcat_key]['z'] as $type_key => $type_value) {
                    if (($type_key !== 'z') && (($this->_params['subcatz'] == $type_key) || ($this->_params['subcatz'] == '*'))) {
                        // Now determine whether we need to enable the checkbox
                        $isSelected = strpos($compressedCatList, ',cat'.$hcat_key.'_z'.$type_key.',') !== false ? 'true' : 'false';

                        // Is this strongnot?
                        $isStrongNot = strpos($compressedCatList, ',~cat'.$hcat_key.'_z'.$type_key.',') !== false ? true : false;
                        if ($isStrongNot) {
                            $isStrongNot = '"strongnot": true, "addClass": "strongnotnode", ';
                            $isSelected = 'true';
                        } else {
                            $isStrongNot = '';
                        } // if

                        // If the user choose to show only one categortype, we dont want the categorytype item itself
                        if ($this->_params['subcatz'] == '*') {
                            $typeCatTmp = '{"title": "'.$type_value.'", "isFolder": true, '.$isStrongNot.' "select": '.$isSelected.', "hideCheckbox": false, "key": "cat'.$hcat_key.'_z'.$type_key.'", "unselectable": false, "children": [';
                        } // if
                    } // if

                    $subcatDesc = [];
                    foreach (SpotCategories::$_subcat_descriptions[$hcat_key] as $sclist_key => $sclist_desc) {
                        if (($sclist_key !== 'z') && (($this->_params['subcatz'] == $type_key) || ($this->_params['subcatz'] == '*'))) {

                            // We inherit the strongnode from our parent
                            $isStrongNot = strpos($compressedCatList, ',~cat'.$hcat_key.'_z'.$type_key.',') !== false ? true : false;
                            if ($isStrongNot) {
                                $isStrongNot = '"strongnot": true, "addClass": "strongnotnode", ';
                            } else {
                                $isStrongNot = '';
                            } // if

                            $subcatTmp = '{"title": "'.$sclist_desc.'", "isFolder": true, '.$isStrongNot.' "hideCheckbox": true, "key": "cat'.$hcat_key.'_z'.$type_key.'_'.$sclist_key.'", "unselectable": false, "children": [';
                            // echo ".." . $sclist_desc . " <br>";

                            $catList = [];
                            foreach (SpotCategories::$_categories[$hcat_key][$sclist_key] as $key => $valTmp) {
                                //error_log($hcat_key . ' => ' . $sclist_key . ' ==:: ' . $key);

                                if (in_array('z'.$type_key, $valTmp[2])) {
                                    $val = $valTmp[0];

                                    if ((strlen($val) != 0) && (strlen($key) != 0)) {
                                        // Now determine whether we need to enable the checkbox
                                        $isSelected = strpos($compressedCatList, ',cat'.$hcat_key.'_z'.$type_key.'_'.$sclist_key.$key.',') !== false ? true : false;
                                        $parentSelected = strpos($compressedCatList, ',cat'.$hcat_key.'_z'.$type_key.',') !== false ? true : false;
                                        $isSelected = ($isSelected || $parentSelected) ? 'true' : 'false';

                                        /*
                                         * Is this strongnot?
                                         */
                                        $isStrongNot = strpos($compressedCatList, ',~cat'.$hcat_key.'_z'.$type_key.',') !== false ? true : false;
                                        if (!$isStrongNot) {
                                            $isStrongNot = strpos($compressedCatList, ',~cat'.$hcat_key.'_z'.$type_key.'_'.$sclist_key.$key.',') !== false ? true : false;
                                        } // if
                                        if ($isStrongNot) {
                                            $isStrongNot = '"strongnot": true, "addClass": "strongnotnode", ';
                                            $isSelected = 'true';
                                        } else {
                                            $isStrongNot = '';
                                        } // if

                                        $catList[] = '{"title": "'.$val.'", "icon": false, "select": '.$isSelected.', '.$isStrongNot.'"key":"'.'cat'.$hcat_key.'_z'.$type_key.'_'.$sclist_key.$key.'"}';
                                    } // if
                                } // if
                            } // foreach
                            $subcatTmp .= implode(',', $catList);

                            $subcatDesc[] = $subcatTmp.']}';
                        } // if
                    } // foreach

                    if ($type_key !== 'z') {
                        // If the user choose to show only one categortype, we dont want the categorytype item itself
                        if ($this->_params['subcatz'] == '*') {
                            $typeCatDesc[] = $typeCatTmp.implode(',', $subcatDesc).']}';
                        } else {
                            if (!empty($subcatDesc)) {
                                $typeCatDesc[] = implode(',', array_filter($subcatDesc));
                            } // if
                        } // else
                    } else {
                        $typeCatDesc[] = implode(',', $subcatDesc);
                    } // else
                } // foreach
            } // foreach

            // If the user choose to show only one category, we dont want the category item itself
            if ($this->_params['category'] == '*') {
                $hcatList[] = $hcatTmp.implode(',', $typeCatDesc).']}';
            } else {
                $hcatList[] = implode(',', $typeCatDesc);
            } // if
        } // foreach

        echo implode(',', $hcatList);
        echo ']';
    }

    // categoriesToJson
} // class SpotPage_catjson
