<?php
include_once('AuthoringEndpoint.php');
ce_run('POST', function($db) {
    return $db->createTemplate(ce_input_json());
});
?>
