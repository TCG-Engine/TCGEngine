<?php

function RenderAssetVersioningUI($folderPath) {
    $folderPath = trim((string)$folderPath);
    if(!preg_match('/^[A-Za-z0-9_]+$/', $folderPath)) return;
    $cssPath = '/TCGEngine/Core/Versioning/AssetVersioningUI.css';
    $jsPath = '/TCGEngine/Core/Versioning/AssetVersioningUI.js';
    if(function_exists('_VersionedClientInclude')) {
        $cssPath = _VersionedClientInclude($cssPath);
        $jsPath = _VersionedClientInclude($jsPath);
    }
    $folderJSON = json_encode($folderPath);
    echo "<link rel='stylesheet' href='" . htmlspecialchars($cssPath, ENT_QUOTES) . "'>";
    echo "<script src='" . htmlspecialchars($jsPath, ENT_QUOTES) . "'></script>";
    echo "<script>(function(){var mount=function(){"
        . "if(window.AssetVersioningUI)window.AssetVersioningUI.mount({folderPath:"
        . $folderJSON
        . "});};if(document.readyState==='loading'){"
        . "document.addEventListener('DOMContentLoaded',mount);"
        . "}else{mount();}})();</script>";
}

?>
