<?php

require_once 'login.php';

require 'ort_colors.php';

/*
$db_hostname = 'localhost';
$db_database = 'csrdb';
$db_username = 'root';
$db_password = 'chrome21';*/

$pn = "";
if(isset($_GET['pn']))
{
    $pn = $_GET['pn'];
    $db_server = mysqli_connect($db_hostname, $db_username, $db_password);
    if(!$db_server)die ("Unable to connect to MySQL: ".mysqli_error($db_server));
    mysqli_select_db($db_server, $db_database) or die ("Unable to select database: " .mysqli_error($db_server));
    $query = "SELECT * from series WHERE pn_format <> 'UNKNOWN' ";
    $result = mysqli_query($db_server, $query);
    if(!$result) die ("Database access failed: ". mysqli_error($db_server));
    else
    {
        $rows = mysqli_num_rows($result);
        $row = 0;
        $color = "";
        $color_text = "";
        $length = "";
        $qtybox = 0;
        $revlevel = "";
        $customer = "";
        $pn_format = "";
        $description = "";
        $id_serie = -1;
        for($c=0;$c<$rows;$c++)
        {
            $row = mysqli_fetch_row($result);
            $regex = "/".$row[3]."/";
            $matches = array();
            $match = preg_match($regex,$pn,$matches);
            
            if($match)
            {
                //echo "regex: ". $row[3] ." pn: " .$pn;
                //print_r($matches);
                $id_serie = $row[0];
                $customer = $row[1];
                $pn_format = $row[2];
                $description = $row[4];
                $revlevel = $row[5];
                
                if(key_exists('color',$matches))
                {
                    $color = $matches['color'];
                    $color_text = num2color($color);
                }
                if(key_exists('length',$matches))
                    $length = (float)$matches['length'];
                if(key_exists('qtybox',$matches))
                    $qtybox = $matches['qtybox'];
                break;
            }
        }
        if($id_serie>=0 && $length>0)
        {
            $query = "SELECT price FROM prices WHERE serie_id = $id_serie and length = $length ";
            $result = mysqli_query($db_server, $query);
            if(!$result) die ("Database access failed: ". mysqli_error($db_server));
            else
            {
                $row = mysqli_fetch_row($result);
                $price = $row[0];
                echo <<<_END
                <table id="item_table" class="table table-striped table-bordered table-hover table-condensed" style="font-size:14px">
                    <thead>
                        <tr class='centered info'>
                            <th>Customer</th>
                            <th>Serie</th>
                            <th>Description</th>
                            <th>Color</th>
                            <th>Length</th>               
_END;
                if($qtybox!=0)
                    echo "<th>Qty/Box</th>";
                echo <<<_END
                            <th>Unit Price</th>
                            <th>Rev. Level</th>
                        </thead>
                    <tbody>
                        <tr class='centered'>
                            <td>$customer</td>
                            <td>$pn_format</td>
                            <td>$description</td>
                            <td>$color_text ($color)</td>
                            <td class='number'>$length ft</td>
_END;
                if($qtybox!=0)
                    echo "<td>$qtybox</td>";
                echo <<<_END
                            <td class="number">$$price</td>
                            <td>$revlevel</td>
                        </tr>
                    </tbody>
                </table>
_END;
            }           
        }
        else
        {
            echo "<h2>This Part number was not found.</h2>";
        }
    }
}
mysqli_close($db_server);
?>