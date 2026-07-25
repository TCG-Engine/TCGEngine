# VehicleAttack_PreventSelfDamage
#// JTL_193 I Have You Now — Attack with a Vehicle; prevent all damage that would be dealt to it this
#// attack. SOR_237 attacks SOR_044: the defender takes 2, but SOR_237's counter-damage is prevented (0).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_193
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:2

---

# PreventsLethalCounter
#// JTL_193 I Have You Now — the prevention saves the attacker even from a lethal counter. SOR_237 (2/3)
#// attacks SOR_052 (6/9): normally the 6 counter would defeat SOR_237, but all damage to it is prevented,
#// so it survives at 0 damage while SOR_052 takes 2.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_193
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_052:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:2

---

# NonVehicle_NoValidAttacker
#// JTL_193 I Have You Now — the attacker must be a VEHICLE. With only a non-Vehicle unit (SOR_046 Trooper)
#// in play there is no legal attacker: the event fizzles to the discard and no attack occurs.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_193
WithP1Resources: 5
WithP1GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_193
P2SPACEARENAUNIT:0:DAMAGE:0

---

# ShieldedAttacker_PreventAll_ShieldKept
#// JTL_193 I Have You Now — the attacking Vehicle already carries a Shield (SOR_T02). "Prevent all damage"
#// covers the whole attack, so no damage is ever dealt to the attacker and its Shield is never triggered —
#// the Shield is KEPT. SOR_237 (2/3, +Shield) attacks SOR_044: defender takes 2, attacker stays at 0 damage
#// with its Shield intact. (There is no defeat-Shield-vs-prevent-all ordering prompt in SWUSim because
#// prevent-all leaves nothing for the Shield to absorb — see the deferred-branch note.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_193
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T02
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:2

---

# PreventsEnemyRhokaiWhenDefeated
#// JTL_193 I Have You Now — the prevention also covers an enemy When-Defeated ability that fires DURING the
#// attack and points at the protected attacker. SOR_237 (2/3 Vehicle) attacks P2's Rhokai Gunship (SHD_164,
#// 2/1): the 2 combat damage defeats Rhokai, whose "When Defeated: deal 1 to a unit or base" then targets the
#// attacker. Both the Rhokai counter (2) AND its When-Defeated 1 are prevented — SOR_237 finishes at 0 damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_193
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SHD_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0

---

# PreventsFriendlyTarfulRedirect
#// JTL_193 I Have You Now — the prevention also covers a survivor-observer redirect that resolves AFTER
#// combat damage but still during the same attack. AT-ST (SOR_232, 6/7 Ground Vehicle) attacks P2's Wookiee
#// Gentle Giant (SHD_048, 2/8): it deals 6 combat damage (Gentle Giant survives). P2's Tarfful (SHD_250)
#// then has that Wookiee deal its 6 to an enemy ground unit — the only one is the protected AT-ST, so it
#// auto-targets. The JTL_193 marker is phase-lived (cleared at the action boundary), so the redirect is
#// prevented too and AT-ST ends at 0 (not 6).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_193
WithP1Resources: 5
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SHD_048:1:0
WithP2GroundArena: SHD_250:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_232
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:6
