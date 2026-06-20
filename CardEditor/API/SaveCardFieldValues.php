<?php
include_once('AuthoringEndpoint.php');
ce_run('POST', function($db) {
    $input = ce_input_json();
    return $db->saveCardFieldValues(ce_int_param('cardId', $input), $input['values'] ?? []);
});
?>
