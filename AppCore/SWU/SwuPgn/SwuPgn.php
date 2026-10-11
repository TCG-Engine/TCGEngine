<?php
// SWU-PGN reader — opens `.swupgn` files (a portable record of one Star Wars: Unlimited game),
// checks them, rebuilds the board at any moment, and turns them into a readable story.
//
// SWU-PGN is a community standard authored by Travis Chase. This reader is our own implementation,
// written from the SWU-PGN/1.0 spec text alone; every rule below cites it as "SWU-PGN/1.0 spec §N".
// It needs no engine, no card database and no DB: a file is folded from its own records.
//
// Files come from users and from other engines, so everything here treats input as untrusted:
// nothing throws except SwuPgnParse()'s four documented parse errors (§4), and every list the fold
// grows is capped (SWUPGN_MAX_* below) so a crafted file cannot cause unbounded work.
//
// ─── API ─────────────────────────────────────────────────────────────────────────────────────────
// Parse (§4, §5)                                                         SwuPgnParse.php
//   SwuPgnParse(string $text): array           doc; throws SwuPgnParseException (§4 messages only)
//   SwuPgnTryParse(string $text): array        ['doc' => ?array, 'error' => ?string] — never throws
//   SwuPgnRequiredHeaderTags(): array          the 17 §5.1 tags, in spec order
//   doc = ['headers' => [tag => value], 'story' => ?list<string> (verbatim; null = no STORY),
//          'decks'|'cards'|'setup'|'events'|'annotations' => list of decoded records,
//          'lines' => [section => parallel list of 1-based line numbers],
//          'sections' => banners in file order, 'dropped' => [section => records over the cap]]
//   Records are PHP arrays; an EMPTY JSON object stays a stdClass so `{}` and `[]` remain distinct.
//
// Ids (§6)                                                               SwuPgnIds.php
//   SwuPgnBaseId(string $id): string           strip the trailing `:N` copy suffix
//   SwuPgnCopyNumber(string $id): ?int         the N of `:N`, null for a first copy
//   SwuPgnTokenParts(string $id): ?array       TOKEN:<name>#<cardId>[:N] → name/cardId/copy/resolvable
//   SwuPgnSeatOfBaseRef($ref): ?int            the N of `base@N`, else null
//   SwuPgnCardIndex(array $doc): array         %%% CARDS as [baseId => display name]
//   SwuPgnName(array $index, $id): string      §16 nm(): name, ` #N` for a copy, `Player N's base`
//   SwuPgnWho($p): string                      §16 who(): "Player 1" / "Player 2" / ""
//
// Fold (§11, §12, §13)                                                   SwuPgnFold.php
//   SwuPgnEmptyState(): array                  §11 starting board
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
//
// Integrity (§14)                                                        SwuPgnIntegrity.php
//   SwuPgnCheckKeyframes(array $events): array ['ok' => bool, 'mismatches' => [[seq, path, expected?, got?]]]
//     `expected` (keyframe) / `got` (fold) is left OUT when that side does not have the field.
//
// Validate (§4, §5, §6, §7, §8, §9, §10, §15, §18)                       SwuPgnValidate.php
//   SwuPgnValidate(array $doc): array          ['ok' => no errors, 'errors' => [...], 'warnings' => [...]]
//     each issue: ['code', 'message', 'line' => ?int, 'seq' => ?string]
//   SwuPgnCheckVersion($gameTag): string       'ok' | 'newer-minor' | 'unsupported' (§18)
//   SwuPgnDefeatReasons(): array               §6.4 the closed DEFEAT.reason set
//
// Render (§16)                                                           SwuPgnRender.php
//   SwuPgnRender(array $doc, ?array $names = null): string   the %%% STORY text (lines joined "\n")
//   SwuPgnRenderEvents(array $events, array $names): string
//   SwuPgnStoryMatches(array $doc, ?array $names = null): array  ['present', 'matches', 'line' => first diff]
//   SwuPgnIsNumberedAction(array $e): bool     §16 — is this a player's own (numbered) action
//   SwuPgnStoryLine(array $e, array $names): ?string   §16 — one event's story line, or null
//
// Steps (replay viewer)                                                  SwuPgnSteps.php
//   SwuPgnSteps(array $doc, ?int $seat = null): array
//                                              ['steps' => [n, kind, seq, round, phase, actor, pos,
//                                              caption, lines], 'rounds' => [round => first step]];
//                                              $seat 1/2 = that seat's view: the other seat's draws,
//                                              resources and search finds name no card
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
//   - counts the degraded `TOKEN:credit` / `TOKEN:the-force` forms as the reserved tokens;
//   - ignores a negative EXHAUST_/READY_RESOURCES amount, and clamps shields at 0 on SHIELD_GAIN too;
//   - treats a keyframe whose `cards`/`hand`/`discard` is ABSENT, or that is `null`, as damaged;
//   - in §14, exempts the leader (like baseHp/deckSize) at the first usable keyframe when the fold
//     has none — Appendix A requires it — and reads the fold's absent initiativeTaken /
//     baseEpicActionUsed as false; round, phase, initiative, baseMaxHp and seat are not compared;
//   - in §16, prints `(cost 0)`, takes the banner's initiative from the round's keyframe (none → no
//     initiative part), and right-aligns "initiative: Player N " with the trailing space Appendix A
//     shows (the §16 illustration omits it).
// ─────────────────────────────────────────────────────────────────────────────────────────────────

// Reader limits for untrusted input. A real game is ~550–1000 events with a few dozen cards per
// seat; these are far above that and only exist to bound the work a crafted file can cause.
const SWUPGN_MAX_EVENTS = 20000;          // EVENTS records kept by the parser and folded
const SWUPGN_MAX_SECTION_RECORDS = 5000;  // DECKS / CARDS / SETUP / ANNOTATIONS records kept
const SWUPGN_MAX_STORY_LINES = 100000;
const SWUPGN_MAX_JSON_DEPTH = 64;         // deeper JSON is reported as invalid JSON
const SWUPGN_MAX_LIST = 500;              // hand, discard, resources per seat
const SWUPGN_MAX_CARDS_PER_SEAT = 200;    // in-play cards per seat
const SWUPGN_MAX_ATTACHED = 32;           // upgrades / captured per card
const SWUPGN_MAX_TOKEN_KINDS = 32;        // statusTokens keys per card
const SWUPGN_MAX_KEYWORDS = 64;           // keywords per card
const SWUPGN_MAX_WARNINGS = 200;          // fold warnings kept
const SWUPGN_MAX_ISSUES = 500;            // validate() issues kept
const SWUPGN_MAX_MISMATCHES = 1000;       // checkKeyframes() mismatches kept
const SWUPGN_TIMELINE_STRIDE = 16;        // timeline checkpoint spacing (events)

require_once __DIR__ . '/SwuPgnParse.php';
require_once __DIR__ . '/SwuPgnIds.php';
require_once __DIR__ . '/SwuPgnFold.php';
require_once __DIR__ . '/SwuPgnIntegrity.php';
require_once __DIR__ . '/SwuPgnValidate.php';
require_once __DIR__ . '/SwuPgnRender.php';
require_once __DIR__ . '/SwuPgnSteps.php';
