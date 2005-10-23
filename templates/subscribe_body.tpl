<script type="text/javascript">
<!--
var submitted = false;

function check_form(e)
{
    var emailAddr = document.forms['subscribe-form'].elements['email'].value;
    
    if( emailAddr.indexOf('@', 1) == -1 || emailAddr.indexOf('.', 1) == -1 )
    {
        window.alert('{L_INVALID_EMAIL}');
        
        if( e && e.preventDefault ) { // standard
            e.preventDefault();
        } else { // MS
            window.event.returnValue = false;
        }
    }
    else if( submitted == true )
    {
        window.alert('{L_PAGE_LOADING}');
        
        if( e && e.preventDefault ) { // standard
            e.preventDefault();
        } else { // MS
            window.event.returnValue = false;
        }
    }
    else
    {
        submitted = true;
    }
}

window.onload = function() {
    document.forms['subscribe-form'].onsubmit = check_form;
}
//-->
</script>

<form id="subscribe-form" method="post" action="./subscribe.php">
<fieldset>
    <legend xml:lang="en" lang="en">Mailing liste</legend>
    
    <div class="bloc">
        <label for="email">{L_EMAIL}&#160;:</label>
        <input type="text" id="email" name="email" maxlength="100" />
    </div>
    
    <div class="bloc">
        <label for="format">{L_FORMAT}&#160;:</label>
        <select id="format" name="format"><option value="1">TXT</option><option value="2">HTML</option></select>
    </div>
    
    <div class="bloc">
        <label for="liste">{L_DIFF_LIST}&#160;:</label>
        {LIST_BOX}
    </div>
    
    <div class="center">
        <label><input type="radio" name="action" value="inscription" checked="checked" /> {L_SUBSCRIBE}</label>
        <label><input type="radio" name="action" value="setformat" /> {L_SETFORMAT}</label>
        <label><input type="radio" name="action" value="desinscription" /> {L_UNSUBSCRIBE}</label>
    </div>
    
    <div class="center"> <input type="submit" name="wanewsletter" value="{L_VALID_BUTTON}" /> </div>
</fieldset>
</form>

<p class="message">{MESSAGE}</p>
