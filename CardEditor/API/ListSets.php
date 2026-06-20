<?php
include_once('AuthoringEndpoint.php');
ce_run('GET', function($db) {
    return $db->listSets(ce_int_param('gameId'));
});
?>
