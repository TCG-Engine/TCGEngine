# Bloodrush full-cycle analysis (model v2)

Open `DevTools/FaB/bloodrush.html` for a live sweep, or `DevTools/FaB/results/bloodrush-v2/explorer.html` for saved results. The old `bloodrush-v1` bookmark and exports are updated with the same v2 report. JSON identifies model version 2.

```sh
node DevTools/FaB/bloodrush-run.mjs
node --test DevTools/FaB/bloodrush-tests.test.mjs
node DevTools/FaB/bloodrush-browser-test.mjs chromium,webkit
node DevTools/FaB/bloodrush-browser-test.mjs chromium http://localhost/TCGEngine/DevTools/FaB
```

The browser test uses `DevTools/ui-harness/node_modules/playwright` with a temporary server or the optional real-host base URL. On this Windows host, set `PLAYWRIGHT_BROWSERS_PATH=C:\Users\maxim\AppData\Local\ms-playwright`. Installed Firefox fails to launch (`spawn UNKNOWN`), so Firefox remains unverified. The scoped `.htaccess` supplies the JavaScript MIME type for `.mjs`, including module workers, on older XAMPP installations.

## Deck and starting state

Default: 60 cards, three yellow Bloodrush Bellows, 12 blue six-power block-3 attacks, nine blue six-power nonblocks, and 36 variable slots split between red seven-power block-3 attacks and blue five-power block-3 attacks. Every integer split is evaluated. Reds cost two; blues default to cost three, configurable separately for sixes and fives. All abstract attacks are Brute attacks with blank text and no go again. Real card abilities and format legality are not inferred from the database. Bellow blocks three and pitches for two.

Default hand mode draws four, conditioned on at least one Bellow. Optional arsenal mode reserves one of the deck's Bellows in arsenal, then draws four from the remaining deck without conditioning. The reserved copy cannot be pitched or discarded and is played first. Its setup in a previous cycle is not valued. Both modes start the attack turn with one action point, zero resources, and two unused Mandible Claws.

## Full-cycle value

**Cycle value = pre-turn block value + unblocked attack damage.** Before attacking, choose a subset of the known opening hand to defend with while retaining a legal first Bellow. Block-3 cards contribute three; nonblocks cannot defend. Assume enough incoming blockable damage to realize every chosen block point. Blockers leave hand and cannot later be pitched, discarded, or drawn this turn. Defensive choices maximize expected cycle value over unknown discards/draws. Newly drawn and unused cards receive no retroactive block credit.

Blocking and nonblocking blue sixes remain distinct throughout. Metric filters show damage or block components of the **same cycle-optimized policy**, rather than reoptimizing a separate objective. Statistics use joint damage/block outcomes, preserving covariance in cycle standard deviation.

First action must be Bellow. Resource payment precedes the random-discard effect cost ([rules 5.1.7–5.1.9](https://rules.fabtcg.com/en/cr/05-layers-cards-abilities/)). All legal pitches are evaluated, stopping once the cost is paid. Equal-value payments prefer blue fives, nonblocking blue sixes, blocking blue sixes, Bellows, then reds. Pitching a non-six blue to protect a six-power discard is considered explicitly.

A six-power discard draws two and grants go again; a miss ends this model's sequence. Bellow buffs stack and affect Brute weapons and actions. Each Claw costs two, attacks for three plus buffs, is usable once, and gains go again after a six-power discard. Abstract attack actions end the sequence. Pitched cards remain outside the draw pile all turn. No hero/equipment abilities, opponent blocks, reactions, additional discard costs, or on-hit effects are included.

## Exact solver and output

`bloodrush-model.mjs` uses hypergeometric opening hands/draws, weighted random discards, and memoized expected-value decisions. No future draw order is visible. Once defense is fixed, plays/pitches maximize expected remaining damage. Ties retain the first enumerated policy; defensive ties favor fewer blocks in the first differing category. Group frequencies depend on that rule.

Outputs include cycle, damage, and block mean/min/max/population SD/histograms, group probabilities, and failed-discard rates. Min/max are outcomes of the selected expected-value policy, not hindsight choices. There is no sampling noise. Groups count Bellows actually played, including a final failed Bellow. Group statistics are conditional on group; frequencies use all focused openings. Empty groups have null statistics. Opening qualification probability and hand Bellow-count distributions are recorded separately; arsenal-mode counts describe additional Bellows in hand.

`analyze(redCount, options)` returns a composition; `defaults` documents options, including `arsenal: 0` (hand) or `1` (arsenal). `createSolver(options).opening(hand, remainingDeck)` supports fixtures with category order `[Bellow, red seven, blue six block-3, blue five block-3, blue six nonblock]` and returns the joint distribution and chosen blockers. `summarize(dist)` gives all/group statistics.

Regression fixtures cover pitching blue five first for a guaranteed 19, arsenal Bellow for 19 + 3 = 22, nonblocks being unable to defend, no retroactive block credit, legal resource payment, double/triple Bellows, and joint-distribution statistics. This is an abstract cycle-value model, not a win-rate or overall balance estimate.
