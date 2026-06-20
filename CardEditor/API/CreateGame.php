<?php
include_once('AuthoringEndpoint.php');
ce_run('POST', function($db) {
    return $db->createGame(ce_input_json());
});
?>
