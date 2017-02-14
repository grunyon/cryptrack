<?php
session_start ();
require_once ("settings/accounts.php");
require_once ("functions.php");
if (isset($_POST["setup_step"])) $_GET["setup_step"]=$_POST["setup_step"];
if (isset($_GET)) {
    /* Create session variables from our information, except the step */
    foreach (array_keys($_GET) as $key) {
        if ($key != "setup_step") {
            $_SESSION[$key] = $_GET[$key];
        }
    }
}
if (isset($_POST)) {
    /* Create session variables from our information, except the step */
    foreach (array_keys($_POST) as $key) {
        if ($key != "setup_step") {
            $_SESSION[$key] = $_POST[$key];
        }
    }
}
function randomPassword() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 16; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

?>
<html>
<head>
<title>Cryptrack Setup</title>
<link rel="stylesheet" type="text/css"href="style.css">    
</head>
<body>
<div class="title">Cryptrack</div>
    <br>
<div class="setup">
<?php if (!isset($_GET["setup_step"])): ?>
Welcome to Cryptrack crypto-currency trading and mining tracking software. We need to do an initial setup and configuration of the software to continue.
<br><br>                                                                                             
<input type="button" value="Continue >>" onClick="document.location='setup.php?setup_step=2';">
<?php elseif ($_GET["setup_step"] == 2): ?>
<span class="header">MySQL Setup</span>
<br>
    We need to setup the connection to the MySQL database that will be used for storing all the information. Please complete the information below to create our initial tables and user for accessing MySQL.<br><br><span class="note"><b>NOTE:</b> A new user account will be created with access to the table using the root user id and the root information will not be stored.</span><br>
<br>
<form method="POST" action="setup.php">
    <input type="hidden" name="setup_step" value="3">
    <table>
    <tr>
    <td>MySQL Hostname/IP:</td>
    <td><input type="text" name="mysql_ip" value="localhost"></td>
    </tr><tr>
    <td>MySQL Root User:</td>
    <td><input type="text" name="mysql_root_user" value="root"></td>
    </tr><tr>
    <td>MySQL Root Password:</td>
    <td><input type="password" name="mysql_root_password"></td>
    </tr><tr>
    <td>MySQL Database:</td>
    <td><input type="text" name="mysql_database" value="cryptrack"></td>
    </tr>
    </table>
    <input type="submit" value="Continue >>">    
</form>
<?php elseif ($_GET["setup_step"] == 3): ?>
<span class="header">MySQL Setup</span>
<br>
    We are checking mysql connectivity, please wait...<br>
<?php
    $sql = new mysqli ($_SESSION["mysql_ip"], $_SESSION["mysql_root_user"], $_SESSION["mysql_root_password"]);
?>
<?php if ($sql->connect_error != ""): ?>
<br>
We were unable to connect to MySQL with the information provided. Please go back and correct the information and try again.
    <input type="button" value="<< Back" onClick="document.location='setup.php?setup_step=2';">
<?php else: ?>
<br>
MySQL connection was successful!
    <input type="button" value="Continue >>" onClick="document.location='setup.php?setup_step=4';">
<?php endif; ?>
<?php elseif ($_GET["setup_step"] == 4): ?>
Now you need to create an account to track. Please select the account to below to create your first account.<br>
    <br>
<form method="GET" action="setup.php">
<input type="hidden" name="setup_step" value="5">
    Account Type: <select name="account_type">
<?php
    foreach ($available_accounts as $account)  {
        printf ("<option value=\"%s\">%s</option>\r\n", $account["name"], $account["description"]);
    }
?>
    <!--
    <option value="Coinbase">Coinbase</option>
    <option value="Poloniex">Poloniex</option>
    <option value="Bitrex">Bitrex</option>
    <option value="Flypool">Flypool (Zcash)</option>
    <option value="Zpool">Zpool</option>
    <option value="MPH">Mining Pool Hub</option>
    -->
    </select>
<br><br>
<input type="submit" value="Continue >>">
</form>
<?php elseif ($_GET["setup_step"] == 5): ?>
<form method="POST" action="setup.php">
<input type="hidden" name="setup_step" value="6">
<?php
/* Get our class for the selected account and show it's setup information */
foreach ($available_accounts as $account) {
    if ($_SESSION["account_type"] == $account["name"]) {
        /* We found our account, so show it's setup page */
        eval ("require_once(\"".$account["file"]."\");");
        eval ($account["class"]."::show_setup();");
    }
}
?>
<input type="submit" value="Continue >>">
</form>
<?php elseif ($_GET["setup_step"] == 6): ?>
Creating database and saving configuration...
<?php
$mysql_pass = randomPassword();
/* Save our configuration information */
$f = fopen (__DIR__."/settings/settings.php", "w");
fprintf ($f, "<?php\n");
fprintf ($f, "\$MYSQL_HOST=\"%s\";\n", $_SESSION["mysql_ip"]);
fprintf ($f, "\$MYSQL_USER=\"cryptrack\";\n");
fprintf ($f, "\$MYSQL_PASS=\"%s\";\n", $mysql_pass);
fprintf ($f, "\$MYSQL_DB=\"%s\";\n", $_SESSION["mysql_database"]);
fprintf ($f, "?>\n");
fclose ($f);
$sql = new mysqli ($_SESSION["mysql_ip"], $_SESSION["mysql_root_user"], $_SESSION["mysql_root_password"]);
/* Remove an old database */
$qry = "DROP DATABASE ".$_SESSION["mysql_database"];
$sql->query ($qry);
$qry = "CREATE DATABASE ".$_SESSION["mysql_database"];
$sql->query ($qry);
$sql->query ("USE ".$_SESSION["mysql_database"]);
$qry = "CREATE TABLE ".$_SESSION["mysql_database"].".accounts ".
    "(id INT NOT NULL AUTO_INCREMENT,".
    "type VARCHAR(20) NOT NULL,".
    "name VARCHAR(40) NOT NULL,".
    "api_key VARCHAR(80) NOT NULL,".
    "api_secret VARCHAR(200) NOT NULL, notes TEXT NOT NULL, ".
    "PRIMARY KEY  (id)) ENGINE = InnoDB;";
