# Card play → win rates — rules v37

2160 games; 1766 decisive, 394 draws, 0 capped at 60 rounds. Mean length: 16.90 rounds.

Win rate = wins / completed player-games in which that card was used in the indicated role. Draws remain in the denominator and count as no win; capped games are excluded. A card is counted once per player-game per role, regardless of repeated main plays. These are associations under the simulated policies, not causal estimates of card strength.

Main means the original face-down commitment. Darting Strike keeps attribution when cancelled; its damage totals include the replacement's effects. Replacements are recorded separately as combatPlans in each trace, and are not counted as a second main commitment.

Both players use the same policy within a game. All 36 ordered opening pairs and both initial reaction priorities receive equal game counts within each profile. The exposure-weighted pooled result can differ from individual profiles.

| Bot style | Decisive | Draws | Capped |
|---|---:|---:|---:|
| tactical | 552 | 168 | 0 |
| casual | 617 | 103 | 0 |
| explore | 597 | 123 | 0 |

## main — ranked cards with at least 100 completed player-games

| Card | Color | Win rate | Wins / completed | Draws | Uses | tactical win rate (N) | casual win rate (N) | explore win rate (N) |
|---|---|---:|---:|---:|---:|---:|---:|---:|
| Combination Strike | Red | 46.7% | 588 / 1259 | 238 | 1565 | 58.6% (220) | 60.5% (377) | 34.9% (662) |
| Firm Counter | Green | 46.1% | 428 / 928 | 142 | 1204 | 66.0% (47) | 48.3% (327) | 43.1% (554) |
| Watchful Guard | Blue | 45.1% | 584 / 1296 | 222 | 1805 | 42.3% (196) | 47.9% (449) | 43.9% (651) |
| Find the Gap | Blue | 44.7% | 699 / 1565 | 288 | 2128 | 42.4% (490) | 46.1% (432) | 45.4% (643) |
| Heavy Feint | Purple | 44.7% | 435 / 974 | 156 | 1229 | 44.6% (83) | 47.5% (377) | 42.6% (514) |
| Body Lock | Orange | 44.5% | 660 / 1482 | 248 | 2002 | 43.6% (349) | 45.6% (485) | 44.3% (648) |
| Shield Bash | Green | 44.3% | 722 / 1628 | 297 | 2225 | 40.3% (407) | 47.1% (541) | 44.6% (680) |
| Driving Palm | Green | 43.8% | 876 / 1999 | 376 | 3257 | 40.2% (833) | 48.5% (517) | 44.7% (649) |
| Crushing Blow | Purple | 43.5% | 914 / 2099 | 416 | 2874 | 40.0% (797) | 50.0% (600) | 42.0% (702) |
| Read the Strike | Blue | 43.4% | 451 / 1039 | 185 | 1376 | 37.4% (190) | 48.1% (339) | 42.5% (510) |
| Iron Guard | Purple | 43.1% | 469 / 1089 | 181 | 1475 | 43.2% (95) | 40.4% (381) | 44.7% (613) |
| Mirror Step | Blue | 42.9% | 546 / 1273 | 206 | 1836 | 38.9% (208) | 42.9% (445) | 44.2% (620) |
| Driving Kick | Yellow | 42.8% | 855 / 1999 | 361 | 2825 | 40.2% (610) | 45.8% (624) | 42.4% (765) |
| Brace | Green | 42.6% | 607 / 1425 | 215 | 2091 | 34.3% (172) | 43.6% (574) | 43.9% (679) |
| Trade Blows | Purple | 42.5% | 948 / 2231 | 424 | 3246 | 40.0% (1016) | 47.6% (594) | 41.7% (621) |
| Sidestep | Yellow | 42.4% | 485 / 1143 | 175 | 1645 | 32.1% (78) | 42.4% (441) | 43.8% (624) |
| Grip Break | Orange | 42.1% | 298 / 707 | 113 | 905 | 46.2% (13) | 43.9% (326) | 40.5% (368) |
| Catch the Opening | Blue | 41.7% | 512 / 1227 | 219 | 1665 | 39.1% (192) | 43.6% (429) | 41.3% (606) |
| Measured Advance | Green | 41.7% | 653 / 1567 | 297 | 2082 | 36.5% (521) | 43.4% (424) | 44.9% (622) |
| Darting Strike | Yellow | 41.5% | 718 / 1731 | 304 | 2478 | 43.4% (341) | 43.3% (630) | 39.1% (760) |
| Off-Balance Sweep | Orange | 41.4% | 681 / 1646 | 294 | 2555 | 37.5% (627) | 44.5% (436) | 43.2% (583) |
| Counter Throw | Orange | 41.3% | 816 / 1978 | 384 | 2660 | 39.2% (727) | 39.9% (556) | 44.5% (695) |
| Covering Step | Red | 41.1% | 682 / 1661 | 295 | 2309 | 35.7% (384) | 44.7% (628) | 40.7% (649) |
| Rooted Guard | Green | 41.0% | 458 / 1118 | 215 | 1415 | 37.1% (428) | 44.0% (273) | 42.9% (417) |
| Outside Step | Yellow | 40.4% | 561 / 1388 | 268 | 1854 | 34.4% (331) | 43.1% (434) | 41.7% (623) |
| Glancing Step | Yellow | 40.2% | 561 / 1395 | 240 | 1932 | 35.3% (187) | 37.9% (557) | 43.6% (651) |
| Bait and Slip | Yellow | 40.1% | 333 / 830 | 140 | 1108 | 34.5% (29) | 43.9% (301) | 38.2% (500) |
| Rushdown | Red | 40.1% | 878 / 2192 | 412 | 3664 | 38.3% (751) | 40.8% (732) | 41.0% (709) |
| Charged Fist | Purple | 40.0% | 355 / 887 | 154 | 1219 | 26.7% (15) | 40.3% (372) | 40.2% (500) |
| Counterpunch | Blue | 39.9% | 323 / 810 | 144 | 1065 | 37.0% (127) | 42.1% (323) | 38.9% (360) |
| Clinch Throw | Orange | 39.8% | 794 / 1997 | 394 | 2861 | 38.5% (724) | 40.6% (599) | 40.4% (674) |
| Guarded Strike | Red | 38.8% | 449 / 1157 | 220 | 1531 | 36.0% (566) | 39.2% (263) | 43.3% (328) |
| Driving Tackle | Orange | 38.7% | 645 / 1668 | 333 | 2214 | 37.1% (372) | 40.8% (608) | 37.6% (688) |
| Feint | Red | 38.4% | 686 / 1785 | 318 | 2538 | 33.0% (457) | 40.6% (665) | 40.0% (663) |
| Haymaker | Purple | 38.2% | 597 / 1564 | 305 | 2113 | 33.5% (409) | 41.6% (490) | 38.5% (665) |
| Quick Jab | Red | 37.4% | 553 / 1477 | 296 | 2053 | 30.9% (246) | 35.5% (468) | 40.8% (763) |

