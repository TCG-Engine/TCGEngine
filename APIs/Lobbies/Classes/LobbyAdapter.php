<?php
// The per-sim seam for lobby behaviour.
//
// Before this existed, the lobby endpoints asked `rootName === 'SWUSim' && SWUFormatIsRoomFormat(...)`
// in six places. That made the whole room flow SWUSim-only, and it conflated two unrelated questions:
//
//   "does this lobby get a page?"   -> ROUTING   -> private && !localMode
//   "how many seats do I draw?"     -> RENDERING -> maxPlayers, teams
//
// Answering the first with a seat-count predicate is why a private 2-player game never got a lobby,
// and why twinsuns-preview (declared seats 2-2) silently never got one either.
//
// A sim opts in by adding a `waitingRoom` block to its SiteDef. No block = no waiting room; the page
// exists for every sim but redirects to MainMenu when the config resolves to null.
interface LobbyAdapter {
    // ROUTING. True iff this lobby gets a WaitingRoom page: private, and not a local/solo format.
    public function wantsWaitingRoom(object $lobby): bool;

    // RENDERING. ['maxPlayers' => int, 'teams' => ['red','blue']|null, 'queueType' => string]
    public function seatModel(object $lobby): array;

    // VALIDATION. ['ok' => bool, 'message' => string, 'identity' => ['cards' => [...]]]
    public function validateDeck(object $lobby, string $deckInput): array;

    // START GATE. [] = the host may start; otherwise a list of human-readable blockers.
    public function startBlockers(object $lobby): array;
}

// Resolve a sim's adapter from its SiteDef, or null when the sim has not opted in.
//
// Null is a NORMAL answer, not an error: most sims have no waitingRoom block, and every caller in the
// lobby endpoints treats null as "behave exactly as before". Nothing here throws — a misconfigured
// SiteDef must not take down JoinQueue for a sim that never wanted a lobby page.
//
// The adapter CLASS defaults to the rootName minus a trailing "Sim", plus "LobbyAdapter"
// (SWUSim -> SWULobbyAdapter). A sim whose class does not follow that convention sets
// waitingRoom.class explicitly. require_once + a class-name lookup is deliberate: having the adapter
// file `return new X()` instead would re-execute and fatally redeclare the class the second time a
// caller required it directly (which the tests do).
function LobbyAdapterFor(string $rootName): ?LobbyAdapter {
    static $cache = [];
    if (array_key_exists($rootName, $cache)) return $cache[$rootName];
    $cache[$rootName] = null;

    if ($rootName === '' || !preg_match('/^[A-Za-z0-9]+$/', $rootName)) return null;   // never a path
    $defPath = __DIR__ . '/../../../SharedUI/Sites/' . $rootName . '/SiteDef.php';
    if (!is_file($defPath)) return null;

    $def = require $defPath;                       // SiteDef.php returns a plain array
    if (!is_array($def)) return null;
    $adapterPath = $def['waitingRoom']['adapter'] ?? '';
    if (!is_string($adapterPath) || $adapterPath === '') return null;   // not opted in

    $path = __DIR__ . '/../../../' . $adapterPath;
    if (!is_file($path)) return null;
    require_once $path;

    $class = $def['waitingRoom']['class'] ?? (preg_replace('/Sim$/', '', $rootName) . 'LobbyAdapter');
    if (!is_string($class) || !class_exists($class)) return null;
    $obj = new $class();
    if (!($obj instanceof LobbyAdapter)) return null;

    $cache[$rootName] = $obj;
    return $obj;
}
