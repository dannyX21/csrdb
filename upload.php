<?php
$name = $_FILES['filename']['name'];
$r = move_uploaded_file($_FILES['filename']['tmp_name'],"po_files/".$name);
if($r)
{
    exec('./process_po.py po_files/'.$name);
    header('Location: index.php');
    die();
}
?>