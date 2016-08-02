<?php
require_once 'login.php';

$po = "";
if(isset($_GET['po']))
{
    $po = $_GET['po'];
    $db_server = mysqli_connect($db_hostname, $db_username, $db_password);
    if(!$db_server)die ("Unable to connect to MySQL: ".mysqli_error($db_server));
    mysqli_select_db($db_server, $db_database) or die ("Unable to select database: " .mysqli_error($db_server));
    $query = "SELECT pos.po_number,pos.customer_id,pos.date_received,pos.ship_to,pos.planner,pos.comments,pos.csr,pos.status,pos.total,customers.name FROM pos LEFT JOIN csrdb.customers ON pos.customer_id = customers.id  WHERE po_number = '$po' ";
    $result = mysqli_query($db_server, $query);
    if(!$result) die ("Database access failed: ". mysqli_error($db_server));
    else
    {
        //$rows = mysql_numrows($result);
        $row = mysqli_fetch_row($result);
        $customer_id = $row[1];
        $date_received = date_format(date_create($row[2]),"n/d/Y");
        $ship_to = $row[3];
        $planner = $row[4];
        $comments = $row[5];
        $csr = $row[6];
        $status = $row[7];
        $total = $row[8];
        $customer_name = $row[9];
        
        $query = "SELECT po_lines.id,ln,pn,req_rev_level,our_rev_level,req_qty,req_unit_price, our_unit_price,req_ship_date,series.description, series.regex FROM po_lines INNER join series on po_lines.serie_id=series.id WHERE po_number = '$po' ";
        $result = mysqli_query($db_server, $query);
        if(!$result) die ("Database access failed: ". mysqli_error($db_server));
        else
        {
            $rows = mysqli_num_rows($result);
            $row = array();
            $ids = array();
            $ship_dates = array();
            $unit_prices = array();
            
            for($c=0;$c<$rows;$c++)
            {
                $row[$c] = mysqli_fetch_row($result);
                $ids[$c] = $row[$c][0];
                $ship_dates[$c] =  date_format(date_create($row[$c][8]),"mdy");
                $unit_prices[$c] = $row[$c][7];
            }          
        }
    
    }
}
mysqli_close($db_server);
?>
<!doctype html>
<html lang="en">
<head>
    <title>Bel Stewart Connectors</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Roboto font -->
    <link href='http://fonts.googleapis.com/css?family=Roboto+Condensed:400,300,700' rel='stylesheet' type='text/css'>    
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap-datepicker.standalone.min.css" rel='stylesheet' type='text/css'>
    <link href='styles.css' rel='stylesheet' type='text/css'>
   
    <!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <!-- Latest compiled JavaScript -->
    <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
    <script src="js/bootstrap-datepicker.min.js"></script>
    <script>
        function updateTotal(ids) {
            //code
            var our_total = 0;            
            for (c=0;c<ids.length;c++) {
                console.log("qty: " + $('#span_req_qty'+ids[c]).text());
                console.log("our price: " + $('#txt_our_price'+ids[c]).val());
                
                our_total += (parseFloat($('#span_req_qty'+ids[c]).text().replace(/,/g,'')) * parseFloat($('#txt_our_price'+ids[c]).val().replace(/,/g,'')));                
                //code
            }
            return our_total;
        };
        function numberWithCommas(x) {
            
            var parts = (parseFloat(x).toFixed(2)).toLocaleString('en').toString().split(".");
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            return parts.join(".");
        }
        $(document).ready(function (){
            var ids = [<?php echo join(',',$ids); ?>];
            var ship_dates = ['<?php echo join("','",$ship_dates); ?>'];
            var unit_prices = [<?php echo join(",",$unit_prices); ?>];
            $('#sandbox-container .input-group.date').datepicker({
                todayBtn: "linked",
                setDate: "",
                clearBtn: true,
                todayHighlight: true
            });
            $('#shipdate_ctrl').change(function(){                
                //alert($('#shipdate_ctrl').val());
                var x= new Date($('#shipdate_ctrl').val());
                var month = (x.getMonth()+1).toString();
                var day = x.getDate().toString();                
                var year = x.getFullYear().toString().substring(2,4);
                var new_shipdate = (month[1]?month:"0"+month) +(day[1]?day:"0"+day) +year;
                
                $('.txt_req_ship').each(function(){
                    $(this).val(new_shipdate);
                });
            });
            $('#btn-save').click(function(){
                
                for(c=0; c<ids.length;c++)
                {                    
                    index = ids[c];
                    //console.log(ids[c]);
                    var new_date = $('#txt_req_ship'+index).val();
                    var new_price = $('#txt_our_price'+index).val();                    
                    if (new_date!=ship_dates[c] || new_price!=unit_prices[c]) {
                        i = index;
                        p = new_price;
                        ship_dates[c] = new_date;
                        unit_prices[c]= new_price;
                        if ($('#span_req_unit_price'+i).text()==p) {
                            $('#txt_our_price'+i).removeClass("text-danger");
                            $('#txt_our_price'+i).addClass("text-success");
                            }
                        else{
                            $('#txt_our_price'+i).removeClass("text-success");
                            $('#txt_our_price'+i).addClass("text-danger");
                            }
                        //alert("original date:" + ship_dates[c] + "; new date: " + new_date+"; original price: " + unit_prices[c] + "; new price: " + new_price);
                        $.post("updateline.php", { id: ids[c], ship_date: new_date, unit_price: new_price }, function(data, status){
                            if (status!="success") {
                                //code
                                alert("Date: " + new_date + "or unit price: " + new_price +" is not valid.");
                            }
                            //console.log($('#txt_our_price'+i).val());
                                
                                
                                //if ($('#txt_our_price'+ids[c]).val()!=$('#txt_our_price'+ids[c]).prev().val()) {
                                    
                                    //code
                                //}                            
                            //alert("data: " + data + ", status: "+status);
                            
                        //code
                        });
                    }
                                        
                }
                
                var our_total = ("$" +numberWithCommas(updateTotal(ids)));
                if ($('#span_req_total').text()!=our_total) {
                    $('#span_our_total').text(our_total);
                    $('#span_our_total').removeClass('text-success');
                    $('#span_our_total').addClass('text-danger');                    
                }
                else
                {
                    $('#span_our_total').text(our_total);
                    $('#span_our_total').removeClass('text-danger');
                    $('#span_our_total').addClass('text-success');  
                }
            });            
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-1"></div>
            <div class="col-md-6" id="header">
                <a href="index.php"><img src="logo.png" width=242 id="headerimg"></a>
                <br>
            </div>            
        </div>
        <div class="row roboto">            
            <div class="col-sm-2">
                <h4>PO# <span class="text-info"><?php echo $po; ?></span></h4>
            </div>
            <div class="col-sm-2">
                <h4>Customer: <span class="text-info"><?php echo $customer_name; ?></span></h4>
            </div>
            <div class="col-sm-2">
                <h4>Planner: <span class="text-info"><?php echo $planner; ?></span></h4>
            </div>
            <div class="col-sm-2">
                <h4>CSR: <span class="text-info"><?php echo "Connie Sheetz"; ?></span></h4>
            </div>
            <div class="col-sm-3">
                <h4>Date received: <span class="text-info"><?php echo $date_received; ?></span></h4>
            </div>
            <div class='col-sm-1'>
                <div class="btn-group">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary dropdown-toggle <?php if($status==1) echo "disabled"; ?>" data-toggle="dropdown">Menu <span class="caret"></span></button>
                        <ul class="dropdown-menu pull-right" role="menu">
                            <li><a id="btn-save" href="#"><span class="glyphicon glyphicon-floppy-disk"></span> Save</a></li>
                            <li><a href="#"><span class="glyphicon glyphicon-remove"></span> Cancel</a></li>
                            <li><a href="#" data-toggle="modal" data-target="#myModal"><span class="glyphicon glyphicon-calendar"></span> Set Ship Date</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row roboto">
            <br>
            <table id="po_lines_table" class="table table-striped table-bordered table-hover table-condensed">
                <thead>
                    <tr>
                        <th>ln</th>
                        <th>P/N</th>
                        <th>Description</th>
                        <th>Req. Revision</th>
                        <th>Our Revision</th>
                        <th>Req. Qty</th>
                        <th>Req. Unit Price</th>
                        <th>Our Unit Price</th>
                        <th>Req. Ship Date</th>                        
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $our_total = 0;
                        $total_qty = 0;
                        for($c=0;$c<$rows;$c++)
                        {
                            //$req_ship_date = date_format(date_create($row[$c][8]),"mdy");
                        
                            echo "<tr class='centered'>";                            
                            echo "<td>" . $row[$c][1] ."</td>";
                            echo "<td>" . $row[$c][2] ."</td>";
                            echo "<td>" . $row[$c][9] ."</td>";
                            //echo "<td><input type='text' size='2' class='txt_req_rev' id='txt_req_rev".$row[$c][0] ."' value='". $row[$c][3] ."'></td>";
                            echo "<td>". $row[$c][3] ."</td>";
                            if($row[$c][3]!=$row[$c][4])
                                echo "<td class='text-danger'>" . $row[$c][4] ."</td>";
                            else
                                echo "<td class='text-success'>" . $row[$c][4] ."</td>";
                            echo "<td class='number'><span id='span_req_qty" . $row[$c][0]."'>" . number_format($row[$c][5],0,'.',',') ."</span></td>";
                            //echo "<td><input type='text' size='6' class='txt_req_up number' id='txt_req_up".$row[$c][0] ."' value='". $row[$c][6] ."'></td>";
                            echo "<td class='number'>$<span id='span_req_unit_price".$row[$c][0]."'>". $row[$c][6] ."</span></td>";
                            if($row[$c][6]!=$row[$c][7])
                                $class = 'text-danger';                                
                            else
                                $class = ' text-success';
                            echo "<td><input class='$class number' type='text' size='6' id='txt_our_price".$row[$c][0]."' value='" . $row[$c][7] ."' style='text-align:right;'></td>";
                            echo "<td><input type='text' size='6' class='txt_req_ship' id='txt_req_ship".$row[$c][0] ."' value='".$ship_dates[$c] ."' style='text-align:center;'></td>";                            
                            echo "</tr>";
                            $our_total += ($row[$c][5] * $row[$c][7]);
                            $total_qty += $row[$c][5];
                        }                    
                    ?>
                </tbody><tfoot><tr style="border-top: solid 2px #ddd;"><td class='number'><strong>Totals:</strong></td><td></td><td></td><td></td><td></td><td class='number'><?php echo number_format($total_qty, 0,'.',','); ?></td><td class='number'><span id='span_req_total'>$<?php echo number_format($total,2,'.',',');?></span></td><td class='number <?php if($total != $our_total) echo "text-danger"; else echo "text-success"; ?>'><span id='span_our_total'>$<?php echo number_format($our_total,2,'.',',');?></span></td><td></td></tr></tfoot>
                
            </table>
        </div>
    </div>
    <!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-sm">
    <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Requested Ship Date</h4>
            </div>
            <div class="modal-body">
                <p>Select new requested ship date for all the lines.</p>
                <div id = "sandbox-container">
                    <label for="shipdate_ctrl">Ship Date</label>
                    <div class="input-group date">
                        <input type="text" class="form-control" id="shipdate_ctrl"><span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>