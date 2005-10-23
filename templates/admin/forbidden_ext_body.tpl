<form method="post" action="./tools.php?mode=attach">
<div class="smallbloc">
    <h2>{L_TITLE_EXT}</h2>
    
    <table class="content">
        <tr>
            <td class="explain" colspan="2"> {L_EXPLAIN_TO_FORBID} </td>
        </tr>
        <tr>
            <td class="medrow1"> <label for="ext_list">{L_FORBID_EXT}&#160;:</label> </td>
            <td class="row2"> <input type="text" id="ext_list" name="ext_list" size="30" maxlength="100" class="text" /> </td>
        </tr>
        <tr>
            <td class="explain" colspan="2"> {L_EXPLAIN_TO_REALLOW} </td>
        </tr>
        <tr>
            <td class="medrow1"> <label for="ext_list_id">{L_REALLOW_EXT}&#160;:</label> </td>
            <td class="row2"> {REALLOW_EXT_BOX} </td>
        </tr>
    </table>
    
    <div class="bottom"> {S_HIDDEN_FIELDS}
        <input type="submit" name="submit" class="pbutton" value="{L_VALID_BUTTON}" /> <input type="reset" value="{L_RESET_BUTTON}" class="button" />
    </div>
</div>
</form>
