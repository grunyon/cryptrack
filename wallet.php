<?php
require_once ("header.php");
/* Get our account information from sql */
$res = $sql->query ("SELECT * FROM accounts WHERE (id=".$_GET["id"].")");
$account = $res->fetch_assoc();
$class = null;
/* Load our class for this account */
foreach ($available_accounts as $tacct) {
    if ($account["type"] == $tacct["name"]) {
        $cmd = "require_once(__DIR__.\"/".$tacct["file"]."\");";
        eval ($cmd);
        $cmd = "\$class = new ".$tacct["class"]."(\"".$account["api_key"]."\",\"".
            $account["api_secret"]."\",\"".$account["notes"]."\");";
        eval ($cmd);
        break;
    }
}
if ($class == null) {
    printf ("Unable to load class");
}
if (isset($_POST["fromacct"])) {
    if (!strcmp($_POST["fromacct"],"default")) $_POST["fromacct"]="";
    if (!strcmp($_POST["toacct"],"default")) $_POST["toacct"]="";
    $class->move($_POST["fromacct"], $_POST["toacct"], $_POST["amount"]);
    printf ("<script language=\"javascript\">\r\n");
    printf ("document.location='wallet.php?id=".$_GET["id"]."';\r\n");
    printf ("</script>\r\n");
}
$info = $class->getAccounts();
?>

<div class="title">
    Cryptrack - Wallet for <?php echo $account["name"];?>
</div>
<br>

    <form method="post" action="wallet.php?id=<?php echo $_GET["id"];?>">
<div class="rbordered">
    <table width="100%">
    <tr>
    <td><b>Move Funds</b></td>
    <td>From Account:
    <select name="fromacct">
    <?php
    foreach ($info as $account) {
        printf ("<option value=\"%s\">%s</option>", $account["account"], $account["account"]);
    }
    ?>
    </select>
    </td>
    <td>To Account:
    <select name="toacct">
    <?php
    foreach ($info as $account) {
        printf ("<option value=\"%s\">%s</option>", $account["account"], $account["account"]);
    }
    ?>
    ?>
    </select>
    </td>
    <td>Amount:
    <input type="text" name="amount" value="0.00000000"></td>
    <td align="right">
    <input type="submit" value="Move Funds">
    </td>
    </tr>
    </table>
</div>
</form>

<form method="post" action="wallet.php?id=<?php echo $_GET["id"];?>">
<div class="rbordered">
    <table width="100%">
    <tr>
    <td><b>Send Funds</b></td>
    </tr>
    </table>
</div>
</form>

<table width="100%">
<tr>

<td width="50%" valign="top">
<div class="rbordered">
<span class="title">Accounts</span>
<br>
<table class="bordered" width="100%">
    <tr>
    <th>Account</th>
    <th>Address</th>
    <th>Balance</th>
    </tr>
<?php
foreach ($info as $account) {
    printf ("<tr>\r\n");
    printf ("<td>%s</td>\r\n", $account["account"]);
    printf ("<td>");
    $addresses = $class->getAddressesByAccount($account["account"]);
    for ($i = 0; $i < count($addresses); $i ++) {
        printf ("%s", $addresses[$i]);
        if ($i < count($addresses)) printf ("<br>");
    }
    printf ("</td>\r\n");
    printf ("<td>%4.8f</td>\r\n", $account["balance"]);
    printf ("</tr>\r\n");
}
?>
</table>
</div>
</td>

<td width="50%" valign="top">
    <div class="rbordered">
    <span class="title">Transactions</span>
    <br>
    <table class="bordered" width="100%">
    <tr>
    <th>Time</th>
    <th>Address</th>
    <th>Amount</th>
    <th>Conf</th>
    </tr>
<?php
$transactions = $class->getTransactions();
/*
echo "<pre>";
print_r ($transactions);
echo "</pre>";
*/
foreach ($transactions as $trans) {
    printf ("<tr>\r\n");
    printf ("<td>%s</td>\r\n", strftime("%c", $trans["time"]));
    printf ("<td>%s</td>\r\n", $trans["address"]);
    printf ("<td align=\"right\">%4.8f</td>\r\n", $trans["amount"]);
    printf ("<td align=\"right\">%d</td>\r\n", $trans["confirmations"]);
    printf ("</tr>\r\n");
};
?>
    </table>
    </div>
</td>
    
</tr>
</table>

<br>

<?php
require_once ("footer.php");
?>