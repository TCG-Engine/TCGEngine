<?php
// Optional settings for saved-deck simulations. Copy to
// SWUDeck/SimulationConfig.local.php to override the defaults.
// Set SWU_SIMULATION_API_SECRET to the same 32+ character secret on both hosts.
return [
    'simulation_url' => 'https://soulmastersdb.net/TCGEngine/APIs/SWUSimulation.php',
    // On the bot host only, if PHP CLI is not next to the web PHP binary:
    // 'php_bin' => '/absolute/path/to/php',
    'replay_base' => 'https://petranaki.net/TCGEngine',
];
