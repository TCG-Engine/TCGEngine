# FrontPlayIgnoreAspect
#// LAW_003 Agent Kallus (leader front) — "Action [1 resource, Exhaust]: Play a card from your hand,
#// ignoring its aspect penalties." Kallus is Vigilance/Villainy (base Cunning), so SOR_095 (Command/
#// Heroism, cost 2) is normally 2+4=6. With the action: pay 1 resource, then play SOR_095 for just 2
#// (full penalty waived). 3 resources → 1 (action) + 2 (card) = 0 left.

## GIVEN
CommonSetup: ybk/grw/{
  myLeader:LAW_003;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_095

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1RESAVAILABLE:0

---

# Deployed_NoPlayableCard_UsableCostPaid
#// LAW_003 Agent Kallus (deployed) — CR 6.4.587.c: the deployed action's cost is [1 resource] (no self-
#// exhaust; the leader-unit side has no exhaustSelf), a game-state change, so the Action is usable even with
#// no affordable card to play. It spends 1 resource and plays nothing; the deployed unit stays ready.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:LAW_003:1:1:1;myBase:JTL_019;theirBase:SOR_021;myResources:1}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:0

---

# FrontExhaustsSelf
#// LAW_003 Agent Kallus (leader front) — the Action costs [1 resource, Exhaust], so using it EXHAUSTS
#// Kallus. He plays SOR_095 (Battlefield Marine, off-aspect for a Cunning/Vigilance/Villainy deck) ignoring
#// its aspect penalties, then is exhausted (a once-per-turn ability on the front side).

## GIVEN
CommonSetup: ybk/rrk/{myLeader:LAW_003;myBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_095

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:CARDID:SOR_095

---

# DeployedActionStaysReady
#// LAW_003 Agent Kallus (deployed unit side) — the same "play a card ignoring aspect penalties" Action
#// costs only [1 resource] (NO self-exhaust on the unit side), so Kallus stays READY and the Action can be
#// used repeatedly. He plays SEC_213 (A-Wing, off-aspect) via the Action: 1 (Action) + 1 (card) = 0 left,
#// leader still ready.

## GIVEN
CommonSetup: ybk/rrk/{myLeader:LAW_003:1:1:1;myBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_213

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1LEADER:READY
P1SPACEARENAUNIT:0:CARDID:SEC_213
P1RESAVAILABLE:0

---

# DeployedUsableWhileExhausted
#// LAW_003 Agent Kallus (deployed) — because the unit-side Action has no exhaust-self cost, it can be used
#// even while Kallus is already EXHAUSTED (e.g. after he attacked). Seated exhausted, he still plays
#// SEC_213 (A-Wing) via the Action.

## GIVEN
CommonSetup: ybk/rrk/{myLeader:LAW_003:0:1:1;myBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_213

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_213
P1LEADER:EXHAUSTED

---

# HealHeroismUnit
#// LAW_003 Agent Kallus (deployed) — "When you play a Heroism card, heal 2 damage from your base." Kallus
#// (Vigilance/Villainy) can never have Heroism on-aspect, so the Heroism card is played via his own Action
#// (ignoring aspect penalties). Playing SOR_095 (Battlefield Marine, Command/Heroism) heals base 10→8.

## GIVEN
CommonSetup: ybk/rrk/{myLeader:LAW_003:1:1:1;myBase:SOR_021;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_095

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1BASEDMG:8
P1GROUNDARENAUNIT:1:CARDID:SOR_095

---

# HealHeroismEvent
#// LAW_003 Agent Kallus (deployed) — the heal fires on a Heroism EVENT too (not just units). Playing
#// SOR_245 (Medal Ceremony, Heroism event, cost 0) via Kallus's Action heals base 10→8.

## GIVEN
CommonSetup: ybk/rrk/{myLeader:LAW_003:1:1:1;myBase:SOR_021;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SOR_245

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1BASEDMG:8

---

# HealHeroismUpgrade
#// LAW_003 Agent Kallus (deployed) — the heal fires on a Heroism UPGRADE too. Playing SOR_053 (Luke's
#// Lightsaber, Vigilance/Heroism upgrade) via Kallus's Action (attaching to Kallus, the lone friendly
#// non-Vehicle unit) heals base 10→8.

## GIVEN
CommonSetup: ybk/rrk/{myLeader:LAW_003:1:1:1;myBase:SOR_021;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_053

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1BASEDMG:8

---

# NoHealNonHeroism
#// LAW_003 Agent Kallus (deployed) — no heal when the card we play is NOT a Heroism card. SEC_213 (A-Wing,
#// Cunning) enters play but base damage stays at 10.

## GIVEN
CommonSetup: ybk/rrk/{myLeader:LAW_003:1:1:1;myBase:SOR_021;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SEC_213

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:10
P1SPACEARENAUNIT:0:CARDID:SEC_213

---

# NoHealVillainy
#// LAW_003 Agent Kallus (deployed) — a Villainy card is not a Heroism card, so no heal. SOR_128 (Death Star
#// Stormtrooper, Villainy) enters play but base damage stays at 10.

## GIVEN
CommonSetup: ybk/rrk/{myLeader:LAW_003:1:1:1;myBase:SOR_021;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_128

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:10
P1GROUNDARENAUNIT:1:CARDID:SOR_128

---

# NoHealOpponentHeroism
#// LAW_003 Agent Kallus (deployed) — the heal only triggers when WE play the Heroism card. When the OPPONENT
#// plays a Heroism unit (SOR_095) our base is not healed (stays at 10). (P2 uses a Command/Heroism leader so
#// SOR_095 is on-aspect for them.)

## GIVEN
CommonSetup: ybk/bgw/{myLeader:LAW_003:1:1:1;theirLeader:SEC_009;myBase:SOR_021;myBaseDamage:10;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Resources: 2
WithP2Hand: SOR_095

## WHEN
- P2>PlayHand:0

## EXPECT
P1BASEDMG:10
P2GROUNDARENAUNIT:0:CARDID:SOR_095

---

# TwoHeroismHealTwice
#// LAW_003 Agent Kallus (deployed) — the heal has no once-per-round limit: playing TWO Heroism cards in a
#// phase heals twice. Kallus plays two Battlefield Marines (SOR_095) via his repeatable Action, healing
#// base 10→8→6.

## GIVEN
CommonSetup: ybk/rrk/{myLeader:LAW_003:1:1:1;myBase:SOR_021;myBaseDamage:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: [SOR_095 SOR_095]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1BASEDMG:6
