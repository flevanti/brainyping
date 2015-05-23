<?php

if (!isset($_GET["term"])) {
    exit;
}
$term = strtolower(trim($_GET["term"]));
if (strlen($term) < 4) {
    exit;
}
if ($term == "http"
    or $term == "http:"
    or $term == "http:/"
    or $term == "http://"
    or $term == "http://w"
    or $term == "http://ww"
    or $term == "http://www"
    or $term == "http://www."
    or $term == "www."
    or $term == "https"
    or $term == "https:"
    or $term == "https:/"
    or $term == "https://"
    or $term == "https://w"
    or $term == "https://ww"
    or $term == "https://www"
    or $term == "https://www."
) {
    exit;
}
if (user::isLogged() === true) {
    $id_user = user::getID();
    $limit = 15;
} else {
    $id_user = 0;
    $limit = 8;
}
$sql = "select host as label, host as value, public_token
            from hosts
              where (host like :term
                          or title like :term)
                  and (
                        (public=1 and enabled=1)
                            or (id_user = $id_user)
                      )
                        order by host limit $limit;";
$stmt = $mydbh_web->prepare($sql);
$stmt->bindValue("term", '%' . $term . '%');
$ret = $stmt->execute();
if ($stmt->rowCount() == 0) {
    exit;
}
$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rs);