<?php
class dashrpc {

    protected $username;
    protected $password;
    protected $url;
    protected $currency;

    public function __construct($api_key, $api_secret, $notes) {
        $this->username = $api_key;
        $this->password = $api_secret;
        $this->url = $notes;
        $this->currency = "DASH";
    }

    protected function query($method, $params = false) {
        $json_string =
            "{\"jsonrpc\":\"1.0\",\"id\":\"curltext\",\"method\":\"".$method."\",\"params\":[";
        if (is_array($params)) {
            foreach ($params as $param) {
                if (is_integer($param)) {
                    $json_string.=$param;
                } else {
                    if ((strcmp($param, "true")==0) || (strcmp($param,"false") == 0)) {
                        echo $param;
                        /* Boolean so just use it */
                        $json_string.=$param;
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
        $balance["name"] = "Dashcoin RPC";
        $balance["currency"] = $this->currency;
        $balance["available"] = $this->query("getbalance")["result"];
        $balance["onorder"] = $this->query("getunconfirmedbalance")["result"];
        $balances[] = $balance;
        return $balances;
    }

    

    /* Show our setup table for the setup script */
    public static function show_setup() {
        printf ("Please fill out the following information to setup a local Dashcoin RPC Connection.");
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