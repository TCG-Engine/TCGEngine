<?php
include_once('AuthoringEndpoint.php');
ce_run('GET', function($db) {
    return $db->getTemplate(ce_int_param('id'));
});
?>
