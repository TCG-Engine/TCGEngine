<?php
include_once('AuthoringEndpoint.php');
ce_run('GET', function($db) {
    return $db->listCards(ce_int_param('setId'));
});
?>
