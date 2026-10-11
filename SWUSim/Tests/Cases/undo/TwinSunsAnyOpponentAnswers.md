# Seat3Requests_Seat1Approves
#// ⚠ OWNER RULING 2026-09-26: "Request Undo in Twin Suns should be presented to ALL opponents. Any Allow
#// or any Deny fulfills as the answer. It does not have to be unanimous."
#//
#// Everything in the consent path was written for two seats. SWUApproveUndo opened with
#// `if ($requestingPlayer < 1 || $requestingPlayer > 2) return;` — so a request from seat 3 or 4 could
#// never be approved by anybody, and the request simply sat there forever.
#// Here P3 plays a unit, requests the undo (public match => consent required), and P1 — an opponent, but
#// NOT "the" opponent in the two-seat sense — approves. The play must revert.
## GIVEN
CommonSetup: yyk/yyk
WithSeatOrder: 1234
WithLiveSeats: 123
WithGamePhase: ActionPhase
WithInitiativePlayer: 3
WithInitiativeClaimed: true
WithActivePlayer: 3
WithP3Base: SOR_021:0
WithP3Resources: 9
WithP3Hand: SOR_095
## WHEN
#// ⚠ UndoPhase, NOT Undo. A plain step-undo needs consent only when it crosses revealed info,
#// an OPPONENT's action, or a phase boundary — undoing your own single play crosses none of them, so it
#// applies instantly and every assertion below passes or fails for reasons that have nothing to do with
#// the consent flow. `kind === 'phase'` always requests in a public game.
- P3>PlayHand:0
- P3>UndoPhase
- P1>ApproveUndo
## EXPECT
P3HANDCOUNT:1
P3GROUNDARENACOUNT:0

---

# Seat3Requests_Seat2Denies
#// The mirror: a DENY from the other opponent also resolves the request, and the board stays put. The
#// deny path had the same two-seat guard (`$requestingPlayer >= 1 && <= 2`), so a seat-3 requester's
#// deny count was never even recorded.
## GIVEN
CommonSetup: yyk/yyk
WithSeatOrder: 1234
WithLiveSeats: 123
WithGamePhase: ActionPhase
WithInitiativePlayer: 3
WithInitiativeClaimed: true
WithActivePlayer: 3
WithP3Base: SOR_021:0
WithP3Resources: 9
WithP3Hand: SOR_095
## WHEN
- P3>PlayHand:0
- P3>UndoPhase
- P2>DenyUndo
## EXPECT
#// ⚠ THE BOARD ASSERTIONS ALONE ARE DECORATIVE and passed before the fix: while a seat-3 request could
#// not be answered at all, the deny was a silent no-op and the board stayed put for the WRONG reason —
#// "denied" and "never processed" look identical from the board. The LOG line is what distinguishes
#// them, because only a deny that actually ran writes it (and it names the real denier, P2 — the old
#// code hardcoded "the other of seats 1/2").
P3HANDCOUNT:0
P3GROUNDARENACOUNT:1
LOGCONTAINS:P2 denied P3's undo request

---

# AnyOneOpponentSuffices_NotUnanimous
#// The ruling's second half: ONE answer settles it. P1 requests with two opponents at the table; P2
#// approves and P3 never answers at all. The undo applies immediately — it does not wait for P3.
#// ⚠ This is the section that would survive a "collect every opponent's vote" misreading of the ruling,
#// so it is the one that pins "not unanimous".
## GIVEN
CommonSetup: yyk/yyk/{myResources:9}
WithSeatOrder: 1234
WithLiveSeats: 123
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P1>UndoPhase
- P2>ApproveUndo
## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0

---

# TheRequesterCannotApproveTheirOwn
#// The guard the seat-range check used to provide by accident: an undo request is answered by an
#// OPPONENT, never by the requester. Without this, widening the seat range would let a player approve
#// their own request and turn every public undo into a free one.
## GIVEN
CommonSetup: yyk/yyk
WithSeatOrder: 1234
WithLiveSeats: 123
WithGamePhase: ActionPhase
WithInitiativePlayer: 3
WithInitiativeClaimed: true
WithActivePlayer: 3
WithP3Base: SOR_021:0
WithP3Resources: 9
WithP3Hand: SOR_095
## WHEN
- P3>PlayHand:0
- P3>UndoPhase
- P3>ApproveUndo
- P1>ApproveUndo
## EXPECT
#// ⚠ TWO answers, and that is what makes this discriminating rather than decorative. Asserting only
#// "P3's self-approve did nothing" passed trivially while NO seat-3 request could be approved by anyone.
#// Following it with a REAL opponent's approve proves both halves at once: the self-approve was refused
#// AND it did not consume the pending request, so P1 can still answer it. The board must end reverted.
#// ⚠ THE BOARD ASSERTIONS ALONE ARE NOT ENOUGH — mutation proved it. If self-approval were allowed, P3's
#// own approve would revert the board and P1's would then be a no-op, ending in the SAME board state.
#// Only the log names the approver, so that is what separates "P1 approved" from "P3 approved himself".
P3HANDCOUNT:1
P3GROUNDARENACOUNT:0
LOGCONTAINS:P3 undid an action (approved by P1)
