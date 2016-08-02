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
        function getFormattedDate(date) {
            var year = date.getFullYear();
            var month = (1 + date.getMonth()).toString();
            month = month.length > 1 ? month : '0' + month;
            var day = date.getDate().toString();
            day = day.length > 1 ? day : '0' + day;
            return month + '/' + day + '/' + year;
            }
        $(document).ready(function (){
            $('#sandbox-container .input-group.date').datepicker({
                todayBtn: "linked",
                setDate: "",
                clearBtn: true,                
                todayHighlight: true
            });
            $('#sandbox-container2 .input-group.date').datepicker({
                todayBtn: "linked",
                setDate: "",
                clearBtn: true,                
                todayHighlight: true
            });                       

            $("#orders_info").load("orders.php?nocache="+Math.random()*1000000,function(responseTxt,statusTxt,xhr){
                    if (statusTxt=="error") {
                        alert("Error: "+ xhr.status+": "+ xhr.statusText);
                    }
            });
            $('#btn-go').click(function(){
                var pn = $('#txt_pn').val();
                //alert(pn);
                $("#pn_info").load("lookup.php?pn=" + pn+"&nocache="+Math.random()*1000000,function(responseTxt,statusTxt,xhr){
                    if (statusTxt=="error") {
                        alert("Error: "+ xhr.status+": "+ xhr.statusText);
                    }
            });
            });
            $('#btn-proc-go').click(function(){
                var inicio = $("#fechainicio").val();
                var fin = $("#fechafin").val();
                $("#proc-pos").load("orders.php?processed=1&startdate=" + inicio +"&enddate=" + fin+"&nocache="+Math.random()*1000000,function(responseTxt,statusTxt,xhr){
                    if (statusTxt=="error") {
                        alert("Error: "+ xhr.status+": "+ xhr.statusText);
                    }
            });
            });
            $('#btn-proc-search').click(function(){
                var po = $('#txt-proc-po').val();
                $("#proc-pos").load("orders.php?processed=1&po_num=" + po +"&nocache="+Math.random()*1000000,function(responseTxt,statusTxt,xhr){
                    if (statusTxt=="error") {
                        alert("Error: "+ xhr.status+": "+ xhr.statusText);
                    }
            });
            });
            today = new Date();
            today.setDate(today.getDate()+1);
            $('#fechafin').val(getFormattedDate(today));
            next_month = new Date();
            next_month.setDate(next_month.getDate()-30);
            $('#fechainicio').val(getFormattedDate(next_month));
            $('#btn-proc-go').click();
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
        <div class="row">
            <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#home"><span class='glyphicon glyphicon-file'></span> Purchase Orders</a></li>                
                <li><a data-toggle="tab" href="#menu2"><span class='glyphicon glyphicon-upload'></span> Upload Purchase Order</a></li>
                <li><a data-toggle="tab" href="#menu3"><span class='glyphicon glyphicon-search'></span> Verify P/N</a></li>
                <li><a data-toggle="tab" href="#menu4"><span class='glyphicon glyphicon-ok'></span> Processed PO's</a></li>
            </ul>
            <div class="tab-content">
                <div id="home" class="tab-pane fade in active">
                    <h3>Unprocessed Purchase Orders</h3>
                    <div id="orders_info" class="roboto"></div>    
                </div>                
                <div id="menu2" class="tab-pane fade">
                    <h3>Upload Purchase Order</h3>
                    <form method='post' action='upload.php' enctype='multipart/form-data'>
                        Select file:   <!--<input class="btn btn-default" type='file' name='filename' size='10'>-->
                        <span class="btn btn-default btn-file">
                            Choose File<input type="file" name='filename' size='10'>
                        </span>  
                        <input type='submit' class="btn btn-default" value='Upload'>
                    </form>
                    <h3>Upload EDI Purchase Order</h3>
                    <form method='post' action='uploadSNAP.php' enctype='multipart/form-data'>
                        Select EDI Order file:   <!--<input class="btn btn-default" type='file' name='filename' size='10'>-->
                        <span class="btn btn-default btn-file">
                            Choose File<input type="file" name='filename' size='10'>
                        </span>  
                        <input type='submit' class="btn btn-default" value='Upload'>
                    </form>
                </div>
                <div id="menu3" class="tab-pane fade">
                    <h3><span class='glyphicon glyphicon-search'></span> Verify Item</h3>
                    <div class="well well-sm">
                        Part Number:  <input type="text" id="txt_pn" placeholder="MC5EXX-YY">  <button class="btn btn-sm btn-primary" id='btn-go'>Go</button>
                        <br>
                        <br>
                        <div id='pn_info'></div>
                    </div>
                </div>
                <div id="menu4" class="tab-pane fade">
                    <h3>Processed PO's</h3>
                    <div class="form-horizontal">                        
                        <div class="form-group">
                            <label for="txt-po" class="col-sm-1 control-label">PO# </label>
                            <div class="col-sm-2">
                                <input  type="text" id="txt-proc-po" class="form-control" size=6>
                            </div>
                            <div class="col-sm-1">
                                <button id="btn-proc-search" class="btn btn-primary">Search</button>
                            </div>
                            <label for="fechainicio" class="col-sm-1 control-label">Start Date</label>
                            <div class="col-sm-2" id="sandbox-container">                                
                                <div class="input-group date">
                                    <input type="text" class="form-control" id="fechainicio"><span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
                                </div>
                            </div>
                            <label for="fechafin" class="col-sm-1 control-label">End Date</label>
                            <div class="col-sm-2" id="sandbox-container2">                                
                                <div class="input-group date">
                                    <input type="text" class="form-control" id="fechafin"><span class="input-group-addon"><i class="glyphicon glyphicon-th"></i></span>
                                </div>
                            </div>
                            <div class="col-sm-1">
                                <button class="btn btn-primary" id="btn-proc-go">Go</button>
                            </div>
                        </div>                        
                    </div>
                    <div class="row">
                        <div class="col-sm-12" id="proc-pos">
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
                    
        </div>
    </div>
</body>
</html>