<?php
// SWU-PGN reader — opens `.swupgn` files (a portable record of one Star Wars: Unlimited game),
// checks them, rebuilds the board at any moment, and turns them into a readable story.
//
// SWU-PGN is a community standard authored by Travis Chase. This reader is our own implementation,
// written from the SWU-PGN/1.0 spec text alone; every rule below cites it as "SWU-PGN/1.0 spec §N".
// It needs no engine, no card database and no DB: a file is folded from its own records.
//
// Files come from users and from other engines, so everything here treats input as untrusted:
// nothing throws except SwuPgnParse()'s §24 fatal codes (a malformed file), and every list the fold
// grows is capped (SWUPGN_MAX_* below) so a crafted file cannot cause unbounded work.
//
// ─── API ─────────────────────────────────────────────────────────────────────────────────────────
// Parse (§4, §5, §23, §24 fatal codes, Appendix B)                       SwuPgnParse.php
//   SwuPgnParse(string $text, array $caps = []): array   doc; a malformed file throws SwuPgnParseException
//                                              (->errorCode = the §24 fatal code, ->lineNumber, message "SWU-PGN: …");
//                                              over a cap it is REJECTED, never truncated ([P-05])
//   SwuPgnTryParse(string $text, array $caps = []): array   ['doc' => ?array, 'error' => ?string, 'code' => ?string]
//   SwuPgnParseCaps(array $over = []): array   ['bytes' => 16 MiB, 'line' => 1 MiB, 'records' => 1e6, 'depth' => 64] (§23)
//   SwuPgnRequiredHeaderTags(): array          the 17 §5.1 tags, in spec order
//   SwuPgnCountTags(): array / SwuPgnIsCount($v): bool   §5.2 count tags / base-10 digits within 2^53−1
//   doc = ['headers' => [tag => value], 'headerLines' => [tag => line], 'story' => ?list<string> (verbatim, blank
//          lines at its start/end dropped; null = no STORY), 'decks'|'cards'|'setup'|'events'|'annotations' => list
//          of decoded records, 'lines' => [section => parallel list of 1-based line numbers],
//          'notObject' => [section => [index => true]] (JSON but not an object), 'sections' => banners in file order
//          (unknown ones included; their lines are skipped unparsed), 'dropped' => [] (always empty)]
//   Records are PHP arrays; an EMPTY JSON object stays a stdClass so `{}` and `[]` remain distinct. An integral
//   number (1.0, 1e2) reads as an integer.
//
// Ids (§6)                                                               SwuPgnIds.php
//   SwuPgnBaseId(string $id): string           strip the trailing `:N` copy suffix
//   SwuPgnCopyNumber(string $id): ?int         the N of `:N`, null for a first copy
//   SwuPgnTokenParts(string $id): ?array       TOKEN:<name>#<cardId>[:N] → name/cardId/copy/resolvable
//   SwuPgnTokenIdOk(string $id): bool          Appendix B token-card grammar ([V-05] bad-token-name)
//   SwuPgnTokenNameOk(string $name): bool      §6.1 token-name grammar (a STATUS_TOKEN.token)
//   SwuPgnEffectIdOk($id): bool                §6.1 fx@N (N ≥ 1, no leading zero)
//   SwuPgnReservedNames(): array               §23 the thirteen reserved names
//   _SwuPgnReservedToken($id): ?string         §6.1 'credit' / 'the-force' by the name part, else null
//   SwuPgnSeatOfBaseRef($ref): ?int            the N of `base@N`, else null
//   SwuPgnCardIndex(array $doc): array         %%% CARDS as [baseId => display name]
//   SwuPgnCardBackIndex(array $doc): array     %%% CARDS as [baseId => backSideName], for cards with two names
//   SwuPgnName(array $index, $id, ?array $faces = null): string   §16 nm(): name, ` #N` for a copy, `Player N's base`;
//                                              with $faces, a card whose back side is up reads by its backSideName
//   SwuPgnWho($p): string                      §16 who(): "Player 1" / "Player 2" / ""
//
// Fold (§11, §12, §13)                                                   SwuPgnFold.php
//   SwuPgnEmptyState(): array                  §11 starting board (initiativeTaken false)
//     board keys (§11, absent until a record/keyframe supplies them): root `active`, `effects`
//     [{id, src, p, targets?, seat?, duration}]; seat `abilityUses` [{card, ability, count, per, max}],
//     `foreignResources`; card `traits` (sorted) / `blank` / `leaderUnit` (true only) / `copyOf` / `name` /
//     `cost` / `aspects`, `owner` (only ≠ holder), `upgradeControllers` {upgradeId: seat} (never empty)
//   SwuPgnZones(): array / SwuPgnIsArena($z)   §6.2 zone vocabulary; only ground/space are in play
//   SwuPgnReduce(array $s, $event, ?array &$w): array   one §12 delta (no keyframe snapping)
//   SwuPgnFold(array $events, ?array &$w, ?array $start = null): array   the board after all events
//     $w (fold warnings): [['seq' => ?string, 'message' => string], …] — unknown types (once each),
//     ignored non-conformant MOVEs, damaged keyframes, list limits reached.
//   SwuPgnStateAt(array $events, string $seq, ?array &$w): array   §12.3, starts at the last keyframe
//   SwuPgnKeyframeProblem($keyframe): ?string  null if snappable, else why it is damaged (§13)
//   SwuPgnTimeline(array $events, ?array $start = null): array   build once for scrubbing (checkpointed fold)
//                                              $start: the board before event 0 (default SwuPgnEmptyState());
//                                              a reader with card data seeds base HP / deck size here
//   SwuPgnTimelineStateAt(array $tl, int $pos): array   board after event #pos (-1 = before any)
//   SwuPgnTimelineIndexOf(array $tl, string $seq): ?int position of a seq
//   SwuPgnStateToJson(array $state, bool $pretty = false): string   `{}` where the model has objects
//                                              (players, statusTokens, upgradeControllers — the last never emitted empty)
//
// Integrity (§14)                                                        SwuPgnIntegrity.php
//   SwuPgnCheckKeyframes(array $events): array ['ok' => bool, 'mismatches' => [[seq, path, expected?, got?]]]
//     `expected` (keyframe) / `got` (fold) is left OUT when that side does not have the field. Whole-list
//     paths: "effects" (once), "players.N.abilityUses" (once per seat).
//
// Validate (§2.1, §3 [V-*], §5, §6, §10, §15, §18, §24)                  SwuPgnValidate.php
//   SwuPgnValidate(array $doc, array $opts = []): array   ['ok' => no errors, 'level' => malformed|well-formed|valid|
//                                              verified|complete, 'errors' => [...], 'warnings' => [...], 'notices' => [...]]
//     each issue: ['code' (a §24 name), 'message', 'line' => ?int, 'seq' => ?string]; fatal codes are listed as errors.
//     notices: §5.3/§16 things to surface that are no §24 issue (engine-unknown, engine-dirty, unseeded, story-drift).
//     Folds (SwuPgnCheckKeyframes) only when there are no errors. $opts['pilotProducts'] overrides the §25 registry.
//   SwuPgnValidateText(string $text, array $opts = []): array   parse + validate; unparseable → level 'malformed'
//                                              with the fatal code; $opts['caps'] → SwuPgnParse caps
//   SwuPgnIssueCodes(): array                  §24 code => [fatal|error|warning, level at most]
//   SwuPgnLevels(): array                      §2.1 levels, lowest first
//   SWUPGN_PILOT_WRITER_PRODUCTS               §25 pilot writer products (empty; the owner fills it in)
//   SwuPgnCheckVersion($gameTag): string       'ok' | 'newer-minor' | 'unsupported' (§18)
//   SwuPgnDefeatReasons(): array               §6.4 the closed DEFEAT.reason set
//
// Render (§16)                                                           SwuPgnRender.php
//   SwuPgnRender(array $doc, ?array $names = null): string   the %%% STORY text (lines joined "\n")
//   SwuPgnRenderEvents(array $events, array $names, array $backs = []): string   (capped at 16,777,216 units)
//   SwuPgnStoryMatches(array $doc, ?array $names = null): array  ['present', 'matches', 'line' => first diff]
//   SwuPgnIsNumberedAction(array $e): bool     §16 — is this a player's own (numbered) action
//   SwuPgnStoryLine(array $e, array $names, ?array $faces = null): ?string   §16 — one event's story line, or null
//   SwuPgnFacesStart(array $backs) / SwuPgnFacesStep(array $faces, array $e)   what nm() knows as the story goes:
//                                              the side that is up and each copy's name (per full id), effect sources
//   SwuPgnCutName(string $s): string           §16 "cut like a card name": controls/bidi → space, > 120 UTF-16 → 119 + "…"
//   SwuPgnStoryText(string $s): string         §16 layout: controls/bidi → space (any other printed field)
//   SwuPgnPlainMarkup(string $s): string       §10.1 icon markup → plain text (plain(text) of EFFECT_START)
//
// Steps (replay viewer)                                                  SwuPgnSteps.php
//   SwuPgnSteps(array $doc, ?int $seat = null): array
//                                              ['steps' => [n, kind, seq, round, phase, actor, pos,
//                                              caption, lines], 'rounds' => [round => first step]];
//                                              $seat 1/2 = that seat's view: captions from that seat's own-view
//                                              cut (a card it never saw reads "a card"); same steps in every view
//
// Write (§4) and own-view cut (internal; §21.1)                          SwuPgnWriterDoc.php, SwuPgnPerspective.php
//   SwuPgnWriteText(array $doc): string        doc (the parser's shape) → .swupgn text, canonical section order
//   SwuPgnCutPerspective(array $doc, int $seat): array   that seat's file: the other seat's hidden cards "HIDDEN",
//                                              their public copies renumbered by appearance, their deck lists and
//                                              keyframe hand/resources left out, Seed "unseeded", Perspective set;
//                                              §21.1: their LOOK.cards / SEARCH.seen+found / blind CHOICE.offered /
//                                              hidden EFFECT targets hidden; NAME kept
//
// Where the spec text leaves a choice open, this reader:
//   - treats a MOVE whose `p` is missing OR not 1/2 as "no p" (§12.1: only a tracked card's zone
//     changes — taken literally, so such an arena exit leaves the card in cards[] with its new zone);
//   - places cards (PLAY, DEPLOY_LEADER, CREATE_TOKEN) in ground/space only (§10.1 "the fold places
//     arena zones only");
//   - reads `epic: true` and `kind: "epic"` alike on ABILITY_ACTIVATE (§10.1 "equivalent");
//   - creates the seat's leader on a LEADER_FLIP that finds none (no keyframe yet);
//   - on a resource TAKE_CONTROL moves the count AND the card's resources[] membership: §12.2 lists
//     only the count, but §10.1 says the record "re-seats the card itself" and §14 gates membership;
//   - keeps an EFFECT_START `seat` only when 1 or 2 (§12.2's guard; §11's type also allows "both");
//     keeps a record's string `targets` (≤ 1000); reads a non-list EFFECT_END `targets` as none;
//   - on a resource TAKE_CONTROL without `from`, touches p's foreignResources only for an `owner` of 1|2;
//   - sets STATS `cost` only when it is an integer; leaves a keyframe's keywords/traits/aspects length unchecked
//     (§13's damaged list does not name them); takes an empty PHP array where §13 asks for an object;
//   - in §14 reads an `owner` that names the holder seat as absent;
//   - counts the degraded `TOKEN:credit` / `TOKEN:the-force` forms as the reserved tokens;
//   - ignores a negative EXHAUST_/READY_RESOURCES amount, and clamps shields at 0 on SHIELD_GAIN too;
//   - treats a keyframe whose `cards`/`hand`/`discard` is ABSENT, or that is `null`, as damaged;
//   - in §14 reads the fold's absent initiativeTaken / baseEpicActionUsed as false;
//   - accepts a same-zone MOVE only inside an arena AND with a `kind` (§10.1: a rebind "carries the new role");
//   - in §16, prints `(cost 0)` and prints PLAY_UPGRADE / PLAY_EVENT's `(cost N)` only with an integer cost (like
//     PLAY's rule), takes the banner's initiative from the round's keyframe (none → no initiative part);
//   - in §16 nm(): cuts name + ` #N` as one string; a cut through a surrogate pair writes U+FFFD (what UTF-8 makes of
//     the lone half); DEL (U+007F) is kept (neither C0 nor C1); a copy's name ends at a MOVE from an arena to a
//     non-arena zone (ground↔space keeps it) or a STATS lacking copyOf OR name; a ROUND_START keyframe replaces every
//     copy name; plain(text) capitalises a keyword's first letter and reads only the six §6.4 aspects as tokens;
//   - in §16, counts the story cap as each line's UTF-16 length + 1 and checks it only before a record that prints.
// ─────────────────────────────────────────────────────────────────────────────────────────────────

