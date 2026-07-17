# OpponentUsesActionDealsPower
#// TS26_15 C-3P0 — Action [Exhaust]: deal damage equal to this unit's power (2) to another ground unit;
#// only opponents may use. P1 plays C-3P0 → P2 takes control (enters exhausted, like any played unit). Both
#// pass to the regroup; the ready phase readies C-3P0 under P2. In the next round P2 (the controller, an
#// opponent of the owner) activates C-3P0 and deals 2 to P1's SOR_095, exhausting C-3P0 as the cost.
## GIVEN
CommonSetup: gbw/rrk/{handCardIds:TS26_15;myResources:6}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>UseUnitAbility:myGroundArena-0
- P2>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:CARDID:TS26_15
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# OwnerCannotUseAction
#// TS26_15 C-3P0 — "Only opponents may use this ability." The owner-gate blocks the OWNER from activating
#// the action even when they control a ready C-3P0. P1 (owner) tries to use it: nothing happens — C-3P0
#// stays ready (the exhaust cost is never paid) and the enemy SOR_095 takes no damage.
## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: TS26_15:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_OpponentTakesControl
#// TS26_15 C-3P0 (Unit 2/5, cost 2, Droid, Vigilance/Command) — When Played: an opponent takes control of
#// this unit. P1 plays C-3P0; it enters P1's play then transfers to P2's control (P2's arena), leaving P1's
#// ground arena empty.
## GIVEN
CommonSetup: gbw/rrk/{handCardIds:TS26_15;myResources:6}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TS26_15
