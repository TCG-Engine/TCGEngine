<?php
// SWUSim's LobbyAdapter — the sim-specific half of the shared waiting-room flow: which lobbies get a
// page, how many seats to draw, how a deck is validated, and what blocks the host from starting.
require_once __DIR__ . '/../AppCore/SWU/Formats.php';
require_once __DIR__ . '/../AppCore/SWU/CardImagePath.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/LobbyAdapter.php';
require_once __DIR__ . '/Custom/DeckImport.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/TeamRooms.php';

class SWULobbyAdapter implements LobbyAdapter {

    // ROUTING: private && not local/solo.
    //
    // Note what this does NOT ask: seat count. "Does this lobby get a page?" and "how many seats do I
    // draw?" are different questions, and the predicate this replaces
    // (rootName === 'SWUSim' && SWUFormatIsRoomFormat, i.e. maxPlayers > 2) answered the first with
    // the second. That denied a lobby to every private 2-player game, and to twinsuns-preview, which
    // is declared seats 2-2 despite being Twin Suns.
    public function wantsWaitingRoom(object $lobby): bool {
        if (empty($lobby->isPrivate)) return false;              // public queue -> quick match, no page
        $f = SWUGetFormat($lobby->format ?? '');
        return $f !== null && empty($f['localMode']);            // goldfish / hotseat -> no page
    }

    // RENDERING: how many seats to draw, and whether they split into teams.
    public function seatModel(object $lobby): array {
        $f = SWUGetFormat($lobby->format ?? '');
        return [
            'maxPlayers' => $f !== null ? intval($f['maxPlayers']) : 2,
            'teams'      => ($f !== null && !empty($f['teams'])) ? ['red', 'blue'] : null,
            // Carried so Spec 2's per-match queueType choice slots in without a payload change.
            'queueType'  => strval($lobby->queueType ?? 'bo1'),
        ];
    }

    // Resolve + format-check a deck, and build the roster's identity strip from it.
    // ⚠ Every failure path returns an EMPTY identity. A stale identity keeps a seat advertising a
    // deck it no longer has, which is worse than showing nothing at all.
    public function validateDeck(object $lobby, string $deckInput): array {
        $empty = ['cards' => []];
        if (!function_exists('SWUResolveDeckInput') || !function_exists('SWUCheckFormat')) {
            return ['ok' => false, 'message' => 'Deck validation is temporarily unavailable.', 'identity' => $empty];
        }
        if (trim($deckInput) === '') {
            return ['ok' => false, 'message' => 'Deck link is required.', 'identity' => $empty];
        }
        $r = SWUResolveDeckInput($deckInput);
        if (empty($r['success'])) {
            return ['ok' => false, 'message' => $r['message'] ?? 'Could not read deck.', 'identity' => $empty];
        }
        // Checked against the LOBBY'S format, never a hardcoded one — a Team Suns seat editing its
        // deck must be checked as teamsuns, and a Twin Suns lobby must reject a 1-leader deck.
        $errs = SWUCheckFormat($lobby->format ?? 'premier', $r['leader'] ?? '', $r['base'] ?? '',
                               $r['mainDeck'] ?? [], $r['sideboard'] ?? []);
        if (!empty($errs)) {
            return ['ok' => false, 'message' => implode('; ', array_slice($errs, 0, 3)), 'identity' => $empty];
        }
        return ['ok' => true, 'message' => '', 'identity' => ['cards' => $this->_identityCards($r)]];
    }

    // SWU aspect -> ring colour. The page never learns what an "aspect" is; it just draws a ring from
    // the colour list, which is why another sim can fill it with whatever its identity means.
    //
    // ⚠ THE KEY ORDER IS LOAD-BEARING. It is the canonical aspect order
    // (Vigilance < Command < Aggression < Cunning < Villainy < Heroism) and _aspectColors() sorts
    // against it. Reordering these entries silently reorders every dual-aspect ring in the game.
    //
    // Canonical is the DEFAULT, not the whole story: CardAspect()'s order does not reliably track the
    // printed art, so sorting gives every card a predictable ring, and ASPECT_ORDER_OVERRIDES below
    // restores the handful whose art genuinely differs. That keeps two leaders sharing an aspect pair
    // visually comparable without lying about the cards that really do print differently.
    private const ASPECT_COLORS = [
        'vigilance'  => '#3b7dd8',   // blue
        'command'    => '#2e9e4f',   // green
        'aggression' => '#c0392b',   // red
        'cunning'    => '#e2b13c',   // yellow
        'villainy'   => '#141414',   // black
        'heroism'    => '#e8e4d8',   // white
    ];
    private const NEUTRAL_COLOR = '#4b5b6d';   // aspect-less (the one base with no aspect at all)

