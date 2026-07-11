<?php
include_once('AuthoringEndpoint.php');
ce_run('POST', function($db) {
    $input = ce_input_json();
    return $db->deleteAsset(ce_int_param('id', $input));
});
?>
