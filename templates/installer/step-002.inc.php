<form name='dbform' method='POST'>
    <script type="text/javascript">
        function dohide() {
            var e = document.getElementById('pdo');
            var r = e.options[e.selectedIndex].value;
            //alert("do hide" + r);
			if (r == 'pdo_pgsql') {
                document.getElementById("portfield").value = 5432;
				document.getElementById("spsh").style.visibility = "visible";
				document.getElementById("spdb").textContent = " Postgres database:"
            };   
            if (r == 'pdo_mysql') {
                document.getElementById("portfield").value = 3306;
				document.getElementById("spsh").style.visibility = "collapse";
				document.getElementById("spdb").textContent = " MySQL database:"
            };     
            if (r == 'pdo_sqlite') {
                document.getElementById("rhost").style.visibility = "collapse";
                document.getElementById("rport").style.visibility = "collapse";
                document.getElementById("rpwd").style.visibility = "collapse";
                document.getElementById("spuser").style.visibility = "collapse";
                document.getElementById("sppass").style.visibility = "collapse";
				document.getElementById("spsh").style.visibility = "collapse";
                document.getElementById("spdb").textContent = " SQLite file name"
            } else {
                document.getElementById("rhost").style.visibility = "visible";
                document.getElementById("rport").style.visibility = "visible";
                document.getElementById("rpwd").style.visibility = "visible";
                document.getElementById("spuser").style.visibility = "visible";				
                document.getElementById("sppass").style.visibility = "visible";
                
            }
        }
    </script>
    <table summary="PHP settings">
        <tr>
            <th> Database settings</th>
            <th></th>
        </tr>
        <tr>
            <td colspan='2'> Spotweb needs an available MySQL or PostgreSQL database. The spotweb database and spotweb user will be created if needed.
                In that case, you need to fill the root password for the connection.
            </td>
        </tr>
        <tr>
            <td> type</td>
            <td><select id="pdo" name='dbform[engine]' onchange="dohide()" >
					<option value='pdo_mysql'>MySQL</option>
					<option value='pdo_pgsql'>PostgreSQL</option>					
                    <option value='pdo_sqlite'>SQLite</option>
                </select></td>
        </tr>
        <tr id="rhost">
            <td> server</td>
            <td><input type='text' maxlength='40' name='dbform[host]'
                       value='<?php echo htmlspecialchars($form['host']); ?>'/></td>
        </tr>
        <tr id="rport">
            <td> port</td>
            <td><input type='text' maxlength='5' size='5' id='portfield' name='dbform[port]'
                       value='<?php echo htmlspecialchars($form['port']); ?>'/></td>
        </tr>
        <tr id="rpwd">
            <td> Root password to create spotweb database/user <br> Leave blank if database and user are already created. </td>
            <td><input type='password' length='40' name='dbform[rootpwd]'
                       value='<?php echo htmlspecialchars($form['rootpwd']); ?>'/></td>
        </tr>
        <tr>
            <td id="spdb"> MySQL database:</td>
            <td><input type='text' maxlength='40' name='dbform[dbname]'
                       value='<?php echo htmlspecialchars($form['dbname']); ?>'/></td>
        </tr>
		<tr id="spsh" style="visibility: collapse;">
            <td> Postgres schema (default: public)</td>
            <td><input type='text' maxlength='40' name='dbform[schema]'
                       value='<?php echo htmlspecialchars($form['schema']); ?>'/></td>
        </tr>
        <tr id="spuser">
            <td> Database user name:</td>
            <td><input type='text' maxlength='40' name='dbform[user]'
                       value='<?php echo htmlspecialchars($form['user']); ?>'/></td>
        </tr>
        <tr id="sppass">
            <td> Database user password:</td>
            <td><input type='text' maxlength='40' name='dbform[pass]'
                       value='<?php echo htmlspecialchars($form['pass']); ?>'/></td>
        </tr>
        <tr>
            <td colspan='2'><input type='submit' name='dbform[submit]' value='Verify database'></td>
        </tr>
    </table>
</form>
<br/>
<?php
