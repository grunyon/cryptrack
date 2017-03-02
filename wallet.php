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
?>

<div class="title">
    Cryptrack - Wallet for <?php echo $account["name"];?>
</div>
<br>

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
$info = $class->getAccounts();
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
<br>
<table width="100%">
<tr>
<td width="50%" valign="top">
<div class="rbordered">
    <span class="title">Move</span>
    <form method="post" action="wallet.php?id=<?php echo $_GET["id"];?>">
    <table width="100%">
    <tr>
    <td width="33%">From:
    <select name="fromacct">
    <?php
    foreach ($info as $account) {
        printf ("<option value=\"%s\">%s</option>", $account["account"], $account["account"]);
    }
    ?>
    </select>
    </td>
    <td width="33%">To:
    <select name="toacct">
    <?php
    foreach ($info as $account) {
        printf ("<option value=\"%s\">%s</option>", $account["account"], $account["account"]);
    }
    ?>
    ?>
    </select>
    </td>
    <td width="33%">Amount:
    <input type="text" name="amount" value="0.00000000"></td>
    </tr>
    </table>
    <br>
    <center>
    <input type="submit" value="Move Funds">
    </center>
    </form>
</div>
</td>
<td width="50%" valign="top">
    <div class="rbordered">
    <span class="title">Send</span>
    </div>
</td>
</tr>
</table>

<?php
require_once ("footer.php");
?>