<?php
$now = time();
$day = $now - (60 * 60 * 24);
$week = $now - (7 * 60 * 60 * 24);
$month = $now - (30 * 60 * 60 * 24);
require_once ("functions.php");
/* Load in our settings */
require_once ("settings/settings.php");
require_once ("settings/accounts.php");
/* Connect to our SQL server */
$sql = new mysqli ($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS, $MYSQL_DB);
/* Get our current equity value */
$res = $sql->query ("SELECT SUM(value) AS value FROM balance ".
"GROUP BY timestamp ORDER BY timestamp DESC LIMIT 1");
$current_equity = $res->fetch_assoc()["value"];
/* Get our 1 day equity */
$res = $sql->query ("SELECT SUM(value) AS value FROM balance WHERE ".
"(timestamp>=".$day.") GROUP BY timestamp ORDER BY timestamp ASC LIMIT 1");
$day_equity = $res->fetch_assoc()["value"];/* Get our 1 day equity change */
/* Get our 1 week equity */
$res = $sql->query ("SELECT SUM(value) AS value FROM balance WHERE ".
"(timestamp>=".$week.") GROUP BY timestamp ORDER BY timestamp ASC LIMIT 1");
$week_equity = $res->fetch_assoc()["value"];
/* Get our 1 month equity */
$res = $sql->query ("SELECT SUM(value) AS value FROM balance WHERE ".
"(timestamp>=".$month.") GROUP BY timestamp ORDER BY timestamp ASC LIMIT 1");
$month_equity = $res->fetch_assoc()["value"];
/* Get our earliest equity value */
$res = $sql->query ("SELECT timestamp,SUM(value) AS value FROM balance ".
" GROUP BY timestamp ORDER BY timestamp ASC LIMIT 1");
$data = $res->fetch_assoc();
$earliest_equity = $data["value"];
$earliest_time = $data["timestamp"];
/* Get our average daily change */
$sum = 0.0;
$avgtime = mktime(localtime(time(),true)["tm_hour"],0,0);
for ($i = $avgtime, $c = 0; $i>$earliest_time; $i -= (60 * 60 * 24), $c++) {
    $etime = $i;
    $stime = $etime - (60 * 60 * 24) + 1;
    $qry = "SELECT MIN(timestamp) as start,MAX(timestamp) as end ".
        "FROM balance WHERE (timestamp>".$stime." AND timestamp<".$etime.")";    
    $res = $sql->query ($qry);
    $timed = $res->fetch_assoc();
    $startt = $timed["start"];
    $endt = $timed["end"];
    $qry = "SELECT SUM(value) AS value FROM balance WHERE (timestamp=".$startt.")";
    $res = $sql->query ($qry);
    $startv = $res->fetch_assoc()["value"];
    $qry = "SELECT SUM(value) AS value FROM balance WHERE (timestamp=".$endt.")";
    $res = $sql->query ($qry);
    $endv = $res->fetch_assoc()["value"];
    $sum+=($endv - $startv);
}
//printf ("%4.2f - %d<br>", $sum, $c);
if ($c > 0) $avg_daily = $sum/$c;
else $avg_daily = 0;
/* Get our average daily power cost */
/* Get our 24 hour average power usage and cost add it to the array */
$res = $sql->query ("SELECT miners.name AS name, MAX(power_cost) as power_cost, ".
    "AVG(power_usage) as avg_power ".
    "FROM power_draw,miners WHERE ".
    "(power_draw.miner_id=miners.id AND timestamp>".($now - 24 * 60 * 60).
    ") GROUP BY miners.name");
$total_cost = 0.0;
if ($total_cost > 0.0) {
    while ($power = $res->fetch_assoc()) {
        $total_cost += $power["avg_power"] * 24 / 1000.0 * $power["power_cost"];
    }
}
?>
<table width="100%">
<tr>
<td><b>Equity Change: </b></td>
<td>24 Hour Change: 
<?php
$change = $current_equity - $day_equity;
if ($change > 0) {
    printf ("<span class=\"bpositive\">+$%4.2f</span>", $change);
} else {
    printf ("<span class=\"bnegative\">-$%4.2f</span>", abs($change));
}
?>
</td>
<td>7 Day Change: 
<?php
$change = $current_equity - $week_equity;
if ($change > 0) {
    printf ("<span class=\"bpositive\">+$%4.2f</span>", $change);
} else {
    printf ("<span class=\"bnegative\">-$%4.2f</span>", abs($change));
}
?>
</td>
<td>30 Day Change: 
<?php
$change = $current_equity - $month_equity;
if ($change > 0) {
    printf ("<span class=\"bpositive\">+$%4.2f</span>", $change);
} else {
    printf ("<span class=\"bnegative\">-$%4.2f</span>", abs($change));
}
?>
</td>
<td>All Time Change: 
<?php
$change = $current_equity - $earliest_equity;
if ($change > 0) {
    printf ("<span class=\"bpositive\">+$%4.2f</span>", $change);
} else {
    printf ("<span class=\"bnegative\">-$%4.2f</span>", abs($change));
}
?>
</td>
</tr>
<tr>
<td &nbsp;</td>
<td>Average Daily Change: 
<?php
if ($avg_daily > 0) {
    printf ("<span class=\"bpositive\">+$%4.2f</span>", $avg_daily);
} else {
    printf ("<span class=\"bnegative\">-$%4.2f</span>", abs($avg_daily));
}
?>
</td>
<?php if ($INVESTMENT>0): ?>
<td colspan="3">Estimated ROI:
<?php
/* Get our current balance */
$qry = "SELECT SUM(value) as value FROM balance ".
    "GROUP BY timestamp ORDER BY timestamp DESC LIMIT 1";
$res = $sql->query($qry);
$total_balance = $res->fetch_assoc()["value"];
$owed = $INVESTMENT - $total_balance;
$totaldays = $owed/($avg_daily - $total_cost); 
$days = $totaldays;
$years = (int)($days/365);
$days = $days%365;
$months = (int)($days/30);
$days = $days%30;
if ((int)$years > 0)
    printf ("%d Year%s%s ", $years, $years>1 ? "s" : "",
            $months >0 ? "," : "");
if ((int)$months > 0)
    printf ("%d Month%s%s ", $months,
            $months>1 ? "s" : "", $days > 0 ? "," : "");
if ((int)$days > 0) printf ("%d Day%s", $days, $days>1 ? "s" : "");
?>
</td>
<?php endif; ?>
</tr>
</table>