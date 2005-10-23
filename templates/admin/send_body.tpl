<script type="text/javascript">
<!--
var lang = [];
lang["preview"] = '{L_PREVIEW_BUTTON}';
lang["addlink"] = '{L_ADDLINK_BUTTON}';
//-->
</script>

<p id="explain">{L_EXPLAIN}</p>

<form id="send-form" method="post" action="./envoi.php" enctype="{S_ENCTYPE}">
<ul class="links">
    <li> <a href="./envoi.php?mode=load">{L_LOAD_LOG}</a> </li>
    <li> <a href="./envoi.php?mode=resend">{L_RESEND_LOG}</a> </li>
</ul>

<div class="bloc">
    <table class="content">
        <tr>
            <td class="medrow1"> <span class="texte">{L_DEST}&#160;: </span> </td>
            <td class="row2"> <span class="texte"><b>{S_DEST}</b></span> </td>
        </tr>
        <tr>
            <td class="medrow1"> <label for="subject">{L_SUBJECT}&#160;: </label> </td>
            <td class="row2"> <input type="text" id="subject" name="subject" value="{S_SUBJECT}" size="40" maxlength="100" class="text" /> </td>
        </tr>
        <tr>
            <td class="medrow1"> <label for="log_status">{L_STATUS}&#160;: </label> </td>
            <td class="row2"> <select id="log_status" name="log_status"><option value="0" {SELECTED_STATUS_WRITING}> - {L_STATUS_WRITING} - </option><option value="3"{SELECTED_STATUS_HANDLE}> - {L_STATUS_HANDLE} - </option></select> </td>
        </tr>
    </table>
</div>

<!-- BEGIN formulaire -->
<div class="bloc" id="textarea{formulaire.S_FORMAT}">
    <h2>{formulaire.L_TITLE}</h2>
    
    <table class="content">
        <tr>
            <td class="explain"> {formulaire.L_EXPLAIN_BODY} </td>
        </tr>
        <tr>
            <td class="row-full">
                <textarea name="{formulaire.S_TEXTAREA_NAME}" cols="90" rows="20" class="text">{formulaire.S_BODY}</textarea>
            </td>
        </tr>
    </table>
</div>
<!-- END formulaire -->

<!-- BEGIN joined_files -->
<div class="bloc">
    <h2>{joined_files.L_TITLE_ADD_FILE}</h2>
    
    <table class="content">
        <tr>
            <td class="explain" colspan="2"> {joined_files.L_EXPLAIN_ADD_FILE} </td>
        </tr>
        <tr>
            <td rowspan="{joined_files.S_ROWSPAN}" class="medrow1"> <label for="join_file">{joined_files.L_ADD_FILE} :</label> </td>
            <td class="row2"> <input type="text" id="join_file" name="join_file" size="30" class="text" /> </td>
        </tr>
        <!-- BEGIN upload_input -->
        <tr>
            <td class="row2"> <input type="file" name="join_file" size="30" /> </td>
        </tr>
        <!-- END upload_input -->
        <!-- BEGIN select_box -->
        <tr>
            <td class="row2"> {joined_files.select_box.SELECT_BOX} </td>
        </tr>
        <!-- END select_box -->
        <tr>
            <td class="row2"> <input type="submit" name="attach" value="{joined_files.L_ADD_FILE_BUTTON}" class="button" /> </td>
        </tr>
    </table>
    
    <!-- BEGIN files_box -->
    <h2>{joined_files.files_box.L_TITLE_JOINED_FILES}</h2>
    
    {JOINED_FILES_BOX}
    <!-- END files_box -->
</div>

<!-- BEGIN files_box -->
<div id="nav-bottom">
    <div class="left">&#160;</div>
    <div class="right"> <input type="submit" name="unattach" value="{joined_files.files_box.L_DEL_FILE_BUTTON}" class="button" /> </div>
</div>
<!-- END files_box -->
<!-- END joined_files -->

<div class="bloc">
    <div class="bottom"> {S_HIDDEN_FIELDS}
        <input type="submit" name="send" value="{L_SEND_BUTTON}" class="button" /> 
        <input type="submit" name="save" value="{L_SAVE_BUTTON}" class="pbutton" tabindex="1" /> 
        <input type="submit" name="delete" value="{L_DELETE_BUTTON}" class="button" />
    </div>
</div>
</form>

{LISTBOX}
