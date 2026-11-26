<?php
// Public selections from Talishar-FE's PLAYER_PLAYMATS catalog.
$fabPlaymats = [
    '' => 'Plain',
    'Default' => 'Talishar Default',
    'The-Pits' => 'The Pits',
    'Demonastery' => 'Demonastery',
    'Metrix' => 'Metrix',
    'Misteria' => 'Misteria',
    'Savage-Lands' => 'Savage Lands',
    'Solana' => 'Solana',
    'Ironsong-Determination' => 'Ironsong Determination',
    'Volcor' => 'Volcor',
    'Data-Doll' => 'Data Doll',
    'Korshem' => 'Korshem',
    'Dynasty' => 'Dynasty',
    'Everfest' => 'Everfest',
    'Find-Center' => 'Find Center',
    'Part-The-Mistveil' => 'Part The Mistveil',
    'Rosetta' => 'Rosetta',
    'Candlehold' => 'Candleheim',
    'Isenloft' => 'Isenloft',
    'Volthaven' => 'Volthaven',
    'High-Seas' => 'High Seas',
    'High-Seas-Necro' => 'High Seas Necro',
    'The-Hunted' => 'The Hunted',
    'Deathmatch-Arena' => 'Deathmatch Arena',
    'LibraryOfSolana' => 'Library of Solana',
    'Omens' => 'Omens of the Third Age',
    'CoaxACommotion' => 'Coax A Commotion',
    'hammerhead' => 'Hammerhead',
];
$fabPlaymatAssets = './FaBSim/Assets/Playmats/';
?>
<link rel="stylesheet" href="./FaBSim/Custom/Playmats.css?v=<?= filemtime(__DIR__ . '/Playmats.css') ?>">
<dialog id="fab-playmats" aria-labelledby="fab-playmats-title">
  <header class="fab-playmat-header">
    <h2 id="fab-playmats-title">Playmats</h2>
    <form method="dialog"><button class="fab-playmat-close" aria-label="Close playmats">×</button></form>
  </header>
  <div class="fab-playmat-sides" role="group" aria-label="Field">
    <button type="button" data-fab-playmat-side="local" aria-pressed="true">Your field</button>
    <button type="button" data-fab-playmat-side="opponent" aria-pressed="false">Opponents</button>
  </div>
  <div class="fab-playmat-grid" role="group" aria-label="Available playmats">
    <?php foreach ($fabPlaymats as $id => $name): ?>
      <button type="button" class="fab-playmat-tile" data-fab-playmat="<?= htmlspecialchars($id, ENT_QUOTES) ?>"
        data-image="<?= $id === '' ? '' : $fabPlaymatAssets . rawurlencode($id) . '.webp' ?>" aria-pressed="false">
        <?php if ($id === ''): ?>
          <span class="fab-playmat-plain" aria-hidden="true"></span>
        <?php else: ?>
          <img src="<?= $fabPlaymatAssets . 'thumbnails/' . rawurlencode($id) . '.webp' ?>" alt="" width="320" height="180" loading="lazy" decoding="async">
        <?php endif; ?>
        <span><?= htmlspecialchars($name, ENT_QUOTES) ?></span>
      </button>
    <?php endforeach; ?>
  </div>
</dialog>
<script defer src="./FaBSim/Custom/Playmats.js?v=<?= filemtime(__DIR__ . '/Playmats.js') ?>"></script>
