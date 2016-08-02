<?php
require_once 'login.php';

$id = 0;
$ship_date = "";
$unit_price = 0;
if(isset($_POST['id']))
{
    $id = $_POST['id'];
    //echo $id;
}
if(isset($_POST['ship_date']))
{    
    $d = $_POST['ship_date'];    
    $ship_date= date_create_from_format("mdy",$d);
    //echo date_format($ship_date,'Y-m-d');
}
if(isset($_POST['unit_price']))
{    
    $unit_price = $_POST['unit_price'];            
}
if($id!=0 && $ship_date!="" && $unit_price>=0)
{
    $db_server = mysqli_connect($db_hostname, $db_username, $db_password);
    if(!$db_server)die ("Unable to connect to MySQL: ".mysqli_error($db_server));
    mysqli_select_db($db_server, $db_database) or die ("Unable to select database: " .mysqli_error($db_server));
    
    $query = "UPDATE po_lines set req_ship_date='".date_format($ship_date,'Y-m-d')."', our_unit_price = $unit_price WHERE id=".$id;
    //echo $query;
    
    $result = mysqli_query($db_server, $query);
    
    if(!$result) die ("Database access failed: ". mysqli_error($db_server));
    /*else
    {
        header('Location: index.php');
        die();
    }*/
}
mysqli_close($db_server);
?>