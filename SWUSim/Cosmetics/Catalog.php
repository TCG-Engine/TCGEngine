<?php
// Declarative cosmetics catalog — the single source of truth for available options + defaults.
// Adding a cosmetic = a new entry here + the webp asset (+ a DB choice). asset=null means "render nothing".

function SWUCosmeticCatalog() {
    $builtins = [
        'background' => [
            'default' => ['label'=>'Default',        'asset'=>'./Assets/Boards/SWUSim/default.webp',        'isDefault'=>true],
            'sor-starfield' => ['label'=>'SOR Starfield',  'asset'=>'./Assets/Boards/SWUSim/sor-starfield.webp',  'isDefault'=>false],
            'shd-starfield' => ['label'=>'SHD Starfield',  'asset'=>'./Assets/Boards/SWUSim/shd-starfield.webp',  'isDefault'=>false],
            'twi-starfield' => ['label'=>'TWI Starfield',  'asset'=>'./Assets/Boards/SWUSim/twi-starfield.webp',  'isDefault'=>false],
            'jtl-starfield' => ['label'=>'JTL Starfield',  'asset'=>'./Assets/Boards/SWUSim/jtl-starfield.webp',  'isDefault'=>false],
            'lof-starfield' => ['label'=>'LOF Starfield',  'asset'=>'./Assets/Boards/SWUSim/lof-starfield.webp',  'isDefault'=>false],
            'sec-starfield' => ['label'=>'SEC Starfield', 'asset'=>'./Assets/Boards/SWUSim/sec-starfield.webp', 'isDefault'=>false],
            'ibh-starfield' => ['label'=>'IBH Starfield', 'asset'=>'./Assets/Boards/SWUSim/ibh-starfield.webp', 'isDefault'=>false],
            'law-starfield' => ['label'=>'LAW Starfield', 'asset'=>'./Assets/Boards/SWUSim/law-starfield.webp', 'isDefault'=>false],
            'ash-starfield' => ['label'=>'ASH Starfield', 'asset'=>'./Assets/Boards/SWUSim/ash-starfield.webp', 'isDefault'=>false],
            'death-star' => ['label'=>'Death Star',     'asset'=>'./Assets/Boards/SWUSim/death-star.webp',     'isDefault'=>false],
            'echo-base' => ['label'=>'Echo Base',      'asset'=>'./Assets/Boards/SWUSim/echo-base.webp',      'isDefault'=>false],
            //new backgrounds above this line
        ],
        'cardback' => [
            // The default lives under Assets/CardBacks (always deployed) — NOT ./SWUSim/concat,
            // which is a gitignored/generated folder and 404s on servers where concat isn't
            // regenerated (that was the broken default-cardback bug). default.webp is a copy of
            // the engine's face-down back.
            'classic' => ['label'=>'Classic', 'asset'=>'./Assets/CardBacks/SWUSim/default.webp', 'isDefault'=>true],
            'against-the-galaxy'             => ['label'=>'Against The Galaxy', 'asset'=>'./Assets/CardBacks/SWUSim/against-the-galaxy.webp', 'isDefault'=>false],
            'aixopluc-squadron'              => ['label'=>'Aixopluc Squadron', 'asset'=>'./Assets/CardBacks/SWUSim/aixopluc-squadron.webp', 'isDefault'=>false],
            'babu-freaks'                    => ['label'=>'Babu Freaks', 'asset'=>'./Assets/CardBacks/SWUSim/babu-freaks.webp', 'isDefault'=>false],
            'bachforellen-freunde-franken'   => ['label'=>'Bachforellen Freunde Franken', 'asset'=>'./Assets/CardBacks/SWUSim/bachforellen-freunde-franken.webp', 'isDefault'=>false],
            'baddest-batch'                  => ['label'=>'Baddest Batch', 'asset'=>'./Assets/CardBacks/SWUSim/baddest-batch.webp', 'isDefault'=>false],
            'bodega-loth-cats'               => ['label'=>'Bodega Loth Cats', 'asset'=>'./Assets/CardBacks/SWUSim/bodega-loth-cats.webp', 'isDefault'=>false],
            'bordure-exterieure'             => ['label'=>'Bordure Exterieure', 'asset'=>'./Assets/CardBacks/SWUSim/bordure-exterieure.webp', 'isDefault'=>false],
            'bothan-network'                 => ['label'=>'Bothan Network', 'asset'=>'./Assets/CardBacks/SWUSim/bothan-network.webp', 'isDefault'=>false],
            'bvs'                            => ['label'=>'BVS', 'asset'=>'./Assets/CardBacks/SWUSim/bvs.webp', 'isDefault'=>false],
            'c4'                             => ['label'=>'C4', 'asset'=>'./Assets/CardBacks/SWUSim/c4.webp', 'isDefault'=>false],
            'canadian-snow-troopers'         => ['label'=>'Canadian Snow Troopers', 'asset'=>'./Assets/CardBacks/SWUSim/canadian-snow-troopers.webp', 'isDefault'=>false],
            'central-spacers'                => ['label'=>'Central Spacers', 'asset'=>'./Assets/CardBacks/SWUSim/central-spacers.webp', 'isDefault'=>false],
            'coastal-cantina-name'           => ['label'=>'Coastal Cantina Name', 'asset'=>'./Assets/CardBacks/SWUSim/coastal-cantina-name.webp', 'isDefault'=>false],
            'coastal-cantina'                => ['label'=>'Coastal Cantina', 'asset'=>'./Assets/CardBacks/SWUSim/coastal-cantina.webp', 'isDefault'=>false],
            'darth-players'                  => ['label'=>'Darth Players', 'asset'=>'./Assets/CardBacks/SWUSim/darth-players.webp', 'isDefault'=>false],
            'dodonna-s-disciples-dark'       => ['label'=>'Dodonna S Disciples Dark', 'asset'=>'./Assets/CardBacks/SWUSim/dodonna-s-disciples-dark.webp', 'isDefault'=>false],
            'dodonna-s-disciples-light'      => ['label'=>'Dodonna S Disciples Light', 'asset'=>'./Assets/CardBacks/SWUSim/dodonna-s-disciples-light.webp', 'isDefault'=>false],
            'driftless-squadron'             => ['label'=>'Driftless Squadron', 'asset'=>'./Assets/CardBacks/SWUSim/driftless-squadron.webp', 'isDefault'=>false],
            'enigma'                         => ['label'=>'Enigma', 'asset'=>'./Assets/CardBacks/SWUSim/enigma.webp', 'isDefault'=>false],
            'fallen-order'                   => ['label'=>'Fallen Order', 'asset'=>'./Assets/CardBacks/SWUSim/fallen-order.webp', 'isDefault'=>false],
            'galactic-gonks'                 => ['label'=>'Galactic Gonks', 'asset'=>'./Assets/CardBacks/SWUSim/galactic-gonks.webp', 'isDefault'=>false],
            'galactic-shuffle'               => ['label'=>'Galactic Shuffle', 'asset'=>'./Assets/CardBacks/SWUSim/galactic-shuffle.webp', 'isDefault'=>false],
            'golden-dice-podcast'            => ['label'=>'Golden Dice Podcast', 'asset'=>'./Assets/CardBacks/SWUSim/golden-dice-podcast.webp', 'isDefault'=>false],
            'golden-squadron'                => ['label'=>'Golden Squadron', 'asset'=>'./Assets/CardBacks/SWUSim/golden-squadron.webp', 'isDefault'=>false],
            'gonkgang'                       => ['label'=>'Gonkgang', 'asset'=>'./Assets/CardBacks/SWUSim/gonkgang.webp', 'isDefault'=>false],
            'holocron-card-hub'              => ['label'=>'Holocron Card Hub', 'asset'=>'./Assets/CardBacks/SWUSim/holocron-card-hub.webp', 'isDefault'=>false],
            'indy-swu'                       => ['label'=>'Indy SWU', 'asset'=>'./Assets/CardBacks/SWUSim/indy-swu.webp', 'isDefault'=>false],
            'l8-night-gaming'                => ['label'=>'L8 Night Gaming', 'asset'=>'./Assets/CardBacks/SWUSim/l8-night-gaming.webp', 'isDefault'=>false],
            'les-cartes-sur-table'           => ['label'=>'Les Cartes Sur Table', 'asset'=>'./Assets/CardBacks/SWUSim/les-cartes-sur-table.webp', 'isDefault'=>false],
            'lonestar-destroyers'            => ['label'=>'Lonestar Destroyers', 'asset'=>'./Assets/CardBacks/SWUSim/lonestar-destroyers.webp', 'isDefault'=>false],
            'lxo'                            => ['label'=>'LXO', 'asset'=>'./Assets/CardBacks/SWUSim/lxo.webp', 'isDefault'=>false],
            'maclunky-gaming'                => ['label'=>'Maclunky Gaming', 'asset'=>'./Assets/CardBacks/SWUSim/maclunky-gaming.webp', 'isDefault'=>false],
            'mainedalorians'                 => ['label'=>'Mainedalorians', 'asset'=>'./Assets/CardBacks/SWUSim/mainedalorians.webp', 'isDefault'=>false],
            'mobyus1-simple'                 => ['label'=>'Mobyus1 Simple', 'asset'=>'./Assets/CardBacks/SWUSim/mobyus1-simple.webp', 'isDefault'=>false],
            'mobyus1-titled'                 => ['label'=>'Mobyus1 Titled', 'asset'=>'./Assets/CardBacks/SWUSim/mobyus1-titled.webp', 'isDefault'=>false],
            'mothemonster'                   => ['label'=>'Mothemonster', 'asset'=>'./Assets/CardBacks/SWUSim/mothemonster.webp', 'isDefault'=>false],
            'mythic-force'                   => ['label'=>'Mythic Force', 'asset'=>'./Assets/CardBacks/SWUSim/mythic-force.webp', 'isDefault'=>false],
            'omaha-alliance'                 => ['label'=>'Omaha Alliance', 'asset'=>'./Assets/CardBacks/SWUSim/omaha-alliance.webp', 'isDefault'=>false],
            'outer-rim-ccg'                  => ['label'=>'Outer Rim CCG', 'asset'=>'./Assets/CardBacks/SWUSim/outer-rim-ccg.webp', 'isDefault'=>false],
            'outer-team'                     => ['label'=>'Outer Team', 'asset'=>'./Assets/CardBacks/SWUSim/outer-team.webp', 'isDefault'=>false],
            'outmaneuver'                    => ['label'=>'Outmaneuver', 'asset'=>'./Assets/CardBacks/SWUSim/outmaneuver.webp', 'isDefault'=>false],
            'padawan-unlimited'              => ['label'=>'Padawan Unlimited', 'asset'=>'./Assets/CardBacks/SWUSim/padawan-unlimited.webp', 'isDefault'=>false],
            'pittsburgh-radar-technicians'   => ['label'=>'Pittsburgh Radar Technicians', 'asset'=>'./Assets/CardBacks/SWUSim/pittsburgh-radar-technicians.webp', 'isDefault'=>false],
            'porg-depot'                     => ['label'=>'Porg Depot', 'asset'=>'./Assets/CardBacks/SWUSim/porg-depot.webp', 'isDefault'=>false],
            'prairiepirates'                 => ['label'=>'Prairiepirates', 'asset'=>'./Assets/CardBacks/SWUSim/prairiepirates.webp', 'isDefault'=>false],
            'rajeux-tcg'                     => ['label'=>'Rajeux TCG', 'asset'=>'./Assets/CardBacks/SWUSim/rajeux-tcg.webp', 'isDefault'=>false],
            'rebel-resource-dark'            => ['label'=>'Rebel Resource Dark', 'asset'=>'./Assets/CardBacks/SWUSim/rebel-resource-dark.webp', 'isDefault'=>false],
            'rebel-resource-team'            => ['label'=>'Rebel Resource Team', 'asset'=>'./Assets/CardBacks/SWUSim/rebel-resource-team.webp', 'isDefault'=>false],
            'rebel-resource'                 => ['label'=>'Rebel Resource', 'asset'=>'./Assets/CardBacks/SWUSim/rebel-resource.webp', 'isDefault'=>false],
            'rtchompgg'                      => ['label'=>'Rtchompgg', 'asset'=>'./Assets/CardBacks/SWUSim/rtchompgg.webp', 'isDefault'=>false],
            'rva-swu'                        => ['label'=>'RVA SWU', 'asset'=>'./Assets/CardBacks/SWUSim/rva-swu.webp', 'isDefault'=>false],
            'sekrit'                         => ['label'=>'Sekrit', 'asset'=>'./Assets/CardBacks/SWUSim/sekrit.webp', 'isDefault'=>false],
            'squadriglia-taurinense'         => ['label'=>'Squadriglia Taurinense', 'asset'=>'./Assets/CardBacks/SWUSim/squadriglia-taurinense.webp', 'isDefault'=>false],
            'star-wars-dad-dad'              => ['label'=>'Star Wars Dad Dad', 'asset'=>'./Assets/CardBacks/SWUSim/star-wars-dad-dad.webp', 'isDefault'=>false],
            'star-wars-dad-family'           => ['label'=>'Star Wars Dad Family', 'asset'=>'./Assets/CardBacks/SWUSim/star-wars-dad-family.webp', 'isDefault'=>false],
            'swu-australia'                  => ['label'=>'SWU Australia', 'asset'=>'./Assets/CardBacks/SWUSim/swu-australia.webp', 'isDefault'=>false],
            'swu-nz'                         => ['label'=>'SWU NZ', 'asset'=>'./Assets/CardBacks/SWUSim/swu-nz.webp', 'isDefault'=>false],
            'swu-tang-clan'                  => ['label'=>'SWU Tang Clan', 'asset'=>'./Assets/CardBacks/SWUSim/swu-tang-clan.webp', 'isDefault'=>false],
            'swuneff-hyperspace'             => ['label'=>'Swuneff Hyperspace', 'asset'=>'./Assets/CardBacks/SWUSim/swuneff-hyperspace.webp', 'isDefault'=>false],
            'swuneff'                        => ['label'=>'Swuneff', 'asset'=>'./Assets/CardBacks/SWUSim/swuneff.webp', 'isDefault'=>false],
            'team-serialized'                => ['label'=>'Team Serialized', 'asset'=>'./Assets/CardBacks/SWUSim/team-serialized.webp', 'isDefault'=>false],
            'team-shoot-first'               => ['label'=>'Team Shoot First', 'asset'=>'./Assets/CardBacks/SWUSim/team-shoot-first.webp', 'isDefault'=>false],
            'tgb-team'                       => ['label'=>'TGB Team', 'asset'=>'./Assets/CardBacks/SWUSim/tgb-team.webp', 'isDefault'=>false],
            'the-cantina-crew'               => ['label'=>'The Cantina Crew', 'asset'=>'./Assets/CardBacks/SWUSim/the-cantina-crew.webp', 'isDefault'=>false],
            'the-nordic-takedown'            => ['label'=>'The Nordic Takedown', 'asset'=>'./Assets/CardBacks/SWUSim/the-nordic-takedown.webp', 'isDefault'=>false],
            'ticorah'                        => ['label'=>'Ticorah', 'asset'=>'./Assets/CardBacks/SWUSim/ticorah.webp', 'isDefault'=>false],
            'too-many-hans'                  => ['label'=>'Too Many Hans', 'asset'=>'./Assets/CardBacks/SWUSim/too-many-hans.webp', 'isDefault'=>false],
            'top-cut-target'                 => ['label'=>'Top Cut Target', 'asset'=>'./Assets/CardBacks/SWUSim/top-cut-target.webp', 'isDefault'=>false],
            'tropa-do-boba'                  => ['label'=>'Tropa Do Boba', 'asset'=>'./Assets/CardBacks/SWUSim/tropa-do-boba.webp', 'isDefault'=>false],
            'under-the-twin-suns'            => ['label'=>'Under The Twin Suns', 'asset'=>'./Assets/CardBacks/SWUSim/under-the-twin-suns.webp', 'isDefault'=>false],
            'unplayable'                     => ['label'=>'Unplayable', 'asset'=>'./Assets/CardBacks/SWUSim/unplayable.webp', 'isDefault'=>false],
            'wasatch-wookies'                => ['label'=>'Wasatch Wookies', 'asset'=>'./Assets/CardBacks/SWUSim/wasatch-wookies.webp', 'isDefault'=>false],
            //new cardbacks above this line
        ],
        'playmat' => [
            'none' => ['label'=>'None', 'asset'=>null, 'isDefault'=>true],
            'sor-key-art' => ['label'=>'SOR Key Art', 'asset'=>'./Assets/Playmats/SWUSim/sor-key-art.webp', 'isDefault'=>false],
            'shd-key-art' => ['label'=>'SHD Key Art', 'asset'=>'./Assets/Playmats/SWUSim/shd-key-art.webp', 'isDefault'=>false],
            'twi-key-art' => ['label'=>'TWI Key Art', 'asset'=>'./Assets/Playmats/SWUSim/twi-key-art.webp', 'isDefault'=>false],
            'jtl-key-art' => ['label'=>'JTL Key Art', 'asset'=>'./Assets/Playmats/SWUSim/jtl-key-art.webp', 'isDefault'=>false],
            'lof-key-art' => ['label'=>'LOF Key Art', 'asset'=>'./Assets/Playmats/SWUSim/lof-key-art.webp', 'isDefault'=>false],
            'sec-key-art' => ['label'=>'SEC Key Art', 'asset'=>'./Assets/Playmats/SWUSim/sec-key-art.webp', 'isDefault'=>false],
            'ibh-key-art' => ['label'=>'IBH Key Art', 'asset'=>'./Assets/Playmats/SWUSim/ibh-key-art.webp', 'isDefault'=>false],
            'law-key-art' => ['label'=>'LAW Key Art', 'asset'=>'./Assets/Playmats/SWUSim/law-key-art.webp', 'isDefault'=>false],
            'ash-key-art' => ['label'=>'ASH Key Art', 'asset'=>'./Assets/Playmats/SWUSim/ash-key-art.webp', 'isDefault'=>false],
            'home-one' => ['label'=>'Home One', 'asset'=>'./Assets/Playmats/SWUSim/home-one.webp', 'isDefault'=>false],
            'overwhelming-barrage' => ['label'=>'Overwhelming Barrage', 'asset'=>'./Assets/Playmats/SWUSim/overwhelming-barrage.webp', 'isDefault'=>false],
            //new playmats above this line
        ],
    ];
    return $builtins;
}

