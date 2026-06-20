<?php
include_once('AuthoringEndpoint.php');
ce_run('POST', function($db) {
    return $db->createCard(ce_input_json());
});
?>