## bonus — ranked cards with at least 100 completed player-games

| Card | Color | Win rate | Wins / completed | Draws | Uses | tactical win rate (N) | casual win rate (N) | explore win rate (N) |
|---|---|---:|---:|---:|---:|---:|---:|---:|
| Haymaker | Purple | 49.3% | 917 / 1860 | 359 | 1860 | 44.9% (898) | 55.1% (706) | 48.8% (256) |
| Catch the Opening | Blue | 46.9% | 492 / 1050 | 185 | 1050 | 44.1% (431) | 49.2% (396) | 48.0% (223) |
| Find the Gap | Blue | 46.5% | 319 / 686 | 112 | 686 | 40.4% (57) | 48.1% (397) | 45.3% (232) |
| Driving Palm | Green | 44.9% | 551 / 1228 | 217 | 1228 | 42.1% (397) | 46.9% (542) | 45.0% (289) |
| Clinch Throw | Orange | 44.5% | 593 / 1332 | 234 | 1332 | 41.0% (449) | 45.9% (619) | 47.3% (264) |
| Read the Strike | Blue | 44.3% | 273 / 616 | 90 | 616 | 38.4% (73) | 45.7% (394) | 43.6% (149) |
| Measured Advance | Green | 44.1% | 557 / 1262 | 231 | 1262 | 40.3% (432) | 46.6% (554) | 45.3% (276) |
| Driving Kick | Yellow | 44.1% | 486 / 1102 | 200 | 1102 | 42.2% (256) | 45.8% (583) | 42.2% (263) |
| Bait and Slip | Yellow | 44.0% | 336 / 763 | 113 | 763 | 40.5% (242) | 43.9% (351) | 49.4% (170) |
| Shield Bash | Green | 44.0% | 695 / 1580 | 278 | 1580 | 40.8% (708) | 47.2% (564) | 45.5% (308) |
| Trade Blows | Purple | 43.7% | 537 / 1230 | 257 | 1230 | 40.5% (484) | 48.0% (502) | 41.0% (244) |
| Rushdown | Red | 43.7% | 667 / 1528 | 294 | 1528 | 43.9% (579) | 47.1% (646) | 36.0% (303) |
| Charged Fist | Purple | 43.4% | 521 / 1200 | 251 | 1200 | 39.9% (569) | 48.0% (458) | 42.8% (173) |
| Outside Step | Yellow | 43.1% | 490 / 1136 | 213 | 1136 | 41.3% (361) | 43.8% (548) | 44.5% (227) |
| Driving Tackle | Orange | 43.1% | 765 / 1775 | 337 | 1775 | 40.6% (847) | 45.2% (644) | 45.8% (284) |
| Counterpunch | Blue | 43.0% | 351 / 817 | 156 | 817 | 40.8% (400) | 44.9% (305) | 45.5% (112) |
| Firm Counter | Green | 42.2% | 416 / 986 | 151 | 986 | 34.1% (220) | 44.4% (520) | 44.7% (246) |
| Mirror Step | Blue | 41.8% | 390 / 934 | 179 | 934 | 38.8% (255) | 40.0% (445) | 48.3% (234) |
| Darting Strike | Yellow | 41.3% | 462 / 1118 | 200 | 1118 | 35.2% (301) | 43.5% (545) | 43.8% (272) |
| Feint | Red | 41.1% | 437 / 1062 | 194 | 1062 | 40.0% (250) | 42.5% (544) | 39.6% (268) |
| Rooted Guard | Green | 41.1% | 352 / 857 | 154 | 857 | 36.9% (320) | 42.6% (373) | 45.7% (164) |
| Heavy Feint | Purple | 40.9% | 521 / 1275 | 259 | 1275 | 39.1% (608) | 43.1% (489) | 40.4% (178) |
| Off-Balance Sweep | Orange | 40.6% | 557 / 1371 | 257 | 1371 | 39.4% (520) | 39.8% (590) | 44.8% (261) |
| Body Lock | Orange | 40.3% | 697 / 1729 | 348 | 1729 | 38.2% (795) | 42.0% (681) | 42.3% (253) |
| Crushing Blow | Purple | 40.3% | 325 / 807 | 151 | 807 | 32.3% (195) | 41.7% (398) | 44.9% (214) |
| Watchful Guard | Blue | 40.2% | 313 / 779 | 115 | 779 | 28.4% (102) | 42.6% (441) | 40.7% (236) |
| Glancing Step | Yellow | 39.7% | 356 / 896 | 161 | 896 | 39.2% (189) | 41.2% (469) | 37.4% (238) |
| Quick Jab | Red | 38.9% | 404 / 1038 | 187 | 1038 | 31.2% (154) | 41.8% (598) | 37.1% (286) |
| Iron Guard | Purple | 38.6% | 432 / 1118 | 207 | 1118 | 35.7% (412) | 40.2% (460) | 40.7% (246) |
| Sidestep | Yellow | 37.4% | 411 / 1099 | 215 | 1099 | 31.2% (292) | 38.6% (591) | 42.6% (216) |
| Combination Strike | Red | 37.3% | 723 / 1936 | 395 | 1936 | 35.8% (948) | 37.9% (734) | 41.7% (254) |
| Covering Step | Red | 36.1% | 494 / 1370 | 257 | 1370 | 34.1% (574) | 35.9% (571) | 41.3% (225) |
| Counter Throw | Orange | 36.0% | 400 / 1110 | 207 | 1110 | 25.5% (259) | 37.7% (597) | 42.9% (254) |

