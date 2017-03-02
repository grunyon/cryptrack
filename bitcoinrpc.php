<?php
class bitcoinrpc {

    protected $username;
    protected $password;
    protected $url;
    protected $currency;

    public function __construct($api_key, $api_secret, $notes) {
        $this->username = $api_key;
        $this->password = $api_secret;
        $this->url = $notes;
        $this->currency = "BTC";
    }

    protected function query($method, $params = false) {
        $json_string =
            "{\"jsonrpc\":\"1.0\",\"id\":\"curltext\",\"method\":\"".$method."\",\"params\":[";
        if (is_array($params)) {
            foreach ($params as $param) {
                if (is_integer($param) || is_float($param)) {
                    $json_string.=$param;
                } else {
                    if ((strcmp($param, "true")==0) || (strcmp($param,"false") == 0)) {
                        /* Boolean so just use it */
                        $json_string.=$param;
                    } else {
                        $json_string.="\"".$param."\"";
                    }
                }
                $json_string.=",";
            }
            $json_string=substr($json_string,0,-1);
        }
        $json_string.="]}";

        $ch = curl_init($this->url);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => "$this->username:$this->password",
            CURLOPT_HTTPHEADER => array("Content-type: application/json") ,
            CURLOPT_POSTFIELDS => $json_string
        );
        curl_setopt_array ($ch, $options);
        $result = curl_exec ($ch);

        return json_decode($result,true);
    }

    public function getBalances() {
        $balances = array ();
        /* Get our addresses */
        $balance = array();
        $balance["name"] = "Bitcoin RPC";
        $balance["currency"] = $this->currency;
        $balance["available"] = $this->query("getbalance")["result"];
        $balance["onorder"] = $this->query("getunconfirmedbalance")["result"];
        $balances[] = $balance;
        return $balances;
    }

    public function move($from, $to, $amount) {
        $res = $this->query("move", array ($from, $to, $amount));
    }

    public function getAddresses() {
        $addresses = array ();
        $res = $this->query ("listreceivedbyaddress", array (0, "true"));
        foreach ($res["result"] as $info) {
            $address = array ();
            $address["address"] = $info["address"];
            $address["account"] = $info["account"];
            if (!strcmp($address["account"],"")) $address["account"] = "default";
            $address["balance"] = $info["amount"];
            $addresses[] = $address;
        }
        return $addresses;
    }

    public function getAddressesByAccount($account) {
        if (!strcmp($account,"default")) $account="";
        $res = $this->query("getaddressesbyaccount", array($account));
        return $res["result"];
    }

    public function getAccounts() {
        $accounts = array ();
        $res = $this->query ("listaccounts");
        foreach (array_keys($res["result"]) as $account) {
            $acct = array ();
            if ($account == "default") continue;
            if ($account == "") $acct["account"] = "default";
            else $acct["account"] = $account;
            $acct["balance"] = $res["result"][$account];
            $accounts[] = $acct;
        }
        return $accounts;
    }

    private static function transCmp ($a, $b) {
        return ($a["time"] < $b["time"]) ? +1 : -1;
    }

    public function getTransactions() {
        /* First we have to get our accounts */
        $accounts = $this->query ("listaccounts");
        $transactions = array();
        /* Loop through our accounts and get the transactions */
        foreach (array_keys($accounts["result"]) as $acctname) {            
            $trans = $this->query ("listtransactions", array($acctname));
            foreach ($trans["result"] as $tran) {
                if (!strcmp($tran["category"],"move")) continue;
                $transactions[] = $tran;
            }
        }
        uasort ($transactions, array('bitcoinrpc','transCmp'));
        return $transactions;
    }
    
    /* Show our setup table for the setup script */
    public static function show_setup() {
        printf ("Please fill out the following information to setup a local Bitcoin RPC Connection.");
        printf ("<br><br>\r\n");
        printf ("<table>\r\n");
        printf ("<tr>\r\n");
        printf ("<td>Name:</td>\r\n");
        printf ("<td><input type=\"text\" name=\"account_name\" value=\"Bitcoin RPC\"></td>\r\n");
        printf ("</tr>\r\n");
        printf ("<tr>\r\n");
        printf ("<td>URL:</td>\r\n");
        printf ("<td><input type=\"text\" name=\"notes\"></td>\r\n");
        printf ("</tr>\r\n");
        printf ("<tr>\r\n"); 
        printf ("<td>Username:</td>\r\n");
        printf ("<td><input type=\"text\" name=\"api_key\"></td>\r\n");
        printf ("</tr>\r\n");
        printf ("<tr>\r\n"); 
        printf ("<td>Password:</td>\r\n");
        printf ("<td><input type=\"password\" name=\"api_secret\"></td>\r\n");
        printf ("</tr>\r\n");
        printf ("</table>\r\n");
    }

    public static function get_url($name) {
        return ("");
    }
}
?>