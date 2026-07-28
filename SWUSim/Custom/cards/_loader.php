<?php
// Loads every per-card ability file under cards/<set>/. Each file appends into
// the same global ability arrays the monoliths use ($whenPlayedAbilities,
// $customDQHandlers, …); appends are order-independent, so runtime behavior is
// identical regardless of load order. Files whose basename begins with `_` are
// skipped (e.g. _index.generated.php, editor scratch).
//
// New-set files are bootstrapped by DevTools/scaffold-cards.php as empty stubs carrying
// a `// TODO: UNIMPLEMENTED` marker; an un-implemented stub registers nothing, so
// including it here is a harmless no-op until a human fills it in.
foreach (glob(__DIR__ . '/*/*.php') as $__cardFile) {
    if (basename($__cardFile)[0] === '_') continue;
    include_once $__cardFile;
}
