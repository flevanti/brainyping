<?php


$arr_result["error"] = true;
$arr_result["error_descr"] = "";
$arr_result["result"] = "";
if (user::isLogged() === False and !isset($_POST["email_subscription"])) {
    $arr_result["error_descr"] = "Unable to find email POST data";
    die(json_encode($arr_result));
}
if (user::isLogged() === true and !isset($_POST["id_contact"])) {
    $arr_result["error_descr"] = "Unable to find contact ID";
    die(json_encode($arr_result));
}
if (!isset($_POST["public_token"])) {
    $arr_result["error_descr"] = "Unable to find host public token POST data";
    die(json_encode($arr_result));
}
//PUBLIC TOKEN PRESENT... CHECK IF IS VALID....
//we also retrieve the title of the host so we could personalize the email!!!! ;)
$sql = "SELECT id,title FROM hosts WHERE public_token = :pt AND public=1 LIMIT 1;";
$stmt = $mydbh->prepare($sql);
$ret = $stmt->execute(["pt" => $_POST["public_token"]]);
if ($ret === false) {
    $arr_result["error_descr"] = "SQL statement to verify token failed";
    die(json_encode($arr_result));
}
if ($stmt->rowCount() == 0) {
    $arr_result["error_descr"] = "Unable to find host to save";
    die(json_encode($arr_result));
}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$host_title = $row["title"];
$id_host = $row["id"];
//PROCEED...
//IF USER IS LOGGED LOOK FOR CONTACT INFO AND CHECK THAT THE USER IS THE OWNER
//JS INJECTION IS SOOOOO EASY
if (user::isLogged()) {
    $sql = "SELECT * FROM user_contacts WHERE id_user = :id_user AND id = :id_contact AND enabled=1;";
    $stmt = $mydbh->prepare($sql);
    if ($stmt === false) {
        $arr_result["error_descr"] = "Unable to prepare statement to check contact ownership";
        die(json_encode($arr_result));
    }
    $ret = $stmt->execute(["id_user" => user::getID(), "id_contact" => $_POST["id_contact"]]);
    if ($ret === false) {
        $arr_result["error_descr"] = "Unable to execute statement to check contact ownership";
        die(json_encode($arr_result));
    }
    if ($stmt->rowCount() == 0) {
        $arr_result["error_descr"] = "Unable to verify contact ownership";
        die(json_encode($arr_result));
    }
    $row_contact = $stmt->fetch(PDO::FETCH_ASSOC);
    $contact = $row_contact["contact"];
    $contact_type_id = $row_contact["contact_type_id"];
    //SO USER IS LOGGED, CONTACT OWNERSHIP VERIFIED.... PROCEED....
    //CONTACT INFORMATION HAS BEEN ALREADY VERIFIED DURING THE "ADD CONTACT" PROCESS SO WE JUST VERIFY THAT IT'S NOT
    //ALREADY LINKED WITH THE HOST THEN WE ADD IT ... NO MORE CHECKS...
    $sql = "SELECT * FROM host_subscriptions WHERE id_user_contact= :id_contact AND id_host = :id_host;";
    $stmt = $mydbh->prepare($sql);
    if ($stmt === false) {
        $arr_result["error_descr"] = "Unable to prepare statement to check if contact already present";
        die(json_encode($arr_result));
    }
    $ret = $stmt->execute(["id_contact" => $_POST["id_contact"], "id_host" => $id_host]);
    if ($ret === false) {
        $arr_result["error_descr"] = "Unable to execute statement to check if contact already present" . implode("\n", $stmt->errorInfo());
        die(json_encode($arr_result));
    }
    if ($stmt->rowCount() > 0) {
        $arr_result["error_descr"] = "Contact is already linked to this monitor, thanks!";
        die(json_encode($arr_result));
    }
    //FINE.... LET'S GO...
    //PREPARE THE SQL
    $sql = "INSERT INTO host_subscriptions (  id_contact_type,
                                          contact,
                                          validated,
                                          added_ts,
                                          id_host,
                                          id_user,
                                          id_user_contact) VALUES (
                                          :contact_type_id,
                                          :contact,
                                          1,
                                          :added_ts,
                                          :id_host,
                                          :id_user,
                                          :id_user_contact);";
    $stmt = $mydbh->prepare($sql);
    $stmt = $mydbh->prepare($sql);
    if ($stmt === false) {
        $arr_result["error_descr"] = "Unable to prepare statement to insert contact";
        die(json_encode($arr_result));
    }
    $ret = $stmt->execute(["contact_type_id" => $contact_type_id,
                           "contact"         => $contact,
                           "added_ts"        => time(),
                           "id_host"         => $id_host,
                           "id_user"         => user::getID(),
                           "id_user_contact" => $_POST["id_contact"]]);
    if ($ret === false) {
        $arr_result["error_descr"] = "Unable to execute statement to insert contact";
        die(json_encode($arr_result));
    }
    $arr_result["error"] = false;
    die(json_encode($arr_result));
} //END IF USER IS LOGGED
//////////////////////////////
//USER IS NOT LOGGED PERFORM SOME CHECKS
//////////////////////////////
//CHECK IF EMAIL SUBMITTED IS VALID....
$host = new host_manager();
//TRIM EMAIL.... USER COULD TAKE HOURS TO UNDERSTAND WHY EMAIL IS NOT VALID OTHERWISE..... :)
$_POST["email_subscription"] = strtolower(trim($_POST["email_subscription"]));
if ($host->checkEmailSyntax($_POST["email_subscription"]) === false) {
    $arr_result["error_descr"] = "Email is not valid";
    die(json_encode($arr_result));
}
//OK.. EMAIL IS VALID... CHECK IF HAS BEEN ALREADY LINKED TO THIS HOST......
$sql = "select * from host_subscriptions where id_host=$id_host and contact=:contact;";
$stmt = $mydbh->prepare($sql);
$ret = $stmt->execute(["contact" => $_POST["email_subscription"]]);
if ($ret === false) {
    $arr_result["error_descr"] = "Error during sql statement, unable to check if contact is already linked to host.";
    die(json_encode($arr_result));
}
//WE FOUND THE SAME EMAIL/HOST....
//CHECK IF IT'S VALIDATED OR NOT
if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row["validated"] == 1) {
        //ALREADY VALIDATED...
        $arr_result["error_descr"] = "Contact is already linked to this host, thanks! :)";
        die(json_encode($arr_result));
    }
    //EMAIL IS NOT VALIDATED....
    //IF THE REGISTRATION IS OLD (4 HOURS) THAN WE PROCEED AGAIN
    //OTHERWISE WE ASK USER TO BE PATIENT
    if ($row["added_ts"] > (time() - 4 * 60 * 60)) {
        $arr_result["error_descr"] = "Contact is already linked to this host, please click the link in the email you received to activate it.";
        die(json_encode($arr_result));
    }
    //IF HERE.....
    //WE CONTINUE AND JUST ADD A NEW RECORD
}
//LET'S GO!
$validation_token = $host->generateRandomPublicToken(20);
//HERE WE ARE.... EMAIL IS OK, PUBLIC TOKEN IS OK, EMAIL AND HOST ARE NOT YET LINKED.....
//WE CAN PROCEED!!!
//YEAHH!
//PREPARE THE SQL
$sql = "insert into host_subscriptions (  id_contact_type,
                                          contact,
                                          friendly_name,
                                          validation_token,
                                          validated,
                                          added_ts,
                                          id_host) values (
                                          'EMAIL',
                                          :contact,
                                          '',
                                          '$validation_token',
                                          0,
                                          :ts,
                                          $id_host);";
