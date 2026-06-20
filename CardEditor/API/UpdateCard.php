<?php
include_once('AuthoringEndpoint.php');
ce_run('POST', function($db) {
    $input = ce_input_json();
    return $db->updateCard(ce_int_param('id', $input), $input);
});
?>