$sql->query ($qry);
$qry = "INSERT INTO accounts VALUES (0,'".$_SESSION["account_type"]."','".
    $_SESSION["account_name"]."','".$_SESSION["api_key"]."','".
    $_SESSION["api_secret"]."','".$_SESSION["notes"]."')";
$sql->query ($qry);
/* Create our user */
$qry = "GRANT ALL PRIVILEGES ON ".$_SESSION["mysql_database"].".* TO ".
    "cryptrack@'%' IDENTIFIED BY '".$mysql_pass."'";
$sql->query ($qry);
$qry = "GRANT ALL PRIVILEGES ON ".$_SESSION["mysql_database"].".* TO ".
    "cryptrack@'localhost' IDENTIFIED BY '".$mysql_pass."'";
$sql->query ($qry);
?>

<input type="button" value="Done" onClick="document.location='index.php';">
<?php elseif ($_GET["setup_step"] == 7): ?>
Please select the account type below to create your account.<br>
    <br>
<form method="GET" action="setup.php">
<input type="hidden" name="setup_step" value="8">
    Account Type: <select name="account_type">
<?php
    foreach ($available_accounts as $account)  {
        printf ("<option value=\"%s\">%s</option>\r\n", $account["name"], $account["description"]);
    }
?>
    </select>
<br><br>
<input type="submit" value="Continue >>">
</form>
<?php elseif ($_GET["setup_step"] == 8): ?>
<form method="POST" action="setup.php">
<input type="hidden" name="setup_step" value="9">
<?php
/* Get our class for the selected account and show it's setup information */
foreach ($available_accounts as $account) {
    if ($_SESSION["account_type"] == $account["name"]) {
        /* We found our account, so show it's setup page */
        eval ("require_once(\"".$account["file"]."\");");
        eval ($account["class"]."::show_setup();");
    }
}
?>
<input type="submit" value="Continue >>">
</form>
<?php elseif ($_GET["setup_step"] == 9): ?>
<?php
require_once ("settings/settings.php");
$sql = new mysqli ($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS, $MYSQL_DB);
$qry = "INSERT INTO accounts VALUES (0,'".$_SESSION["account_type"]."','".
    $_SESSION["account_name"]."','".$_SESSION["api_key"]."','".
    $_SESSION["api_secret"]."','".$_SESSION["notes"]."')";
$sql->query ($qry);
echo $sql->error;
?>
<script language="javascript">
    document.location='index.php';
</script>

<?php elseif ($_GET["setup_step"] == 10): ?>
Enter information below to add a miner to calculate power usage from for cost calculations.<br><br>
<span class="note"><b>NOTE:</b> You will need to establish ssh key authentication from the webserver account used for the grabber to the miner machine to enable data collection.</span>
<br>
    <span class="note"><b>NOTE:</b> Currently only NVIDIA power readings are supported.</span><br>
    <form method="post" action="setup.php">
    <input type="hidden" name="setup_step" value="11">
    <table>
    <tr>
    <td>Miner Name:</td>
    <td><input type="text" name="miner_name"></td>
    </tr>
    <tr>
    <td>Account Name:</td>
    <td><input type="text" name="account_name"></td>
    </tr>
    <tr>
    <td>Hostname/IP Address:</td>
    <td><input type="text" name="miner_hostname"></td>
    </tr>
    <tr>
    <td>Cost per KWh:</td>
    <td><input type="text" name="cost_per_kwh"></td>
    </tr>
    </table>
    <input type="submit" value="Continue >>">
    </form>

<?php elseif ($_GET["setup_step"] == 11): ?>

<?php
/* Check if the miner table exists and add it if not */
require_once ("settings/settings.php");
$sql = new mysqli ($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS, $MYSQL_DB);
$res = $sql->query ("SELECT * FROM miners");
if (!$res) {
    create_miner_table ($sql);
}
/* Now we are going to add the miner */
$qry = "INSERT INTO miners VALUES (0,'".$_SESSION["miner_name"]."','".
    $_SESSION["account_name"]."','".$_SESSION["miner_hostname"]."',".
    $_SESSION["cost_per_kwh"].")";
$sql->query($qry);
?>
<script language="javascript">
    document.location='index.php';
</script>
    
<?php endif; ?>
</div>

</body>