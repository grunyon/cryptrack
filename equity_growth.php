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
$res = $sql->query ("SELECT SUM(value) AS value FROM balance ".
" GROUP BY timestamp ORDER BY timestamp ASC LIMIT 1");
$earliest_equity = $res->fetch_assoc()["value"];
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
</table>