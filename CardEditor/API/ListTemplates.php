<?php
include_once('AuthoringEndpoint.php');
ce_run('GET', function($db) {
    return $db->listTemplates(ce_int_param('gameId'));
});
?>
