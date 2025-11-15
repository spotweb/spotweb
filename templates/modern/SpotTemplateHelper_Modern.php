<?php

class SpotTemplateHelper_Modern extends SpotTemplateHelper_We1rdo
{
    public function getParentTemplates()
    {
        $parents = parent::getParentTemplates();
        $parents[] = 'we1rdo';

        return $parents;
    }

    public function getStaticFiles($type)
    {
        $list = parent::getStaticFiles($type);

        switch ($type) {
            case 'css':
                $list[] = 'templates/modern/css/base.css';
                $list[] = 'templates/modern/css/dark.css';
                $list[] = 'templates/modern/css/filters.css';
                $list[] = 'templates/modern/css/layout.css';
                $list[] = 'templates/modern/css/cards.css';
                $list[] = 'templates/modern/css/detail.css';
                $list[] = 'templates/modern/css/table.css';

                break;

            case 'js':
                // Load modern JS separately in header to avoid concat-side effects
                break;
        }

        return $list;
    }
}
