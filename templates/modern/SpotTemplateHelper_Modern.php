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

    // getTemplatePreferences

    public function getThemeHeaderCssFiles()
    {
        return [
            $this->getThemeAssetPath('css/posting.css'),
            $this->getThemeAssetPath('css/config.css'),
        ];
    }

    // getThemeHeaderCssFiles

    public function getThemeHeaderJsFiles()
    {
        return [
            $this->getThemeAssetPath('js/theme-toggle.js'),
            $this->getThemeAssetPath('js/back-fix.js'),
            $this->getThemeAssetPath('js/open-spot-fix.js'),
            $this->getThemeAssetPath('js/sticky-offset.js'),
            $this->getThemeAssetPath('js/infinite.js'),
            $this->getThemeAssetPath('js/table-enhance.js'),
            $this->getThemeAssetPath('js/filter-overlay.js'),
        ];
    }

    // getThemeHeaderJsFiles

    protected function getThemePostingJsFile()
    {
        return $this->getThemeAssetPath('js/modernpost.js');
    }

    // getThemePostingJsFile

    protected function getThemeExtraStaticFiles($type)
    {
        switch ($type) {
            case 'css':
                return [
                    $this->getThemeAssetPath('css/base.css'),
                    $this->getThemeAssetPath('css/dark.css'),
                    $this->getThemeAssetPath('css/filters.css'),
                    $this->getThemeAssetPath('css/layout.css'),
                    $this->getThemeAssetPath('css/cards.css'),
                    $this->getThemeAssetPath('css/detail.css'),
                    $this->getThemeAssetPath('css/table.css'),
                ];
        } // switch

        return [];
    }

    // getThemeExtraStaticFiles
}
