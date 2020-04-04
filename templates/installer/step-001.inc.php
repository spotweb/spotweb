<?php

require_once __DIR__.'/includes/header.inc.php';

global $_testInstall_Ok;

?>

<table summary="PHP settings">
    <tr>
        <th>PHP settings</th>
        <th>Value</th>
        <th>Result</th>
    </tr>
    <tr>
        <td>PHP version</td>
        <td><?php echo phpversion(); ?></td>
        <td><?php
            SpotInstall::showResult(
    (version_compare(PHP_VERSION, '5.3.0') >= 0),
    true,
    '',
    'PHP 5.3 or later is recommended'
);
            ?></td>
    </tr>
    <tr>
        <td>timezone settings</td>
        <td><?php echo ini_get('date.timezone'); ?></td>
        <td><?php SpotInstall::showResult(
                ini_get('date.timezone'),
                true,
                '',
                'Please specify date.timezone in your PHP.ini'
            ); ?></td>
    </tr>
    <tr>
        <td> Open base dir</td>
        <td><?php echo ini_get('open_basedir'); ?></td>
        <td><?php SpotInstall::showResult(
                !ini_get('open_basedir'),
                true,
                '',
                'Not empty, <strong>might</strong> be a problem'
            ); ?></td>
    </tr>
    <tr>
        <td> Allow furl open</td>
        <td><?php echo ini_get('allow_url_fopen'); ?></td>
        <td><?php SpotInstall::showResult(
                ini_get('allow_url_fopen') == 1,
                true,
                '',
                'allow_url_fopen not on -- will cause problems to retrieve external data'
            ); ?></td>
    </tr>
    <tr>
        <td> Memory limit</td>
        <td><?php echo ini_get('memory_limit'); ?></td>
        <td><?php SpotInstall::showResult(
                SpotInstall::returnBytes(ini_get('memory_limit')) >= (128 * 1024 * 1024),
                true,
                '',
                'memory_limit below 128M'
            ); ?></td>
    </tr>
</table>
<br/>

