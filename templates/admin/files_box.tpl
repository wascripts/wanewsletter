<table class="content">
    <tr>
        <th> # </th>
        <th> {L_FILENAME} </th>
        <th> {L_FILESIZE} </th>
        <!-- BEGIN del_column -->
        <th> &#160; </th>
        <!-- END del_column -->
    </tr>
    <!-- BEGIN file_info -->
    <tr>
        <td class="minirow"> &#160;<span class="m-texte"><b>{file_info.OFFSET}</b></span>&#160; </td>
        <td class="row1"> <span class="texte">{file_info.S_SHOW} <a href="{file_info.U_DOWNLOAD}">{file_info.FILENAME}</a></span> </td>
        <td class="row1"> <span class="texte">{file_info.FILESIZE}</span> </td>
        <!-- BEGIN delete_options -->
        <td class="row2"> <input type="checkbox" name="file_ids[]" value="{file_info.delete_options.FILE_ID}" /> </td>
        <!-- END delete_options -->
    </tr>
    <!-- END file_info -->
    <tr>
        <td class="explain" colspan="{S_ROWSPAN}"> {L_TOTAL_LOG_SIZE}&#160;: {TOTAL_LOG_SIZE} </td>
    </tr>
</table>
