<?php

class SpotTemplateHelper_Modern extends SpotTemplateHelper
{
    public function getThemeName()
    {
        return 'modern';
    }

    // getThemeName

    public function getTemplatePreferences()
    {
        return ['modern' => []];
    }

    protected function getThemePostingJsFile()
    {
        return $this->getThemePath().'/js/modernpost.js';
    }

    // getThemePostingJsFile

    protected function getThemeExtraStaticFiles($type)
    {
        switch ($type) {
            case 'css':
                return [
                    $this->getThemePath().'/css/base.css',
                    $this->getThemePath().'/css/dark.css',
                    $this->getThemePath().'/css/filters.css',
                    $this->getThemePath().'/css/layout.css',
                    $this->getThemePath().'/css/cards.css',
                    $this->getThemePath().'/css/detail.css',
                    $this->getThemePath().'/css/table.css',
                ];
        } // switch

        return [];
    }

    // getThemeExtraStaticFiles
}
