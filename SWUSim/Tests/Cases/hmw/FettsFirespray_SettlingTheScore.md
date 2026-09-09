# AmbushCanHitTheBase_WhileFirespraysPermissionApplies
#// HMW_053 Fett's Firespray — Settling the Score. Space Unit, cost 6, 6/6, [Aggression][Cunning].
#// Text: "Friendly units can attack bases while using Ambush."
#//
#// COVERAGE: offer=Offer_BaseAndEnemyUnitBothOffered
#//           decline=N/A (structural — Firespray prints no ability of its own to decline; the
#//                 Ambush YESNO it widens belongs to the ambushing unit and is covered generically
#//                 under Tests/Cases/keywords/)
#//           boundary=N/A (structural — a permission, not a quantity; nothing is counted or capped)
#//           control=N/A (structural — the permission is recomputed live from who controls a
#//                 HMW_053 at the moment Ambush targets are collected, and nothing is written to
#//                 the ambushing unit; EnemyAmbush_DoesNotGainThePermission is the direction test)
#//           reqboundary=N/A (structural — the permission is read inside the target collection and
#//                 nothing is written before the Ambush YESNO and read after it)
#//           modes=2P,TeamSuns (text says "FRIENDLY units" — in Team Suns a teammate's Firespray
#//                 grants it, covered by TeamSuns_TeammatesFirespraysGrantsThePermission)
#//                 TwinSuns=N/A (no player reference; the pool already unions all live opponents
#//                 through SWUGetAllValidAmbushTargets, which is the same code path)
#//
#// ⚠ THE WHOLE CARD IS ONE LINE IN SWUGetValidAmbushTargets. Ambush is units-only by CR 5.9.a
#// ("attack that enemy unit"), and the base is simply never added to that pool — so Firespray is a
#// permission that widens the pool, not a trigger. There is exactly one seam: both the collect
#// (CollectEntryTriggers) and the dispatch (case 'Ambush') call SWUGetAllValidAmbushTargets, and
#// SWUAmbushAnswer carries the resulting list verbatim without re-filtering.
#//
#// This section is the sharpest form of the positive: the enemy board is EMPTY, so the base is the
#// ONLY possible Ambush target. Without the permission the pool is empty, Ambush is never even
#// bagged as a trigger, and nothing happens at all — which is exactly what the next section pins.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;myhandCardIds:SHD_210}
P1OnlyActions: true
WithP1SpaceArena: HMW_053:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_210
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# WithoutFirespray_AmbushCannotHitTheBase
#// NEGATIVE — proves the permission is load-bearing. Identical board minus Firespray: the Ambush
#// pool is empty, so per the Blue Leader ruling (03/06/2025) the unit does not even ready and no
#// trigger is bagged. No prompt, no damage.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;myhandCardIds:SHD_210}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_210
P1NODECISION

---

# EnemyAmbush_DoesNotGainThePermission
#// NEGATIVE on the word "FRIENDLY". P1 controls Firespray; P2 plays an Ambush unit into an empty
#// P1 board. The permission belongs to Firespray's controller, so P2's Ambush still finds no legal
#// target and P1's base is untouched.
#//
#// A field passive written as "anyone with Ambush may hit bases" passes every other section here.

## GIVEN
CommonSetup: ryk/ryk/{myResources:4;theirResources:4;theirhandCardIds:SHD_210}
WithActivePlayer: 2
WithP1SpaceArena: HMW_053:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P1BASEDMG:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_210
P2NODECISION

---

# Offer_BaseAndEnemyUnitBothOffered
#// OFFER CELL. Answering a target proves the branch, never the pool. With an enemy unit on the
#// board AND the permission live, the Ambush choose must list BOTH — the base is added to the
#// existing unit pool, not substituted for it.
#//
#// Two targets also means the choose genuinely prompts rather than auto-firing, which is what
#// leaves it pending for SELECTABLEEXACT to read.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;myhandCardIds:SHD_210}
P1OnlyActions: true
WithP1SpaceArena: HMW_053:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirBase-0

---

# EnemySentinel_StillForcesTheAmbushOntoItself
#// RULES-INTERACTION CELL. Sentinel is a restriction on what may be attacked, and Firespray is a
#// permission to attack bases — the restriction wins. SWUGetValidAmbushTargets returns ONLY the
#// sentinels when any are present, so the base must not appear even with the permission live, and
#// the lone Sentinel target auto-fires.
#//
#// This is the cell that reds if the base is appended unconditionally instead of inside the
#// no-Sentinel branch, which is the obvious way to write the one-liner.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;myhandCardIds:SHD_210}
P1OnlyActions: true
WithP1SpaceArena: HMW_053:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:0

---

# TeamSuns_TeammatesFirespraysGrantsThePermission
#// TEAM SUNS CELL, earned by the word "FRIENDLY". In a 2v2 game a teammate's unit is friendly but
#// you do NOT control it — so the permission must be a TEAM relation (SWUTeamOf), not a
#// controller check. Seats 1 and 3 are Red, 2 and 4 are Blue.
#//
#// Firespray sits on seat 3; seat 1 plays the Ambush unit. Both enemy bases are then legal targets
#// (the pool unions every live opponent), so the choose prompts with exactly those two — and
#// without the teammate's permission there would be no Ambush trigger at all and no decision to
#// read. Asserting the POOL rather than the damage is what makes this section unable to pass for
#// the wrong reason.
#//
#// ⚠ The actor is seat 1, not a far seat: CommonSetup dresses seats 1-2 only, so a far seat cannot
#// hold a hand to play from. Seats 3/4 carry boards here, which is what they are for.

## GIVEN
CommonSetup: ryk/rrk/{myResources:4;myhandCardIds:SHD_210}
WithTeams: true
WithActivePlayer: 1
WithP3SpaceArena: HMW_053:1:0
WithP3Base: SOR_024
WithP4Base: SOR_024

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:p2Base-0&p4Base-0
