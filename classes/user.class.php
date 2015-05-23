<?php

class user {
    private $email = "";
    private $password = "";
    private $password_hash = "";
    private $dbhandler;
    private $validation_errors = array();
    public $error_code = "";
    public $last_error = "";
    private $activation_code;
    private $email_sent = false;
    private $public_token = "";

    function __construct(&$dbconn = false) {
        if ($dbconn === false) {
            global $mydbh;
            $this->dbhandler = $mydbh;
        } else {
            $this->dbhandler = $dbconn;
        }
    }

    function changePwd($current, $new, $new2, $user_token, $user_id) {
        $ret = $this->validatePasswordRules($new, $new2);
        if ($ret === false) {
            $this->last_error = implode("\n", $this->validation_errors);

            return false;
        }
        if ($current == $new) {
            $this->last_error = "New password cannot be the same of the old one";

            return false;
        }
        if (trim($user_token) == "") {
            $this->last_error = "Invalid token";

            return false;
        }
        $sql = "SELECT * FROM users WHERE email = :email AND public_token = :token AND enabled=1 AND locked=0;";
        $stmt = $this->dbhandler->prepare($sql);
        if ($stmt === false) {
            $this->last_error = "Unable to proceed (0)";

            return false;
        }
        $ret = $stmt->execute(["email" => $this->getLoginEmail(), "token" => $user_token]);
        if ($ret === false) {
            $this->last_error = "Unable to proceed (1)";

            return false;
        }
        if ($stmt->rowCount() == 0) {
            $this->last_error = "Unable to proceed (2)";

            return false;
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $ret = password_verify($current, $row["password"]);
        if ($ret === false) {
            $this->last_error = "Current password is not valid";

            return false;
        }
        //IF HERE... CURRENT PASSWORD IS CORRECT....
        //CREATE THE HASH OF THE NEW PASSWORD AND SAVE IT....
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $sql = "update users set password = :hashy where id = {$row["id"]} limit 1;";
        $stmt = $this->dbhandler->prepare($sql);
        if ($stmt === false) {
            $this->last_error = "Unable to proceed (4)";

            return false;
        }
        $ret = $stmt->execute(["hashy" => $hash]);
        if ($ret === false) {
            $this->last_error = "Unable to proceed (5)";

            return false;
        }
        if ($stmt->rowCount() == 0) {
            $this->last_error = "Operation completed but no records updated. Please check your credentials trying to login again";

            return false;
        }

        return true;
    }


    ////////////////////////////////////////////////////////////
    //METHODS FOR NEW USERS
    function setNewUserPOST() {
        $this->setEmail($_POST["newUserEmail"], true, true);
        $this->setPassword($_POST["newUserPassword"], $_POST["newUserPassword2"]);
    }

    function saveNewUser() {
        if (count($this->validation_errors) > 0) {
            return false;
        }
        $this->activation_code = $this->generateRandomActivationCode();
        $this->public_token = $this->generateRandomToken();
        $this->sendActivationEmail();
        if ($this->email_sent == false) {
            $this->validation_errors[] = "Failed to send activation email";

            return false;
        }
        $this->addNewUserToDB();

        return true;
    }

    function addNewUserToDB() {
        $this->dbhandler->beginTransaction();
        $sql = "INSERT INTO users (email,
                                  email_verification_code,
                                  email_verified,
                                  enabled,
                                  password,
                                  date_added_ts,
                                  role,
                                  last_login_ts,
                                  public_token)
                                  VALUES (
                                  :email,
                                  :email_verification_code,
                                  :email_verified,
                                  :enabled,
                                  :password,
                                  :date_added_ts,
                                  :role,
                                  :last_login_ts,
                                  :public_token);";
        $stmt = $this->dbhandler->prepare($sql);
        $arr_bind["email"] = $this->email;
        $arr_bind["email_verification_code"] = 'CODE' . $this->activation_code;
        $arr_bind["email_verified"] = 0;
        $arr_bind["enabled"] = 1;
        $arr_bind["password"] = $this->password_hash;
        $arr_bind["date_added_ts"] = time();
        $arr_bind["role"] = 'USER';
        $arr_bind["last_login_ts"] = time();
        $arr_bind["public_token"] = $this->public_token;
        if ($stmt->execute($arr_bind) === false) {
            die ('A fatal error has occurred during user creation');
        }
        $last_id = $this->dbhandler->lastInsertId();
        $ret = $this->addUserPrimaryContact($this->email, $last_id);
        if ($ret === false) {
            $this->dbhandler->rollBack();

            return false;
        }
        $this->dbhandler->commit();

        return true;
    }

