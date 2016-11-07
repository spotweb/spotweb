<form name='dbform' method='POST'>
    <table summary="PHP settings">
        <tr>
            <th> Database settings</th>
            <th></th>
        </tr>
        <tr>
            <td colspan='2'> Spotweb needs an available MySQL or PostgreSQL database. The database needs to be created
                and you need to have an user account and password for this database.
            </td>
        </tr>
        <tr>
            <td> type</td>
            <td><select name='dbform[engine]'>
                    <option value='pdo_mysql'>mysql</option>
                    <option value='pdo_pgsql'>PostgreSQL</option>
                    <option value='pdo_sqlite'>SQLite (untested)</option>
                </select></td>
        </tr>
        <tr>
            <td> server</td>
            <td><input type='text' length='40' name='dbform[host]'
                       value='<?php echo htmlspecialchars($form['host']); ?>'/></td>
        </tr>
        <tr>
            <td> database</td>
            <td><input type='text' length='40' name='dbform[dbname]'
                       value='<?php echo htmlspecialchars($form['dbname']); ?>'/></td>
        </tr>
        <tr>
            <td> username</td>
            <td><input type='text' length='40' name='dbform[user]'
                       value='<?php echo htmlspecialchars($form['user']); ?>'/></td>
        </tr>
        <tr>
            <td> password</td>
            <td><input type='password' length='40' name='dbform[pass]'
                       value='<?php echo htmlspecialchars($form['pass']); ?>'/></td>
        </tr>
        <tr>
            <td colspan='2'><input type='submit' name='dbform[submit]' value='Verify database'></td>
        </tr>
    </table>
</form>
<br/>
<?php
