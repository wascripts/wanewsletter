<p id="explain">{L_EXPLAIN}</p>

<form method="get" action="./tools.php">
<div class="bloc">
    <h2>{L_TITLE}</h2>
    
    <table class="content">
        <tr>
            <td class="row1"> <label for="mode">{L_SELECT_TOOL}&#160;:</label> </td>
            <td class="row2"> {S_TOOLS_BOX} </td>
        </tr>
    </table>
    
    <div class="bottom"> {S_TOOLS_HIDDEN_FIELDS}
        <input type="submit" value="{L_VALID_BUTTON}" class="pbutton" />
    </div>
</div>
</form>

{TOOL_BODY}

{LISTBOX}   