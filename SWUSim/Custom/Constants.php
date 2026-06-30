<?php

// ═══════════════════════════════════════════════════════════════════════════
// SWU Shared Constants — reusable filter presets for ZoneSearch and friends
// ═══════════════════════════════════════════════════════════════════════════

// Every card type that counts as a "unit" on the battlefield, including the
// leader once it has been deployed (Leader Unit).
const AnyUnitFilter = ["Unit", "Token Unit", "Leader Unit"];

// Non-leader units only — deployed leaders are excluded. In hand/discard zones
// (where a leader unit can never appear) this is simply "unit cards".
const NonLeaderUnitFilter = ["Unit", "Token Unit"];
