<?php

class Services_Nntp_PipelineDepth
{
    const SettingName = 'article_pipeline_depth';
    const DefaultDepth = 32;
    const MinDepth = 1;
    const MaxDepth = 128;

    public static function normalize($value)
    {
        $value = (int) $value;
        if ($value < self::MinDepth) {
            return self::MinDepth;
        }
        if ($value > self::MaxDepth) {
            return self::MaxDepth;
        }

        return $value;
    }

    public static function validate($value)
    {
        if (!is_numeric($value)) {
            return false;
        }

        $value = (int) $value;

        return ($value >= self::MinDepth) && ($value <= self::MaxDepth);
    }

    public static function serverValue(array $server)
    {
        if (!isset($server[self::SettingName])) {
            return self::DefaultDepth;
        }

        return self::normalize($server[self::SettingName]);
    }

    public static function normalizeServer(array $server)
    {
        $server[self::SettingName] = self::serverValue($server);

        return $server;
    }
}
