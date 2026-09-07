<?php

require_once __DIR__.'/Services_Nntp_PipelineDepth.php';
require_once __DIR__.'/Services_Nntp_PipelinedTransport.php';

class Services_Nntp_ClientPool
{
    private static $_instances = [];

    public static function pool(Services_Settings_Container $settings, $role)
    {
        SpotDebug::msg(SpotDebug::DEBUG, __CLASS__.'::pool:('.$role.') called');

        if (isset(self::$_instances[$role])) {
            return self::$_instances[$role];
        }

        $settings_nntp_hdr = $settings->get('nntp_hdr');
        if (empty($settings_nntp_hdr)) {
            throw new MissingNntpConfigurationException();
        }

        switch ($role) {
            case 'hdr':
                self::$_instances[$role] = new Services_Nntp_PipelinedTransport(
                    self::normalizeServer($settings_nntp_hdr),
                    Services_Nntp_PipelinedTransport::DEFAULT_TIMEOUT,
                    'hdr'
                );
                break;

            case 'bin':
                $settings_nntp_bin = $settings->get('nntp_nzb');
                if (empty($settings_nntp_bin['host'])) {
                    self::$_instances[$role] = self::pool($settings, 'hdr');
                } else {
                    self::$_instances[$role] = new Services_Nntp_PipelinedTransport(
                        self::normalizeServer($settings_nntp_bin),
                        Services_Nntp_PipelinedTransport::DEFAULT_TIMEOUT,
                        'bin'
                    );
                }
                break;

            case 'post':
                $settings_nntp_post = $settings->get('nntp_post');
                if (empty($settings_nntp_post['host'])) {
                    self::$_instances[$role] = self::pool($settings, 'hdr');
                } else {
                    self::$_instances[$role] = new Services_Nntp_PipelinedTransport(
                        self::normalizeServer($settings_nntp_post),
                        Services_Nntp_PipelinedTransport::DEFAULT_TIMEOUT,
                        'post'
                    );
                }
                break;

            default:
                throw new Exception('Unknown NNTP role client ('.$role.') for pool creation');
        }

        return self::$_instances[$role];
    }

    private static function normalizeServer(array $server)
    {
        if (!isset($server['verifyname'])) {
            $server['verifyname'] = false;
        }
        if (!isset($server['article_pipeline_depth'])) {
            $server['article_pipeline_depth'] = Services_Nntp_PipelineDepth::DefaultDepth;
        }

        return $server;
    }
}