    private function addUserPrimaryContact($email, $user_id) {
        $sql = "insert into user_contacts (contact, validated, id_user, contact_type_id,primary_contact)
                    values ('$email',1,$user_id,'EMAIL',1);";
        $ret = $this->dbhandler->exec($sql);
        if ($ret === false) {
            return false;
        }

        return true;
    }

    function getUserContactsList($id_user) {
        $sql = "select * from user_contacts uc
                      where uc.id_user = $id_user;";
        $rs = $this->dbhandler->query($sql);
        if ($rs->rowCount() == 0) { //IF NO RECORDS FOUND....
            return false;
        }

        return $rs;
    }

    static function getNewUserFormHTML() {
        global $uriobj;

        return "<form name=\"formNewUser\" method=\"post\" action=\"" . $uriobj->URI_gen(["signup"]) . "\" class=\"form-signup\" id=\"form_signup\">
                    <label for=\"newUserEmail\">Email</label>
                    <input type=\"text\" class=\"form-control\" id=\"newUserEmail\" name=\"newUserEmail\" placeholder=\"Insert your email\" value=\"\">
                    <label for=\"newUserPassword\">Password</label>
                    <input type=\"password\" class=\"form-control\" id=\"newUserPassword\" name=\"newUserPassword\" placeholder=\"Insert your password\" value=\"\">
                    <input type=\"password\" class=\"form-control\" id=\"newUserPassword2\" name=\"newUserPassword2\" placeholder=\"Repeat your password\" value=\"\">
                    <br>
                    <input type=\"submit\" class=\"btn btn-lg btn-primary btn-block\" name=\"submit\" value=\"Submit\">
                </form>";
    } //END method  getFormHTML

    function sendActivationEmail() {
        $text = "Welcome to PING!\n";
        $text .= "Please activate your account clicking on the following link and enter your email and the activation code " . $this->activation_code . "\n\n\n";
        $text .= _APP_ROOT_URL . "/useractivation/" . $this->public_token . "\n\n\n";
        $text .= "Thanks\n";
        $text .= _APP_NAME_ . " Team\n\n\n\n";
        $obj = _APP_NAME_ . " Account activation";
        $this->email_sent = $this->sendEmail($this->email, $obj, $text);
    }


    /// END METHODS FOR NEW USER
    //////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////
    ////LOGIN METHODS
    static function getLoginFormHTML() {
        global $uriobj;

        return "<form class=\"form-signin\" role=\"form\" method=\"post\" action=\"" . $uriobj->URI_gen(["signin"]) . "\" id=\"form_signin\">
                        <input type=\"text\" class=\"form-control input-lg\" placeholder=\"Email address\"  name=\"loginEmail\" value=\"\" autofocus>
                    <input type=\"password\" class=\"form-control input-lg\" placeholder=\"Password\" name=\"loginPassword\">
                    <br>
                    <button class=\"btn btn-lg btn-primary btn-block\" type=\"submit\">Sign in</button><br>
                </form>
               <a href=\"" . $uriobj->URI_gen(["signup"]) . "\">Not registered? Sign Up now!</a><br><br>
                <a href=\"" . $uriobj->URI_gen(["forgotpassword"]) . "\">I forgot my password</a>";
    }

    function setLoginPOST() {
        $this->setEmail($_POST["loginEmail"], true, false);
        $this->setPassword($_POST["loginPassword"]);

        return true;
    }

    function checkLoginCredentials() {
        if (count($this->validation_errors) > 0) {
            return false;
        }
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->dbhandler->prepare($sql);
        $stmt->execute(['email' => $this->email]);
        if ($stmt->rowCount() == 0) {
            $this->validation_errors[] = "Email or Password not correct";
            $this->error_code = "CREDENTIALS";

            return false;
        }
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($this->password, $r["password"]) !== true) {
            $this->validation_errors[] = "Email or Password not correct";
            $this->error_code = "CREDENTIALS";

            return false;
        }
        if ($r["enabled"] == 0) {
            $this->validation_errors[] = "User in not enabled to access the system";
            $this->error_code = "NOTENABLED";

            return false;
        }
        if ($r["email_verified"] == 0) {
            $this->validation_errors[] = "Email not verified";
            $this->error_code = "EMAILNOTVERIFIED";

            return false;
        }
        //IF WE ARE HERE MEANS THAT ALL CHECKS ARE OK... USER IS LOGGED IN SUCCESSFULLY....
        //LET'S CREATE THE SESSION.....
        $this->createUserSession($r);
        //AND UPDATE LAST LOGIN DATE.....
        $sql = "UPDATE users SET last_login_ts= " . time() . " WHERE id = " . $r["id"];
        $this->dbhandler->query($sql);

