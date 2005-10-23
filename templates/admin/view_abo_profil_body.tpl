<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./view.php?mode=abonnes">
<ul class="links special">
    <li>{RETURN_TO_BACK}</li>
</ul>

<div class="smallbloc">
    <h2>{L_TITLE}</h2>
    
    <table class="content">
        <tr>
            <td class="row1"> <span class="texte">{L_PSEUDO}&#160;:</span> </td>
            <td class="row2"> <span class="texte">{S_ABO_PSEUDO}</span> </td>
        </tr>
        <tr>
            <td class="row1"> <span class="texte">{L_EMAIL}&#160;:</span> </td>
            <td class="row2"> <span class="texte"><a href="mailto:{S_ABO_EMAIL}">{S_ABO_EMAIL}</a></span> </td>
        </tr>
        <tr>
            <td class="row1"> <span class="texte">{L_REGISTER_DATE}&#160;:</span> </td>
            <td class="row2"> <span class="texte">{S_REGISTER_DATE}</span> </td>
        </tr>
        <tr>
            <td class="explain" colspan="2"> {L_LISTE_TO_REGISTER}&#160;: </td>
        </tr>
        <!-- BEGIN listerow -->
        <tr>
            <td class="row1" colspan="2"> <span class="texte"> -&#160;<a href="{listerow.U_VIEW_LISTE}">{listerow.LISTE_NAME}</a></span> </td>
        </tr>
        <!-- END listerow -->
    </table>
    
    <div class="bottom"> {S_HIDDEN_FIELDS}
        <input type="submit" name="delete" value="{L_DELETE_ACCOUNT_BUTTON}" class="pbutton" />
    </div>
</div>
</form>

{LISTBOX}
