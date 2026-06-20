<?php
include_once('AuthoringEndpoint.php');
ce_run('GET', function($db) {
    return $db->getGame(ce_int_param('id'));
});
?>
