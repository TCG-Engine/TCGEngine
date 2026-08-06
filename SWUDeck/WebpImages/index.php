<?php
// Legacy card-art URLs — compatibility shim, not a live path.
//
// Discord embed history is pinned to
//   https://swustats.net/TCGEngine/SWUDeck/WebpImages/<uuid>.webp
// forever (APIs/DiscordBot.php has posted that shape for over a year). SWU card art moved to the
// shared SET_NNN corpus at AppCore/SWU/Images/ on 2026-08-05, so every one of those URLs would
// 404 without this. New code must use SWUCardImagePath(); nothing should link here deliberately.
//
// Reachable only via the .htaccess rewrite in this directory, which routes a MISSING .webp here.
// Streams rather than redirects so cached embeds keep getting a 200 with image/webp, which is what
// Discord expects.
//
// Design: docs/superpowers/specs/2026-08-04-swu-shared-card-universe-design.md §8

require_once __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php'; // CardIDLookup, façade
require_once __DIR__ . '/../../AppCore/SWU/CardImagePath.php';

$id = isset($_GET['id']) ? preg_replace('/[^A-Za-z0-9_]/', '', (string)$_GET['id']) : '';
if ($id === '') { http_response_code(404); exit; }

// SWUCardImageID() handles all three schemes: an FFG UID normalises to SET_NNN, a SET_NNN passes
// through, and a preview card picks up its mock_ prefix.
$path = SWUCardImageFsPath($id, 'card');
if (!is_file($path)) { http_response_code(404); exit; }

header('Content-Type: image/webp');
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=604800');
readfile($path);
