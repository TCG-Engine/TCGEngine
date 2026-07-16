# AttacksGround_DefenderDebuffed
#// SOR_212 Strafing Gunship (3/4 Space) — "This unit can attack units in the ground arena. While this
#// unit is attacking a ground unit, the defender gets -2/-0." The space Gunship attacks an enemy GROUND
#// unit (SEC_080 3/3): it deals 3 (defeating SEC_080), and SEC_080's counter is reduced from 3 to 1 by
#// the -2 power debuff, so the Gunship takes only 1 damage.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackSpaceArena:0:G0

## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:EXHAUSTED

---

# AttacksSpace_NoDebuff
#// SOR_212 Strafing Gunship — the -2/-0 applies only while attacking a GROUND unit. Attacking a SPACE
#// unit normally, the defender (SOR_237 2/3) deals its full 2 counter-damage, so the Gunship takes 2.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_212:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:2
