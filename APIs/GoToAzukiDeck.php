<?php
// Resolves a friendly deck code (/deck/{code} on zendo.gg) to an AzukiDeck deck and redirects to it.
require_once "../Core/HTTPLibraries.php";
require_once "../Database/ConnectionManager.php";
require_once "../AccountFiles/AccountDatabaseAPI.php";

$code = TryGet("code", default: "");

// Root-relative redirects: scheme/host-agnostic (works local http + prod https on zendo.gg).
if (preg_match('/^[A-Za-z]{12}$/', $code)) {
  $id = ResolveFriendlyCode($code);
  if ($id !== null) {
    header("Location: /TCGEngine/NextTurn.php?gameName={$id}&playerID=1&folderPath=AzukiDeck", true, 302);
    exit;
  }
}
// Unknown/invalid code -> AzukiDeck index (no crash).
header("Location: /TCGEngine/AzukiDeck/", true, 302);
exit;
