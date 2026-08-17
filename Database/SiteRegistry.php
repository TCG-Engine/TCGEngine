<?php
// THE registry: which database each app root belongs to, and which root renders as that
// database's site. Two consumers read it, in opposite directions:
//
//   Database/DatabaseResolution.php  rootName -> db   (which database do I connect to?)
//   SharedUI/ActiveSite.php          db -> rootName   (which site do I render?)
//
// Keeping both directions in ONE table is what stops them drifting: a box can never connect to
// one site's database while rendering another's.
//
// `site` marks the root that renders as that database's site. Deck builders share their sim's
// database (see `AssetReflection:` in Schemas/<Root>/GameSchema.txt) but are NOT sites — they have
// no menu pages of their own — so exactly one root per database carries site=true. Without the
// flag the reverse lookup would depend on array order.
//
// Roots absent on purpose: FaBSim, FaBDeck, GudnakSim, RBDeck, SoulMastersDB, MatchTestSim. They
// have no database of their own (no docker-compose service, nothing here). A CLI run under those
// folders throws rather than guessing — inventing a database name is exactly the silent
// wrong-database bug this registry exists to remove.
return [
    // rootName        => ['db' => database name,     'site' => renders as this db's site?]
    'SWUDeck'          => ['db' => 'swudeck',         'site' => true],
    'SWUSim'           => ['db' => 'swusim',          'site' => true],
    'GrandArchiveSim'  => ['db' => 'grandarchivesim', 'site' => true],
    'AzukiSim'         => ['db' => 'azukisim',        'site' => true],
    'AzukiDeck'        => ['db' => 'azukisim',        'site' => false],
    'HellbreakSim'     => ['db' => 'hellbreaksim',    'site' => true],
    'HellbreakDeck'    => ['db' => 'hellbreaksim',    'site' => false],
];
