<form name='nntpform' method='POST'>
    <table summary="PHP settings">
        <tr>
            <th> Usenet server settings</th>
            <th></th>
        </tr>
        <tr>
            <td colspan='2'> Spotweb needs an usenet server. We have several usenet server profiles defined from which
                you can choose. If your server is not listed, please choose 'custom', more advanced options can be set
                from within Spotweb itself.
            </td>
        </tr>
        <tr>
            <td> Usenet server</td>
            <td>
                <select id='nntpselectbox' name='nntpform[name]' onchange='toggleNntpField();'>
                    <?php
                    foreach ($serverList->usenetservers->server as $provider) {
                        $server = '';

                        /* Make sure the server is supported, eg filter out ssl only servers when openssl is not loaded */
                        if (extension_loaded('openssl') && isset($provider->ssl)) {
                            $server = $provider->ssl;
                        } elseif (isset($provider->plain)) {
                            $server = $provider->plain;
                        } # if

                        if (!empty($server)) {
                            echo "<option value='{$provider['name']}'" . (($provider['name'] == $form['name']) ? "selected='selected'" : '') . ">{$provider['name']}</option>";
                        } # if
                    } # foreach
                    ?>
                    <option value='custom'>Custom</option>
                </select>
            </td>
        </tr>
        <tr id='customnntpfield' style='display: none;'>
            <td> server</td>
            <td><input type='text' length='40' name='nntpform[host]'
                       value='<?php echo htmlspecialchars($form['host']); ?>'/></td>
        </tr>
        <tr>
            <td> username</td>
            <td><input type='text' length='40' name='nntpform[user]'
                       value='<?php echo htmlspecialchars($form['user']); ?>'/></td>
        </tr>
        <tr>
            <td> password</td>
            <td><input type='password' length='40' name='nntpform[pass]'
                       value='<?php echo htmlspecialchars($form['pass']); ?>'/></td>
        </tr>
        <tr>
            <td><input type='submit' name='nntpform[submit]' value='Verify usenet server'></td>
            <td><input type='submit' name='nntpform[submit]' value='Skip validation'></td>
        </tr>
    </table>
</form>
<br/>
<?php
