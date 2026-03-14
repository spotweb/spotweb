<?php

class SpotTemplateHelper_We1rdo extends SpotTemplateHelper
{
    public function getThemeName()
    {
        return 'we1rdo';
    }

    // getThemeName

    /*
     * Return a list of preferences specific for this template.
     *
     * When a user changes their template, and changes their
     * preferences these settings are lost.
     *
     * Settings you want to be able to set must always be
     * present in this array with a sane default value, else
     * the setting will not be saved.
     */
    public function getTemplatePreferences()
    {
        return ['we1rdo' => ['example_setting' => 1],
        ];
    }

    // getTemplatePreferences
    protected function getThemePostingJsFile()
    {
        return $this->getThemeAssetPath('js/we1rdopost.js');
    }

    // getThemePostingJsFile
} // class We1rdoTemplateHelper
