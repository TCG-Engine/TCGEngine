<?php
// The active site that root SharedUI/ pages render — the site follows the database.
//
// Each app (dev container or prod deployment) connects to its own DB via MYSQL_DATABASE_NAME
// (see Database/ConnectionManager.php), so we resolve the site from that same var. Keying on
// the connection's own var means the rendered site and the connected DB can never disagree,
// and it works everywhere: browser, curl, CLI, and the regression runner alike.
//
// Locally this mirrors the per-port containers (:3100 SWUDeck, :3200 GA, :3300 Azuki,
// :3400 SWUSim, :3500 Hellbreak). There is no fallback on purpose: an unset or unmapped DB is a
// misconfiguration, and serving the wrong site silently is worse than failing loudly.
//
// The db -> site map lives in Database/SiteRegistry.php, shared with ResolveDatabaseName() so the
// two directions cannot drift. Deck-builder roots (HellbreakDeck, AzukiDeck) share their sim's
// database but are not sites, which is what the registry's `site` flag records.
//
// This file MUST keep returning a plain site-name string from a top-level `return`: every
// generated root pointer consumes it inline as
//   include __DIR__ . '/Sites/' . (require __DIR__ . '/ActiveSite.php') . '/<page>.php';
// (see SharedUI/Render/GenerateSites.php). Turning it into a function would break all of them.
require_once __DIR__ . '/../Database/DatabaseResolution.php';

$db = getenv('MYSQL_DATABASE_NAME');
if ($db === false || $db === '') {
    throw new RuntimeException('ActiveSite: MYSQL_DATABASE_NAME is not set; cannot resolve the active site.');
}
$site = SiteForDatabase($db);
if ($site === null) {
    throw new RuntimeException("ActiveSite: no site mapped for MYSQL_DATABASE_NAME '$db'; add it to Database/SiteRegistry.php.");
}
return $site;