// Reader limits for untrusted input. A real game is ~550–1000 events with a few dozen cards per
// seat; these are far above that and only exist to bound the work a crafted file can cause.
const SWUPGN_MAX_EVENTS = 20000;          // EVENTS records kept by the parser and folded
const SWUPGN_MAX_SECTION_RECORDS = 5000;  // DECKS / CARDS / SETUP / ANNOTATIONS records kept
const SWUPGN_MAX_STORY_LINES = 100000;
const SWUPGN_MAX_JSON_DEPTH = 64;         // deeper JSON is reported as invalid JSON
// §12 "Every list the fold grows is capped at 1000 entries" — the fold caps are the spec's, never lower
// (a lower cap would drop what a conformant file holds); a keyframe over them is damaged (§13).
const SWUPGN_MAX_LIST = 1000;             // hand, discard, resources, foreignResources, baseUpgrades/baseCaptured per seat
const SWUPGN_MAX_CARDS_PER_SEAT = 1000;   // in-play cards per seat
const SWUPGN_MAX_ATTACHED = 1000;         // upgrades / captured per card
const SWUPGN_MAX_SEAT_ATTACHED = 1000;    // upgrades + captured across one seat's cards ([F-05])
const SWUPGN_MAX_TOKEN_KINDS = 1000;      // statusTokens keys per card
const SWUPGN_MAX_KEYWORDS = 1000;         // keywords / traits / aspects / effect targets taken from one record
const SWUPGN_MAX_EFFECTS = 1000;          // board effects
const SWUPGN_MAX_ABILITY_USES = 1000;     // abilityUses entries per seat
const SWUPGN_MAX_WARNINGS = 200;          // fold warnings kept
const SWUPGN_MAX_ISSUES = 1000;           // validate() issues kept (§24: a cap of at least 1000)
const SWUPGN_MAX_MISMATCHES = 1000;       // checkKeyframes() mismatches kept
const SWUPGN_TIMELINE_STRIDE = 16;        // timeline checkpoint spacing (events)

require_once __DIR__ . '/SwuPgnParse.php';
require_once __DIR__ . '/SwuPgnIds.php';
require_once __DIR__ . '/SwuPgnFold.php';
require_once __DIR__ . '/SwuPgnIntegrity.php';
require_once __DIR__ . '/SwuPgnValidate.php';
require_once __DIR__ . '/SwuPgnRender.php';
require_once __DIR__ . '/SwuPgnSteps.php';
require_once __DIR__ . '/SwuPgnWriterDoc.php';
require_once __DIR__ . '/SwuPgnPerspective.php';
