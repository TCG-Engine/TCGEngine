# JTL_009 Boba Fett (undeployed leader) — When you deal non-combat damage: you may exhaust this leader;
# if you do, deal 1 indirect damage to a player. P1 plays JTL_176 Shoot Down (3 to a space unit) onto
# P2's SOR_046 — that effect damage is non-combat, so Boba's reaction is offered. P1 exhausts Boba and
# deals 1 indirect to P2, who assigns it to their base. (Base damage of 1 comes only from the reaction.)

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8
WithP1Hand: JTL_176
WithP2SpaceArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:1

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P2BASEDMG:1
