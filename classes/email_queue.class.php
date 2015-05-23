<?php

class email_queue {

    static function addToQueue($from, $to, $subject, $body, $bcc = "") {
        global $mydbh;
        $now = time();
        $now_str = date("d M Y H:i:s");
        $token = self::generateRandomToken(80);
        $sql = "insert into email_queue ( date_ts,
                                                date_str,
                                                subject,
                                                body,
                                                from_,
                                                to_,
                                                bcc,
                                                sent_date_ts,
                                                machine_id,
                                                db_id,
                                                token
                                                ) values (
                                                $now,
                                                '$now_str',
                                                :subject,
                                                :body,
                                                :from,
                                                :to,
                                                :bcc,
                                                0,
                                                :machine_id,
                                                @db_id,
                                                :token
                                                );";
        $arr_bind_var = ["subject"    => $subject,
                         "body"       => $body,
                         "from"       => $from,
                         "to"         => $to, "bcc" => $bcc,
                         "machine_id" => _MACHINE_ID_,
                         "token"      => $token];
        $stmt = $mydbh->prepare($sql);
        $r = $stmt->execute($arr_bind_var);
        if ($r === false) {
            mail(_APP_DEFAULT_EMAIL_, "FATAL ERROR ON MAIL QUEUE PROCESS", "NEED SUPPORT\n" . implode("\n", $stmt->errorInfo()), "From:" . _APP_DEFAULT_EMAIL_ROBOT_ . "\r\n");

            return false;
        }

        return true;
    } //END addToQueue METHOD

    static function generateRandomToken($length = 20) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = "";
        $random_max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $random_max)];
        }

        return $randomString;
    }

    static function processQueue() {
        global $mydbh;
        $now = time();
        $now_str = date("d M Y H:i:s");
        $sent_ok_ids = array();
        $sent_failed_ids = array();
        //WE LIMIT THE EMAIL IN ORDER TO NOT EXCEED SMTP SERVER LIMITS.... ACTUALLY ARUBA IS 250emails/20min
        //EMAIL ENGINE RUNS EVERY 1 minute
        $sql = "SELECT * FROM email_queue WHERE sent_date_ts = 0 ORDER BY date_ts LIMIT 4;";
        $rs = $mydbh->query($sql);
        while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
            set_time_limit(60);
            $r = mail($row["to_"], $row["subject"], $row["body"], "From:" . $row["from_"] . "\r\nBcc:" . $row["bcc"] . "\r\n");
            if ($r === true) {
                $sent_ok_tokens[] = "'" . $row["token"] . "'";
            } else {
                $sent_failed_tokens[] = "'" . $row["token"] . "'";
            }
        } //END WHILE
        if (count($sent_ok_tokens) > 0) {
            $tokens = implode(",", $sent_ok_tokens);
            $sql = "update email_queue set status = 'OK',
                                            sent_date_ts = $now,
                                            sent_date_str = '$now_str'
                                      where sent_date_ts = 0 and token in ($tokens);";
            echo "SQL TO UPDATE MAIL SENT: \n" . $sql;
            $mydbh->query($sql);
        }
        if (count($sent_failed_tokens) > 0) {
            $tokens = implode(",", $sent_failed_tokens);
            $sql = "update email_queue set status = 'FAILED',
                                            sent_date_ts = $now,
                                            sent_date_str = '$now_str'
                                      where sent_date_ts = 0 and token in ($tokens);";
            echo "SQL TO UPDATE MAIL FAILED: \n" . $sql;
            $mydbh->query($sql);
        }

        return ["ok" => count($sent_ok_tokens), "failed" => count($sent_failed_tokens)];
    } //END processQueue METHOD
} //END CLASS