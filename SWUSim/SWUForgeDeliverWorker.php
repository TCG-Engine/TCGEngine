<?php
// SWU Forge delivery worker: posts every due row of swuforge_deliveries (SWUForgeDelivery.php). Run every minute.
// An HTTP page rather than a CLI script because the database credentials reach PHP through the vhost's Apache SetEnv
// (newhost/setup-box.sh), which a cron `php` never sees. It answers only the box itself:
//   * * * * * curl -s --resolve petranaki.net:443:127.0.0.1 https://petranaki.net/TCGEngine/SWUSim/SWUForgeDeliverWorker.php >/dev/null
//   dev: docker exec otmtcge-swusim-web-server-1 curl -s http://localhost/TCGEngine/SWUSim/SWUForgeDeliverWorker.php
require_once __DIR__ . '/SWUForgeDelivery.php';

function SWUForgeWorkerAllowed(string $sapi, string $remoteAddr): bool {
    return $sapi === 'cli' || in_array($remoteAddr, ['127.0.0.1', '::1'], true);
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Content-Type: application/json');
    if (!SWUForgeWorkerAllowed(PHP_SAPI, (string)($_SERVER['REMOTE_ADDR'] ?? ''))) {
        http_response_code(403);
        echo json_encode(['error' => 'local only']);
        exit();
    }
    set_time_limit(600);
    $started = microtime(true);
    $n = SWUForgeDeliverDue();
    echo json_encode(['attempted' => $n, 'ms' => (int)round((microtime(true) - $started) * 1000)]), "\n";
}
