<?php

class Services_Posting_Validator
{
    /**
     * Strip invald characters from the tag.
     *
     * @param Dto_FormResult $result
     *
     * @return Dto_FormResult
     */
    public function verifyTag(Dto_FormResult $result)
    {
        $spot = $result->getData('spot');

        // Fix up some overly long spot properties and other minor issues
        $spot['tag'] = substr(trim($spot['tag'], " |;\r\n\t"), 0, 99);

        $result->addData('spot', $spot);

        return $result;
    }

    // verifyTag

    /**
     * Remove any too long website results.
     *
     * @param Dto_FormResult $result
     *
     * @return Dto_FormResult
     */
    public function verifyWebsite(Dto_FormResult $result)
    {
        $spot = $result->getData('spot');

        $spot['website'] = substr(trim($spot['website']), 0, 449);

        $result->addData('spot', $spot);

        return $result;
    }

    // verifyWebsite

    /**
     * Make sure the title is valid (not too short, etc..).
     *
     * @param Dto_FormResult $result
     *
     * @return Dto_FormResult
     */
    public function verifyTitle(Dto_FormResult $result)
    {
        $spot = $result->getData('spot');

        // Title cannot be empty or very short
        $spot['title'] = trim($spot['title']);
        if (strlen($spot['title']) < 5) {
            $result->addError(_('Enter a title'));
        } // if

        /*
         * If the post's character does not fit into ISO-8859-1, we HTML
         * encode the UTF-8 characters so we can properly post the spots
         */
        if (mb_detect_encoding($spot['title'], 'UTF-8, ISO-8859-1', true) == 'UTF-8') {
            $spot['title'] = mb_convert_encoding($spot['title'], 'HTML-ENTITIES', 'UTF-8');
        } // if

        $result->addData('spot', $spot);

        return $result;
    }

    // verifyTitle

    /**
     * Make sure the body is correct.
     *
     * @param Dto_FormResult $result
     *
     * @return Dto_FormResult
     */
    public function verifyBody(Dto_FormResult $result)
    {
        $spot = $result->getData('spot');

        // Body cannot be empty, very short or too long
        $spot['body'] = trim($spot['body']);
        if (strlen($spot['body']) < 30) {
            $result->addError(_('Please enter a description'));
        } // if

        if (strlen($spot['body']) > 9000) {
            $result->addError(_('Entered description is too long'));
        } // if

        $result->addData('spot', $spot);

        return $result;
    }

    // verifyBody

    /**
     * Make sure the correct categories are chosen.
     *
     * @param Dto_FormResult $result
     *
     * @return Dto_FormResult
     */
    public function verifyCategories(Dto_FormResult $result)
    {
        $spot = $result->getData('spot');

        /* Make sure the category is valid
         * We use array_key_exists() to allow for gaps in the category numbering. This is an intentional
         * deviation from similar code used in Services_Posting_Spot.php
         */
        if (!array_key_exists($spot['category'], SpotCategories::$_head_categories)) {
            $result->addError(sprintf(_('Incorrect headcategory (%s)'), $spot['category']));
        } // if

        // Make sure the subcategories are in the proper format
        if ((is_array($spot['subcata'])) || (is_array($spot['subcatz'])) || (!is_array($spot['subcatb'])) || (!is_array($spot['subcatc'])) || (!is_array($spot['subcatd']))) {
            $result->addError(_('Invalid subcategories given'));
        } // if

        // create a list of the chosen subcategories
        $spot['subcatlist'] = array_merge(
            [$spot['subcata']],
            $spot['subcatb'],
            $spot['subcatc'],
            $spot['subcatd']
        );

        /*
         * Loop through all subcategories and check if they are valid in
         * our list of subcategories
         */
        $subCatSplitted = ['a' => [], 'b' => [], 'c' => [], 'd' => [], 'z' => []];

        foreach ($spot['subcatlist'] as $subCat) {
            $subcats = explode('_', $subCat);
            // If not in our format
            if (count($subcats) != 3) {
                $result->addError(sprintf(_('Incorrect subcategories (%s)'), $subCat));
            } else {
                $subCatLetter = substr($subcats[2], 0, 1);

                $subCatSplitted[$subCatLetter][] = $subCat;

                if (!isset(SpotCategories::$_categories[$spot['category']][$subCatLetter][substr($subcats[2], 1)])) {
                    $result->addError(sprintf(_('Incorrect subcategories (%s)'), $subCat.' !! '.$subCatLetter.' !! '.substr($subcats[2], 1)));
                } // if
            } // else
        } // foreach

        /*
         * Make sure all subcategories are in the format we expect, for
         * example we strip the 'cat' part and strip the z-subcat
         */
        $subcatCount = count($spot['subcatlist']);
        for ($i = 0; $i < $subcatCount; $i++) {
            $subcats = explode('_', $spot['subcatlist'][$i]);

            // If not in our format
            if (count($subcats) != 3) {
                $result->addError(sprintf(_('Incorrect subcategories (%s)'), $spot['subcatlist'][$i]));
            } else {
                $spot['subcatlist'][$i] = substr($subcats[2], 0, 1).str_pad(substr($subcats[2], 1), 2, '0', STR_PAD_LEFT);

                // Explicitly add the 'z'-category - we derive it from the full categorynames we already have
                $zcatStr = substr($subcats[1], 0, 1).str_pad(substr($subcats[1], 1), 2, '0', STR_PAD_LEFT);
                if ((is_numeric(substr($subcats[1], 1))) && (array_search($zcatStr, $spot['subcatlist']) === false)) {
                    $spot['subcatlist'][] = $zcatStr;
                } // if
            } // else
        } // for

        // Make sure the spot isn't being posted in many categories
        if (count($subCatSplitted['a']) > 1) {
            $result->addError(_('You can only specify one format for a spot'));
        } // if

        // Make sure the spot has at least a format
        if (count($subCatSplitted['a']) < 1) {
            $result->addError(_('You need to specify a format for a spot'));
        } // if

        // Make sure the spot isn't being posted for too many categories
        if (count($spot['subcatlist']) > 10) {
            $result->addError(_('Too many categories'));
        } // if

        // Make sure the spot isn't being posted for too many categories
        // The "A"-subcategory, and the "Z" subcategory are always selected by
        // the form, so we need to check for 3
        if (count($spot['subcatlist']) < 3) {
            $result->addError(_('At least one category need to be selected'));
        } // if

        $result->addData('spot', $spot);

        return $result;
    }

    // verifyCategories
} // Services_Posting_Validator
