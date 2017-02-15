<?php
/* Mining Pool Hub Class */

class mph {
    protected $api_key;
    protected $user_id;
    /* The URLs we are currently supporting */
    private $urls = array ("BTC" => "bitcoin", "ZEC" => "zcash",
                         "FTC"=>"feathercoin", "XZC" => "zcoin");

    public function __construct($api_key, $user_id, $note = '') {
        $this->api_key = $api_key;
        $this->user_id = $user_id;
    }

    protected function query ($coin, $query) {
        $URL = "http://".$coin.".miningpoolhub.com/index.php?page=api".
            "&action=".$query."&api_key=".$this->api_key.
            "&id=".$this->user_id;
        $opts = array('http' =>
            array(
                'method' => 'GET',
                'timeout' => 10,
            )
        );
        $context = stream_context_create($opts);
        $feed = file_get_contents($URL, false, $context);
        $json = json_decode($feed, true);
        return ($json);
    }

    public function getBalances() {
        $balances = array ();        
        foreach (array_keys($this->urls) as $coin) {
            $bal = $this->query($this->urls[$coin], "getuserbalance");
            $available = $bal["getuserbalance"]["data"]["confirmed"];
            $onorder = $bal["getuserbalance"]["data"]["unconfirmed"];
            $total = $available + $onorder;
            if ($total == 0) continue;
            $balance = array ();
            $balance["name"] = "Mining Pool Hub - ".$this->urls[$coin];
            $balance["currency"] = $coin;
            $balance["available"] = $available;
            $balance["onorder"] = $onorder;
            $balances[] = $balance;
        }
        return $balances;
    }

    public function updateMiningData ($sql, $now) {
        /* Check to see if we have a table to save the transactions */
        $res = $sql->query ("SELECT * FROM mph_transactions");
        if (!$res) {
            /* Create our transaction table */
            $qry = "CREATE TABLE mph_transactions ".
                "(id INT NOT NULL,".
                "timestamp INT NOT NULL,".
                "currency VARCHAR(8) NOT NULL,".
                "type VARCHAR(15) NOT NULL,".
                "amount FLOAT NOT NULL,".
                "PRIMARY KEY (id,timestamp,currency)) ENGINE = InnoDb;";
            $sql->query ($qry);
        }
        foreach (array_keys($this->urls) as $key) {
            /* Skip bitcoin, not mining it */
            if (!strcmp($key, "BTC")) continue;
            $qry = $this->query($this->urls[$key], "getusertransactions");
            $transactions = $qry["getusertransactions"]["data"]["transactions"];
            foreach ($transactions as $transaction) {
                $timestamp = strtotime($transaction["timestamp"]." UTC");
                $qry = "INSERT INTO mph_transactions VALUES (".
                    $transaction["id"].",".
                    $timestamp.",'".
                    $key."','".
                    $transaction["type"]."',".
                    $transaction["amount"].")";
                $sql->query ($qry);
            }
        }        
    }

    public function getMiningData ($sql, $start, $end) {
        $qry = "SELECT currency,type,amount FROM mph_transactions ".
            "WHERE (timestamp>=".$end." AND timestamp<=".$start.")";
        $res = $sql->query ($qry);
        $retval = array ();
        while ($data = $res->fetch_assoc()) {
            switch ($data["type"]) {
//            case "Debit_MP":
            case "Debit_AE":
            case "Fee":
                @$retval[$data["currency"]] -= $data["amount"];
                break;
            case "Credit":
            case "Credit_AE":
                @$retval[$data["currency"]] += $data["amount"];
                break;
            }
        }
        return $retval;
    }

    /* Show our setup table for the setup script */
    public static function show_setup() {
        printf ("Please fill out the following information to setup your Mining Pool Hub account configuration. ");
        printf ("<br><br>\r\n");
        printf ("<table>\r\n");
        printf ("<tr>\r\n");
        printf ("<td>Name:</td>\r\n");
        printf ("<td><input type=\"text\" name=\"account_name\" value=\"Mining Pool Hub\"></td>\r\n");
        printf ("</tr>\r\n");
        printf ("<tr>\r\n"); 
        printf ("<td>User ID:</td>\r\n");
        printf ("<td><input type=\"text\" name=\"api_secret\"></td>\r\n");
        printf ("</tr>\r\n");
        printf ("<tr>\r\n");
        printf ("<td>API key:</td>\r\n");
        printf ("<td><input type=\"text\" name=\"api_key\"></td>\r\n");
        printf ("</tr>\r\n");
        printf ("</table>\r\n");
    }

    public static function get_url ($name) {
        if (strstr($name, "Mining Pool Hub")) {
            return ("https://miningpoolhub.com");
        }
        return ("");
    }
}

?>