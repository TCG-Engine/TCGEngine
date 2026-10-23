<?php
// The SWU-PGN writer (docs/superpowers/specs/2026-10-19-swusim-swupgn-writer-design.md).
require_once __DIR__ . '/../../../AppCore/SWU/SwuPgn/SwuPgn.php';   // SwuPgnBaseId / SwuPgnCopyNumber
require_once __DIR__ . '/../../../AppCore/SWU/Formats.php';         // SWUFormatLegalSets — a recording's CardPool
require_once __DIR__ . '/Identity.php';
require_once __DIR__ . '/Store.php';
require_once __DIR__ . '/Snapshot.php';
require_once __DIR__ . '/Ledger.php';
require_once __DIR__ . '/Diff.php';
require_once __DIR__ . '/Notes.php';
require_once __DIR__ . '/Matcher.php';
require_once __DIR__ . '/Recorder.php';
require_once __DIR__ . '/../SwuPgnCardMap.php';   // SwuPgnMapCardId — the CARDS index names
require_once __DIR__ . '/Finish.php';
require_once __DIR__ . '/Export.php';
require_once __DIR__ . '/Shadow.php';
