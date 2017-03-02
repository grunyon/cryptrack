<?php

class flypool {

    protected $wallet;

    public function __construct($wallet, $na1, $na2) {
        $this->wallet = $wallet;
    }

    public function getBalances() {
        $balances = array ();
        $bal = $this->get_zcash_flypool($this->wallet);
        if (!is_array($bal)) return $balances;
        if ($bal["unpaid"] > 0) {
            $balance = array ();
            $balance["name"] = "Flypool (Zcash)";
            $balance["currency"] = "ZEC";
            $balance["available"] = $bal["unpaid"]/100000000.0;
            $balance["onorder"] = 0.0;
            $balances[] = $balance;
        }
        return $balances;
    }

    public function getLastUpdates ($sql, $start, $end) {
        $update = $this->get_zcash_flypool($this->wallet);
        $total = 0;
        foreach ($update["rounds"] as $round) {
            
        }
    }

    public function updateMiningData ($sql, $now) {
        /* Check to see if we have a table to save our deltas */
        $res = $sql->query ("SELECT * FROM flypool_delta");
        if (!$res) {
            /* Create our delta data storage table */
            $qry = "CREATE TABLE flypool_delta ".
                "(timestamp INT NOT NULL,".
                "zec FLOAT NOT NULL,".
                "PRIMARY KEY (timestamp)) ENGINE = InnoDb;";
            $sql->query ($qry);
            echo $sql->error;
        }
        /* Get our previous balance */
        $res = $sql->query ("SELECT available FROM balance WHERE ".
            "(name='Flypool (Zcash)' AND timestamp<".$now.") ORDER BY timestamp DESC LIMIT 1");
        $lastbal = $res->fetch_assoc()["available"];
        $cur = $this->getBalances();
        /* There is no balance, so set it to zero */
        if (count($cur) == 0) {
            $cur[0]["available"] = 0.0;
            return;
        }
        $delta = $cur[0]["available"] - $lastbal;
        /* Only grab changes */
        if ($delta != 0.0) {
            /* If it's less, than we must have cashed out, so the -delta is our value */
            if ($delta < 0.0) {
                $delta = $cur[0]["available"];
                printf ("\nNegative delta: (%4.8f,$%4.8f) %4.8f\n",
                        $lastbal, $cur[0]["available"], $delta);
            }
            /*
            printf ("\n");
            printf ("timestamp: %d\n", $now);
            printf ("last: %4.8f\n", $lastbal);
            printf ("now : %4.8f\n", $cur[0]["available"]);
            printf ("flypool delta: %4.8f\n", $delta);
            */
            $qry = "INSERT INTO flypool_delta VALUES (".$now.",".$delta.")";
            $sql->query ($qry);
        }
    }

    /* Return the currency and the amount for the specified timeframe as an array of arrays */
    public function getMiningData ($sql, $start, $end) {
        /*
        $qry = "SELECT SUM(zec) as zec FROM flypool_delta WHERE (timestamp>$end AND timestamp <$start)";
        $res = $sql->query ($qry);
        $retval = array ();
        $retval["ZEC"] = $res->fetch_assoc()["zec"];
        */
        $res = $sql->query ("SELECT available FROM balance WHERE ".
            "(name='Flypool (Zcash)' AND timestamp>=".$end.
            " AND timestamp<=".$start.") ORDER BY timestamp ASC");
        $data = $res->fetch_assoc();
        $last=$data["available"];
        $total = 0.0;
        $retval = array ();
        while ($data = $res->fetch_assoc()) {            
            $cur = $data["available"];
            if ($cur == $last) continue;
            if ($cur > $last) $total += $cur - $last;
            if ($cur < $last) {
                $total += $cur;
            }
            $last = $cur;
        }
        $retval["ZEC"] = $total;
        return ($retval);
    }

    function get_zcash_flypool ($address) {
        $URL="http://zcash.flypool.org/api/miner_new/".$address;
        $opts = array('http' =>
        array(
            'method' => 'GET',
            'timeout' => 30
        )
        );
        $context = stream_context_create($opts);
        $feed = file_get_contents($URL, false, $context);
        $json =json_decode($feed, true);
        return $json;
    }

    /* Show our setup table for the setup script */
    public static function show_setup() {
        printf ("Please fill out the following information to setup your Flypool Zcash account configuration. ");
        printf ("<br><br>\r\n");
        printf ("<table>\r\n");
        printf ("<tr>\r\n");
        printf ("<td>Name:</td>\r\n");
        printf ("<td><input type=\"text\" name=\"account_name\" value=\"Mining Pool Hub\"></td>\r\n");
        printf ("</tr>\r\n");
        printf ("<tr>\r\n"); 
        printf ("<td>Wallet Address:</td>\r\n");
        printf ("<td><input type=\"text\" name=\"api_key\"></td>\r\n");
        printf ("</tr>\r\n");
        printf ("</table>\r\n");
    }

    public static function get_url ($name) {
        if (strstr($name, "Flypool")) {
            return ("https://zcash.flypool.org/");
        }
        return ("");
    }
}
?>