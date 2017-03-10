<?php
$now = time();
require_once ("functions.php");
/* Start the table */
printf ("<table class=\"bordered\" width=\"100%%\">\r\n");
$numcols = 6;
/* Load in our settings */
require_once ("settings/settings.php");
require_once ("settings/accounts.php");
/* Connect to our SQL server */
$sql = new mysqli ($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS, $MYSQL_DB);
/* Check to see if we have miners to get power information from */
$res = $sql->query ("SELECT * FROM miners");
if (!$res) {
    /* We don't have a miners table, so create it */
    create_miner_table ($sql);
}
/* Get our last time update */
$res = $sql->query ("SELECT timestamp FROM power_draw ORDER BY timestamp DESC LIMIT 1");
if ($res->num_rows > 0) {
    $lasttime = $res->fetch_assoc()["timestamp"];
} else {
    $lasttime = 0;
}
if ($lasttime) {
    /* We have some power data, so we can add it to the table */
    printf ("<tr>\r\n");
    printf ("<th class=\"poolhead\" colspan=\"%d\">Power Usage</td>\r\n", $numcols);
    printf ("</tr>\r\n");
    /* Print our headers */
    printf ("<tr>\r\n");
    printf ("<th colspan=\"%d\">Miner</th>\r\n", $numcols - 3);
    printf ("<th>Current Usage</th>\r\n");
    printf ("<th>Avg Usage (24hr)</th>\r\n");
    printf ("<th>Cost/Day</th>\r\n");
    printf ("</tr>\r\n");
    $total_cost = 0.0;
    $power = array ();
    /* Get our last update data and add it to an array */
        $res = $sql->query ("SELECT * FROM power_draw,miners WHERE ".
        "(power_draw.miner_id=miners.id AND timestamp=".$lasttime.")");
        while ($data = $res->fetch_assoc()) {
            $power[$data["name"]]["last"] = $data["power_usage"];
            $power[$data["name"]]["cost"] = $data["power_cost"];
        }
        /* Get our 24 hour average power usage and cost add it to the array */
        $res = $sql->query ("SELECT miners.name AS name,MIN(timestamp) as min, ".
        "MAX(timestamp) as max, AVG(power_usage) as avg_power ".
        "FROM power_draw,miners WHERE ".
        "(power_draw.miner_id=miners.id AND timestamp>".($now - 24 * 60 * 60).
        ") GROUP BY miners.name");
        while ($data = $res->fetch_assoc()) {
            $power[$data["name"]]["avg"] = $data["avg_power"];
            $power[$data["name"]]["min"] = $data["min"];
            $power[$data["name"]]["max"] = $data["max"];        
        }
        foreach (array_keys ($power) as $key) {
            $timediff = ($power[$key]["max"] - $power[$key]["min"]);
            $hours = $timediff / 60.0 / 60.0;
            printf ("<tr>\r\n");
            printf ("<td colspan=\"%d\">%s</td>\r\n", $numcols - 3, $key);
            printf ("<td align=\"right\">%4.2f Watts</td>", $power[$key]["last"]);
            printf ("<td align=\"right\">%4.2f Watts</td>", $power[$key]["avg"]);
            $cost_per_day = $power[$key]["avg"] * $hours / 1000. * $power[$key]["cost"];
            printf ("<td align=\"right\"><span class=\"negative\">-$%4.2f</span></td>", $cost_per_day);
            printf ("</tr>\r\n");
            $total_cost += $cost_per_day;
        }
}
printf ("<tr>\r\n");
printf ("<td colspan=\"%d\"><b>Total Cost Per Day</b></td>\r\n", $numcols - 1);
printf ("<td align=\"right\"><b>");
if ($total_cost < 0) {
    printf ("<span class=\"positive\">+");
} else {
    printf ("<span class=\"negative\">-");
}
printf ("\$%4.2f</span></b></td>\r\n", abs($total_cost));
printf ("</tr>\r\n");
/* Check to see if we have any active miners from our accounts to add for positive income */
$res = $sql->query ("SELECT * FROM accounts");
$miners = array ();
$mining = array ();
while ($acct = $res->fetch_assoc()) {
    /* Get our account type information */
    foreach ($available_accounts as $aacct) {
        if (!strcmp($acct["type"], $aacct["name"])) break;
    }
    /* If we are not a pool account, then skip */
    if (strcmp($aacct["type"],"Pool")) continue;
    /* Load our class for the pool account */
    $cmd = "require_once(\"".$aacct["file"]."\");";
    eval ($cmd);
    $cmd = "\$class=new ".$aacct["class"]."('".$acct["api_key"]."','".
        $acct["api_secret"]."','".$acct["notes"]."');";
    eval ($cmd);
    $updates1hour = $class->getMiningData ($sql, $now, ($now - 60 * 60));
    $updates24hour = $class->getMiningData ($sql, $now, ($now - 60 * 60 * 24));
    /* Build our array of information */
    foreach (array_keys($updates24hour) as $key) {
        @$mining[$acct["name"]][$key]["1hour"] = $updates1hour[$key];
        @$mining[$acct["name"]][$key]["24hour"] = $updates24hour[$key];
        
    }
}
$total_income_per_day = 0.0;
$total_income_per_hour = 0.0;
if (count($mining)>0) {
    printf ("<tr>\r\n");
    printf ("<th class=\"poolhead\" colspan=\"%d\">Mining Income</td>\r\n", $numcols);
    printf ("</tr>\r\n");
    printf ("<tr>\r\n");
    printf ("<th>Pool</th>\r\n");
    printf ("<th>Currency</th>\r\n");
    printf ("<th>Amount Last Hour\r\n</th>\r\n");
    printf ("<th>USD/Hour\r\n</th>\r\n");
    printf ("<th>Amount Last Day\r\n</th>\r\n");
    printf ("<th>USD/Day\r\n</th>\r\n");
    printf ("</tr>\r\n");
    /* Fill out our table */
    foreach (array_keys($mining) as $name) {
        foreach (array_keys($mining[$name]) as $coin) {
            $usdpday = get_usd_amount($sql, $coin, $mining[$name][$coin]["24hour"]);
            if ($usdpday == 0) continue;
            printf ("<tr>\r\n");
            printf ("<td>%s</td>\r\n", $name);
            printf ("<td align=\"center\">%s</td>\r\n", $coin);
            printf ("<td align=\"right\">%4.8f</td>\r\n", $mining[$name][$coin]["1hour"]);
            $usdphour = get_usd_amount($sql, $coin, $mining[$name][$coin]["1hour"]); 
            printf ("<td align=\"right\">");
            if ($usdphour >= 0.0) {
                printf ("<span class=\"positive\">+$%6.4f</span>", $usdphour);
            } else {
                printf ("<span class=\"negative\">-$%6.4f</span>", abs($usdphour));
            }
            printf ("</td>\r\n");
            printf ("<td align=\"right\">%4.8f</td>\r\n", $mining[$name][$coin]["24hour"]);
            printf ("<td align=\"right\">");
            if ($usdpday >= 0.0) {
                printf ("<span class=\"positive\">+$%4.2f</span>", $usdpday);
            } else {
                printf ("<span class=\"negative\">-$%4.2f</span>", abs($usdpday));
            }
            printf ("</td>\r\n");
            printf ("</tr>\r\n");
            $total_income_per_day += $usdpday;
            $total_income_per_hour += $usdphour;
        }

    }
}
printf ("<tr>\r\n");
printf ("<td colspan=\"%d\"><b>Total Income Per Day</b></td>\r\n", $numcols - 1);
printf ("<td align=\"right\"><b>");
if ($total_income_per_day > 0) {
    printf ("<span class=\"positive\">+");
} else {
    printf ("<span class=\"negative\">-");
}
printf ("\$%4.2f</span></b></td>\r\n", abs($total_income_per_day));
printf ("</tr>\r\n");
printf ("<tr>\r\n");
printf ("<td colspan=\"%d\">&nbsp;</td>\r\n", $numcols);
printf ("</tr>\r\n");
printf ("<tr>\r\n");
printf ("<th class=\"poolhead\" colspan=\"%d\">Estimated Daily Profit</td>\r\n", $numcols - 1);
printf ("<td align=\"right\"><b><big>");
$daily_income = $total_income_per_day - $total_cost;
if ($daily_income >= 0) {
    printf ("<span class=\"positive\">+");
} else {
    printf ("<span class=\"negative\">-");
}
printf ("\$%4.2f", abs($daily_income));
printf ("</span></big></b></td>");
printf ("</tr>\r\n");
printf ("</table>\r\n");
printf ("<small><small>Last updated on %s</small></small>\r\n", strftime("%c"));
?>