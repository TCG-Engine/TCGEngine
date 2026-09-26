# VISUAL CHECK — four-seat chat: per-seat colours, the three whisper states, and log/chat interleaving
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
#
# SETUP — load this file in the Test Schema Editor (zzTestSchemaEditor.php), or:
#   GN=$(curl -s -X POST http://localhost:3400/TCGEngine/SWUSim/TestSchemaSetup.php \
#          --data-urlencode "schema@SWUSim/Tests/Visual/Chat_4P_WhisperMatrix.md" \
#        | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   open "http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=1&authKey=testschema"
#   Then swap &playerID= 2 / 3 / 4, and &playerID=S for the spectator. The panel to look at is
#   #swuLogPanel, the right-hand column — the board MERGES the game log and chat into that one panel.
#
# ⚠ THE BOARD IS SEEDED BY THE SCHEMA AT THE BOTTOM OF THIS FILE, via WithChat: and WithGameLog:
#   (added 2026-09-26). Before that this file was a CLI fixture and would NOT load in the editor —
#   pasting it silently produced an empty two-seat game. If you see two arenas and "Card" placeholders,
#   you are not looking at this board: a seeded one has THREE far-seat panels and 16 rows in the panel.
#   Directive reference and guards: SWUSim/DevTools/tests/schema_chat_and_gamelog_directives_test.php.
#
# ⚠ "Log in to chat." bottom-right is CORRECT and is not part of this check — it is the guest branch,
#   and a browser with no SWUSim session always gets it. Log in (claudebot1:pass) to get the composer;
#   the seeded rows render identically either way.
#
# WHAT IT SEEDS (owner, 2026-09-26) — eight messages with a game-log line between each pair:
#   P1 public · P2 public · P3 public · P4 public
#   P1 ⇒ P3 · P4 ⇒ P3 · P2 ⇒ P1,P4 · P3 ⇒ P2,P4
#   Four DIFFERENT recipient sets on purpose: no two seats' panels look alike, so a rendering bug that
#   collapses "sent", "received" and "redacted" into one style cannot hide in a single screenshot.
#
# ── THE VISIBILITY MATRIX. Open all five and check each column. ──────────────────────────────────
#
#   whisper        P1              P2              P3              P4              Spectator
#   P1 ⇒ P3        SENT            stub            RECEIVED        stub            stub
#   P4 ⇒ P3        stub            stub            RECEIVED        SENT            stub
#   P2 ⇒ P1,P4     RECEIVED        SENT            stub            RECEIVED        stub
#   P3 ⇒ P2,P4     stub            RECEIVED        SENT            RECEIVED        stub
#
#   Everyone sees all four PUBLIC messages and all eight game-log lines.
#   ⚠ A non-recipient still gets a ROW — redacted, never omitted. "P2 whispered something to P1 and P4"
#   with no text. That is deliberate: the table can see that a conversation happened.
#
# ⚠ CHECK THE PHONE BOARD TOO — append &swuLayout=mobile at 430x932. GameLayoutMobile.php does NOT
#   load GameLayout.php's CSS: it is a second stylesheet with its own copy of everything, so "the chat
#   patterns did not carry over" is the standing failure here (owner, 2026-09-26 — the phone panel had
#   the chat rules and none of the LOG rules, so log lines rendered near-white with no separators and
#   no card links, louder than the chat). Everything below must hold on BOTH boards.
#
# ── WHAT TO LOOK AT (desktop 1600x1000, and phone 430x932 via &swuLayout=mobile) ─────────────────
#   • SEAT COLOURS. P1 blue #6fb8ff · P2 orange #ff9b6f · P3 green #7fd88f · P4 purple #d79bff, on the
#     row's left rail, the speaker's name AND THE MESSAGE TEXT ITSELF (owner, 2026-09-26). A whole row
#     reads in one colour; only the name stays bold. Game-log lines are grey #aab6c4 and are the only
#     rows that are NOT seat-coloured, which is what separates the two streams at a glance.
#     ⚠ Colour is chosen by SEAT NUMBER, never by account — the same person in seat 2 next game is
#     orange again. There is no per-user chat colour anywhere.
#   • THE THREE WHISPER STATES are visibly different. All three are ITALIC and all three carry their
#     SENDER's colour — the thing that separates them is the wash and the wording:
#       SENT      "P3 → P2, P4: …"   — your own, addressed outward.      faint purple wash
#       RECEIVED  "P1 → you: …"      — or "P2 → you, P4: …" to several.  faint purple wash
#       REDACTED  "P4 whispered something to P3"   — no text, none recoverable.  GREY wash
#     ⚠ THE GREY IS LOAD-BEARING, not decoration. The redacted stub is the only PUBLIC piece of a
#     whisper, so it washes neutral grey like the game-log rows it sits between — it is an announcement
#     that a conversation happened, not a message. It also retires a real collision: the purple wash is
#     the same hue as P4's seat colour, so purple used to mean both "private" and "P4 said it". With the
#     stub grey, a purple row is P4 and only P4.
#   • INTERLEAVING. Chat and log strictly ALTERNATE here, because the directives are declared that way
#     and are applied in declaration order ACROSS BOTH KEYS. If chat clumps at the top or bottom,
#     timestamp ordering has broken — that is the whole reason each log line carries '@<microtime>'
#     (see the log-entry-format note).
#   • CARD LINKS. "Rebel Trooper", "Imperial Dark Trooper", "Alliance X-Wing", "Battlefield Marine"
#     render as underlined, hoverable card links — NOT raw [[SOR_046|…]] tokens and not flat text.
#     Both token shapes are in the seeded log; only one of them used to render.
#   • ROW SEPARATORS. Every row carries a faint bottom border so the wall of text is parseable.
#
# ── SPECTATOR ───────────────────────────────────────────────────────────────────────────────────
#   Four publics, FOUR stubs, and NO composer at all — no input, no Send, no notice.
#   See Spectator_SeatPickerAndNoChat.md for that ruling; this file is where you can see it beside
#   real traffic rather than on an empty board.
#
# ── WHAT IS ALREADY AUTOMATED (do not re-pin it here) ────────────────────────────────────────────
#   WHO MAY READ WHAT is covered and does not need eyes:
#     DevTools/tdd-regression/test_swusim_whisper_chat_read.php   — sender + recipients get the text;
#       everyone else gets the row with text "" and redacted:true. Also covers GetChat.php.
#     DevTools/tdd-regression/test_swusim_whisper_chat_send.php   — SubmitChat accepts/refuses whisperTo.
#     DevTools/tdd-regression/test_chat_whisper_core.php          — the pure Core helpers.
#   What NONE of them can see is what this file is for: whether the four colours are distinguishable,
#   whether the three whisper states read differently at a glance, and whether the two streams are
#   actually interleaved on screen.
#
# ⚠ THE PALETTE LIVES IN FOUR PLACES and only one is tokenised. Changing a colour means editing all
#   of them or the surfaces drift apart:
#     SWUSim/Custom/GameLayout.php       --swu-chat-p1..p4   (desktop board — the only real tokens)
#     SWUSim/Custom/GameLayoutMobile.php hardcoded hex        (mobile board)
#     SharedUI/Render/ChatPanel.php      hardcoded hex        (Waiting Room + Sideboard)
#     SWUSim/Custom/GameLayoutShared.php the two WASHES       (whisper purple + stub grey, both boards)
#   ⚠ The fourth is the one you would actually reach for to change a whisper's look, and it was missing
#     from this list until 2026-09-26.
#
# ⚠ COLOUR IS NOW AUTOMATED IN TWO LAYERS — do not re-pin it here by eye, but DO check the two are
#   still green before trusting this file:
#     SWUSim/DevTools/tests/chat_seat_colour_surfaces_test.php — the rules exist, and the four seat
#       hexes are IDENTICAL across all three surfaces (the drift guard).
#     SWUSim/DevTools/tests/log_panel_desktop_mobile_parity_test.php — every .swu-log-* rule and every
#       --swu-log-* token the DESKTOP panel has, the PHONE panel has, at the same value. Parity rather
#       than a list, so anything the desktop panel gains later must reach the phone too.
#     DevTools/ui-harness/swusim-chat-colours-xbrowser.mjs — COMPUTED colour of each message BODY and
#       each LOG row, on DESKTOP AND PHONE, in chromium + firefox + webkit: 240 checks. The source scan
#       cannot see specificity, and the body is coloured by INHERITANCE, which loses to any rule
#       matching the span directly. Mutation-proven twice: adding ".swu-log-CHAT > span { color: … }"
#       leaves the source scan green and turns this red; deleting the phone's .swu-log-entry rule (the
#       original bug) fails 27 checks, all of them /phone.