//PREPARE THE MAIL MESSAGE
$email_message = "Hi! We received a request using this email and we want to be sure it's you!\n";
$email_message .= "If you want to receive an email every time " . strtoupper($host_title) . " goes down that's fine, just click the following link within 7 days\n(or copy it in the address bar of your browser)\n\n";
$email_message .= $_SESSION["config"]["_APP_ROOT_URL_"] . "confirmsubscription/$validation_token\n\n";
$email_message .= "If this was not you please ignore this email, you do not receive any further email about this.\n\n\n";
$email_message .= "Thanks\nBrainyping Staff";
//START TRANSACTION
$mydbh->beginTransaction();
$stmt = $mydbh->prepare($sql);
$ret = $stmt->execute(["contact" => $_POST["email_subscription"],
                       "ts"      => time()]);
if ($ret === false) {
    $arr_result["error_descr"] = "Error during subscription process";
    die(json_encode($arr_result));
}
$ret = email_queue::addToQueue($_SESSION["config"]["_APP_DEFAULT_EMAIL_ROBOT_"], $_POST["email_subscription"], strtoupper($host_title) . " ALERT MONITOR CONFIRM", $email_message);
if ($ret === false) {
    $arr_result["error_descr"] = "Unable to send confirmation email";
    die(json_encode($arr_result));
}
$mydbh->commit();
$arr_result["error"] = false;
die(json_encode($arr_result));