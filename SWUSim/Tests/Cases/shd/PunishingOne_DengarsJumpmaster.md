# PunishingOne_UpgradedEnemyDefeated_MayReady
#// SHD_137 Punishing One — "When an upgraded enemy unit is defeated: You may ready this unit. Once each
#// round." Punishing One starts exhausted. P1's SOR_046 (3 power) attacks the enemy SHD_095 (2/3) that
#// wears SHD_072 (an upgrade → "upgraded"): SHD_095 is defeated, so Punishing One readies (readying an
#// exhausted unit is pure benefit → auto-resolved).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: SHD_137:0:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_072

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:READY

---

# OncePerRound_SecondUpgradedDefeatDoesNotReadyAgain
#// "Use this ability only once each round." The first upgraded enemy defeated readies Punishing One; it
#// then attacks P2's base (exhausting itself), and a second upgraded enemy defeated the same round leaves
#// it exhausted. (Each Clone Deserter carries a Bounty — declined, so its prompt doesn't hold up the next
#// attack.)
## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: SHD_137:0:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0
WithP2GroundArena: SHD_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_072
WithP2GroundArenaUpgrade: 1:SHD_072
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO
- P1>AttackSpaceArena:0:BASE
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:NO
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1SPACEARENAUNIT:0:EXHAUSTED

---

# SelfKill_PunishingOneDefeatsTheUpgradedUnit_Readies
#// The defeat may come from Punishing One's OWN attack. It attacks the enemy TIE/ln (2/1) wearing SHD_072
#// Imprisoned (an upgrade → "upgraded"); the attack exhausts it, the TIE is defeated, and the reaction
#// readies it again. It survives the 2 counter-damage (4 HP).
#// The sections above always kill with ANOTHER unit, so they never cover the attacker being the unit
#// that readies — the case where it is exhausted by the very attack that sets off the trigger.
## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: SHD_137:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArenaUpgrade: 0:SHD_072
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SHD_137
P1SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:READY
P1NODECISION

---

# SelfKill_AfterTheRoundIsSpent_StaysExhausted
#// Once each round still holds when the second defeat is Punishing One's own kill. P1's SOR_046 defeats the
#// upgraded Clone Deserter (Bounty declined) → the exhausted Punishing One readies, spending the round. It
#// then attacks the upgraded TIE/ln itself: the TIE is defeated, but it stays exhausted.
#// (It can't simply self-kill twice: two TIE counters are 2+2 = its full 4 HP.)
## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: SHD_137:0:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_072
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArenaUpgrade: 0:SHD_072
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO
- P1>AttackSpaceArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:EXHAUSTED
P1NODECISION
