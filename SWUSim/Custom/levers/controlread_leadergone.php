<?php
// LEVER 'leadergone' (theme 'controlread', report OG3; owner 2026-10-19). 'leaderrisk' (p28) prices a deployed leader's loss in a trade at
// x0.3 because "it returns". Owner: a defeated leader is usually gone as a UNIT for the rest of the game — only Poe JTL_013, Trench JTL_014 and
// Grogu ASH_018 come out again. 0 of 44 deploys in the 23 Hemlock DV games redeployed. This undoes the x0.3 for every other leader.
SWUBotLeverRegister('controlread', 'leadergone', [
    'lossfactor' => function (float $f, array $att): ?float {
        if (empty($att['isLeader']) || !SWUBotFeatureOn('leaderrisk')) return null;
        if (in_array(strval($att['cardID'] ?? ''), SWU_BOT_LV_REDEPLOY_LEADERS, true)) return null;
        return $f / SWU_BOT_LEADERRISK_FACTOR;
    },
]);