<table summary="PHP extensions">
    <tr>
        <th colspan="2"> PHP extension</th>
        <th> Result</th>
    </tr>
    <tr>
        <td colspan="2"> curl</td>
        <!--<td><?php SpotInstall::showResult(extension_loaded('curl'), true); ?></td>-->
        <td><?php SpotInstall::showResult(
                extension_loaded('curl'),
                false,
                '',
                'You need this module to communicate with sabnzbd/nzbget'
            ); ?></td>
    </tr>
    <tr>
        <td colspan="2"> DOM</td>
        <td><?php SpotInstall::showResult(extension_loaded('dom'), true); ?></td>
    </tr>
    <tr>
        <td colspan="2"> gettext</td>
        <td><?php SpotInstall::showResult(extension_loaded('gettext'), false); ?></td>
    </tr>
    <tr>
        <td colspan="2"> mbstring</td>
        <td><?php SpotInstall::showResult(extension_loaded('mbstring'), true); ?></td>
    </tr>
    <tr>
        <td colspan="2"> json</td>
        <td><?php SpotInstall::showResult(extension_loaded('json'), true); ?></td>
    </tr>
    <tr>
        <td colspan="2"> xml</td>
        <td><?php SpotInstall::showResult(extension_loaded('xml'), true); ?></td>
    </tr>
    <tr>
        <td colspan="2"> zip</td>
        <td><?php SpotInstall::showResult(
                extension_loaded('zip'),
                false,
                '',
                'You need this module to select multiple NZB files'
            ); ?></td>
    </tr>
    <tr>
        <td colspan="2"> zlib</td>
        <td><?php SpotInstall::showResult(extension_loaded('zlib'), true); ?></td>
    </tr>

    <tr>
        <th colspan="2"> Database support</th>
        <td><?php SpotInstall::showResult(
                extension_loaded('pdo_mysql') || extension_loaded('pdo_pgsql'),
                true
            ); ?></td>
    </tr>
    <tr>
        <td colspan="2"> MySQL (PDO)</td>
        <td><?php SpotInstall::showResult(extension_loaded('pdo_mysql'), false); ?></td>
    </tr>
    <tr>
        <td colspan="2"> PostgreSQL (PDO)</td>
        <td><?php SpotInstall::showResult(extension_loaded('pdo_pgsql'), false); ?></td>
    </tr>
    <tr>
        <td colspan="2"> SQLite (PDO)</td>
        <td><?php SpotInstall::showResult(extension_loaded('pdo_sqlite'), false); ?></td>
    </tr>
    <?php if (extension_loaded('gd')) {
                $gdInfo = gd_info();
            } else {
                $gdInfo = ['FreeType Support' => 0, 'GIF Read Support' => 0, 'GIF Create Support' => 0, 'JPEG Support' => 0, 'JPG Support' => 0, 'PNG Support' => 0];
            }?>
    <tr>
        <th colspan="2"> GD</th>
        <td><?php SpotInstall::showResult(extension_loaded('gd'), true); ?></td>
    </tr>
    <tr>
        <td colspan="2"> FreeType Support</td>
        <td><?php SpotInstall::showResult($gdInfo['FreeType Support'], true); ?></td>
    </tr>
    <tr>
        <td colspan="2"> GIF Read Support</td>
        <td><?php SpotInstall::showResult($gdInfo['GIF Read Support'], true); ?></td>
    </tr>
    <tr>
        <td colspan="2"> GIF Create Support</td>
        <td><?php SpotInstall::showResult($gdInfo['GIF Create Support'], true); ?></td>
    </tr>
    <tr>
        <td colspan="2"> JPEG Support</td>
        <td><?php SpotInstall::showResult($gdInfo['JPEG Support'] || $gdInfo['JPG Support'], true); ?></td>
    </tr><!-- Previous to PHP 5.3.0, the JPEG Support attribute was named JPG Support. -->
    <tr>
        <td colspan="2"> PNG Support</td>
        <td><?php SpotInstall::showResult($gdInfo['PNG Support'], true); ?></td>
    </tr>
    <tr>
        <th colspan="3"> OpenSSL</th>
    </tr>
    <tr>
        <td rowspan="3"> At least 1 of these must be OK <br/>these modules are sorted from fastest to slowest</td>
        <td> openssl</td>
        <td><?php SpotInstall::showResult(extension_loaded('openssl'), false); ?></td>
    </tr>
    <tr>
        <td> gmp</td>
        <td><?php SpotInstall::showResult(extension_loaded('gmp'), false); ?></td>
    </tr>
    <tr>
        <td> bcmath</td>
        <td><?php SpotInstall::showResult(extension_loaded('bcmath'), false); ?></td>
    </tr>
    <tr>
        <td colspan="2"> Can create private key?</td>
        <td><?php SpotInstall::showResult(
                isset($privKey['public']) && !empty($privKey['public']) && !empty($privKey['private']),
                true
            ); ?></td>
    </tr>
    <tr>
        <th colspan="3"> Cache directory</th>
    </tr>
    <tr>
        <td colspan="2"> Cache directory is writable?</td>
        <td><?php SpotInstall::showResult(is_writable('./cache'), true); ?></td>
    </tr>
</table>
<br/>

<table summary="Include files">
    <tr>
        <th> Include files</th>
        <th> Result</th>
    </tr>
    <tr>
        <td> Settings file</td>
        <td><?php $result = SpotInstall::testInclude(__DIR__.'/../../settings.php');
            echo SpotInstall::showResult($result, true, $result); ?></td>
    </tr>
    <tr>
        <td> Own settings file</td>
        <td><?php $result = SpotInstall::testInclude(__DIR__.'/../../ownsettings.php');
            echo SpotInstall::showResult($result, true, $result, 'optional'); ?></td>
    </tr>
</table>
<br/>

<?php if ($_testInstall_Ok) { ?>
    <table summary="result" class="tableresult">
        <tr>
            <th colspan="2"> Please continue to setup Spotweb</th>
            <th><a href="?page=2" class="button">Next</a></th>
        </tr>
    </table>
    <br/>
<?php } else { ?>
    <table summary="result">
        <tr>
            <th> Please fix above errors before you can continue to install Spotweb</th>
        </tr>
    </table>
    <br/>
<?php } ?>
