<?php

class SpotTemplateHelper_Examplechild extends SpotTemplateHelper_We1rdo
{
    public function getFilterIcons()
    {
        $filterIcons = parent::getFilterIcons();

        $filterIcons['extraicon'] = _('Extra icon from Example Child Theme');

        return $filterIcons;
    }

    // getFilterIcons

    /*
     * Returns an array of parent template paths
     */
    public function getParentTemplates()
    {
        $tmpList = parent::getParentTemplates();
        $tmpList[] = 'we1rdo';

        return $tmpList;
    }

    // getParentTemplates

    public function getStaticFiles($type)
    {
        $tmpList = parent::getStaticFiles($type);

        switch ($type) {
            case 'css':
                $tmpList[] = 'templates/examplechild/css/extraspoticons.css';

                break;
             // case css
        } // switch

        return $tmpList;
    }

    // getStaticFiles
} // SpotTemplateHelper_ExampleChild
