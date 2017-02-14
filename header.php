<?php
/* If we don't have a settings file, then we need to run the wizard to create it */
if (!file_exists (__DIR__."/settings/settings.php")) {
    header('Location: setup.php');
}
/* Load in our settings */
require_once ("settings/settings.php");
/* Load in our account types */
require_once ("settings/accounts.php");
/* Load additional functions */
require_once ("functions.php");
/* Connect to our SQL server */
$sql = new mysqli ($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS, $MYSQL_DB);
/* For each of our accounts, load a new class */
$res = $sql->query ("SELECT * FROM accounts");
$accounts = array();
while ($acct = $res->fetch_assoc()) {
    /* Find the information for this account type from the available accounts */
    foreach ($available_accounts as $aaccount) {
        if (!strcmp($acct["type"],$aaccount["name"])) break;
    }
    /* Load in that class if not already */
    eval ("require_once(\"".$aaccount["file"]."\");");
    /* Create our class */
    $cmd = "\$acct[\"class\"]=new ".$aaccount["class"].
        "('".$acct["api_key"]."','".$acct["api_secret"]."','".$acct["notes"]."');";
    eval ($cmd);
    $accounts[] = $acct;
}
?>
<html>
<head>
<title>Cryptrack</title>
<script type="text/javascript" src="loader.js"></script>
<script type="text/javascript" src="jquery-3.1.1.min.js"></script>    
    
<link rel="stylesheet" type="text/css"href="style.css">    
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<!--<meta http-equiv="refresh" content="60" />-->
</head>
<body>