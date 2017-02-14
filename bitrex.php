<?php

class bitrex {
    protected $api_key;
    protected $api_secret;
    protected $public_url = "https://bittrex.com/api/v1.1/public";
    protected $account_url = "https://bittrex.com/api/v1.1/account";
    protected $market_url = "https://bittrex.com/api/v1.1/market";

    public function __construct($api_key, $api_secret, $notes='') {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }

    private function query ($request, array $data = array()) {        
        $nonce = time();
        $uri=$this->public_url."/".$request;
        $uri.="?apikey=".$this->api_key;
        $uri.="&nonce=".$nonce;
        foreach (array_keys($data) as $key) {
            $uri.="&".$key."=".$data[$key];
        }
        $sign=hash_hmac("sha512",$uri,$this->api_secret);
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('apisign:'.$sign));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 
        'Mozilla/4.0 (compatible; Poloniex PHP bot; '.php_uname('a').'; PHP/'.phpversion().')');
        $execResult = curl_exec($ch);
        $obj = json_decode($execResult, true);
        return $obj;
    }

    private function query_account ($request, array $data = array()) {        
        $nonce = time();
        $uri=$this->account_url."/".$request;
        $uri.="?apikey=".$this->api_key;
        $uri.="&nonce=".$nonce;
        foreach (array_keys($data) as $key) {
            $uri.="&".$key."=".$data[$key];
        }
        $sign=hash_hmac("sha512",$uri,$this->api_secret);
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('apisign:'.$sign));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 
        'Mozilla/4.0 (compatible; Poloniex PHP bot; '.php_uname('a').'; PHP/'.phpversion().')');
        $execResult = curl_exec($ch);
        $obj = json_decode($execResult, true);
        return $obj;
    }

    private function query_market ($request, array $data = array()) {        
        $nonce = time();
        $uri=$this->market_url."/".$request;
        $uri.="?apikey=".$this->api_key;
        $uri.="&nonce=".$nonce;
        foreach (array_keys($data) as $key) {
            $uri.="&".$key."=".$data[$key];
        }
        $sign=hash_hmac("sha512",$uri,$this->api_secret);
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('apisign:'.$sign));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 
        'Mozilla/4.0 (compatible; Poloniex PHP bot; '.php_uname('a').'; PHP/'.phpversion().')');
        $execResult = curl_exec($ch);
        $obj = json_decode($execResult, true);
        return $obj;
    }

    public function get_ticker () {
        return array();
    }

    public function get_ticker_old($market_name) {
        return $this->query("getticker",
            array(
                'market' => $market_name
            )
        );
    }

        public static function show_setup() {
            printf ("Please fill out the following information to setup your Poloniex account configuration. ");
            printf ("You will need a full access API key to continue.<br><br>\r\n");
            printf ("<table>\r\n");
            printf ("<tr>\r\n");
            printf ("<td>Name:</td>\r\n");
            printf ("<td><input type=\"text\" name=\"account_name\" value=\"Bittrex\"></td>\r\n");
            printf ("</tr>\r\n");
            printf ("<tr>\r\n");
            printf ("<td>API key:</td>\r\n");
            printf ("<td><input type=\"text\" name=\"api_key\"></td>\r\n");
            printf ("</tr>\r\n");
            printf ("<tr>\r\n"); 
            printf ("<td>API secret:</td>\r\n");
            printf ("<td><input type=\"text\" name=\"api_secret\"></td>\r\n");
            printf ("</tr>\r\n");
            printf ("</table>\r\n");
        }

        public function getOpenOrders () {
            return $this->query_market ("getopenorders")["result"];
        }

        public function getBalances () {
            $balances = array ();
            $orders = $this->getOpenOrders ();
            $data = $this->query_account ("getbalances");
            foreach ($data["result"] as $bal) {
                $balance = array ();
                $currency = $bal["Currency"];
                $onorder = $bal["Pending"];
                /* Check to see if we have an order out for this */
                foreach ($orders as $order) {
                    /* Skip any that are finished */
                    if ($order["QuantityRemaining"] == 0) continue;
                    /* Skip if not for our currency */
                    if (strcmp($order["Exchange"], "BTC-".$currency)) continue;
                    /* Update the order amount */
                    $onorder += $order["QuantityRemaining"];
                }
                $balance["name"] = "Bittrex - ".$currency;
                $balance["currency"] = $currency;
                $balance["available"] = $bal["Available"];
                $balance["onorder"] = $onorder;
                $balances[] = $balance;
            }
            return $balances;
        }
    public static function get_url ($name) {
        if (strstr($name, "Bittrex")) {
            return ("https://bittrex.com/");
        }
        return ("");
    }

}
?>