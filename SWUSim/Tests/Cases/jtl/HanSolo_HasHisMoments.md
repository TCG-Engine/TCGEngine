# AsUpgrade_Attack
#// JTL_203 Han Solo (pilot) — When played as an upgrade: You may attack with the attached unit. Played
#// onto SOR_237, P1 chooses to attack the base, exhausting the host.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: JTL_203
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:EXHAUSTED

---

# AsUnit_Ambush
#// JTL_203 Han Solo has Ambush — played as a UNIT (no friendly Vehicle to pilot, so the Unit/Pilot prompt is
#// skipped) he may immediately attack an enemy UNIT (Ambush targets units, not bases). He attacks SOR_046
#// (3/7) for his 4 power and takes its 3 counter damage, ending exhausted.

## GIVEN
CommonSetup: yyw/bbk/{
  theirBase:SOR_021;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_203
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_203
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# AsUpgrade_DeclineAttack
#// JTL_203 Han Solo (pilot) — the "When played as an upgrade: You may attack" is a MAY. P1 declines (No), so
#// the host is NOT exhausted and no attack happens.

## GIVEN
CommonSetup: yyw/bbk/{
  theirBase:SOR_021;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_203
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:READY
P2BASEDMG:0

---

# Piloting_StatGrant
#// JTL_203 Han Solo — Piloting grants the host +2/+3. SOR_237 (2/3) carrying Han is a 4/6.

## GIVEN
CommonSetup: yyw/bbk/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_203

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:6

---

# AsUpgrade_FalconShootsFirst
#// JTL_203 Han Solo (pilot) — "If it's the Millennium Falcon, it deals its combat damage before the
#// defender." Han attaches to SOR_193 Millennium Falcon (3/4 → 5/7 with Han's +2/+3) and attacks P2's
#// SOR_237 (2/3): the Falcon deals 5 first, defeating SOR_237 before it can counter — so the Falcon takes 0
#// damage (contrast the non-Falcon case, which takes the 2 counter).

## GIVEN
CommonSetup: yyw/bbk/{
  theirBase:SOR_021;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1Hand: JTL_203
WithP1SpaceArena: SOR_193:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:EXHAUSTED

---

# AsUpgrade_NonFalconTakesCounter
#// JTL_203 Han Solo (pilot) — the shoot-first ordering is Falcon-only. On a non-Falcon host (SOR_237,
#// 2/3 → 4/6 with Han's +2/+3) the When-Played attack is simultaneous: Han's host defeats P2's SOR_237
#// (2/3) but takes its 2 counter damage.

## GIVEN
CommonSetup: yyw/bbk/{
  theirBase:SOR_021;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1Hand: JTL_203
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:EXHAUSTED
