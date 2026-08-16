# ExhaustedDefeated_Heal5OwnBase
#// COVERAGE: offer=N/A — the reward is "Heal 5 damage from YOUR base", a fixed single object with no
#//           target choice; the only decision is the collect YES/NO · decline=DeclineTheBounty_NoHealing ·
#//           boundary=the exhausted/ready pair — ExhaustedDefeated_Heal5OwnBase and
#//           ExhaustedByItsOwnAttack_CollectorHeals5 (exhausted, bounty live) vs
#//           ReadyWhenDefeated_NoBountyOffered (ready, no offer at all) ·
#//           control=ExhaustedDefeated_Heal5OwnBase / ExhaustedCaptured_CollectorHeals5 (P2 owns the
#//           Headhunter, P1 heals) vs ExhaustedByItsOwnAttack_CollectorHeals5 (P1 owns it, P2 heals) —
#//           "your base" follows the COLLECTOR, i.e. the opponent of the bountied unit's controller ·
#//           reqboundary=N/A — the exhausted condition is snapshotted and the heal applied inside the same
#//           action that removes the unit; no state survives to a later request ·
#//           leave-play legs: defeat=ExhaustedDefeated_Heal5OwnBase, capture=ExhaustedCaptured_CollectorHeals5
#// SHD_165 Unlicensed Headhunter (2-cost 3/2 space, Saboteur) — "While this unit is exhausted, it
#// gains: 'Bounty — Heal 5 damage from your base.'" P2's EXHAUSTED Headhunter is defeated by
#// Munificent Frigate (4 ≥ HP 2); P1 collects — "your base" resolves from the collector's
#// perspective, healing P1's pre-damaged base 5 (5 → 0) with no target choice.

## GIVEN
CommonSetup: grw/grw/{myBaseDamage:5}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SHD_165:0:0    # exhausted

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:0
P1BASEDMG:0
P1SPACEARENAUNIT:0:DAMAGE:3

---

# ExhaustedByItsOwnAttack_CollectorHeals5
#// SHD_165 Unlicensed Headhunter — the conditional Bounty is live whenever the unit is exhausted, and
#// ATTACKING is what exhausts it. P1's Headhunter (ready) attacks P2's Munificent Frigate (JTL_069, 4/7):
#// attacking exhausts it, it deals 3, and the Frigate's 4 kills the 2-HP Headhunter. Its controller is P1,
#// so the OPPONENT — P2 — collects, and "your base" resolves to the COLLECTOR's base: P2's 6 damage → 1.

## GIVEN
CommonSetup: grw/grw/{theirBaseDamage:6}
P1OnlyActions: true
WithP1SpaceArena: SHD_165:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P2>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P2BASEDMG:1
P2SPACEARENAUNIT:0:DAMAGE:3

---

# ReadyWhenDefeated_NoBountyOffered
#// SHD_165 Unlicensed Headhunter — the negative half of the exhausted/ready pair, and the boundary partner
#// of ExhaustedDefeated_Heal5OwnBase (identical fixture except the Headhunter is READY). "While this unit
#// is EXHAUSTED, it gains: 'Bounty - …'" — a ready Headhunter has no Bounty to collect, so its defeat
#// raises no offer at all (P1NODECISION) and P1's base keeps all 5 of its damage.

## GIVEN
CommonSetup: grw/grw/{myBaseDamage:5}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SHD_165:1:0    # ready

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1BASEDMG:5
P1SPACEARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# ExhaustedCaptured_CollectorHeals5
#// SHD_165 Unlicensed Headhunter — the Bounty is collected when the unit is CAPTURED, not only when it is
#// defeated, and the exhausted-only condition is read from the unit as it was before it left play. P1 plays
#// SHD_131 Take Captive (a friendly unit captures an enemy non-leader unit in the same arena); the only
#// captor is P1's Munificent Frigate and the only captive is P2's exhausted Headhunter, so both picks
#// auto-resolve. P1 then collects and heals its own base from 5 to 0. The Headhunter is now a facedown
#// captive under the Frigate (UPGRADECOUNT 1), not in P2's arena.

## GIVEN
CommonSetup: grw/grw/{myBaseDamage:5;myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_131
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SHD_165:0:0    # exhausted

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1BASEDMG:0

---

# DeclineTheBounty_NoHealing
#// SHD_165 Unlicensed Headhunter — collecting a Bounty is optional. The decline branch of
#// ExhaustedDefeated_Heal5OwnBase: same fixture, same defeat of the exhausted Headhunter, but P1 refuses
#// the collect and its base keeps all 5 damage.

## GIVEN
CommonSetup: grw/grw/{myBaseDamage:5}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SHD_165:0:0    # exhausted

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2SPACEARENACOUNT:0
P1BASEDMG:5
