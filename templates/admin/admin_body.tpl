<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./admin.php">
<!-- BEGIN admin_options -->
<ul class="links">
    <li> <a href="./admin.php?mode=adduser">{admin_options.L_ADD_ADMIN}</a> </li>
</ul>
<!-- END admin_options -->

<div class="bloc">
    <h2>{L_TITLE}</h2>
    
    <table class="content">
        <tr>
            <td class="row1"> <label for="language">{L_DEFAULT_LANG}&#160;:</label> </td>
            <td class="row2"> {LANG_BOX} </td>
        </tr>
        <tr>
            <td class="row1"> <label for="email">{L_EMAIL}&#160;:</label> </td>
            <td class="row2"> <input type="text" id="email" name="email" value="{EMAIL}" size="40" maxlength="200" class="text" /> </td>
        </tr>
        <tr>
            <td class="row1"> <label for="dateformat">{L_DATEFORMAT}&#160;:</label><br /><span class="m-texte">{L_NOTE_DATE}</span> </td>
            <td class="row2"> <input type="text" id="dateformat" name="dateformat" value="{DATEFORMAT}" size="15" maxlength="20" class="text" /> </td>
        </tr>
        <tr>
            <td class="row1"> <label>{L_EMAIL_NEW_INSCRIT}&#160;:</label> </td>
            <td class="row2"> <input type="radio" id="email_new_inscrit_yes" name="email_new_inscrit" value="1" {EMAIL_NEW_INSCRIT_YES}/> <label for="email_new_inscrit_yes" class="m-texte">{L_YES}</label> &nbsp; <input type="radio" id="email_new_inscrit_no" name="email_new_inscrit" value="0" {EMAIL_NEW_INSCRIT_NO}/> <label for="email_new_inscrit_no" class="m-texte">{L_NO}</label> </td>
        </tr>
        <!-- BEGIN owner_profil -->
        <tr>
            <td class="row1"> <label for="current_pass">{L_PASS}&#160;:</label><br /><span class="m-texte">{L_NOTE_PASS}</span> </td>
            <td class="row2"> <input type="password" id="current_pass" name="current_pass" size="30" maxlength="20" class="text" /> </td>
        </tr>
        <!-- END owner_profil -->
        <tr>
            <td class="row1"> <label for="new_pass">{L_NEW_PASS}&#160;:</label><br /><span class="m-texte">{L_NOTE_PASS}</span> </td>
            <td class="row2"> <input type="password" id="new_pass" name="new_pass" size="30" maxlength="20" class="text" /> </td>
        </tr>
        <tr>
            <td class="row1"> <label for="confirm_pass">{L_CONFIRM_PASS}&#160;:</label><br /><span class="m-texte">{L_NOTE_PASS}</span> </td>
            <td class="row2"> <input type="password" id="confirm_pass" name="confirm_pass" size="30" maxlength="20" class="text" /> </td>
        </tr>
    </table>
    
    <!-- BEGIN admin_options -->
    <h2>{admin_options.L_TITLE_MANAGE}</h2>
    
    <table class="content">
        <tr>
            <th> {admin_options.L_LISTE_NAME} </th>
            <th> {admin_options.L_VIEW} </th>
            <th> {admin_options.L_EDIT} </th>
            <th> {admin_options.L_DEL} </th>
            <th> {admin_options.L_SEND} </th>
            <th> {admin_options.L_IMPORT} </th>
            <th> {admin_options.L_EXPORT} </th>
            <th> {admin_options.L_BAN} </th>
            <th> {admin_options.L_ATTACH} </th>
        </tr>
        <!-- BEGIN auth -->
        <tr>
            <td class="row1"> <span class="m-texte">{admin_options.auth.LISTE_NAME}</span> <input type="hidden" name="liste_id[]" value="{admin_options.auth.LISTE_ID}" /> </td>
            <td class="smallrow2"> {admin_options.auth.BOX_AUTH_VIEW} </td>
            <td class="smallrow1"> {admin_options.auth.BOX_AUTH_EDIT} </td>
            <td class="smallrow2"> {admin_options.auth.BOX_AUTH_DEL} </td>
            <td class="smallrow1"> {admin_options.auth.BOX_AUTH_SEND} </td>
            <td class="smallrow2"> {admin_options.auth.BOX_AUTH_IMPORT} </td>
            <td class="smallrow1"> {admin_options.auth.BOX_AUTH_EXPORT} </td>
            <td class="smallrow2"> {admin_options.auth.BOX_AUTH_BACKUP} </td>
            <td class="smallrow1"> {admin_options.auth.BOX_AUTH_ATTACH} </td>
        </tr>
        <!-- END auth -->
    </table>
    
    <h2>{admin_options.L_TITLE_OPTIONS}</h2>
    
    <table class="content">
        <tr>
            <td class="row1"> <label for="admin_level">{admin_options.L_ADMIN_LEVEL}&#160;:</label> </td>
            <td class="row2"> <select id="admin_level" name="admin_level"><option value="2"{admin_options.SELECTED_ADMIN}> - {admin_options.L_ADMIN} - </option><option value="1"{admin_options.SELECTED_USER}> - {admin_options.L_USER} - </option></select> </td>
        </tr>
        <tr>
            <td class="row1"> <label for="delete_user">{admin_options.L_DELETE_ADMIN}&#160;:</label> </td>
            <td class="row2"> <input type="checkbox" id="delete_user" name="delete_user" value="1" /> <span class="m-texte">{admin_options.L_NOTE_DELETE}</span> </td>
        </tr>
    </table>
    <!-- END admin_options -->
    
    <div class="bottom"> {S_HIDDEN_FIELDS}
        <input type="submit" name="submit" class="pbutton" value="{L_VALID_BUTTON}" /> <input type="reset" value="{L_RESET_BUTTON}" class="button" />
    </div>
</div>
</form>

<!-- BEGIN admin_box -->
<form id="smallbox" method="post" action="./admin.php">
<div>
    {admin_box.S_HIDDEN_FIELDS} <label for="admin_id">{admin_box.L_VIEW_PROFILE}&#160;:</label> {admin_box.ADMIN_BOX} <input type="submit" value="{admin_box.L_BUTTON_GO}" class="button" />
</div>
</form>
<!-- END admin_box -->