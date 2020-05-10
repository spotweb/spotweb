<form name='nntpform' method='POST'">
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
                <select id='nntpselectbox' name='nntpform[namefield]' onchange='SelectionChanged();'>
                    <?php
                    foreach ($serverList->usenetservers->server as $provider) {
                        $server = '';
                        $sslstr = '';
                        /* Make sure the server is supported, eg filter out ssl only servers when openssl is not loaded */
                        if (extension_loaded('openssl') && isset($provider->ssl)) {
                            $server = $provider->ssl;
                            $sslstr = ',ssl';
                        } elseif (isset($provider->plain)) {
                            $server = $provider->plain;
                        } // if

                        if (!empty($server)) {
                            echo "<option value='{$provider['name']}{$sslstr}'".(($provider['name'] == $form['name']) ? "selected='selected'" : '').">{$provider['name']}</option>";
                        } // if
                    } // foreach
                    ?>
                    <option value='custom'>Custom</option>
                </select>
                <input type='hidden' id='sslfield' name='nntpform[ssl]' value='<?php echo $form['ssl']; ?>' />
                <input type='hidden' id='namefield' name='nntpform[name]' value='<?php echo $form['name']; ?>' />
            </td>
        </tr>
        <tr id='customnntpfield' style='display: none;'>
            <td> server</td>
            <td><input type='text' length='40' name='nntpform[host]'
                       value='<?php echo htmlspecialchars($form['host']); ?>'/></td>
        </tr>
        <tr id='verifybox' style='display: none;'>
            <td>Verify name (CN) on SSL certificate</td>
            <td><input name='nntpform[verifyname]' id='verifyfield' type="checkbox" <?php if (isset($form['verifyname'])) {
                        echo 'checked="checked"';
                    } ?> /> </td>
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
            <td><input type='submit' id='button1' name='nntpform[submit]' value='Verify usenet server'></td>
            <td><input type='submit' id='button2' name='nntpform[submit]' value='Skip validation'></td>
        </tr>
    </table>
</form>
<br/>
	<script type='text/javascript'>
	    function SelectionChanged() {
	        var sel = document.getElementById('nntpselectbox');
	        var res = sel.options[sel.selectedIndex].value.split(',');
	        var x = document.getElementById('customnntpfield');
	        x.style.display = (res[0] == 'custom') ? '' : 'none';
	        document.getElementById('namefield').value = res[0];
	        if (res[1] == 'ssl') {
	            document.getElementById('verifybox').style.display = '';
	            document.getElementById('sslfield').value = 'ssl';
	        } else {
	            document.getElementById('verifybox').style.display = 'none';
	            document.getElementById('sslfield').value = 'plain';
	        };

	    } // SelectionChanged
	    window.onload = function () { window.document.body.onload = SelectionChanged();}
	</script>
<?php
