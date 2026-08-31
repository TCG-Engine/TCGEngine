<?php

// Regression guard for the protected local connection file used by PHP and the MCP server.

error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir(dirname(__DIR__, 2));

$path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tcg-card-code-config-' . getmypid() . '.php';
putenv('CARD_CODE_LOCAL_CONFIG_PATH=' . $path);
include_once './CardEditor/Database/CardCodeConnectionConfig.php';

$fails = 0;
$check = function ($ok, $message) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $message\n"; if (!$ok) ++$fails; };

try {
    $normalized = CardCodeNormalizeConnection('AzukiSim', [
        'url' => 'https://cards.example.com/TCGEngine/CardEditor/API/CardCodeService.php/',
        'workspace' => 'AzukiSim',
        'token' => 'tcc_test-secret',
    ]);
    $check($normalized['url'] === 'https://cards.example.com/TCGEngine/CardEditor/API/CardCodeService.php', 'connection URL is normalized');
    try {
        CardCodeNormalizeConnection('AzukiSim', ['url' => 'http://cards.example.com/api', 'workspace' => 'AzukiSim', 'token' => 'token']);
        $insecureRejected = false;
    } catch (InvalidArgumentException $error) { $insecureRejected = true; }
    $check($insecureRejected, 'non-loopback HTTP is rejected');
    try {
        CardCodeNormalizeConnection('AzukiSim', ['url' => 'https://user:password@cards.example.com/api?debug=1', 'workspace' => 'AzukiSim', 'token' => 'token']);
        $embeddedCredentialsRejected = false;
    } catch (InvalidArgumentException $error) { $embeddedCredentialsRejected = true; }
    $check($embeddedCredentialsRejected, 'URL credentials and query parameters are rejected');

    CardCodeSaveLocalConnections(['AzukiSim' => $normalized]);
    $contents = file_get_contents($path);
    $check(str_starts_with((string)$contents, CARD_CODE_LOCAL_CONFIG_HEADER), 'saved config has an executable HTTP-deny header');
    $loaded = CardCodeLoadLocalConnections();
    $check(($loaded['AzukiSim']['token'] ?? '') === 'tcc_test-secret', 'saved secret is available to local runtimes');
    $metadata = CardCodeConnectionMetadata('AzukiSim', $loaded['AzukiSim']);
    $metadataJson = json_encode($metadata);
    $check(strpos($metadataJson, 'tcc_test-secret') === false && ($metadata['tokenPrefix'] ?? '') === 'tcc_test-sec…', 'browser metadata masks the secret');

    putenv('CARD_CODE_REMOTE_CONFIG={"AzukiSim":{"url":"https://stale.example/api","workspace":"Wrong","token":"stale"}}');
    include_once './CardEditor/Database/CardAbilityRepository.php';
    $effective = CardCodeRemoteConfigForRoot('AzukiSim');
    $check(($effective['url'] ?? '') === $normalized['url'] && ($effective['token'] ?? '') === 'tcc_test-secret', 'GUI connection overrides legacy environment configuration');
} finally {
    if (is_file($path)) unlink($path);
    putenv('CARD_CODE_LOCAL_CONFIG_PATH');
    putenv('CARD_CODE_REMOTE_CONFIG');
}

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
