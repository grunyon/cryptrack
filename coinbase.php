<?php
require_once("coinbase/vendor/autoload.php");
use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;

class coinbase {

    protected $api_key;
    protected $api_secret;
    private $configuration;
    private $client;

    public function __construct($api_key, $api_secret, $notes = '') {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->configuration = Configuration::apiKey($api_key, $api_secret);
        $this->client = Client::create($this->configuration);
    }

    public function getBalances() {
        $balances = array ();
        foreach ($this->client->getAccounts() as $account) {
            $balance = array ();
            $balance["name"] = "Coinbase - ".$account->getName();
            /* For coinbase the currenct is the first 3 characters of the name */
            $balance["currency"] = substr($account->getName(), 0, 3);
            $balance["available"] = $account->getBalance()->getAmount();
            $balance["onorder"] = 0.0;
            $balances[] = $balance;
        }
        return $balances;
    }

    /* Show our setup table for the setup script */
    public static function show_setup() {
        printf ("Please fill out the following information to setup your Coinbase account configuration. ");
        printf ("You will need a full access API key to continue.<br><br>\r\n");
        printf ("<table>\r\n");
        printf ("<tr>\r\n");
        printf ("<td>Name:</td>\r\n");
        printf ("<td><input type=\"text\" name=\"account_name\" value=\"Coinbase\"></td>\r\n");
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

    public static function get_url($name) {
        if (strstr($name, "Coinbase"))
            return ("https://www.coinbase.com/");
        return ("");
    }
}
?>