        return true;
    } //END CHECK LOGIN CREDENTIALS METHOD

    private function createUserSession(&$r) {
        $_SESSION["user"]["logged"] = true;
        $_SESSION["user"]["userID"] = $r["id"];
        $_SESSION["user"]["userToken"] = $r["public_token"];
        $_SESSION["user"]["email"] = $r["email"];
        $_SESSION["user"]["lastLogin"] = $r["last_login_ts"];
        $_SESSION["user"]["role"] = $r["role"];
    }

    static function getID() {
        if ($_SESSION["user"]["logged"] === true) {
            return $_SESSION["user"]["userID"];
        } else {
            return false;
        }
    }

    static function getToken() {
        if ($_SESSION["user"]["logged"] === true) {
            return $_SESSION["user"]["userToken"];
        } else {
            return false;
        }
    }

    static function getLoginEmail() {
        return $_SESSION["user"]["email"];
    }

    static function createGuestSession() {
        $_SESSION["user"]["logged"] = false;
        $_SESSION["user"]["userID"] = 0;
        $_SESSION["user"]["email"] = "";
        $_SESSION["user"]["lastLogin"] = time();
        $_SESSION["user"]["role"] = "";
        $_SESSION["user"]["initialized"] = true;
    }

    static function sessionInitialize() {
        if (!isset($_SESSION["user"]["initialized"])) {
            self::createGuestSession();
        };
    }

    ///END METHODS FOR LOGIN
    //////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    /////USER VALIDATION METHODS
    function getUserValidationFormHTML($token = false) {
        global $uriobj;
        if ($token !== false) {
            $sql = "SELECT email FROM users WHERE public_token = :token;";
            $stmt = $this->dbhandler->prepare($sql);
            $stmt->execute(["token" => $token]);
            if ($stmt->rowCount() > 0) {
                $rs = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->email = $rs["email"];
            }
        }

        return "<form class=\"form-signin\" role=\"form\" method=\"post\" action=\"" . $uriobj->URI_gen(["useractivation"]) . "\" id=\"form_useractivation\">
                    <input type=\"text\" class=\"form-control input-lg\" placeholder=\"Email address\"  name=\"activationEmail\" value=\"" . htmlspecialchars($this->email) . "\" autofocus>
                    <input type=\"password\" class=\"form-control input-lg\" placeholder=\"Code\" name=\"activationCode\">
                    <!--
                    <label class=\"checkbox\">
                        <input type=\"checkbox\" value=\"remember-me\"> Remember me
                    </label>
                    -->
                    <br>
                    <button class=\"btn btn-lg btn-primary btn-block\" type=\"submit\">Validate</button>
                </form>";
    }

    function setUserValidationPOST() {
        $this->setEmail($_POST["activationEmail"], true, false);
        $this->setActivationCode($_POST["activationCode"]);
    }

    function setActivationCode(&$code) {
        $this->activation_code = $code;
        if (strlen($this->activation_code) != 4) {
            $this->validation_errors[] = "Activation code not valid";
            $this->error_code = "CREDENTIALS";

            return false;
        }
        if (is_numeric($this->activation_code) === false) {
            $this->validation_errors[] = "Activation code not valid";
            $this->error_code = "CREDENTIALS";

            return false;
        }

        return true;
    }

    function activateUser() {
        if (count($this->validation_errors) > 0) {
            return false;
        }
        $sql = "SELECT id FROM users WHERE email = :email AND email_verification_code = :code;";
        $stmt = $this->dbhandler->prepare($sql);
        $stmt->execute(['email' => $this->email, 'code' => 'CODE' . $this->activation_code]);
        if ($stmt->rowCount() == 0) {
            $this->validation_errors[] = "Email or activation code not correct";
            $this->error_code = "CREDENTIALS";

            return false;
        }
        //IF HERE EMAIL AND VALIDATION CODE ARE OK....
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        $sql = "UPDATE users SET email_verified=1, public_token = NULL WHERE id=" . $r["id"];
        $this->dbhandler->query($sql);

        return true;
    }



    ///////////////////////////////////
    //// GENERIC METHODS
    function getValidationErrors() {
        return $this->validation_errors;
    } //END getValidationErrors method

    function setEmail(&$email, $check_syntax = false, $check_email_already_used = false) {
        $this->email = strtolower(trim($email));
        if ($check_syntax == true) {
            $this->validateEmailSyntax();
        }
        if ($check_email_already_used == true) {
            $this->checkEmailAlreadyRegistered();
        }

        return true;
    }

    function sendEmail(&$email, &$obj, &$text) {
        return email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_, $email, $obj, $text, "");
    }

    static function logout() {
        //WE UNSET ONLY THE ARRAY KEY USED FOR LOGGING USERS...
        unset ($_SESSION["user"]);

        return true;
    }

    private function generatePasswordHash(&$password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    function validatePasswordRules(&$password, &$password2) {
        if ($password != $password2) {
            $this->validation_errors[] = "Passwords do not match";

            return false;
        }
        if (is_null($password) or trim($password) == "") {
            $this->validation_errors[] = "Password empty";

            return false;
        }
        if (strpos($password, " ") !== false) {
            $this->validation_errors[] = "Spaces are not allowed in password";

            return false;
        }
        if (strlen($password) < 8) {
            $this->validation_errors[] = "Password is too short";

            return false;
        }
        if (preg_match('/[A-Z]+/', $password) === 0) {
            $this->validation_errors[] = "Passwords need at least one CAPITAL letter";

            return false;
        }
        if (preg_match('/[a-z]+/', $password) === 0) {
            $this->validation_errors[] = "Passwords need at least one lower letter";

            return false;
        }
        if (preg_match('/[0-9]+/', $password) === 0) {
            $this->validation_errors[] = "Passwords need at least one number";

            return false;
        }

        return true;
    }

    function validateEmailSyntax() {
        if (is_null($this->email) or $this->email == "") {
            $this->validation_errors[] = "Email empty";
            $this->error_code = "EMAILSYNTAX";

            return false;
        }
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $this->validation_errors[] = "Email not valid";
            $this->error_code = "EMAILSYNTAX";

            return false;
        }

        return true;
    }

    function checkMyContactsIsDouble($contact) {
        $sql = "SELECT id FROM user_contacts WHERE contact=:contact AND id_user = " . self::getID() . ";";
        $stmt = $this->dbhandler->prepare($sql);
        $res = $stmt->execute(["contact" => $contact]);
        if ($stmt->rowCount() == 0) {
            return false;
        }

        return true;
    }

    function setPassword(&$password, &$password2 = false) {
        $this->password = $password;
        $this->password_hash = $this->generatePasswordHash($password);
        if ($password2 !== false) {
        }

        return true;
    }

    private function checkEmailAlreadyRegistered() {
        $sql = "SELECT 1 FROM users WHERE email = :email;";
        $stmt = $this->dbhandler->prepare($sql);
        $stmt->execute(['email' => $this->email]);
        if ($stmt->rowCount() > 0) {
            $this->validation_errors[] = "Email already used";

            return true;
        }

        return false;
    }

    private function generateRandomActivationCode() {
        return rand(1000, 9999);
    }

    function generateRandomToken($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    function addNewMyContacts($arr_data) {
        $arr_data["validation_token"] = $this->generateRandomToken(20);
        $sql = "INSERT INTO user_contacts (contact,
                                            validation_token,
                                            validated,
                                            id_user,
                                            contact_type_id,
                                            primary_contact,
                                            enabled,
                                            friendly_name) VALUES (

                                            :contact,
                                            :validation_token,
                                            0,
                                            " . self::getID() . ",
                                            :contact_type_id,
                                            0,
                                            0,
                                            :friendly_name);";
        $stmt = $this->dbhandler->prepare($sql);
        if ($stmt === false) {
            $this->last_error = "Statement preparation error";

            return false;
        }
        $res = $stmt->execute($arr_data);
        if ($res === false) {
            $this->last_error = "Insert statement execution error";

            return false;
        }
        $message = "Hi, to complete your request please click on the following link:

" . _APP_ROOT_URL_ . "contactactivation/" . $arr_data["validation_token"] . "


If you cannot click the link please copy it and paste it in the address bar of your browser.


Thanks
Brainyping";
        $res = email_queue::addToQueue(_APP_DEFAULT_EMAIL_ROBOT_, $arr_data["contact"], "Brainyping - New contact request", $message);
        if ($res === false) {
            $this->last_error = "Unable to send activation mail";

            return false;
        }

        return true;
    }

    static function isLogged() {
        return $_SESSION["user"]["logged"];
    }

    static function getRole() {
        return $_SESSION["user"]["role"];
    }

    public function activateContact($token) {
        $sql = "UPDATE user_contacts SET validation_token = NULL,
                                            validated=1,
                                            enabled=1
                    WHERE validation_token = :token LIMIT 1;";
        $stmt = $this->dbhandler->prepare($sql);
        $ret = $stmt->execute(["token" => $token]);
        if ($ret === false) {
            $this->last_error = "Update failed";

            return false;
        }
        if ($stmt->rowCount() == 0) {
            $this->last_error = "Contact not found";

            return false;
        }

        return true;
    }
} //END user CLASS