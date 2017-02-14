<?php
$now = time();
$day = $now - (60 * 60  * 24);
require_once ("functions.php");
/* Start the table */
/* Load in our settings */
require_once ("settings/settings.php");
require_once ("settings/accounts.php");
/* Connect to our SQL server */
$sql = new mysqli ($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS, $MYSQL_DB);

?>
<table class="bordered" width="100%">
<tr>
<th>Account</th>
<th>Currency</th>
<th>Available</th>
<th>On Order</th>
<th>Total</th>
<th>Value (USD)</th>
<th>Market</th>
</tr>
<?php
$total_usd = 0.0;
/* Get last bpi */
$res = $sql->query("SELECT last FROM market_data WHERE (exchange='BPI') ORDER BY timestamp DESC LIMIT 1");
if ($res) {
    $bpi = $res->fetch_assoc()["last"];
} else {
    $bpi = get_bpi();
}
/* Get last balance update time */
$res = $sql->query("SELECT timestamp FROM balance ORDER BY timestamp DESC LIMIT 1");
$last = $res->fetch_assoc()["timestamp"];
/* Get our accounts and balances */
$res = $sql->query("SELECT * FROM balance ".
                   "WHERE (timestamp=".$last.") ORDER BY value DESC");
$total_usd = 0;
while ($balance = $res->fetch_assoc()) {
        printf ("<tr>\r\n");
        $url = get_url_from_name ($available_accounts, $balance["name"]);
        if (strcmp($url,"")) {
            printf ("<td><a href=\"%s\" target=\"_blank\">%s</a></td>\r\n", $url, $balance["name"]);
        } else {
            printf ("<td>%s</td>\r\n", $balance["name"]);
        }
        printf ("<td align=\"center\">%s</td>\r\n", $balance["currency"]);
        printf ("<td align=\"right\">%4.8f</td>\r\n", $balance["available"]);
        printf ("<td align=\"right\">%4.8f</td>\r\n", $balance["onorder"]);
        $total = (double)$balance["available"] + (double)$balance["onorder"];
        printf ("<td align=\"right\">%4.8f</td>\r\n", $total);
        printf ("<td align=\"right\">$%4.2f</td>\r\n", $balance["value"]);
        printf ("<td>");
        /* Check to see if the value for this currency has gone up or down in the last
           24 hours */
        if ($balance["currency"] == "BTC") {
            /* We are working with the BPI from 24 hours ago */
            $bres = $sql->query ("SELECT last FROM market_data WHERE ".
                                 "(exchange='BPI' AND timestamp>=".$day.") ".
                                 "ORDER BY timestamp ASC LIMIT 1");
            echo $sql->error;
            $bdata = $bres->fetch_array();
            $mdiff = $bpi - $bdata["last"];
            $pdiff = $mdiff / $bdata["last"] * 100;
            if ($mdiff >= 0) {
                printf ("<img src=\"images/up_arrow.png\" height=\"15\">+");
            } else {
                printf ("<img src=\"images/down_arrow.png\" height=\"15\">-");
            }
            printf ("%4.2f%%", $pdiff);
        } else {
            /* If this is a market account, then check the value on this market, 
               otherwise, just get the averge for it from all markets */
            $ares = $sql->query ("SELECT * FROM accounts WHERE ".
                                 "(id=".$balance["account_id"].")");
            $acct = $ares->fetch_array();

            foreach ($available_accounts as $aaccount) {                
                if (strcmp($acct["type"], $aaccount["name"])) continue;
                $account = $aaccount;
            }
            if ($aaccount["type"] == "Exchange") {
                /* We have an exchange account, so we should use it for our value */
                $qry = "SELECT last FROM market_data WHERE ".
                    "(exchange='".$acct["type"]."' AND timestamp>=".$day.
                    " AND market_name='BTC_".$balance["currency"]."') ".
                    "ORDER BY timestamp DESC LIMIT 1";
                $fres = $sql->query ($qry);
                $first = $fres->fetch_array();
                $qry = "SELECT last FROM market_data WHERE ".
                    "(exchange='".$acct["type"]."' AND timestamp>=".$day.
                    " AND market_name='BTC_".$balance["currency"]."') ".
                    "ORDER BY timestamp ASC LIMIT 1";
                $lres = $sql->query ($qry);
                $last = $lres->fetch_array();
                $mdiff = $first["last"] - $last["last"];
                $pdiff = $mdiff / $last["last"] * 100;
                if ($mdiff >= 0) {
                    printf ("<img src=\"images/up_arrow.png\" height=\"15\">+");
                } else {
                    printf ("<img src=\"images/down_arrow.png\" height=\"15\">-");
                }
                printf ("%4.2f%%", $pdiff);
             }
            if ($aaccount["type"] == "Pool") {
                /* We have an exchange account, so we should use it for our value */
                $qry = "SELECT AVG(last) as last FROM market_data WHERE ".
                    "(timestamp>=".$day." AND market_name='BTC_".$balance["currency"]."') ".
                    "GROUP BY timestamp ORDER BY timestamp DESC LIMIT 1";
                $fres = $sql->query ($qry);
                echo $sql->error;
                $first = $fres->fetch_array();
                $qry = "SELECT AVG(last) as last FROM market_data WHERE ".
                    "(timestamp>=".$day." AND market_name='BTC_".$balance["currency"]."') ".
                    "GROUP BY timestamp ORDER BY timestamp ASC LIMIT 1";
                $lres = $sql->query ($qry);
                $last = $lres->fetch_array();
                $mdiff = $first["last"] - $last["last"];
                $pdiff = $mdiff / $last["last"] * 100;
                if ($mdiff >= 0) {
                    printf ("<img src=\"images/up_arrow.png\" height=\"15\">+");
                } else {
                    printf ("<img src=\"images/down_arrow.png\" height=\"15\">-");
                }
                printf ("%4.2f%%", $pdiff);
            }
            /* Found our account information and class, so load it */
            $cmd = "require_once(__DIR__.\"/".$aaccount["file"]."\");";
            eval ($cmd);
            $cmd = "\$class = new ".$aaccount["class"]."('".
                $acct["api_key"]."','".$acct["api_secret"]."','".$acct["notes"]."');";
            eval ($cmd);
            
        }
        printf ("</td>\r\n");
        printf ("</tr>\r\n");
        $total_usd += $balance["value"];
}
printf ("<tr>\r\n");
printf ("<th class=\"poolhead\" colspan=\"5\">Total</td>\r\n");
printf ("<td align=\"right\"><b><big>$%4.2f</big></b></td>\r\n", $total_usd);
printf ("</tr>\r\n");
?>
</table>
<?php printf ("<small><small>Last Updated on %s</small></small>\r\n", strftime("%c"));?>
