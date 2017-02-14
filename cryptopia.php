<?php

class cryptopia {
    protected $privateKey;
    protected $publicKey;

    public function __construct($api_key, $api_secret, $notes='') {
        $this->publicKey = $api_key;
        $this->privateKey = $api_secret;
    }

    private function apiCall($method, array $req = array()) {
        $public_set = array( "GetCurrencies", "GetTradePairs", "GetMarkets", "GetMarket", "GetMarketHistory", "GetMarketOrders" );
        $private_set = array( "GetBalance", "GetDepositAddress", "GetOpenOrders", "GetTradeHistory", "GetTransactions", "SubmitTrade", "CancelTrade", "SubmitTip" );
        static $ch = null;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Cryptopia.co.nz API PHP client; FreeBSD; PHP/'.phpversion().')');
        if ( in_array( $method ,$public_set ) ) {
            $url = "https://www.cryptopia.co.nz/api/" . $method;
            if ($req) { foreach ($req as $r ) { $url = $url . '/' . $r; } }
            curl_setopt($ch, CURLOPT_URL, $url );
        } elseif ( in_array( $method, $private_set ) ) {
            $url = "https://www.cryptopia.co.nz/api/" . $method;
            $nonce = explode(' ', microtime())[1];
            $post_data = json_encode( $req );
            $m = md5( $post_data, true );
            $requestContentBase64String = base64_encode( $m );
            $signature = $this->publicKey . "POST" . strtolower( urlencode( $url ) ) . $nonce . $requestContentBase64String;
            $hmacsignature = base64_encode( hash_hmac("sha256", $signature, base64_decode( $this->privateKey ), true ) );
            $header_value = "amx " . $this->publicKey . ":" . $hmacsignature . ":" . $nonce;
            $headers = array("Content-Type: application/json; charset=utf-8", "Authorization: $header_value");
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $url );
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode( $req ) );
        }
        // run the query
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE); // Do Not Cache
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        if ($res === false) throw new Exception('Could not get reply: '.curl_error($ch));
        return $res;
    }

    public function get_ticker () {
        $result = json_decode($this->apiCall("GetMarkets", array('Market'=>"")), true);
        $tickers = $result["Data"];
        $ticker = array ();
        foreach ($tickers as $tick) {
            $ex = explode ('/', $tick["Label"]);
            $key = $ex[1]."_".$ex[0];
            $ticker[$key] = array ();
            $ticker[$key]["last"] = $tick["LastPrice"];
            $ticker[$key]["lowestAsk"] = $tick["AskPrice"];
            $ticker[$key]["highestBid"] = $tick["BidPrice"];
            $ticker[$key]["percentChange"] = $tick["Change"];
            $ticker[$key]["baseVolume"] = $tick["LastVolume"];
            $ticker[$key]["quoteVolume"] = $tick["Volume"];
            $ticker[$key]["isFrozen"] = 0;
            $ticker[$key]["high24hr"] = 0;
            $ticker[$key]["low24hr"] = 0;
        }
        return $ticker;
    }    

    public static function show_setup() {
        printf ("Please fill out the following information to setup your Cryptopia account configuration. ");
        printf ("You will need a full access API key to continue.<br><br>\r\n");
        printf ("<table>\r\n");
        printf ("<tr>\r\n");
        printf ("<td>Name:</td>\r\n");
        printf ("<td><input type=\"text\" name=\"account_name\" value=\"Cryptopia\"></td>\r\n");
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
        $result = json_decode($this->apiCall("GetBalance", array('Currency'=>"")), true);
        $balances = array ();
        foreach ($result["Data"] as $bal) {
            if ($bal["Total"] > 0) {
                $balance = array ();
                $currency = $bal["Symbol"];
                $available = $bal["Available"];
                $onorder = $bal["Unconfirmed"] + $bal["HeldForTrades"] + $bal["PendingWithdraw"];
                $balance["name"] = "Cryptopia - ".$currency;
                $balance["currency"] = $currency;
                $balance["available"] = $available;
                $balance["onorder"] = $onorder;
                $balances[] = $balance;
            }
        }
        return $balances;
    }

    public static function get_url ($name) {
        if (strstr($name, "Cryptopia")) {
            return ("https://www.cryptopia.co.nz/");
        }
        return ("");
    }
}

?>