# Debuffs4Power
#// SOR_028 Jedha City (Base) — "Epic Action: Give a non-leader unit -4/-0 for this
#// phase." P1's base is Jedha City; P2's only non-leader unit is Consular Security
#// Force (SOR_046, 3/7). It's the sole target → auto −4/−0: power 3 → floored at 0,
#// HP unchanged at 7.

## GIVEN
CommonSetup: yrw/grw/{
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:7
P1BASE:EPICUSED

---

# SimulateRequestBoundary_Debuff4PowerAcrossBoundary
#// SOR_028 Jedha City — with TWO enemy non-leader units the Epic Action's target pick stays a real
#// prompt (Debuffs4Power's lone target auto-resolves), and in production that prompt ends the request:
#// the answer arrives in a fresh process. The chosen SOR_046 still takes -4/-0 for this phase (power
#// 3 → floored at 0, HP 7), the unchosen SOR_095 is untouched, and the Epic Action is still spent.

## GIVEN
CommonSetup: yrw/grw/{
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseBaseAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:7
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:POWER:3
P2GROUNDARENAUNIT:1:HP:3
P1BASE:EPICUSED
