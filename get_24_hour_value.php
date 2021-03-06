<?php
header ("Content-Type: text/plain");
/* Load in our settings */
require_once ("settings/settings.php");
/* Connect to our SQL server */
$sql = new mysqli ($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS, $MYSQL_DB);

/* Setup our columns, etc */
printf ("{\n");
printf ("  \"cols\": [\n");
printf ("    {\"id\": \"a\", \"label\":\"Time\", \"type\":\"datetime\"},\n");
printf ("    {\"id\": \"b\", \"label\":\"Value\", \"type\":\"number\"},\n");
printf ("    {\"id\": \"c\", \"label\":\"BPI\", \"type\":\"number\"}\n");
printf ("  ],\n");
printf ("  \"rows\": [\n");
$timeend = time();
$timestart = $timeend - (60 * 60 * 24);
for ($time=$timestart; $time<$timeend; $time+=(60 * 30)) {
    $tend = $time + (60 * 30);
    $res = $sql->query ("SELECT COUNT(value) as count, AVG(value) as value FROM balance ".
    "WHERE (timestamp>".$time." AND timestamp<=".$tend.") GROUP BY name");    
    $timestr = strftime("%R", $time);
    $dater = getdate ($time);
    $dates = sprintf ("Date(%d, %d, %d, %d, %d)", $dater["year"], $dater["mon"] - 1,
    $dater["mday"], $dater["hours"], $dater["minutes"]);
    if ($res->num_rows == 0) {
        $total = "null";
    } else {
        $total = 0;
        while ($data = $res->fetch_assoc()) {
            $total += $data["value"];
        }
        $total = sprintf ("%4.2f", $total);
    }
    $res = $sql->query ("SELECT COUNT(last) as count, AVG(last) as value FROM market_data ".
    "WHERE (exchange='BPI' AND timestamp>".$time." AND timestamp<=".$tend.") GROUP BY exchange");    
    if ($res->num_rows == 0) {
        $bpi = "null";
    } else {
        $bpi = sprintf ("%4.2f", $res->fetch_assoc()["value"]);
    }
    printf ("    {\"c\": [ {\"v\": \"%s\"}, {\"v\": %s}, {\"v\": %s}]},\n", $dates, $total, $bpi);
}
printf ("\t]\n");
printf ("}\n");

?>