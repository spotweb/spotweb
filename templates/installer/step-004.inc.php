<form name='settingsform' method='POST'>
    <table summary="PHP settings">
        <tr>
            <th colspan='2'> Spotweb type</th>
        </tr>
        <tr>
            <td colspan='2'> Spotweb has several usages - it can be either run as a personal system, a shared system
                among friends or a completely public system. <br/> <br/> Please select the most appropriate usage below.
            </td>
        </tr>
        <tr>
            <td nowrap="nowrap"><input type="radio" name="settingsform[systemtype]" value="single">Single user</td>
            <td> Single user systems are one-user systems, not shared with friends or family members. Spotweb wil always
                be logged on using the below defined user and Spotweb will never ask for authentication.
            </td>
        </tr>
        <tr>
            <td nowrap="nowrap"><input type="radio" name="settingsform[systemtype]" value="shared">Shared</td>
            <td> Shared systems are Spotweb installations shared among friends or family members. You do have to logon
                using an useraccount, but the users who do log on are trusted to have no malicious intentions.
        </tr>
        <tr>
            <td nowrap="nowrap"><input type="radio" name="settingsform[systemtype]" value="public" checked="checked">Public
            </td>
            <td> Public systems are Spotweb installations fully open to the public. Because the installation is fully
                open, regular users do not have all the features available in Spotweb to help defend against certain
                malicious users.
        </tr>
        <tr>
            <th colspan='2'> Administrative user</th>
        </tr>
        <tr>
            <td colspan='2'> Spotweb will use below user information to create a user for use by Spotweb. The defined
                password will also be set as the password for the built-in 'admin' account. Please make sure to remember
                this password.
            </td>
        </tr>
        <tr>
            <td> Username</td>
            <td><input type='text' length='40' name='settingsform[username]'
                       value='<?php echo htmlspecialchars($form['username']); ?>'/></td>
        </tr>
        <tr>
            <td> Password</td>
            <td><input type='password' length='40' name='settingsform[newpassword1]'
                       value='<?php echo htmlspecialchars($form['newpassword1']); ?>'/></td>
        </tr>
        <tr>
            <td> Password (confirm)</td>
            <td><input type='password' length='40' name='settingsform[newpassword2]'
                       value='<?php echo htmlspecialchars($form['newpassword2']); ?>'/></td>
        </tr>
        <tr>
            <td> First name</td>
            <td><input type='text' length='40' name='settingsform[firstname]'
                       value='<?php echo htmlspecialchars($form['firstname']); ?>'/></td>
        </tr>
        <tr>
            <td> Last name</td>
            <td><input type='text' length='40' name='settingsform[lastname]'
                       value='<?php echo htmlspecialchars($form['lastname']); ?>'/></td>
        </tr>
        <tr>
            <td> Email address</td>
            <td><input type='text' length='40' name='settingsform[mail]'
                       value='<?php echo htmlspecialchars($form['mail']); ?>'/></td>
        </tr>
        <tr>
            <td colspan='2'><input type='submit' name='settingsform[submit]' value='Create system'></td>
        </tr>
    </table>
</form>
<br/>