## GIVEN
#// A four-seat Twin Suns board. Two leaders per seat — a Twin Suns visual test that shows one leader
#// per seat is testing the wrong layout (see the twinsuns-visual-tests note).
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP3Base: SOR_026:5
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Base: SOR_026:8
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010

#// ── The eight messages and the eight log lines, ALTERNATING. ─────────────────────────────────────
#// Order here IS the order on screen: both keys are applied in the sequence they appear, and each
#// entry is stamped with a distinct microtime so the panel can merge the two streams. Move a line and
#// the board moves with it.
WithChat: 1|gl hf everyone
WithGameLog: ATTACK|ALL|P1's [[SOR_046|Rebel Trooper]] attacked P3's base for 3 damage

WithChat: 2|may the best table win
WithGameLog: PLAY|ALL|P2 played [[SEC_080|Imperial Dark Trooper]]

WithChat: 3|watch the space arena
WithGameLog: DAMAGE|ALL|P3's [[SOR_237|Alliance X-Wing]] took 2 damage

WithChat: 4|anyone want to deal?
WithGameLog: RESOURCE|ALL|P4 resourced a card

#// Four whispers, four DIFFERENT recipient sets — the whole point of this board. Every seat therefore
#// sees a different mix of real text and redacted stubs, and no two seats' panels look alike.
WithChat: 1>3|P3 — I will not attack you this round
WithGameLog: ATTACK|ALL|P1's [[SOR_095|Battlefield Marine]] attacked P2's base for 3 damage

WithChat: 4>3|P3, same deal from me
WithGameLog: DEFEAT|ALL|[[SEC_080|Imperial Dark Trooper]] was defeated

WithChat: 2>1,4|P1 and P4 — P3 is the threat here
WithGameLog: DRAW|ALL|P2 drew a card

WithChat: 3>2,4|P2 and P4 — they are both lying to you
WithGameLog: ENDTURN|ALL|P3 passed

## WHEN

## EXPECT
TURNPLAYER:1
SEATCOUNT:4
#// The log half of the seeding is assertable; the chat half is not (there is no chat assertion in the
#// DSL, by design — who may read what is covered by the three suites named above).
LOGCOUNT:2:attacked
LOGCOUNT:1:Alliance X-Wing
LOGCOUNT:1:was defeated
LASTLOGCONTAINS:P3 passed
