<?php
function get_bpi () {
    $URL="https://api.coindesk.com/v1/bpi/currentprice/USD.json";
    $opts = array('http' =>
    array(
        'method' => 'GET',
        'timeout' => 10
    )
    );
    $context = stream_context_create($opts);
    $feed = file_get_contents($URL, false, $context);
    $json =json_decode($feed, true);
    return str_replace(',','',$json["bpi"]["USD"]["rate"]);
}

function get_bitrex_last ($market) {
    $URL="https://bittrex.com/api/v1.1/public/getticker?market=".$market;
    $opts = array('http' =>
    array(
        'method' => 'GET',
        'timeout' => 10
    )
    );
    $context = stream_context_create($opts);
    $feed = file_get_contents($URL, false, $context);
    $json =json_decode($feed, true);
    return $json["result"]["Last"];
}

function get_usd_amount ($sql, $currency, $amount, $bpi = 0.0) {
    /* If we didn't get our bitcoin price, then grab it */
    if ($bpi == 0.0) $bpi = get_bpi();
    $value = 0.0;
    if (!strcmp($currency,"USD")) {
        $value = $amount;
    } else if (!strcmp($currency,"BTC")) {
        $value = $amount * $bpi;
    } else {
        /* We need to grab market amount for this currency in BTC */
        $res = $sql->query ("SELECT last FROM market_data WHERE (".
                            "market_name='BTC_".$currency."') ORDER BY ".
                            "timestamp DESC LIMIT 1");
        if ($res->num_rows > 0) {
            $btc_val = $res->fetch_assoc()["last"];
            $value = $amount * $btc_val * $bpi;
        } else {
            /* Nothing from poloniex, so try bitrex public for market */
            $market = "BTC-".$currency;
            $last = get_bitrex_last ($market);
            $value = $amount * $last * $bpi;
        }
    }
    return $value;
}

function get_url_from_name ($available_accounts, $name) {
    $url = "";
    foreach ($available_accounts as $account) {
        /* Create our class for the account */
        $cmd = "require_once(__DIR__.\"/".$account["file"]."\");";
        eval ($cmd);
        $cmd = "    \$url = ".$account["class"]."::get_url(\"".$name."\");";
        eval ($cmd);
        if (strcmp($url,"")) return ($url);
    }
}

function create_miner_table ($sql) {
    $qry = "CREATE TABLE miners ".
        "(id INT NOT NULL AUTO_INCREMENT,".
        "name VARCHAR(20) NOT NULL,".
        "account VARCHAR(20) NOT NULL,".
        "hostname VARCHAR(40) NOT NULL,".
        "power_cost FLOAT NOT NULL,".
        "PRIMARY KEY (id)) ENGINE = InnoDB;";
    $sql->query ($qry);
    $qry = "CREATE TABLE power_draw ".
        "(miner_id INT NOT NULL AUTO_INCREMENT,".
        "timestamp INT NOT NULL,".
        "power_usage FLOAT NOT NULL,".
        "PRIMARY KEY (miner_id,timestamp)) ENGINE = InnoDB;";
    $sql->query ($qry);
}
?>