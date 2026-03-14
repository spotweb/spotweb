<?php

class SpotThemes
{
    const DEFAULT_THEME = 'modern';

    private static $_themeDefinitions = null;
    private static $_requiredThemeFiles = null;

    public static function getTemplatesPath()
    {
        return 'templates';
    }

    // getTemplatesPath

    public static function getThemePath($themeName)
    {
        return static::getTemplatesPath().'/'.$themeName;
    }

    // getThemePath

    public static function getThemeAssetPath($themeName, $relativePath = '')
    {
        $themePath = static::getThemePath($themeName);
        $relativePath = ltrim(str_replace('\\', '/', (string) $relativePath), '/');

        if (empty($relativePath)) {
            return $themePath;
        } // if

        return $themePath.'/'.$relativePath;
    }

    // getThemeAssetPath

    public static function getThemeHelperClassName($themeName)
    {
        $themeDefinitions = static::discoverThemeDefinitions();
        if (isset($themeDefinitions[$themeName])) {
            return $themeDefinitions[$themeName]['helper_class'];
        } // if

        return 'SpotTemplateHelper_'.ucfirst($themeName);
    }

    // getThemeHelperClassName

    public static function getThemeHelperPath($themeName)
    {
        $themeDefinitions = static::discoverThemeDefinitions();
        if (isset($themeDefinitions[$themeName])) {
            return $themeDefinitions[$themeName]['helper_path'];
        } // if

        return static::getThemePath($themeName).'/'.static::getThemeHelperClassName($themeName).'.php';
    }

    // getThemeHelperPath

    public static function discoverThemeDefinitions()
    {
        if (static::$_themeDefinitions !== null) {
            return static::$_themeDefinitions;
        } // if

        $themeDefinitions = [];
        $themeDirs = glob(static::getTemplatesPath().'/*', GLOB_ONLYDIR);
        if ($themeDirs === false) {
            $themeDirs = [];
        } // if

        foreach ($themeDirs as $themeDir) {
            $themeName = basename($themeDir);
            if ((empty($themeName)) || ($themeName[0] === '.') || ($themeName[0] === '_')) {
                continue;
            } // if

            $helperFiles = glob($themeDir.'/SpotTemplateHelper_*.php');
            if (($helperFiles === false) || empty($helperFiles)) {
                continue;
            } // if

            sort($helperFiles, SORT_NATURAL | SORT_FLAG_CASE);
            $helperPath = str_replace('\\', '/', $helperFiles[0]);
            $helperClass = basename($helperPath, '.php');

            $themeDefinitions[$themeName] = [
                'name'         => $themeName,
                'label'        => $themeName,
                'path'         => str_replace('\\', '/', $themeDir),
                'helper_path'  => $helperPath,
                'helper_class' => $helperClass,
            ];
        } // foreach

        uksort($themeDefinitions, 'strnatcasecmp');
        static::$_themeDefinitions = $themeDefinitions;

        return static::$_themeDefinitions;
    }

    // discoverThemeDefinitions

    public static function discoverThemes()
    {
        $themes = [];
        foreach (static::discoverThemeDefinitions() as $themeName => $themeDefinition) {
            $themes[$themeName] = $themeDefinition['label'];
        } // foreach

        return $themes;
    }

    // discoverThemes

    public static function getConfiguredThemes(Services_Settings_Container $settings)
    {
        $configuredThemes = $settings->get('valid_templates');
        if (!is_array($configuredThemes)) {
            $configuredThemes = [];
        } // if

        $discoveredThemes = static::discoverThemes();
        $validThemes = [];

        foreach ($configuredThemes as $themeName => $themeLabel) {
            if (isset($discoveredThemes[$themeName])) {
                $validThemes[$themeName] = $themeLabel;
            } // if
        } // foreach

        foreach ($discoveredThemes as $themeName => $themeLabel) {
            if (!isset($validThemes[$themeName])) {
                $validThemes[$themeName] = $themeLabel;
            } // if
        } // foreach

        return $validThemes;
    }

    // getConfiguredThemes

    public static function getConfiguredThemeNames(Services_Settings_Container $settings)
    {
        return array_keys(static::getConfiguredThemes($settings));
    }

    // getConfiguredThemeNames

    public static function resolveThemeName(Services_Settings_Container $settings, $themeName)
    {
        $configuredThemes = static::getConfiguredThemes($settings);
        if (isset($configuredThemes[$themeName])) {
            return $themeName;
        } // if

        if (isset($configuredThemes[static::DEFAULT_THEME])) {
            return static::DEFAULT_THEME;
        } // if

        reset($configuredThemes);
        $fallbackTheme = key($configuredThemes);
        if (!empty($fallbackTheme)) {
            return $fallbackTheme;
        } // if

        return (string) $themeName;
    }

    // resolveThemeName

    public static function getRequiredThemeFiles()
    {
        if (static::$_requiredThemeFiles !== null) {
            return static::$_requiredThemeFiles;
        } // if

        $requiredTemplates = [];
        foreach (glob('lib/page/*.php') as $pageFile) {
            $fileContents = file_get_contents($pageFile);
            if ($fileContents === false) {
                continue;
            } // if

            if (preg_match_all("/->template\\('([^']+)'/", $fileContents, $matches)) {
                foreach ($matches[1] as $templateName) {
                    $requiredTemplates[$templateName.'.inc.php'] = true;
                } // foreach
            } // if
        } // foreach

        foreach ([
            'comment.inc.php',
            'editfilter.inc.php',
            'editsecgroupdelete.inc.php',
            'editsecgroupname.inc.php',
            'editspotterblacklist.inc.php',
            'editspotterblacklistdelete.inc.php',
            'listfilters.inc.php',
            'listgroups.inc.php',
            'listusers.inc.php',
            'reportspot.inc.php',
            'usermanagement.inc.php',
            'validatenntp.inc.php',
            'versioncheck.inc.php',
            'includes/basic-html-header.inc.php',
            'includes/filters.inc.php',
            'includes/footer.inc.php',
            'includes/form-messages.inc.php',
            'includes/form-xmlresult.inc.php',
            'includes/header.inc.php',
        ] as $requiredFile) {
            $requiredTemplates[$requiredFile] = true;
        } // foreach

        ksort($requiredTemplates, SORT_NATURAL | SORT_FLAG_CASE);
        static::$_requiredThemeFiles = array_keys($requiredTemplates);

        return static::$_requiredThemeFiles;
    }

    // getRequiredThemeFiles

    public static function validateTheme($themeName)
    {
        $validation = [
            'theme'          => $themeName,
            'is_valid'       => true,
            'missing_files'  => [],
            'helper_class'   => static::getThemeHelperClassName($themeName),
            'theme_path'     => static::getThemePath($themeName),
            'required_files' => static::getRequiredThemeFiles(),
        ];

        foreach ($validation['required_files'] as $requiredFile) {
            $themeFile = static::getThemePath($themeName).'/'.$requiredFile;
            if (!file_exists($themeFile)) {
                $validation['missing_files'][] = str_replace('\\', '/', $themeFile);
            } // if
        } // foreach

        $helperPath = static::getThemeHelperPath($themeName);
        if (!file_exists($helperPath) && !in_array($helperPath, $validation['missing_files'], true)) {
            $validation['missing_files'][] = str_replace('\\', '/', $helperPath);
        } // if

        $validation['is_valid'] = empty($validation['missing_files']);

        return $validation;
    }

    // validateTheme
} // class SpotThemes