function SWUCosmeticSlots() { return array_keys(SWUCosmeticCatalog()); }

// Catalog assets are stored './X' (root-relative to the TCGEngine web root — correct for the
// in-game render served at the root). Convert to an absolute URL for pages served at any depth
// (Profile chooser, Mod uploader). Null/empty -> ''.
function SWUCosmeticAssetUrl($asset) {
    if (empty($asset)) return '';
    return preg_replace('#^\./#', '/TCGEngine/', (string)$asset);
}

// Render a <select> of a slot's catalog options, with $currentId pre-selected. Shared by the
// Profile chooser and the in-game gear menu so option markup + data-asset URLs live in one place.
function SWUCosmeticSelectHtml(string $slot, string $currentId, string $class = 'cos-select'): string {
    $esc = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
    $opts = '';
    foreach (SWUCosmeticCatalog()[$slot] ?? [] as $id => $o) {
        $sel   = ((string)$id === (string)$currentId) ? ' selected' : '';
        $asset = SWUCosmeticAssetUrl($o['asset'] ?? null);
        $opts .= "<option value=\"{$esc($id)}\" data-asset=\"{$esc($asset)}\"$sel>{$esc($o['label'])}</option>";
    }
    return "<select class=\"{$esc($class)}\" data-slot=\"{$esc($slot)}\">$opts</select>";
}

function SWUCosmeticDefault($slot) {
    foreach (SWUCosmeticCatalog()[$slot] ?? [] as $id => $opt) {
        if (!empty($opt['isDefault'])) return $id;
    }
    return '';
}

function SWUCosmeticResolve($slot, $choiceId) {
    $opts = SWUCosmeticCatalog()[$slot] ?? [];
    if (!isset($opts[$choiceId])) $choiceId = SWUCosmeticDefault($slot);
    $opt = $opts[$choiceId] ?? null;
    return ['id' => (string)$choiceId, 'asset' => $opt['asset'] ?? null];
}
