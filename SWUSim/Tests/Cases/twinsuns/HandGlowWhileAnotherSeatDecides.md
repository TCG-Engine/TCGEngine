# HandGlow_FarSeatIsResolvingIndirectDamage_HandMustNotGlow
#// Bug report (game 3608): "when I attack with TIE Bomber, my hand cards glow as if able to play even
#// though I can't — and my opponent is resolving their indirect damage trigger."
#//
#// JTL_237 TIE Bomber — "On Attack: Deal 3 indirect damage to the defending player. (They assign 3
#// unpreventable damage among their base and units.)" — hands the DEFENDER an assignment decision, so
#// the attacker sits idle while another seat decides.
#//
#// ROOT CAUSE: the glow and the play-gate disagree, and they disagree because of a seat hardcode.
#//   • the SERVER gate is DecisionQueueController::AllQueuesEmpty(), which loops 1..SeatCount() — so
#//     the play really IS blocked, exactly as the reporter said;
#//   • the GLOW is SelectionMetadata(), which looked at only two queues:
#//         GetDecisionQueue($turnPlayer)  and  GetDecisionQueue($turnPlayer == 1 ? 2 : 1)
#//     At four seats with seat 1 acting that inspects seats 1 and 2 ONLY, so a decision pending on
#//     seat 3 or 4 is invisible and the hand lights up green.
#//
#// Here seat 1 swings TIE Bomber at SEAT 3's base, leaving the indirect assignment pending on seat 3 —
#// a seat the old two-queue check never looked at. Seat 1's hand card must stay dark.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4; myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP1Hand: [SOR_128]
WithP3Base: SOR_019
WithP3GroundArena: [SOR_095:1:0]

## WHEN
- P1>AttackSpaceArena:0:P3B

## EXPECT
P3HASDECISION
P1HANDGLOWNOT:0

---

# HandGlow_NobodyIsDeciding_TheSameCardDOESGlow
#// THE CONTROL, and without it the section above is worthless: "the card does not glow" is also true of
#// a card that is unaffordable, off-turn, or in the wrong phase. Same seat, same hand, same resources,
#// no attack and therefore no pending decision anywhere — SOR_128 Death Star Stormtrooper costs 1 and
#// is on-aspect for an Aggression base with an Aggression/Villainy leader, so it must light up.
#// This is what proves the first section fails for the RIGHT reason.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4; myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP1Hand: [SOR_128]
WithP3Base: SOR_019
WithP3GroundArena: [SOR_095:1:0]

## WHEN

## EXPECT
P1NODECISION
P3NODECISION
P1HANDGLOW:0

---

# HandGlow_AtTWOSeats_WasAlreadyCorrect
#// The premier control. At two seats `$turnPlayer == 1 ? 2 : 1` names the one real opponent, so the old
#// code got this right and this section passes both BEFORE and AFTER the fix.
#// It earns its place twice over: it pins that the seat-aware rewrite does not regress the two-player
#// path, and it localises the defect to seat COUNT rather than to the glow logic in general.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: [JTL_237:1:0]
WithP1Hand: [SOR_128]
WithP2GroundArena: [SOR_095:1:0]

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2HASDECISION
P1HANDGLOWNOT:0
