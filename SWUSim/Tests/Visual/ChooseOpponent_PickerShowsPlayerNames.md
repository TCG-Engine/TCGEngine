# VISUAL CHECK — the "choose an opponent" picker shows usernames, not seat tokens
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# The changed code is a PURE FUNCTION of its inputs, so it is checked by calling the picker directly on
# a real board page rather than by driving a card to the prompt:
#   window.SWU_SEAT_USERNAMES = {"2":"claudebot2","4":"Drixx"};
#   window.ShowOptionChooseUI('P2&P3&P4', 'Which opponent?', 7, (v,i) => console.log('SUBMITTED', v));
#
# WHAT THIS PINS (2026-08-21, the "an opponent" sweep)
#   SWUQueueChooseOpponent emits its menu as the RAW SEAT TOKENS "P2"/"P3"/"P4", because the server
#   parses the answer back with /^P(\d+)$/ (SWUPickedOpponent). Rendered verbatim that is a UI that asks
#   a player to pick "P3", which means nothing to them.
#   Core/OptionChooseUI.js now humanises the BUTTON TEXT only:
#     seat with an account  -> that username        (e.g. "claudebot2")
#     seat without one      -> "Player N"
#
# ⚠⚠ THE LINE THAT MUST NOT MOVE: the button SUBMITS the untouched option string. Display and value are
#   separate on purpose. Do NOT humanise this server-side — a username is arbitrary user input and the
#   decision Param is a DELIMITED TRANSPORT, so a name containing "&" or a space would corrupt the queue
#   row. Keeping the rename in the renderer means no sanitising is needed anywhere.
#
# WHERE THE NAMES COME FROM
#   window.SWU_SEAT_USERNAMES, published per board render by GameLayoutShared.php from
#   MatchSeatDisplayNames(). It contains ONLY seats whose player has a real account (userId > 0), so a
#   guest seat is absent and falls through to "Player N" without any extra branch.
#   ⚠ Its other consumers are chat labels and the Block Player widget — see the memory note on that
#   global having had two consumers and NO producer until 2026-08-21.
#
# WHAT TO LOOK AT
#   • A 4-seat game where some seats are logged in: the banner offers one button per LIVE opponent,
#     labelled with real names, and never a button for your own seat.
#   • 2-player: no banner at all. SWUQueueChooseOpponent auto-resolves a single eligible opponent to an
#     invisible PASSPARAMETER — Premier must never see this prompt (invariant I1 of the sweep plan).
#   • A degenerate 4-seat choice (only one opponent eligible) also shows NO banner (invariant I2).
#
# AUTOMATED PROBE (what was actually run — assertions on the rendered text AND the submitted value)
#   mixed accounts+guest -> ["claudebot2", "Player 3", "Drixx"]
#   clicking the GUEST button -> submitCallback received "P3"   ← the raw token, unchanged
#   nobody logged in     -> ["Player 2", "Player 3", "Player 4"]
#   non-seat options     -> ["Ground", "Space"]        (untouched — the regex only matches ^P\d+$)
#   2-player picker      -> ["You", "Opponent"]        (untouched)
#   window.SWU_SEAT_USERNAMES absent (another sim on this shared Core file) -> ["P2", "P3"] untouched
#   Chromium: ALL PASS      Firefox: ALL PASS
#   ⚠ WebKit could NOT be verified on this machine: playwright's webkit launches but its first
#     newPage()/about:blank never completes (>90s). Safari is UNVERIFIED. The change is textContent plus
#     a regex, with no engine-specific construct.
#
# NOT COVERED HERE
#   The SERVER side of the picker (which seats are offered, and that the answer routes to the right seat)
#   is covered by the schema suite, which asserts the RAW tokens — e.g.
#   shd/CadBane_HeWhoNeedsNoIntroduction.md::Front_TwinSuns_YouChooseWHICHOpponent
#   (P1OPTIONHAS:P2/P3/P4 + P1OPTIONNOT:P1). Those assertions are deliberately untouched by this change.

## GIVEN
#// A 4-seat board so the picker has something to render against; the probe drives the UI directly.
CommonSetup: rrk/bbw/{myLeader:SHD_014}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN

## EXPECT
SEATCOUNT:4
