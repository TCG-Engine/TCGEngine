<?php
if (!isset($_GET['shell'])) {
    header('Location: /TCGEngine/SharedUI/Sites/HellbreakSim/MainMenu.php', true, 302);
    exit();
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>HellbreakSim Status</title><style>body{margin:0;background:#130d16;color:#f7eee5;font:18px system-ui;display:grid;place-items:center;min-height:100vh}.card{max-width:660px;padding:3rem;background:#241729;border:1px solid #714d67;border-radius:16px}a{color:#ffbf62}</style></head><body><main class="card"><h1>HellbreakSim</h1><p>The board and zones are scaffolded. Gameplay and individual card rules will be added in the next phase.</p><p><a href="/TCGEngine/SharedUI/Sites/HellbreakSim/MainMenu.php">Return to Hellbreak home</a></p></main></body></html>
