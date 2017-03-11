<?php
require_once ("header.php");
?>
<div class="title">
Cryptrack
</div>
<br>
<div class="growth" id="equity_div">
<?php require_once ("equity_growth.php"); ?>
</div>
<br>
<center>
<table>
<tr>
<td valign="top">
<div id="account_balances" class="rbordered">
<span class="title">Account Balances</span>
<br>
<div id="balance_div">
<?php require_once ("balancetable.php"); ?>
</div>
<br>
<input class="topright" type="button" value="Add Account" onClick="document.location='setup.php?setup_step=7';">
</div>
<br>

<div id="mining_info" class="rbordered">
<span class="title">Mining Information</span>
<input type="button" value="Add Miner" class="topright" onClick="document.location='setup.php?setup_step=10';">
<br>
<div id="miner_div">
<?php require_once ("minertable.php"); ?>
</div>    
<br>
</div>

</td>
<td valign="top">
<div class="graph">
<select id="graph_sel" onchange="changeGraph()">
<option>Last 24 Hours</option>
<option>Last 7 Days</option>
<option>Last 30 Days</option>
</select>
<div id="value_24" class="ggraph"></div>
<div id="value_7day" class="ggraph" style="display: none;"></div>
<div id="value_30day" class="ggraph" style="display: none;"></div>
<small><small><span id="graph_time"></span></small></small>
</div>
</td>
</tr>
</table>
</center>

<script type="text/javascript">
google.charts.load('current', {packages: ['corechart', 'line']});
google.charts.setOnLoadCallback(draw24value);
google.charts.setOnLoadCallback(draw7dayvalue);
google.charts.setOnLoadCallback(draw30dayvalue);

var mouseX = 0;
var mouseY = 0;

function getMouseXY (e) {
    mouseX = e.pageX;
    mouseY = e.pageY;
}

document.onMouseMove = getMouseXY;

function showmarketinfo(id) {
    var obj = document.getElementById(id);
    obj.position = "absolute";
    obj.style.display = "block";
    obj.style.top = mouseY;
    obj.style.left = mouseX;
}

function hidemarketinfo(id) { 
    document.getElementById(id).style = "display: none";   
}

function loadMinerInformation() {
    $.ajax({
      url: "minertable.php",
      success: function (data, status, jqxhr) {
            document.getElementById("miner_div").innerHTML=data;
      }
    });
}

function loadBalanceInformation() {
    $.ajax({
      url: "balancetable.php",
      success: function (data, status, jqxhr) {
            document.getElementById("balance_div").innerHTML=data;
      }
    });
}

function loadGrowth() {
    $.ajax({
      url: "equity_growth.php",
      success: function (data, status, jqxhr) {
            document.getElementById("equity_div").innerHTML=data;
      }
    });
}

setInterval(loadBalanceInformation, 120000);
setInterval(loadMinerInformation, 120000);
setInterval(loadGrowth, 120000);

function draw24value() {

    var options = {
      title: 'Value Last 24 Hours',
      hAxis: {
          title: 'Time'
        },
      series: {
          0: {targetAxisIndex: 0},
          1: {targetAxisIndex: 1}
      },
      vAxes: {
          0: {title: 'Value (USD)'},
          1: {title: 'Bitcoin Price Index (USD)'}
      },
      curveType: 'function',
    }

    var jsonData = $.ajax({
      url: "get_24_hour_value.php",
            dataType: "json",
            async: false
    }).responseText;

    var data = new google.visualization.DataTable(jsonData);


    if (document.getElementById('value_24').style.display == 'none') return;
    var chart = new google.visualization.LineChart(document.getElementById('value_24'));
    chart.draw(data, options);
    d = new Date();
    document.getElementById('graph_time').innerHTML='Last updated on ' + d.toDateString() + ' ' + d.toLocaleTimeString();
}

setInterval(draw24value, 120000);

function draw7dayvalue() {

    var options = {
      title: 'Value Last 7 Days',
      hAxis: {
          title: 'Time'
        },
      series: {
          0: {targetAxisIndex: 0},
          1: {targetAxisIndex: 1}
      },
      vAxes: {
          0: {title: 'Value (USD)'},
          1: {title: 'Bitcoin Price Index (USD)'}
      },
      curveType: 'function',
    }

    var jsonData = $.ajax({
      url: "get_7_day_value.php",
            dataType: "json",
            async: false
    }).responseText;

    var data = new google.visualization.DataTable(jsonData);


    if (document.getElementById('value_7day').style.display == 'none') return;
    var chart = new google.visualization.LineChart(document.getElementById('value_7day'));
    chart.draw(data, options);
    d = new Date();
    document.getElementById('graph_time').innerHTML='Last updated on ' + d.toDateString() + ' ' + d.toLocaleTimeString();
}

setInterval(draw7dayvalue, 120000);
document.getElementById('value_7day').style.display = 'none';

function draw30dayvalue() {

    var options = {
      title: 'Value Last 30 Days',
      hAxis: {
          title: 'Time'
        },
      series: {
          0: {targetAxisIndex: 0},
          1: {targetAxisIndex: 1}
      },
      vAxes: {
          0: {title: 'Value (USD)'},
          1: {title: 'Bitcoin Price Index (USD)'}
      },
      curveType: 'function',
    }

    var jsonData = $.ajax({
      url: "get_30_day_value.php",
            dataType: "json",
            async: false
    }).responseText;

    var data = new google.visualization.DataTable(jsonData);


    if (document.getElementById('value_30day').style.display == 'none') return;
    var chart = new google.visualization.LineChart(document.getElementById('value_30day'));
    chart.draw(data, options);
    d = new Date();
    document.getElementById('graph_time').innerHTML='Last updated on ' + d.toDateString() + ' ' + d.toLocaleTimeString();
}

setInterval(draw30dayvalue, 120000);
document.getElementById('value_30day').style.display = 'none';

function changeGraph () {
    var obj=document.getElementById('graph_sel');
    var index=obj.selectedIndex;
    switch (index) {
    case 0:
        document.getElementById('value_30day').style.display='none';
        document.getElementById('value_7day').style.display='none';
        document.getElementById('value_24').style.display='block';
        draw24value();
        break;
    case 1:
        document.getElementById('value_24').style.display='none';
        document.getElementById('value_30day').style.display='none';
        document.getElementById('value_7day').style.display='block';
        draw7dayvalue();
        break;
    case 2:
        document.getElementById('value_24').style.display='none';
        document.getElementById('value_7day').style.display='none';
        document.getElementById('value_30day').style.display='block';
        draw30dayvalue();
    }
}

</script>

<?php
require_once ("footer.php");
?>