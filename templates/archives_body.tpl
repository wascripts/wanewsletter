<p id="explain">{L_EXPLAIN}</p>

<form method="post" action="./profil_cp.php">
<div class="bloc">
    <h2>{TITLE}</h2>
    
    <table class="content">
        <!-- BEGIN listerow -->
        <tr>
            <td class="medrow1"> <label for="liste_{listerow.LISTE_ID}">{listerow.LISTE_NAME}&#160;:</label> </td>
            <td class="medrow2"> {listerow.SELECT_LOG} </td>
        </tr>
        <!-- END listerow -->
    </table>
    
    <div class="bottom"> {S_HIDDEN_FIELDS}
        <input type="submit" name="submit" value="{L_VALID_BUTTON}" class="pbutton" />
    </div>
</div>
</form>
