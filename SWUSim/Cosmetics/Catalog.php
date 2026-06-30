<?php
// Declarative cosmetics catalog — the single source of truth for available options + defaults.
// Adding a cosmetic = a new entry here + the webp asset (+ a DB choice). asset=null means "render nothing".

function SWUCosmeticCatalog() {
    $builtins = [
        'background' => [
            'default' => ['label'=>'Default',       'asset'=>'./Assets/Boards/SWUSim/default.webp', 'isDefault'=>true],
            'spcgnd'  => ['label'=>'Space / Ground', 'asset'=>'./Assets/Boards/SWUSim/spcgnd.webp',  'isDefault'=>false],
        ],
        'cardback' => [
            'classic' => ['label'=>'Classic', 'asset'=>'./SWUSim/concat/CardBack.webp', 'isDefault'=>true],
        ],
        'playmat' => [
            'none' => ['label'=>'None', 'asset'=>null, 'isDefault'=>true],
        ],
    ];
    foreach (_SWUCosmeticUploadedRows() as $slot => $rows) {
        if (!isset($builtins[$slot])) continue;
        foreach ($rows as $id => $opt) $builtins[$slot][$id] = $opt;
    }
    return $builtins;
}

// Uploaded cosmetics merged in from the DB. Defensive: returns [] whenever the DB
// or the cosmeticupload table is unavailable (e.g. the render harness container),
// so the catalog never fatals outside the SWUSim app. Request-memoized.
function _SWUCosmeticUploadedRows() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    if (!function_exists('GetLocalMySQLConnection')) return $cache;
    try {
        $conn = GetLocalMySQLConnection();
        $t = $conn->query("SHOW TABLES LIKE 'cosmeticupload'");
        if ($t && $t->num_rows > 0) {
            $res = $conn->query("SELECT slot, id, label, asset FROM cosmeticupload");
            while ($res && ($r = $res->fetch_assoc())) {
                $cache[$r['slot']][$r['id']] = ['label'=>$r['label'], 'asset'=>$r['asset'], 'isDefault'=>false, 'uploaded'=>true];
            }
        }
        $conn->close();
    } catch (\Throwable $e) { $cache = []; }
    return $cache;
}

function SWUCosmeticSlots() { return array_keys(SWUCosmeticCatalog()); }

// Catalog assets are stored './X' (root-relative to the TCGEngine web root — correct for the
// in-game render served at the root). Convert to an absolute URL for pages served at any depth
// (Profile chooser, Mod uploader). Null/empty -> ''.
function SWUCosmeticAssetUrl($asset) {
    if (empty($asset)) return '';
    return preg_replace('#^\./#', '/TCGEngine/', (string)$asset);
}

function SWUCosmeticDefault($slot) {
    foreach (SWUCosmeticCatalog()[$slot] ?? [] as $id => $opt) {
        if (!empty($opt['isDefault'])) return $id;
    }
    return '';
}

function SWUCosmeticResolve($slot, $choiceId) {
    $opts = SWUCosmeticCatalog()[$slot] ?? [];
    if (!isset($opts[$choiceId])) $choiceId = SWUCosmeticDefault($slot);
    $opt = $opts[$choiceId] ?? null;
    return ['id' => (string)$choiceId, 'asset' => $opt['asset'] ?? null];
}