    // Cards whose PRINTED icon order genuinely differs from the canonical order, verified against the
    // card art one at a time.
    //
    // ⚠ Do NOT bulk-populate this from CardAspect()'s order. That field's order varies for reasons
    // that do not reliably track the art: LAW_001 Saw Gerrera (Command,Aggression), ASH_001 The
    // Armorer (Vigilance,Command) and ASH_002 Fennec Shand (Aggression,Cunning) all print in the
    // canonical order and need no entry, while several SHD leaders whose data reads non-canonically
    // have not been checked against their art at all. An entry belongs here only once someone has
    // LOOKED at the card.
    //
    // An override is ignored unless it names exactly the aspects the card actually has, so a stale
    // entry (errata, re-key) degrades to canonical rather than drawing something invented.
    private const ASPECT_ORDER_OVERRIDES = [
        'LAW_002' => ['cunning', 'vigilance'],   // Tobias Beckett prints Cunning above Vigilance
    ];

    // The ring colours for one card, in CANONICAL aspect order, DEDUPED.
    //
    // Sorted, not printed-order. The printed order varies per CARD (see the note on ASPECT_COLORS —
    // Beckett prints Cunning above Vigilance where most of that pair print the reverse), so sorting
    // makes the ring a property of WHICH aspects a card has rather than of how that particular card
    // happened to lay its icons out. Chosen for comparability across seats, at the cost of exact
    // fidelity to ~9 cards' art.
    //
    // Deduping is what gives DJ (SEC_018, "Cunning,Cunning") a smooth single ring rather than two
    // identical halves. TWI_017 Chancellor Palpatine is the only 3-aspect card in the game, so the
    // page splits into N equal segments rather than special-casing two.
    private function _aspectColors(string $cid): array {
        $raw   = function_exists('CardAspect') ? (string)CardAspect($cid) : '';
        $order = array_keys(self::ASPECT_COLORS);   // canonical: Vigilance -> Heroism
        $keys  = [];
        foreach (explode(',', $raw) as $a) {
            $key = strtolower(trim($a));
            if ($key === '' || !isset(self::ASPECT_COLORS[$key])) continue;
            if (!in_array($key, $keys, true)) $keys[] = $key;
        }
        // A verified per-card override wins over the canonical sort, but only when it describes the
        // SAME set of aspects the card actually has.
        $ov = self::ASPECT_ORDER_OVERRIDES[$cid] ?? null;
        $useOverride = is_array($ov) && count($ov) === count($keys) && empty(array_diff($ov, $keys)) && empty(array_diff($keys, $ov));
        if ($useOverride) $keys = array_values($ov);
        else usort($keys, fn($x, $y) => array_search($x, $order, true) <=> array_search($y, $order, true));
        $out = [];
        foreach ($keys as $key) $out[] = self::ASPECT_COLORS[$key];
        return $out !== [] ? $out : [self::NEUTRAL_COLOR];
    }

    // Leaders then base, as [{id,name,url,kind,colors}] — a GENERIC shape the shared page renders
    // without knowing what a leader is. `kind` is informational; `colors` drives the ring.
    //
    // ⚠ Art URLs go through SWUCardImagePath(), the ONE seam that applies the mock_ filename prefix
    // preview-set art needs. Building the path by hand is how every HMW thumbnail 404s.
    private function _identityCards(array $resolved): array {
        $out = [];
        $add = function ($cid, $kind) use (&$out) {
            $cid = (string)$cid;
            if ($cid === '') return;
            $name = function_exists('CardTitle') ? (string)CardTitle($cid) : '';
            if ($name !== '' && function_exists('CardSubtitle')) {
                $sub = (string)CardSubtitle($cid);
                if ($sub !== '') $name .= ', ' . $sub;
            }
            $out[] = [
                'id'     => $cid,
                'name'   => $name !== '' ? $name : $cid,   // no dictionary -> fall back to the id
                'url'    => SWUCardImagePath($cid, 'card'),
                'kind'   => $kind,
                'colors' => $this->_aspectColors($cid),
            ];
        };
        // 'leader' is a single CardID in standard formats and an ARRAY in Twin Suns / Team Suns.
        foreach ((array)($resolved['leader'] ?? []) as $l) $add($l, 'leader');
        $add($resolved['base'] ?? '', 'base');
        return $out;
    }

    public function startBlockers(object $lobby): array {
        if (!function_exists('SWURoomStartBlockers')) return [];
        return SWURoomStartBlockers($lobby, SWURoomLeaderSets($lobby));
    }
}
