<?php

class zpool {
    protected $wallet_address;
    protected $wallet_url = "http://www.zpool.ca/api/walletEx";

    public function __construct($wallet_address, $na1, $na2) {
        $this->wallet_address = $wallet_address;
    }

    private function query($extra) {
        static $ch = null;
        if (is_null($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT,
                        'Mozilla/4.0 (compatible; zpool PHP bot; '.php_uname('a').'; PHP/'.phpversion().')'
            );
        }
        curl_setopt($ch, CURLOPT_URL, $this->trading_url . "?" . $extra);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $res = curl_exec($ch);
        if ($res == false) throw new Exception('Curl error: '.curl_error($ch));
    }

    protected function retrieveJSON($URL) {
        $opts = array('http' =>
            array(
                'method' => 'GET',
                'timeout' => 5
            )
        );
        $context = stream_context_create($opts);
        $feed = file_get_contents($URL, false, $context);
        if (!$feed) {
            echo "\nErroring getting file contents: $URL\n";
//            sleep (1);
//            return $this->retrieveJSON($URL);
        }
        $json =json_decode($feed, true);
        return $json;
    }

    public function get_wallet_info () {
        $url = $this->wallet_url."?address=".$this->wallet_address;
        $json = $this->retrieveJSON($url);
        
        return $json;
    }

    public function getBalances () {
        $data = $this->get_wallet_info ();
        $balances = array ();
        $balance = array ();
        $balance["name"] = "Zpool - ".$this->wallet_address;
        $balance["currency"] = "BTC";
        $balance["available"] = $data["balance"];
        $balance["onorder"] = $data["unsold"];
        $balances[] = $balance;
        return $balances;
    }

    public function updateMiningData ($sql, $now) {
        /* Check to see if we have a table */
        $res = $sql->query ("SELECT * FROM zpool_data");
        if (!$res) {
            $qry = "CREATE TABLE zpool_data ".
                "(timestamp INT NOT NULL,".
                "wallet VARCHAR(60) NOT NULL,".
                "unsold FLOAT NOT NULL,".
                "balance FLOAT NOT NULL,".
                "unpaid FLOAT NOT NULL,".
                "paid FLOAT NOT NULL,".
                "total FLOAT NOT NULL,".
                "PRIMARY KEY (timestamp)) ENGINE = InnoDb;";
            $sql->query ($qry);
        }
        $data = $this->get_wallet_info ();
        if (!is_array($data)) return;
        $qry = "INSERT INTO zpool_data VALUES (".$now.",'".
            $this->wallet_address."',".
            $data["unsold"].",".$data["balance"].",".
            $data["unpaid"].",".$data["paid"].",".
            $data["total"].")";
        $sql->query ($qry);
        echo $sql->error;
    }

    public function getMiningData ($sql, $start, $end) {
        $qry = "SELECT timestamp,available,onorder FROM balance WHERE (".
            "name='Zpool - ".$this->wallet_address."' AND timestamp>=".$end.
            " AND timestamp<=".$start.") ORDER BY timestamp ASC";
        $res = $sql->query ($qry);
        $amount = 0.0;        
        while ($data = $res->fetch_array()) {
            if (!isset($last)) {
                $last = $data;
                continue;
            }
            $delta = ($data["available"] + $data["onorder"]) -
                ($last["available"] + $last["onorder"]);
            $amount += $delta;
            $last = $data;
        }
        /* Need to check to see if there was a payout during this time period */
        $res = $sql->query ("SELECT MIN(paid) as min,MAX(paid) as max ".
        "FROM zpool_data WHERE (timestamp>=".$end.
        " AND timestamp<=".$start.")");
        $data = $res->fetch_array ();
        $payout = $data["max"] - $data["min"];
        if ($payout > 0.0) $amount += $payout;

        return array ("BTC" => $amount);
        
    }

    /* Show our setup table for the setup script */
    public static function show_setup() {
        printf ("Please fill out the following information to setup your zpool account configuration. ");
        printf ("<br><br>\r\n");
        printf ("<table>\r\n");
        printf ("<tr>\r\n");
        printf ("<td>Name:</td>\r\n");
        printf ("<td><input type=\"text\" name=\"account_name\" value=\"Zpool\"></td>\r\n");
        printf ("</tr>\r\n");
        printf ("<tr>\r\n"); 
        printf ("<td>Wallet Address:</td>\r\n");
        printf ("<td><input type=\"text\" name=\"api_key\"></td>\r\n");
        printf ("</tr>\r\n");
        printf ("</table>\r\n");
    }

    public static function get_url ($name) {
        if (strstr($name, "Zpool")) {
            return ("https://zpool.ca");
        }
        return "";
    }
}


?>