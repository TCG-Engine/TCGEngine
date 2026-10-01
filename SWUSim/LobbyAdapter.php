<?php
// SWUSim's LobbyAdapter — the sim-specific half of the shared waiting-room flow: which lobbies get a
// page, how many seats to draw, how a deck is validated, and what blocks the host from starting.
require_once __DIR__ . '/../AppCore/SWU/Formats.php';
require_once __DIR__ . '/../AppCore/SWU/CardImagePath.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/LobbyAdapter.php';
require_once __DIR__ . '/Custom/DeckImport.php';
require_once __DIR__ . '/../APIs/Lobbies/Classes/TeamRooms.php';
require_once __DIR__ . '/Custom/SetupPanels.php';   // SWUSetupTwinSunsPreCons

class SWULobbyAdapter implements LobbyAdapter, LobbyBotAdapter, LobbyBotPolicyAdapter {

    // ── Twin Suns "Fill Seat with Bot" (SWUSim/docs/todo-twinsuns-fill-bot.md) ──────────────────────────────────
    // A PUBLIC room offers bots only once nobody new has joined for this long (Decision 5); a private room at once.
    public const BOT_ADD_WAIT_PUBLIC = 60;

    // Free-for-all Twin Suns rooms only. Team Suns is out of scope for v1: the bot would read its teammate as an
    // opponent, and the room's team/seat picking has no bot path.
    private function _offersBots(object $lobby): bool {
        $format = strval($lobby->format ?? '');
        return $this->wantsWaitingRoom($lobby) && SWUFormatIsRoomFormat($format) && !SWUFormatIsTeamFormat($format);
    }

    // Profile = where the bot's deck comes from (Decision 3): one of the official Twin Suns pre-cons, or the
    // host's pasted list. Every room bot plays Arenabot's Normal stack (SWUSim/CreateGame.php).
    public function botProfiles(object $lobby): array {
        if (!$this->_offersBots($lobby)) return [];
        $out = [];
        // 'deck' tells the waiting room which control to draw: a pre-con row, or the paste box (the "Fill Seat with
        // Bot" popup). 'cards' = the pre-con's leaders + base in the roster's identity-strip shape, built from the
        // CardIDs alone — this runs on every 1.5s poll, so no deck is resolved here.
        foreach (SWUSetupTwinSunsPreCons() as $pc) {
            $out['precon:' . $pc['key']] = ['name' => 'Arenabot · ' . $pc['name'], 'description' => 'Plays the official ' . $pc['name'] . ' pre-con.',
                'deck' => 'precon', 'deckName' => $pc['name'],
                'cards' => $this->_identityCards(['leader' => $pc['leaders'], 'base' => $pc['base']])];
        }
        $out['custom'] = ['name' => 'Arenabot · your decklist', 'description' => 'Plays a Twin Suns list you paste.', 'deck' => 'paste'];
        return $out;
    }

    public function configureBot(object $lobby, Player $player, string $profile): void {
        if (!isset($this->botProfiles($lobby)[$profile])) throw new InvalidArgumentException('Unknown bot profile.');
        $player->setBotProfile($profile);
        $player->setReady(true);   // deckOk comes from the deck's validation (LobbyApplyBotDeck)
    }

    public function botDeckInput(object $lobby, string $profile, string $posted): string {
        if ($profile === 'custom') return trim($posted);
        if (strpos($profile, 'precon:') === 0) {
            $key = substr($profile, 7);
            foreach (SWUSetupTwinSunsPreCons() as $pc) if ($pc['key'] === $key) return strval($pc['input']);
        }
        return '';
    }

    // Decision 5. The wait runs from the LAST HUMAN join: a bot's own joinedAt never restarts it.
    public function botAddWaitSeconds(object $lobby, int $now): int {
        if (!empty($lobby->isPrivate)) return 0;
        $last = 0;
        foreach (($lobby->players ?? []) as $p) {
            if ($p instanceof Player && $p->getBotProfile() === '') $last = max($last, $p->getJoinedAt());
        }
        return $last > 0 ? max(0, $last + self::BOT_ADD_WAIT_PUBLIC - $now) : 0;
    }

    // Decision 6: a bot is a placeholder in every room that offers bots.
    public function botsYieldToHumans(object $lobby): bool {
        return $this->_offersBots($lobby);
    }

    // ROUTING: not local/solo, AND (private || a room format).
    //
    // Note what this does NOT ask for the PRIVATE case: seat count. "Does this lobby get a page?" and
    // "how many seats do I draw?" are different questions, and an older predicate
    // (rootName === 'SWUSim' && SWUFormatIsRoomFormat, i.e. maxPlayers > 2) answered the first with
    // the second, which denied a page to every private 2-player game.
    //
    // For the PUBLIC case the seat count IS the question (owner, 2026-09-20). The Twin Suns family now
    // takes public queues, and its queue is a public ROOM: you land in an open room, the creator hosts,
    // and only the host starts. Constructed's public queue stays quick-match — it pairs two players and
    // goes straight into the game, so there is nothing for a page to wait for.
    //
    // ⚠ The discriminator is the FORMAT (SWUFormatIsRoomFormat), never $lobby->maxPlayers. A lobby's
    // maxPlayers is data an old or hand-built record may carry wrong; the format registry is the truth.
    public function wantsWaitingRoom(object $lobby): bool {
        $f = SWUGetFormat($lobby->format ?? '');
        if ($f === null || !empty($f['localMode'])) return false;   // unknown / goldfish / hotseat -> no page
        if (!empty($lobby->isPrivate)) return true;                 // every private non-local lobby
        return SWUFormatIsRoomFormat($lobby->format ?? '');         // public: Twin Suns family only
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
        $errors = SWURoomStartBlockers($lobby, SWURoomLeaderSets($lobby));
        foreach (($lobby->players ?? []) as $p) {
            if ($p instanceof Player && $p->getBotProfile() !== '' && !isset($this->botProfiles($lobby)[$p->getBotProfile()])) {
                $errors[] = 'A bot in this room is no longer available.';
            }
        }
        return $errors;
    }
}
