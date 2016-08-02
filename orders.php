<?php

require_once 'login.php';
$processed = FALSE;
$startdate= "";
$enddate = "";
$po_num = "";
if(isset($_GET['processed']))
{
    $processed = $_GET['processed'];
}
if(isset($_GET['startdate']))
{
    $startdate = date_format(date_create($_GET['startdate']),"Y-m-d");
}
if(isset($_GET['enddate']))
{
    $enddate = date_format(date_create($_GET['enddate']),"Y-m-d");
}
if(isset($_GET['po_num']))
{
    $po_num = $_GET['po_num'];
}


$db_server = mysqli_connect($db_hostname, $db_username, $db_password);
                                                                      
if(!$db_server)die ("Unable to connect to MySQL: ".mysqli_error());
mysqli_select_db($db_server, $db_database) or die ("Unable to select database: " .mysqli_error($db_server));
$query = "SELECT pos.po_number, customer_id, date_received, ship_to, planner, csr,status, COUNT(po_lines.id), total FROM `pos` INNER JOIN po_lines on pos.po_number = po_lines.po_number WHERE pos.status" . ($processed ? " >= 1 ":" < 1 ") . ($startdate!=""?" AND pos.date_received >= '" . $startdate ."' ":"") . ($enddate!=""?" AND pos.date_received <= '" . $enddate ."' ":"") . ($po_num!=""?" AND pos.po_number = '" . $po_num ."' ":"") . "  GROUP by pos.po_number ORDER by pos.po_number";
//echo $query;
$result = mysqli_query($db_server, $query);
if(!$result) die ("Database access failed: ". mysqli_error($db_server));
else
{
    echo <<<_END
    <table id="pos_table" class="table table-striped table-bordered table-hover table-condensed" style="font-size:14px">
    <thead>
      <tr class="centered">
        <th>PO#</th>
        <th>Customer</th>        
        <th>Date Received</th>
        <th>Ship To: </th>
        <th>Planner</th>
        <th>CSR</th>
        <th>Status</th>
        <th># lines</th>
        <th>Total</th>     
      </tr>
    </thead>
    <tbody>        
_END;
    $rows = mysqli_num_rows($result);
    $row = 0;    
    for($c=0;$c<$rows;$c++)
    {
        $icon ="";
        $row = mysqli_fetch_row($result);
        $date_rec = date_format(date_create($row[2]),"n/d/Y H:i:s");
        if($row[6]==-1)
        {
            echo "<tr class='text-warning centered'><td><a href='openpo.php?po=$row[0]'>$row[0]</a></td>";
            $icon = "glyphicon glyphicon-exclamation-sign";
        }
        else if($row[6]==0)
        {
            echo "<tr class='text-success centered'><td><a href='openpo.php?po=$row[0]'>$row[0]</a></td>";
            $icon = "glyphicon glyphicon-ok-circle";
        }
        else if($row[6]==1)
        {
            echo "<tr class='text-info centered'><td><a href='openpo.php?po=$row[0]'>$row[0]</a></td>";
            $icon = "glyphicon glyphicon-save";
        }
        echo <<<_END
        <td>$row[1]</td>
        <td>$date_rec</td>
        <td>LEGRAND ORTRONICS EL PASO</td>
        <td>$row[4]</td>
        <td>$row[5]</td>
        <td><span class='$icon'></span></td>
        <td class="number">$row[7]</td>
_END;
        echo "<td class='number'>$".number_format($row[8],2,'.',',')."</td></tr>";

    }
    echo "</tbody></table>";
}
mysqli_close($db_server);
?>