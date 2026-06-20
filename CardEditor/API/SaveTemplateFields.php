<?php
include_once('AuthoringEndpoint.php');
ce_run('POST', function($db) {
    $input = ce_input_json();
    return $db->saveTemplateFields(ce_int_param('templateId', $input), $input['fields'] ?? []);
});
?>
