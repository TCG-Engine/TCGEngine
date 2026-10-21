<?php
// A copied Reveal ability uses the revealed card's name and characteristics for
// self references and bonus conditions. Keep the authored source unchanged.
function GAPrepareRevealCode($code, $cardID) {
    $identity = 'GARevealedCardIdentity(' . var_export($cardID, true) . ')';
    $code = preg_replace_callback('/(["\'])' . preg_quote($cardID, '/') . '\1/', fn($m) => $identity, $code);
    $code = preg_replace('/IsClassBonusActive\(\$player(?:,\s*\[[^\]]*\])?\)/', 'IsClassBonusActive($player, CardClasses(' . $identity . '))', $code);
    return $code;
}
