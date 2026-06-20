<?php
include_once('AuthoringEndpoint.php');
ce_run('GET', function($db) {
    return $db->exportSet(ce_int_param('setId'));
});
?>
