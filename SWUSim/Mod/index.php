<?php
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
$modErr = CheckLoggedInUserMod();
if ($modErr !== '') { http_response_code(403); echo "<h2>Access denied</h2><p>".htmlspecialchars($modErr, ENT_QUOTES)."</p>"; exit; }
?>
<!DOCTYPE html>
<html><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SWUSim Mod Tools</title>
  <link rel="stylesheet" href="/TCGEngine/SharedUI/Sites/SWUSim/css/swusim-overrides.css">
  <style>
    .mod-wrap { max-width: 720px; margin: 40px auto; padding: 24px; }
    .mod-wrap h1 { color: #f5e6c0; }
    .mod-tools { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 12px; }
    .mod-tools a { display: block; padding: 14px 18px; border-radius: 10px; text-decoration: none;
      color: #f0ddb0; background: rgba(62,44,12,0.88); border: 1px solid rgba(190,155,50,0.32); }
    .mod-tools a:hover { border-color: rgba(200,160,55,0.65); }
  </style>
</head><body>
  <div class="mod-wrap card container">
    <h1>SWUSim Mod Tools</h1>
    <ul class="mod-tools">
      <li><a href="/TCGEngine/SWUSim/Mod/CosmeticsUploader.php">🎨 Cosmetics Uploader — add/remove backgrounds, card backs, playmats</a></li>
    </ul>
  </div>
</body></html>
