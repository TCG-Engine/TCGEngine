<?php
// Card data the upstream API OMITS, supplied from tracked source. Applied at generation time by
// AppCore/SWU/CardDataSupplementApply.php — FILL-BLANKS ONLY, official data always wins.
//
// CardID => field => ['value' => ..., 'source' => 'swudb' | 'manual']
//   swudb  — written/refreshed by: php SWUSim/DevTools/backfill-card-data.php [--set=X] [--dry]
//   manual — hand-maintained (e.g. HMW_004's deployed side); tooling never modifies it
// Field names are Schemas/SWUSim/ImportSchema.txt property names.
// Shared by BOTH SWUSim and SWUDeck.
//
// SCAFFOLD-IGNORE: pure DATA, not a card implementation. Tools that infer "is this card
// implemented?" by grepping quoted CardIDs must skip this file, or every card listed here looks
// implemented and gets no stub / is dropped from gap reports.
return array (
  'ASH_019' => 
  array (
    'trait' => 
    array (
      'value' => 'Peridea',
      'source' => 'swudb',
    ),
  ),
  'ASH_020' => 
  array (
    'trait' => 
    array (
      'value' => 'Nevarro',
      'source' => 'swudb',
    ),
  ),
  'ASH_021' => 
  array (
    'trait' => 
    array (
      'value' => 'Death Star',
      'source' => 'swudb',
    ),
  ),
  'ASH_022' => 
  array (
    'trait' => 
    array (
      'value' => 'Kalevala',
      'source' => 'swudb',
    ),
  ),
  'ASH_023' => 
  array (
    'trait' => 
    array (
      'value' => 'Seatos',
      'source' => 'swudb',
    ),
  ),
  'ASH_024' => 
  array (
    'trait' => 
    array (
      'value' => 'Dagobah',
      'source' => 'swudb',
    ),
  ),
  'ASH_025' => 
  array (
    'trait' => 
    array (
      'value' => 'Pillio',
      'source' => 'swudb',
    ),
  ),
  'ASH_026' => 
  array (
    'trait' => 
    array (
      'value' => 'Tatooine',
      'source' => 'swudb',
    ),
  ),
  'HMW_004' => 
  array (
    'leaderUnitArena' => 
    array (
      'value' => 'Space',
      'source' => 'manual',
    ),
    'leaderUnitSubtitle' => 
    array (
      'value' => 'Icon of Tyranny',
      'source' => 'manual',
    ),
    'leaderUnitTitle' => 
    array (
      'value' => 'The Death Star',
      'source' => 'manual',
    ),
    'leaderUnitTrait' => 
    array (
      'value' => 'Imperial,Vehicle,Capital Ship',
      'source' => 'manual',
    ),
    'leaderUnitType' => 
    array (
      'value' => 'Unit',
      'source' => 'manual',
    ),
  ),
  'HMW_019' => 
  array (
    'trait' => 
    array (
      'value' => 'Tatooine',
      'source' => 'swudb',
    ),
  ),
  'HMW_020' => 
  array (
    'trait' => 
    array (
      'value' => 'Naboo',
      'source' => 'swudb',
    ),
  ),
  'HMW_021' => 
  array (
    'trait' => 
    array (
      'value' => 'Kashyyyk',
      'source' => 'swudb',
    ),
  ),
  'HMW_022' => 
  array (
    'trait' => 
    array (
      'value' => 'Endor',
      'source' => 'swudb',
    ),
  ),
  'HMW_023' => 
  array (
    'trait' => 
    array (
      'value' => 'Endor',
      'source' => 'swudb',
    ),
  ),
  'HMW_024' => 
  array (
    'trait' => 
    array (
      'value' => 'Kashyyyk',
      'source' => 'swudb',
    ),
  ),
  'HMW_025' => 
  array (
    'trait' => 
    array (
      'value' => 'Naboo',
      'source' => 'swudb',
    ),
  ),
  'HMW_026' => 
  array (
    'trait' => 
    array (
      'value' => 'Tatooine',
      'source' => 'swudb',
    ),
  ),
  'HMW_027' => 
  array (
    'trait' => 
    array (
      'value' => 'Naboo',
      'source' => 'swudb',
    ),
  ),
  'HMW_028' => 
  array (
    'trait' => 
    array (
      'value' => 'Tatooine',
      'source' => 'swudb',
    ),
  ),
  'HMW_029' => 
  array (
    'trait' => 
    array (
      'value' => 'Endor',
      'source' => 'swudb',
    ),
  ),
  'HMW_030' => 
  array (
    'trait' => 
    array (
      'value' => 'Kashyyyk',
      'source' => 'swudb',
    ),
  ),
  'HMW_031' => 
  array (
    'trait' => 
    array (
      'value' => 'Kashyyyk',
      'source' => 'swudb',
    ),
  ),
  'HMW_032' => 
  array (
    'trait' => 
    array (
      'value' => 'Tatooine',
      'source' => 'swudb',
    ),
  ),
  'HMW_033' => 
  array (
    'trait' => 
    array (
      'value' => 'Naboo',
      'source' => 'swudb',
    ),
  ),
  'HMW_034' => 
  array (
    'trait' => 
    array (
      'value' => 'Endor',
      'source' => 'swudb',
    ),
  ),
  'IBH_002' => 
  array (
    'trait' => 
    array (
      'value' => 'Hoth',
      'source' => 'swudb',
    ),
  ),
  'IBH_054' => 
  array (
    'trait' => 
    array (
      'value' => 'Hoth',
      'source' => 'swudb',
    ),
  ),
  'JTL_019' => 
  array (
    'trait' => 
    array (
      'value' => 'Cloud City',
      'source' => 'swudb',
    ),
  ),
  'JTL_020' => 
  array (
    'trait' => 
    array (
      'value' => 'Endor',
      'source' => 'swudb',
    ),
  ),
  'JTL_021' => 
  array (
    'trait' => 
    array (
      'value' => 'Castilon',
      'source' => 'swudb',
    ),
  ),
  'JTL_022' => 
  array (
    'trait' => 
    array (
      'value' => 'D\'Qar',
      'source' => 'swudb',
    ),
  ),
  'JTL_023' => 
  array (
    'trait' => 
    array (
      'value' => 'Naboo',
      'source' => 'swudb',
    ),
  ),
  'JTL_024' => 
  array (
    'trait' => 
    array (
      'value' => 'Scarif',
      'source' => 'swudb',
    ),
  ),
  'JTL_025' => 
  array (
    'trait' => 
    array (
      'value' => 'Starkiller Base',
      'source' => 'swudb',
    ),
  ),
  'JTL_026' => 
  array (
    'trait' => 
    array (
      'value' => 'Yavin 4',
      'source' => 'swudb',
    ),
  ),
  'JTL_027' => 
  array (
    'trait' => 
    array (
      'value' => 'Nadiri',
      'source' => 'swudb',
    ),
  ),
  'JTL_028' => 
  array (
    'trait' => 
    array (
      'value' => 'Ryloth',
      'source' => 'swudb',
    ),
  ),
  'JTL_029' => 
  array (
    'trait' => 
    array (
      'value' => 'Atollon',
      'source' => 'swudb',
    ),
  ),
  'JTL_030' => 
  array (
    'trait' => 
    array (
      'value' => 'Tatooine',
      'source' => 'swudb',
    ),
  ),
  'JTL_031' => 
  array (
    'trait' => 
    array (
      'value' => 'Naboo',
      'source' => 'swudb',
    ),
  ),
  'LAW_019' => 
  array (
    'trait' => 
    array (
      'value' => 'Lowick',
      'source' => 'swudb',
    ),
  ),
  'LAW_020' => 
  array (
    'trait' => 
    array (
      'value' => 'Tatooine',
      'source' => 'swudb',
    ),
  ),
  'LAW_021' => 
  array (
    'trait' => 
    array (
      'value' => 'Kessel',
      'source' => 'swudb',
    ),
  ),
  'LAW_022' => 
  array (
    'trait' => 
    array (
      'value' => 'Aldhani',
      'source' => 'swudb',
    ),
  ),
  'LAW_023' => 
  array (
    'trait' => 
    array (
      'value' => 'Tatooine',
      'source' => 'swudb',
    ),
  ),
  'LAW_024' => 
  array (
    'trait' => 
    array (
      'value' => 'Lothal',
      'source' => 'swudb',
    ),
  ),
  'LAW_025' => 
  array (
    'trait' => 
    array (
      'value' => 'Quarzite',
      'source' => 'swudb',
    ),
  ),
  'LAW_026' => 
  array (
    'trait' => 
    array (
      'value' => 'Bracca',
      'source' => 'swudb',
    ),
  ),
  'LAW_027' => 
  array (
    'trait' => 
    array (
      'value' => 'Stygeon Prime',
      'source' => 'swudb',
    ),
  ),
  'LAW_028' => 
  array (
    'trait' => 
    array (
      'value' => 'Cantonica',
      'source' => 'swudb',
    ),
  ),
  'LAW_029' => 
  array (
    'trait' => 
    array (
      'value' => 'Scarif',
      'source' => 'swudb',
    ),
  ),
  'LAW_030' => 
  array (
    'trait' => 
    array (
      'value' => 'Segra Milo',
      'source' => 'swudb',
    ),
  ),
  'LOF_019' => 
  array (
    'trait' => 
    array (
      'value' => 'Lothal',
      'source' => 'swudb',
    ),
  ),
  'LOF_020' => 
  array (
    'trait' => 
    array (
      'value' => 'Dathomir',
      'source' => 'swudb',
    ),
  ),
  'LOF_021' => 
  array (
    'trait' => 
    array (
      'value' => 'Coruscant',
      'source' => 'swudb',
    ),
  ),
  'LOF_022' => 
  array (
    'trait' => 
    array (
      'value' => 'Mortis',
      'source' => 'swudb',
    ),
  ),
  'LOF_023' => 
  array (
    'trait' => 
    array (
      'value' => 'Coruscant',
      'source' => 'swudb',
    ),
  ),
  'LOF_024' => 
  array (
    'trait' => 
    array (
      'value' => 'Starlight Beacon',
      'source' => 'swudb',
    ),
  ),
  'LOF_025' => 
  array (
    'trait' => 
    array (
      'value' => 'Malachor',
      'source' => 'swudb',
    ),
  ),
  'LOF_026' => 
  array (
    'trait' => 
    array (
      'value' => 'Mustafar',
      'source' => 'swudb',
    ),
  ),
  'LOF_027' => 
  array (
    'trait' => 
    array (
      'value' => 'Dathomir',
      'source' => 'swudb',
    ),
  ),
  'LOF_028' => 
  array (
    'trait' => 
    array (
      'value' => 'Zeffo',
      'source' => 'swudb',
    ),
  ),
  'LOF_029' => 
  array (
    'trait' => 
    array (
      'value' => 'Ilum',
      'source' => 'swudb',
    ),
  ),
  'LOF_030' => 
  array (
    'trait' => 
    array (
      'value' => 'Jedha',
      'source' => 'swudb',
    ),
  ),
  'SEC_019' => 
  array (
    'trait' => 
    array (
      'value' => 'Ferrix',
      'source' => 'swudb',
    ),
  ),
  'SEC_020' => 
  array (
    'trait' => 
    array (
      'value' => 'Coruscant',
      'source' => 'swudb',
    ),
  ),
  'SEC_021' => 
  array (
    'trait' => 
    array (
      'value' => 'Hosnian Prime',
      'source' => 'swudb',
    ),
  ),
  'SEC_022' => 
  array (
    'trait' => 
    array (
      'value' => 'Coruscant',
      'source' => 'swudb',
    ),
  ),
  'SEC_023' => 
  array (
    'trait' => 
    array (
      'value' => 'Narkina 5',
      'source' => 'swudb',
    ),
  ),
  'SEC_024' => 
  array (
    'trait' => 
    array (
      'value' => 'Coruscant',
      'source' => 'swudb',
    ),
  ),
  'SEC_025' => 
  array (
    'trait' => 
    array (
      'value' => 'Coruscant',
      'source' => 'swudb',
    ),
  ),
  'SEC_026' => 
  array (
    'trait' => 
    array (
      'value' => 'Wayland',
      'source' => 'swudb',
    ),
  ),
  'SHD_019' => 
  array (
    'trait' => 
    array (
      'value' => 'Nevarro',
      'source' => 'swudb',
    ),
  ),
  'SHD_020' => 
  array (
    'trait' => 
    array (
      'value' => 'Sorgan',
      'source' => 'swudb',
    ),
  ),
  'SHD_021' => 
  array (
    'trait' => 
    array (
      'value' => 'Takodana',
      'source' => 'swudb',
    ),
  ),
  'SHD_022' => 
  array (
    'trait' => 
    array (
      'value' => 'Nevarro',
      'source' => 'swudb',
    ),
  ),
  'SHD_023' => 
  array (
    'trait' => 
    array (
      'value' => 'Concordia',
      'source' => 'swudb',
    ),
  ),
  'SHD_024' => 
  array (
    'trait' => 
    array (
      'value' => 'Kessel',
      'source' => 'swudb',
    ),
  ),
  'SHD_025' => 
  array (
    'trait' => 
    array (
      'value' => 'Corellia',
      'source' => 'swudb',
    ),
  ),
  'SHD_026' => 
  array (
    'trait' => 
    array (
      'value' => 'Tatooine',
      'source' => 'swudb',
    ),
  ),
  'SOR_019' => 
  array (
    'trait' => 
    array (
      'value' => 'Scarif',
      'source' => 'swudb',
    ),
  ),
  'SOR_020' => 
  array (
    'trait' => 
    array (
      'value' => 'Lothal',
      'source' => 'swudb',
    ),
  ),
  'SOR_021' => 
  array (
    'trait' => 
    array (
      'value' => 'Dagobah',
      'source' => 'swudb',
    ),
  ),
  'SOR_022' => 
  array (
    'trait' => 
    array (
      'value' => 'Eadu',
      'source' => 'swudb',
    ),
  ),
  'SOR_023' => 
  array (
    'trait' => 
    array (
      'value' => 'Death Star',
      'source' => 'swudb',
    ),
  ),
  'SOR_024' => 
  array (
    'trait' => 
    array (
      'value' => 'Hoth',
      'source' => 'swudb',
    ),
  ),
  'SOR_025' => 
  array (
    'trait' => 
    array (
      'value' => 'Lothal',
      'source' => 'swudb',
    ),
  ),
  'SOR_026' => 
  array (
    'trait' => 
    array (
      'value' => 'Jedha',
      'source' => 'swudb',
    ),
  ),
  'SOR_027' => 
  array (
    'trait' => 
    array (
      'value' => 'Vardos',
      'source' => 'swudb',
    ),
  ),
  'SOR_028' => 
  array (
    'trait' => 
    array (
      'value' => 'Jedha',
      'source' => 'swudb',
    ),
  ),
  'SOR_029' => 
  array (
    'trait' => 
    array (
      'value' => 'Cloud City',
      'source' => 'swudb',
    ),
  ),
  'SOR_030' => 
  array (
    'trait' => 
    array (
      'value' => 'Atollon',
      'source' => 'swudb',
    ),
  ),
  'TS26_09' => 
  array (
    'trait' => 
    array (
      'value' => 'Coruscant',
      'source' => 'swudb',
    ),
  ),
  'TS26_10' => 
  array (
    'trait' => 
    array (
      'value' => 'Serenno',
      'source' => 'swudb',
    ),
  ),
  'TS26_11' => 
  array (
    'trait' => 
    array (
      'value' => 'Geonosis',
      'source' => 'swudb',
    ),
  ),
  'TS26_12' => 
  array (
    'trait' => 
    array (
      'value' => 'Mandalore',
      'source' => 'swudb',
    ),
  ),
  'TWI_019' => 
  array (
    'trait' => 
    array (
      'value' => 'Utapau',
      'source' => 'swudb',
    ),
  ),
  'TWI_020' => 
  array (
    'trait' => 
    array (
      'value' => 'Mandalore',
      'source' => 'swudb',
    ),
  ),
  'TWI_021' => 
  array (
    'trait' => 
    array (
      'value' => 'Cristophsis',
      'source' => 'swudb',
    ),
  ),
  'TWI_022' => 
  array (
    'trait' => 
    array (
      'value' => 'Geonosis',
      'source' => 'swudb',
    ),
  ),
  'TWI_023' => 
  array (
    'trait' => 
    array (
      'value' => 'Vassek',
      'source' => 'swudb',
    ),
  ),
  'TWI_024' => 
  array (
    'trait' => 
    array (
      'value' => 'Kamino',
      'source' => 'swudb',
    ),
  ),
  'TWI_025' => 
  array (
    'trait' => 
    array (
      'value' => 'Zanbar',
      'source' => 'swudb',
    ),
  ),
  'TWI_026' => 
  array (
    'trait' => 
    array (
      'value' => 'Mustafar',
      'source' => 'swudb',
    ),
  ),
  'TWI_027' => 
  array (
    'trait' => 
    array (
      'value' => 'Onderon',
      'source' => 'swudb',
    ),
  ),
  'TWI_028' => 
  array (
    'trait' => 
    array (
      'value' => 'Geonosis',
      'source' => 'swudb',
    ),
  ),
  'TWI_029' => 
  array (
    'trait' => 
    array (
      'value' => 'Coruscant',
      'source' => 'swudb',
    ),
  ),
  'TWI_030' => 
  array (
    'trait' => 
    array (
      'value' => 'Oba Diah',
      'source' => 'swudb',
    ),
  ),
);