## reaction — ranked cards with at least 100 completed player-games

| Card | Color | Win rate | Wins / completed | Draws | Uses | tactical win rate (N) | casual win rate (N) | explore win rate (N) |
|---|---|---:|---:|---:|---:|---:|---:|---:|
| Driving Palm | Green | 57.8% | 78 / 135 | 21 | 135 | 45.0% (20) | 61.9% (42) | 58.9% (73) |
| Counterpunch | Blue | 43.8% | 964 / 2201 | 398 | 2201 | 43.8% (418) | 45.1% (736) | 42.9% (1047) |
| Trade Blows | Purple | 43.8% | 172 / 393 | 68 | 393 | 45.3% (64) | 47.7% (88) | 41.9% (241) |
| Rooted Guard | Green | 43.7% | 947 / 2166 | 385 | 2166 | 40.2% (589) | 47.2% (654) | 43.4% (923) |
| Read the Strike | Blue | 43.0% | 623 / 1449 | 270 | 1449 | 40.4% (327) | 43.8% (390) | 43.7% (732) |
| Guarded Strike | Red | 40.9% | 1538 / 3761 | 687 | 3761 | 38.9% (1192) | 43.5% (1292) | 40.1% (1277) |
| Firm Counter | Green | 40.6% | 451 / 1110 | 224 | 1110 | 36.6% (399) | 42.2% (251) | 43.3% (460) |
| Catch the Opening | Blue | 40.4% | 359 / 889 | 168 | 889 | 39.3% (201) | 41.6% (267) | 40.1% (421) |
| Bait and Slip | Yellow | 40.2% | 905 / 2254 | 434 | 2254 | 37.4% (644) | 43.1% (784) | 39.5% (826) |
| Grip Break | Orange | 40.1% | 1490 / 3712 | 724 | 3712 | 38.6% (1317) | 40.1% (1170) | 41.9% (1225) |
| Charged Fist | Purple | 39.6% | 574 / 1448 | 284 | 1448 | 38.0% (366) | 42.5% (353) | 39.1% (729) |
| Mirror Step | Blue | 39.3% | 238 / 605 | 120 | 605 | 36.4% (151) | 44.4% (135) | 38.6% (319) |
| Heavy Feint | Purple | 39.3% | 477 / 1213 | 242 | 1213 | 39.8% (284) | 40.6% (276) | 38.6% (653) |
| Brace | Green | 38.8% | 449 / 1156 | 239 | 1156 | 36.4% (475) | 42.3% (265) | 39.4% (416) |
| Watchful Guard | Blue | 38.8% | 174 / 448 | 80 | 448 | 40.3% (119) | 35.2% (88) | 39.4% (241) |
| Find the Gap | Blue | 37.6% | 324 / 862 | 175 | 862 | 39.1% (299) | 36.2% (213) | 37.1% (350) |
| Off-Balance Sweep | Orange | 37.2% | 489 / 1313 | 265 | 1313 | 37.5% (357) | 37.3% (346) | 37.0% (610) |
| Outside Step | Yellow | 36.7% | 395 / 1077 | 189 | 1077 | 34.1% (182) | 38.3% (332) | 36.6% (563) |
| Feint | Red | 36.6% | 340 / 930 | 193 | 930 | 37.9% (277) | 36.4% (209) | 35.8% (444) |
| Measured Advance | Green | 35.8% | 147 / 411 | 65 | 411 | 32.2% (118) | 33.0% (88) | 39.0% (205) |
| Iron Guard | Purple | 34.5% | 276 / 799 | 181 | 799 | 41.7% (228) | 36.2% (177) | 29.7% (394) |
| Sidestep | Yellow | 33.6% | 308 / 918 | 195 | 918 | 30.6% (196) | 37.2% (239) | 32.9% (483) |
| Combination Strike | Red | 33.0% | 263 / 797 | 149 | 797 | 30.3% (89) | 31.7% (202) | 34.0% (506) |
| Covering Step | Red | 31.2% | 315 / 1010 | 219 | 1010 | 37.7% (260) | 32.6% (221) | 27.4% (529) |
| Body Lock | Orange | 30.6% | 235 / 768 | 173 | 768 | 34.4% (195) | 27.3% (150) | 30.0% (423) |
| Glancing Step | Yellow | 30.3% | 223 / 736 | 158 | 736 | 26.9% (193) | 34.2% (184) | 30.1% (359) |

## Interpretation

- The overall completed-player-game win baseline is 40.9%; draws make it lower than 50%.
- Main, bonus, and reaction roles are separate. Reactions are often chosen to secure an already-favorable or lethal exchange, so their high rates especially should not be interpreted as independent card power.
- Exposure is selected by the bots and by earlier outcomes. No confidence or causal ranking is claimed from this single batch.
- No card-removal experiments were requested or run in this batch. All seed/opening/action traces are retained in baseline.jsonl for replay.
- Catalog SHA-256: `55c72aa35c1ac5d34242dfebb2d23ec7d4fe46e806290326ce899ad5bd83d538`.

[Full card/profile CSV](play-win-rates.csv) · [Simulation summary](summary.json) · [Draw-adjusted balance report](balance-report.md)
