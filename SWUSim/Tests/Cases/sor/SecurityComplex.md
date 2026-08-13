# SecurityComplexEpicAction
## GIVEN
CommonSetup: brw/grw/{
  myBase:SOR_019
}
SkipPreGame: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1BASE:EPICUSED

---

# TokenUnitIsALegalShieldTarget
#// "Give a Shield token to a NON-LEADER unit" — non-leader excludes leaders, not TOKENS. A bare
#// ["Unit"] filter dropped both (the Open Fire family sweep); the Clone Trooper token must be in the
#// pool beside the real unit, and shielding it sticks.

## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_019}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [TWI_T02:1:0 SOR_095:1:0]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
