# WhenPlayed_RebelGetsBuff
#// SOR_240 Fleet Lieutenant (3/3, Ground) — When Played: You may attack with a unit. If it's a
#// Rebel unit, it gets +2/+0 for this attack. The chosen attacker (Battlefield Marine, a Rebel
#// 3/3) attacks the undefended enemy base for 3 + 2 = 5. The +2 is for THIS attack only, so the
#// attacker is back to power 3 afterward.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_240
WithP1GroundArena: SOR_095:1:0    # Rebel attacker (3/3, ready) — idx 0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3

---

# AttackOffer_ReadyUnitsOnly_ExcludesSelfAndExhausted
#// Intended: the "you may attack with a unit" offer contains only READY friendly units — the
#// just-played Fleet Lieutenant (enters exhausted) and an exhausted bystander are both outside
#// the pool. The choose is left PENDING so the pool itself is asserted: only the two ready
#// units (Wampa idx 0, Battlefield Marine idx 1) are offered; the exhausted Wing Guard (idx 2)
#// and Fleet Lieutenant (idx 3) are not.
#// COVERAGE: offer=this section · reqboundary=WhenPlayed_RebelGetsBuff (play and attacker answer
#//           are separate serialized steps) · control=N/A (friendly-only pool, no control-change
#//           interaction distinct from the generic attack rules) · boundary pair=
#//           NonRebel_NoBuff_UnitTarget vs RebelBuff_UnitTarget (trait match/no-match; Rebel buff
#//           expiry asserted in WhenPlayed_RebelGetsBuff) · decline=Decline_NoAttack

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_240
WithP1GroundArena: [SOR_164:1:0 SOR_095:1:0 SOR_063:0:0]
WithP2GroundArena: SHD_098:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# NonRebel_NoBuff_UnitTarget
#// Intended: a chosen NON-Rebel attacker gets no +2 — Wampa (4/5, Creature) attacks Sundari
#// Peacekeeper (1/5) for exactly 4, takes 1 back, and ends exhausted. The base is untouched.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_240
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SHD_098:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0

---

# RebelBuff_UnitTarget
#// Intended: a Rebel attacker gets +2/+0 for THIS attack against a unit too — Battlefield
#// Marine (3/3, Rebel) hits Sundari Peacekeeper (1/5) for 3 + 2 = 5, exactly lethal, and takes
#// 1 back. Unbuffed the Marine deals only 3 and Sundari survives (see NonRebel_NoBuff_UnitTarget
#// where 4 damage leaves her alive) — her defeat is the proof the +2 applied.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_240
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_098:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:0

---

# Decline_NoAttack
#// Intended: "you may" — declining the attacker choose ends the ability cleanly: nobody
#// attacks, the ready unit stays ready, no damage anywhere, and no decision is left hanging.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_240
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SHD_098:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1NODECISION

---

# AmbushPlay_CannotBuffItself
#// Intended: played with Ambush granted (Energy Conversion Lab base epic action), Fleet
#// Lieutenant's own When Played cannot pick HIMSELF as the attacker. Entering play raises two
#// triggers (When Played attack-with + Ambush) — the Choose_trigger_to_resolve MZCHOOSE is
#// answered with EffectStack-0, then YES accepts the Ambush attack (lone enemy unit, target
#// auto-resolves). In either resolution order his attack-with pool never contains himself
#// (he is exhausted when it resolves — before Ambush readies him, or after the Ambush attack
#// exhausts him again), and with no other ready friendly unit the offer skips. He attacks for
#// his PRINTED 3 (not 3+2): Sundari Peacekeeper (1/5) ends at 3 damage, alive — the +2 Rebel
#// buff never touched him.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3;myBase:SOR_022}
P1OnlyActions: true
WithP1Hand: SOR_240
WithP2GroundArena: SHD_098:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:CARDID:SOR_240
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0
P1BASE:EPICUSED

---

# SimulateRequestBoundary_RebelBuffSurvivesToTargetPick
#// SOR_240 Fleet Lieutenant — the granted attack's TARGET prompt ends the request in production, so
#// the +2/+0 "for this attack" Rebel buff granted at attacker-selection has to be re-read from the
#// serialized gamestate when the target answer lands in a fresh process. Mirrors RebelBuff_UnitTarget
#// with the boundary inserted before the target answer: Sundari's defeat is the proof the +2 survived.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_240
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_098:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:0
