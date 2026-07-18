<?php
// Resolves a friendly deck code (/deck/{code}) to the deck and redirects to the deck view.
require_once "../Core/HTTPLibraries.php";
require_once "../Database/ConnectionManager.php";
require_once "../AccountFiles/AccountDatabaseAPI.php";

$code = TryGet("code", default: "");

// Root-relative redirects: scheme/host-agnostic (works local http + prod https).
if (preg_match('/^[A-Za-z]{12}$/', $code)) {
  $id = ResolveFriendlyCode($code);
  if ($id !== null) {
    header("Location: /TCGEngine/NextTurn.php?gameName={$id}&playerID=1&folderPath=SWUDeck", true, 302);
    exit;
  }
}
// Unknown/invalid code -> SWUDeck main menu (no crash).
header("Location: /TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php", true, 302);
exit;
