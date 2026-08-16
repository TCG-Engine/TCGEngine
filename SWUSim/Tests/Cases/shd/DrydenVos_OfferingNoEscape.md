# PlayCaptiveUnderControl
#// COVERAGE: offer=CaptiveOffer_OnlyCaptivesGuardedByUnitsYouControl (two friendly captives keep the pick
#//           interactive; the enemy captor's captive is excluded)
#//           decline=Decline_TheCaptiveStaysCaptured ("You MAY play it" — the choose-nothing token leaves
#//           every captive guarded)
#//           control=FreedCaptive_OwnerIsUnchanged_GoesToItsOwnersDiscard (it enters under the CASTER's
#//           control but its OWNER never changes, which only shows when it later leaves play)
#//           boundary=the guarded-by-whom pair inside CaptiveOffer_OnlyCaptivesGuardedByUnitsYouControl,
#//           and the free/decline pair PlayCaptiveUnderControl vs Decline_TheCaptiveStaysCaptured
#//           reqboundary=FreedCaptive_OwnerIsUnchanged_GoesToItsOwnersDiscard (the freed unit's owner has
#//           to survive a full action boundary — P2's attack — for the discard to route correctly)
#// SHD_192 Dryden Vos (7-cost, Cunning/Villainy ground) — Shielded + "When Played: Choose a captured card
#// guarded by a unit you control. You may play it for free under your control." P1's Discerning Veteran
#// (SHD_120) captures SOR_128; playing Dryden Vos, P1 plays that captive under its own control (P1 now has
#// SHD_120, Dryden, and SOR_128). Shielded + WhenPlayed = dual entry trigger → resolve WhenPlayed first.

## GIVEN
CommonSetup: gyk/gyk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SHD_120
WithP1Hand: SHD_192
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:SOR_128

---

# CaptiveOffer_OnlyCaptivesGuardedByUnitsYouControl
#// THE OFFER AXIS. "A captured card guarded by a unit YOU control" — P1's SOR_095 guards two captives and
#// P2's SOR_046 guards a third. Only the two on P1's side are staged, so the pool is myTempZone-0 and
#// myTempZone-1 and nothing else; the enemy captor's card is invisible to Dryden even though it is just as
#// much "a captured card". Two legal picks keep the decision interactive, so it is read while still PENDING.

## GIVEN
CommonSetup: gyk/gyk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_192
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaCaptive: 0:SOR_128
WithP1GroundArenaCaptive: 0:SOR_163
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaCaptive: 0:SOR_164

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myTempZone-0&myTempZone-1

---

# Decline_TheCaptiveStaysCaptured
#// THE DECLINE BRANCH. "You MAY play it for free" — with a real choice on the table the choose-nothing
#// token has to leave both captives exactly where they were, still guarded by P1's SOR_095, and put nothing
#// into the arena but Dryden himself. Dryden still cost his full 7 (the free play is only for the captive),
#// so the resource pool is dry either way — the tell is the captive count, not the resources.

## GIVEN
CommonSetup: gyk/gyk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_192
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaCaptive: 0:SOR_128
WithP1GroundArenaCaptive: 0:SOR_163

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_192
P1SPACEARENACOUNT:0
P1RESAVAILABLE:0
P1NODECISION

---

# FreedCaptive_ConstantAbilityApplies
#// A captive freed this way is a normal unit in play, so its CONSTANT ability starts operating at once.
#// SOR_242 General Dodonna gives "other friendly Rebel units +1/+1"; the captor SOR_095 Battlefield Marine
#// is a Rebel, so the moment Dodonna lands on P1's side the Marine reads 4/4 instead of its printed 3/3.
#// Dryden himself is Underworld, not Rebel, and stays 5/7 — an aura applied to every friendly unit rather
#// than to the Rebels would show up there.

## GIVEN
CommonSetup: gyk/gyk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_192
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaCaptive: 0:SOR_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:1:CARDID:SHD_192
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:7
P1GROUNDARENAUNIT:2:CARDID:SOR_242
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:0

---

# FreedCaptive_SpaceUnitGoesToTheSpaceArena
#// The freed card is placed by ITS OWN arena, not the captor's. SOR_163 Star Wing Scout is a SPACE unit
#// captured by a GROUND captor; playing it out of captivity puts it in P1's space arena while Dryden and
#// the captor stay on the ground, and the captor is left guarding nothing.

## GIVEN
CommonSetup: gyk/gyk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_192
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaCaptive: 0:SOR_163

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_163
P1RESAVAILABLE:0

---

# FreedCaptive_OwnerIsUnchanged_GoesToItsOwnersDiscard
#// "Under YOUR control" moves control only. SOR_128 was captured from P2 and is still OWNED by P2 after
#// Dryden frees it onto P1's board — a distinction that is invisible while it sits in the arena and becomes
#// visible the moment it leaves play. P2's AT-AT Suppressor (SOR_039, 8/8) defeats the 3/1 Stormtrooper and
#// the card comes to rest in P2's discard pile, not in the discard of the player who was controlling it.

## GIVEN
CommonSetup: gyk/gyk/{myResources:7}
WithActivePlayer: 1
WithP1Hand: SHD_192
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaCaptive: 0:SOR_128
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myTempZone-0
- P2>AttackGroundArena:0:2

## EXPECT
P1GROUNDARENACOUNT:2
P1DISCARDCOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_128


---

# FreedCaptive_WhenPlayedTriggers
#// SHD_192 — "you may PLAY it for free", so the freed captive is PLAYED and its entry triggers fire.
#// SHD_160 Reckless Gunslinger's When Played deals 1 damage to each base, so freeing it puts 1 on BOTH
#// bases. Constant abilities always worked here (they are recomputed from board state); it was the
#// TRIGGERED entry abilities that were silently lost.

## GIVEN
CommonSetup: gyk/gyk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_192
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaCaptive: 0:SHD_160

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:SHD_160
P1BASEDMG:1
P2BASEDMG:1
P1RESAVAILABLE:0
