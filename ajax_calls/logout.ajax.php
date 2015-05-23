<?php

$result = user::logout();
if ($result === true) {
    echo json_encode(["error" => false]);
} else {
    echo json_encode(["error" => true]);
}