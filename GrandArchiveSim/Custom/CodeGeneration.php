<?php
// Older GA rows used Modal("A:label&B:label", "prompt") and compared the
// returned letter. Normalize to the engine's indexed Modal protocol locally.
// Keep the hosted source unchanged and reject anything other than literal keys.
function GAPrepareLegacyModalCode($code) {
    return preg_replace_callback('/(\$\w+)\s*=\s*await\s+(\$\w+)\.Modal\(([^\n]+)\);/', function($m) {
        $args = ParseAwaitArgs($m[3]);
        if (count($args) !== 2) return $m[0];
        if (!preg_match('/^(["\'])(.*)\1$/D', trim($args[0]), $literal)) return $m[0];
        $keys = []; $labels = [];
        foreach (explode('&', $literal[2]) as $option) {
            if (!preg_match('/^([A-Za-z0-9]+):(.+)$/D', $option, $parts)) return $m[0];
            $keys[] = $parts[1]; $labels[] = $parts[2];
        }
        return $m[1] . ' = await ' . $m[2] . '.Modal(1, 1, ' . var_export(implode('&', $labels), true) . ', ' . $args[1] . ");\n"
            . $m[1] . ' = ' . var_export($keys, true) . '[' . $m[1] . '] ?? "-";';
    }, $code);
}
// A copied Reveal ability uses the revealed card's name and characteristics for
// self references and bonus conditions. Keep the authored source unchanged.
function GAPrepareRevealCode($code, $cardID) {
    $identity = 'GARevealedCardIdentity(' . var_export($cardID, true) . ')';
    $code = preg_replace_callback('/(["\'])' . preg_quote($cardID, '/') . '\1/', fn($m) => $identity, $code);
    $code = preg_replace('/IsClassBonusActive\(\$player(?:,\s*\[[^\]]*\])?\)/', 'IsClassBonusActive($player, CardClasses(' . $identity . '))', $code);
    return $code;
}
