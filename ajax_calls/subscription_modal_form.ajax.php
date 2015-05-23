<?php

//NO PUBLIC TOKEN.....
if ($uriobj->getParam(2) === false or $uriobj->getParam(2) === "") {
    echo "NO PARAMETERS FOUND, SORRY";
    exit;
}
//PUBLIC TOKEN PRESENT... CHECK IF IS VALID....
$sql = "SELECT id, host, title, public_token, id_user
                FROM hosts WHERE public_token = :pt
                                    AND public=1
                              LIMIT 1;";
$stmt = $mydbh->prepare($sql);
$ret = $stmt->execute(["pt" => $uriobj->getParam(2)]);
if ($ret === false) {
    DIE("SOMETHING WENT WRONG SORRY");
}
if ($stmt->rowCount() == 0) {
    DIE("UNABLE TO FIND HOST, SORRY");
}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (user::isLogged() and user::getID() == $row["id_user"] and 1 == 2) {
    echo "No need to subscribe!!<br>";
    echo "If you want to receive notifications edit this monitor and add/remove contacts<br>";

    return;
}
echo "Email me when <b>" . strtoupper(($row["title"])) . "</b> goes down!<br>";
echo "<form id=\"form_subscription\" name=\"form_subscription\">";
echo "<div class=\"form-group\">";
if (user::isLogged()) {
    $sql = "SELECT * FROM user_contacts
                  WHERE id_user = " . user::getID() . "
                    AND enabled = 1
                    ORDER BY contact_type_id, contact;";
    $rs = $mydbh->query($sql);
    echo "<select class=\"form-control\" name=\"id_contact\" id=\"id_contact\">";
    while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
        echo "<option value=\"{$row["id"]}\">" . $row["contact_type_id"] . " " . $row["contact"] . "</option>";
    }
    echo "</select>";
} else {
    echo "<input type=\"text\" class=\"form-control\" id=\"email_subscription\" name=\"email_subscription\" placeholder=\"Your Email\">";
}
echo "<input type=\"hidden\" name=\"public_token\" id=\"public_token\" value=\"" . $uriobj->getParam(2) . "\">";
if (user::isLogged() === false) {
    echo "<span class=\"micro_text\"><a href=\"/signin/redir/" . urlencode(urlencode("/info/" . $row["public_token"] . "/#showsubscrmodal")) . "\">Registered user? Login first!</a><br></span>";
}
echo "<span id=\"error_area\" class=\"micro_text red_text\"> &nbsp;&nbsp;&nbsp;&nbsp;</span>
  </div>
</form>";
echo "<button type=\"button\" class=\"btn btn-primary\" id=\"confirm_subscription\">&nbsp;&nbsp;&nbsp;SAVE&nbsp;&nbsp;&nbsp;</button>&nbsp;&nbsp;
        <button type=\"button\" class=\"btn btn-default\" id=\"close_modal_subscription\">CANCEL</button>";





