<?php
// Core/Match/Hooks.php — declarative per-sim Match hook registry + dispatch.
// A sim registers its adapter once (from its MatchHooks.php); the shared
// framework calls hooks by name, keyed by rootName.

if (!isset($GLOBALS['MATCH_HOOKS'])) { $GLOBALS['MATCH_HOOKS'] = []; }

// Hooks the framework will call as callables (vs. plain config values).
const MATCH_REQUIRED_HOOKS = ['resolveLobbyDecks', 'validateDeck', 'setupGame'];
const MATCH_OPTIONAL_HOOKS = ['recordDeckStats', 'captureGameDetail', 'submitResults',
                              'buildStatsHtml', 'flashMatchResult', 'arePlayersBlocked'];

function MatchRegisterHooks($rootName, array $hooks) {
    $GLOBALS['MATCH_HOOKS'][$rootName] = $hooks;
}

function MatchHookExists($rootName, $name) {
    return isset($GLOBALS['MATCH_HOOKS'][$rootName][$name])
        && is_callable($GLOBALS['MATCH_HOOKS'][$rootName][$name]);
}

// Invoke a callable hook. Required hook missing => exception (fail loud).
// Optional hook missing => null (benign no-op).
function MatchHook($rootName, $name, ...$args) {
    if (MatchHookExists($rootName, $name)) {
        return call_user_func_array($GLOBALS['MATCH_HOOKS'][$rootName][$name], $args);
    }
    if (in_array($name, MATCH_REQUIRED_HOOKS, true)) {
        throw new \RuntimeException("Match: required hook '$name' not registered for '$rootName'");
    }
    return null; // optional
}

// Read a non-callable config value (queueTypes / sideboardUrl / sideboardSeconds).
function MatchConfig($rootName, $name, $default) {
    $v = $GLOBALS['MATCH_HOOKS'][$rootName][$name] ?? null;
    return ($v === null) ? $default : $v;
}
