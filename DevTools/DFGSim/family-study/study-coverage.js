'use strict';
// Revision 3 merges the refined proposals with complete, read-only v44 coverage.
(() => {
  const d=window.DFGFamilyStudy,live=window.DFGStudyLiveCatalog;
  const row=(name,attack,block,text,bet,extra={})=>({name,attack,block,text,bet,...extra});
  function add(color,category,title,question,axis,variants,critique,changes,test) {
    const n=d.families.length+1,i=d.colors.indexOf(color),id=`F${n}`;
    d.families.push({id,number:n,color,category,title,question,axis,scope:'Existing vocabulary',
      bonus:'Gain +1 attack this round.',reaction:'Gain +1 block this round.',routes:[d.colors[(i+1)%6],d.colors[(i+3)%6]],
      before:[],review:{critique,changes,test},crossing:changes,caution:test,
      variants:variants.map((v,j)=>({id:`${id}-${j+1}`,cost:0,tags:v.attack?['Strike']:['Guard'],...v}))});
  }
  add('Red','Counterplay','Damage without an attack','How much reliable guard should a larger retaliation surrender?','Block ↔ clean-defense direct damage',[
    row('Patient Reprisal',0,6,'If you take no combat damage, deal 1 direct damage after combat.','A small punish behind a large guard.'),
    row('Firm Reprisal',0,4,'If you take no combat damage, deal 2 direct damage after combat.','Middle guard and middle retaliation.'),
    row('Exposed Reprisal',0,2,'If you take no combat damage, deal 3 direct damage after combat.','A quiet opposing move earns the greatest punish.')
  ],'Pure healing or block is not a substitute for Guarded Strike’s direct retaliation. It uses combat damage, not total damage, to qualify.',
  'Give retaliation its own family and keep the no-combat-damage check. Both sides check the same combat snapshot; direct retaliation cannot recurse.',
  'Test a mirrored retaliation, a penetrating hit, and a blocked attack. Apply both retaliations before final exchange objectives and knockout checks.');

  add('Orange','Control','Deny the next burn','How much commitment should buy a narrower or broader lockout?','Burn-mode denial ↔ immediate stats',[
    row('Bind the Setup',4,2,'Optional: spend Orange. On dealing exchange damage, prevent opposing bonus burns next round.','Leave reactions available.'),
    row('Bind the Response',3,3,'Optional: spend Orange. On dealing exchange damage, prevent opposing reaction burns next round.','Leave precommitted bonuses available.'),
    row('Bind Both Hands',2,3,'Optional: spend Orange. On dealing exchange damage, prevent opposing bonus and reaction burns next round.','Broader denial costs current offense.')
  ],'Clinch Throw’s next-round burn lock was absent. Temporary exhaustion and defensive status penalties do not reproduce that denial.',
  'Separate bonus-only, reaction-only, and both-mode locks, all lasting exactly one next round. Main play and advantage spending remain available.',
  'Spend on reveal even if the hit fails. Test both priorities, next-hand movement, overlapping locks, and whether the victim retains useful non-burn plans.');

  add('Orange','Reads','Read the defensive investment','What kind of reward best punishes a bonus that actually grants block?','Defensive-bonus read ↔ offense or safety',[
    row('Test the Brace',3,2,'If the opposing committed bonus grants its owner block, gain +1 attack and +1 block.','Balanced reward for the specific read.'),
    row('Crack the Brace',2,2,'If the opposing committed bonus grants its owner block, gain +3 attack.','More offensive upside, less fallback attack.'),
    row('Catch the Brace',1,3,'If the opposing committed bonus grants its owner block, gain +2 attack and +2 block.','A safe fallback with lower offensive pressure.')
  ],'A general bonus-commitment read missed Grip Break’s narrower requirement: the resolved bonus must give its own player block.',
  'Keep defensive commitment separate from reaction block, bonus gifts, and general bonus presence. Vary the reward, not the meaning of the condition.',
  'Check conditional bonus branches, hybrid bonuses, attack-only bonuses, and bonuses gifting block to the other fighter.');

  add('Orange','Reads','Meet the incoming pressure','What should a read of already-carried attack earn?','Carried-pressure read ↔ fallback stats',[
    row('Check the Rush',4,1,'If the opponent carried an attack bonus into this round, gain +2 block.','Good fallback offense, modest pressure defense.'),
    row('Catch the Rush',3,2,'If the opponent carried an attack bonus into this round, gain +3 block.','More protection when the opponent arrives loaded.'),
    row('Break the Rush',2,2,'If the opponent carried an attack bonus into this round, suppress that opposing carried bonus this round.','Suppress a large carry rather than add fixed block.')
  ],'Reading printed attack does not cover Grip Break’s read of pressure carried into the exchange.',
  'Read only attack that entered this round. Current bonuses and rewards being earned for next round do not qualify.',
  'Compare carried amounts 1, 2, and 4 with an equal-sized current-round boost. Test Brace-like suppression before valuing the read.');

  add('Yellow','Adaptation','Change the revealed plan','How much flexibility should a costly main replacement have?','Replacement permission ↔ printed profile',[
    row('Slip the Heavy',3,2,'Optional: spend Yellow. After reveal, if opposing printed attack is 5+, you may permanently burn this main to replace it with another available main in your active hand.','Defensive fallback; replace against heavy commitments.'),
    row('Slip the Quiet',5,0,'Optional: spend Yellow. After reveal, if opposing printed attack is 3 or less, you may permanently burn this main to replace it with another available main in your active hand.','Offensive fallback; adapt to quiet commitments.'),
    row('Open Reversal',4,1,'Optional: spend Yellow. After reveal, you may permanently burn this main to replace it with another available main in your active hand.','Broad permission with a balanced fallback.')
  ],'Telegraphing and cooldowns do not cover Darting Strike’s post-reveal cancel. It changes the main after gaining information and permanently loses the original.',
  'All replacements keep the committed bonus and carry, cannot spend advantage, and recalculate opposing printed reads. Both choices stay hidden until finished.',
  'A spent token is not refunded on Keep or a failed condition. Burn locks prevent the cancel. Test simultaneous replacements and an already-burned bonus being ineligible.');

  add('Yellow','Precision','Choose a small wound','Would you shape a trade to take an exact amount of damage?','Damage taken ↔ carried payoff',[
    row('Untouched Step',3,2,'If you take exactly 0 exchange damage, gain +1 attack next round and Yellow advantage.','Safest target, smallest carried payoff.'),
    row('Glancing Read',3,2,'If you take exactly 1 exchange damage, gain +2 attack next round and Yellow advantage.','Give up a little life for a precise reward.'),
    row('Measured Wound',3,2,'If you take exactly 2 exchange damage, gain +3 attack next round and Yellow advantage.','A larger controlled trade buys more pressure.')
  ],'The refinement removed an exact-one stay condition but also left Glancing Step’s important exact-damage incentive without a family.',
  'Keep exact damage distinct from at-most damage. Use final exchange damage after retaliation, not merely combat damage.',
  'Test incoming damage 0/1/2/3 and reactions that move the exchange off the target. Damage-over-time ticks do not satisfy these draft conditions.');

  add('Yellow','Control','Choose their destination','How attainable should an on-hit forced hand be?','Force trigger ↔ attack and guard',[
    row('Touch and Direct',4,1,'Optional: spend Yellow. If you deal exchange damage, choose the opponent’s next hand from their legal destinations.','Accessible hit, limited guard.'),
    row('Drive and Direct',5,0,'Optional: spend Yellow. If you deal 3+ exchange damage, choose the opponent’s next hand from their legal destinations.','Larger hit, stricter control threshold.'),
    row('Cleanly Direct',3,2,'Optional: spend Yellow. If you deal exchange damage and take none, choose the opponent’s next hand from their legal destinations.','Safer printed profile, clean-hit requirement.')
  ],'Cutting one route is not the same as Driving Kick choosing the actual opposing destination.',
  'Force only after route additions and cuts; preserve the existing possibility of forcing an empty printed destination. Do not invent an unrelated destination.',
  'Test one remaining route, an empty legal destination, both fighters forcing, and protection against forced choices. No hidden destination may be inspected early.');

  add('Green','Objectives','Set tomorrow’s objective','How much setup safety is an easier future objective worth?','Block ↔ next-round damage objective',[
    row('Small Promise',1,3,'Objective: Deal 1+ exchange damage next round: gain Green advantage.','Easy follow-up, least setup guard.'),
    row('Firm Promise',1,4,'Objective: Deal 3+ exchange damage next round: gain Green advantage.','Middle threshold and setup protection.'),
    row('Bold Promise',1,5,'Objective: Deal 5+ exchange damage next round: gain Green advantage.','Strongest guard, hardest follow-up.')
  ],'Delayed healing and Heavy telegraphs do not represent Rooted Guard’s independent next-round objective.',
  'Setup requires neither hitting nor taking no damage. It follows hand changes and expires after one following round, whether successful or denied.',
  'Current-round damage cannot earn it. Test renewal, an already-held Green token, Counterpunch denial, and separate setup and reward rounds.');

  add('Green','Counterplay','Reset the pressure','Which part of the tempo cycle should a reset affect?','Current carry suppression ↔ future prevention',[
    row('Stop the Arrival',3,2,'Optional: spend Green. The opponent’s carried attack bonus does not apply this round.','Answer the pressure already present.'),
    row('Stop the Follow-Up',2,3,'Optional: spend Green. Neither fighter can gain attack carry for next round.','Prevent the next wave without suppressing this one.'),
    row('Reset the Exchange',1,4,'Optional: spend Green. Suppress opposing carried attack this round, and prevent either fighter gaining attack carry for next round.','Broad reset costs current offense.')
  ],'Cleansing conditions was not a replacement for Brace. Brace changes carry timing and affects both players’ future gains.',
  'Separate incoming suppression from future prevention. Own incoming carry remains; delayed objectives are not cancelled.',
  'Test own versus opposing carry, carry earned by taking exactly 1 damage, a Darting replacement, and future bonuses already prevented this round.');

  add('Blue','Control','Take away the held reward','How should successful offense and defense earn token removal?','Removal trigger ↔ printed profile',[
    row('Disrupt the Hit',3,2,'Optional: spend Blue. If you deal exchange damage, remove one opposing held advantage of your choice.','Attack to strip an existing resource.'),
    row('Disrupt the Guard',2,3,'Optional: spend Blue. If you take no exchange damage, remove one opposing held advantage of your choice.','Defend to strip an existing resource.'),
    row('Disrupt Both Ways',1,4,'Optional: spend Blue. Remove one opposing held advantage for dealing exchange damage, and independently another for taking none.','Two possible removals, weak printed attack.')
  ],'Denying a new award does not reproduce Mirror Step’s removal of already-held tokens.',
  'Choose held tokens after damage and before new objective awards. A token spent on reveal is already gone; independent triggers can remove two.',
  'Test no held tokens, one versus two targets, simultaneous stripping, new awards, and a knockout before any meaningful future spend.');

  add('Purple','Attrition','End the reusable threat','What condition should justify permanently losing the opposing main?','Main destruction ↔ hit requirement',[
    row('Destructive Touch',5,0,'Optional: spend Purple. If you deal exchange damage, permanently burn the opposing main after damage.','Broad hit condition with no printed guard.'),
    row('Destructive Drive',4,1,'Optional: spend Purple. If you deal 3+ exchange damage, permanently burn the opposing main after damage.','Some guard, substantial-hit requirement.'),
    row('Clean Destruction',3,2,'Optional: spend Purple. If you deal exchange damage and take none, permanently burn the opposing main after damage.','Safer fallback, stricter outcome.')
  ],'Self-exhaustion and relocation do not cover Charged Fist destroying a reusable opposing main.',
  'The destroyed main completes its simultaneous damage, main effects, and objective first. Destruction occurs instead of its normal return.',
  'Test simultaneous destruction, a failed hit with Purple still spent, the last surviving card, and a target that was replaced before combat.');

  add('Purple','Reads','Read the advantage spend','How much certainty should a token-spending read require?','Base stats ↔ spend specificity',[
    row('Watch the Spend',4,1,'If the opponent spent any advantage this round, gain +1 attack.','Strong fallback, small read reward.'),
    row('Punish the Spend',3,2,'If the opponent spent any advantage this round, gain +2 attack.','Less fallback attack, more safety.'),
    row('Punish the Pair',2,3,'If the opponent spent two advantages this round, gain +3 attack.','Narrower two-token read behind a stronger guard.')
  ],'Token holding, generating, cleansing, and spending are different events. Heavy Feint reads the actual committed spend.',
  'Count consumed reveal-time spends even if their conditional payoff fails. Spending two still triggers an any-spend read only once.',
  'Test held-but-unspent, newly earned, stripped, one spent, two spent, and Yellow consumed before a Darting replacement.');

  add('Orange','Reads','Punish the small attack','How broad should a low-attack punish be?','Printed-attack ceiling ↔ attack reward',[
    row('Punish Stillness',3,2,'If opposing printed attack is 1 or less, gain +4 attack.','Narrow quiet-move punish.'),
    row('Punish the Probe',3,2,'If opposing printed attack is 3 or less, gain +2 attack.','Middle coverage and reward.'),
    row('Punish Restraint',3,2,'If opposing printed attack is 5 or less, gain +1 attack.','Broadest read, smallest boost.')
  ],'High-threshold reads do not cover Off-Balance Sweep and Forearm Check punishing small attacks.',
  'Hold base stats fixed and change the ceiling and reward in opposite directions. A boosted low printed attack remains a qualifying read.',
  'Compare printed attacks 1/3/5/6, then add carry or reaction attack without changing the printed value.');

  add('Blue','Reads','Read an open guard','Should weak opposing guard invite offense or protect your own commitment?','Low-block read ↔ reward type',[
    row('Find the Small Gap',2,2,'If opposing printed block is 1 or less, gain +4 attack.','Narrow, forceful punish.'),
    row('Find the Broad Gap',2,2,'If opposing printed block is 3 or less, gain +2 attack.','More coverage, less force.'),
    row('Cover the Opening',2,2,'If opposing printed block is 2 or less, gain +3 block.','Use the same sort of read to protect your own attack.')
  ],'Catch the Opening’s low-block attack read and Driving Kick’s low-block defensive read were missing.',
  'Show both possible rewards for reading weak guard. They need not share the same purpose merely because their condition overlaps.',
  'Test printed block 1/2/3/4 with both a heavy opposing attack and a quiet one. Later block buffs do not change the read.');

  add('Blue','Precision','Name the printed guard','When does a precise block prediction beat a broad threshold?','Exact printed-block target',[
    ...[1,3,5].map((n,i)=>row(['Read the Opening','Read the Balance','Read the Wall'][i],2,2,`If opposing printed block is exactly ${n}, gain +3 attack and +1 block.`,`Commit to printed block ${n}.`))
  ],'The previous exact-read family tested only attack. Mirror Step independently reads printed block exactly 3.',
  'Use three exact block targets with equal rewards. Keep printed values distinct from temporary block and penetration.',
  'Measure actual target frequency in the active hand. Test the correct value, adjacent values, and an unchanged printed value with reaction block.');

  add('Red','Burn modes','Commit a conditional bonus','Which read should make a face-down bonus efficient?','Bonus predicate ↔ payoff',[
    row('Bonus for the Wall',3,2,'','As a bonus, punish strong printed guard.',{bonus:'If opposing printed block is 4+, gain +3 attack. Otherwise gain +1 attack.'}),
    row('Bonus for the Heavy',3,2,'','As a bonus, defend against a heavy printed attack.',{bonus:'If opposing printed attack is 5+, gain +3 block. Otherwise gain +1 block.'}),
    row('Bonus for the Setup',3,2,'','As a bonus, read opposing commitment.',{bonus:'If the opponent committed a bonus, gain +2 attack. Otherwise gain +1 attack.'})
  ],'Fixed burns omitted Feint, Find the Gap, and Read the Strike’s conditional bonus branches.',
  'Keep the main fixed and vary the permanently burned bonus. Its conditional reward is chosen before the opposing main and bonus are revealed.',
  'Test the resolved branch, the weak fallback, and the permanent loss. Do not apply a card’s own bonus to itself as main.');

  add('Blue','Burn modes','React to what was revealed','What is a conditional reaction worth once both commitments are visible?','Reaction condition ↔ attack or block',[
    row('Check the Heavy',3,2,'','React efficiently to high printed attack.',{reaction:'If opposing printed attack is 5+, gain +2 block. Otherwise gain +1 block.'}),
    row('Check the Setup',3,2,'','React efficiently to a committed bonus.',{reaction:'If the opponent committed a bonus, gain +2 block. Otherwise gain +1 block.'}),
    row('Check the Balance',3,2,'','React offensively to a precise printed guard.',{reaction:'If opposing printed block is exactly 3, gain +2 attack. Otherwise gain +1 attack.'})
  ],'Bait and Slip and Read the Strike have conditional reactions, not just conditional mains or bonuses.',
  'Keep a useful fallback and evaluate the reaction after reveal. Its known condition gives it different risk from the face-down conditional bonus.',
  'Compare actual reaction turns and competing burns. Conditional reaction efficiency still spends a permanent card and cannot create hidden information.');

  add('Red','Burn modes','A missing mode is a cost','What printed strength should unavailable burn modes buy?','Mode availability ↔ printed stats',[
    row('Complete Technique',4,2,'','Keep both alternative burn modes.'),
    row('Reserved Technique',5,2,'','An extra attack costs bonus use.',{bonus:''}),
    row('Committed Technique',4,3,'','An extra block costs reaction use.',{reaction:''})
  ],'Giving every draft both modes erased a real restriction on current cards, including mains with no bonus or no reaction.',
  'Print unavailable modes explicitly. The improved main sacrifices a different possible permanent use; it does not get a free bonus effect.',
  'Test a hand that needs reaction defense, one that needs bonus fuel, and late-game card conservation. Unavailable is not a zero-cost activation.');

  add('Orange','Reads','Two independent reads','What happens when two separate predictions can both succeed?','Read overlap ↔ breadth',[
    row('Precise Double Read',2,2,'If opposing printed attack is exactly 3, gain +2 block. Independently, if opposing printed block is exactly 3, gain +2 attack.','Either exact read can qualify; both may stack.'),
    row('Broad Double Read',2,2,'If opposing printed attack is 5+, gain +1 block. Independently, if opposing printed block is 4+, gain +1 attack.','Broader targets buy smaller rewards.'),
    row('Resource Double Read',2,2,'If the opposing bonus grants its owner block, gain +1 attack and +1 block. Independently, if the opponent carried attack into this round, gain +2 block.','Read defensive commitment and incoming pressure separately.')
  ],'One condition granting two stats is different from Mirror Step and Grip Break having independently evaluated conditions.',
  'Make independence explicit. One branch failing never prevents the other, and both may apply. An OR objective still awards at most its stated token reward.',
  'Test neither, each condition alone, and both. Confirm additive stat rewards without accidentally duplicating an objective award.');

  add('Purple','Follow-through','Pay only when you miss','How much attack should a failed-hit penalty buy?','Attack ↔ miss-triggered recovery',[
    row('Heavy Swing',6,0,'','Shared guaranteed-cost-free baseline.',{referenceOf:'F10-1'}),
    row('Risky Swing',7,0,'If you deal no combat damage, you have -1 final block next round, to a minimum of 0.','A successful hit avoids the penalty.'),
    row('Wild Swing',8,0,'If you deal no combat damage, you have -2 final block next round, to a minimum of 0.','More power makes a miss less likely, so the cost needs careful pricing.')
  ],'The refined commitment family intentionally made the penalty unconditional, leaving Haymaker’s actual miss penalty unrepresented.',
  'Keep a separate miss-cost family rather than treating the two timings as interchangeable. Direct retaliation does not turn a zero-combat-damage miss into a hit.',
  'Measure how often each attack really misses after reactions. Inspect next-round zero-block profiles where the printed penalty may have little value.');

  add('Blue','Objectives','Earn from the matchup','Which successful exchange should create a colored resource?','Objective predicate ↔ earn route',[
    row('Break a Wall',2,3,'Objective: Deal exchange damage against printed block 4+: gain Orange advantage.','Earn by punishing a strong guard.'),
    row('Stop a Heavy',2,3,'Objective: Take no exchange damage against printed attack 5+: gain Green advantage.','Earn by answering a heavy attack.'),
    row('Win Cleanly',2,3,'Objective: Deal exchange damage and take none: gain Green advantage.','Earn through a clean trade, regardless of printed stats.'),
    row('Punish a Heavy',2,3,'Objective: Deal exchange damage against printed attack 5+: gain Purple advantage.','Trade into a heavy attack to earn offense-oriented currency.'),
    row('Punish a Setup',2,3,'Objective: Deal exchange damage against an opposing bonus commitment: gain Red advantage.','Earn by hitting a committed opponent.'),
    row('Defuse a Setup',2,3,'Objective: Take no exchange damage against an opposing bonus commitment: gain Blue advantage.','Earn by defending against the same commitment.')
  ],'A generic deal-damage ladder did not cover the current set’s matchup-dependent and clean-outcome objectives.',
  'Keep objective evaluation after final exchange damage, with held-color caps and simultaneous denial. Exact original objective wording remains on every current-card baseline.',
  'Test printed conditions versus final damage, direct retaliation, already-held colors, Counterpunch denial, and simultaneous success.');

  add('Green','Objectives','Earn by conserving','Which kind of restraint should an objective reward?','Restricted burn mode ↔ reward access',[
    row('Unassisted Impact',4,1,'Objective: Deal 4+ exchange damage without an own bonus: gain Purple advantage.','Reactions remain available, but the hit must be large.'),
    row('Efficient Impact',4,1,'Objective: Deal exchange damage without an own bonus or reaction: gain Orange advantage.','Any hit works if you preserve both modes.'),
    row('Unanswered Impact',4,1,'Objective: Deal 2+ exchange damage without an own reaction: gain Blue advantage.','Bonus support is legal, but late support is not.')
  ],'No-bonus stat boosts were not the same mechanic as Haymaker and Measured Advance earning resources for different kinds of conservation.',
  'Separate no bonus, no reaction, and no own burns. Opposing burns never disqualify your conservation objective.',
  'Test each own mode separately and opposing burns. A bonus sacrifice cannot be undone just to restore the objective condition.');

  add('Blue','Reads','Read any bonus commitment','Should a committed-resource read reward attack, block, or both?','Fallback profile ↔ bonus-read allocation',[
    row('Pressure the Setup',3,2,'If the opponent committed a bonus, gain +1 attack.','Attack-led fallback.'),
    row('Meet the Setup',2,1,'If the opponent committed a bonus, gain +3 attack and +1 block.','A larger offensive reward, weaker ordinary guard.'),
    row('Cover the Setup',1,3,'If the opponent committed a bonus, gain +1 attack and +2 block.','Defensive fallback and read payoff.')
  ],'The refined bonus-read family only varied block. Watchful Guard and Firm Counter also reward attack or both stats.',
  'Represent all three payoff shapes without changing the underlying committed-bonus predicate. Reactions do not satisfy it.',
  'Test bonus and no-bonus commitments, then how each allocation changes both fighters’ damage thresholds.');

  add('Orange','Burn modes','Allocate the bonus','What should a permanent bonus sacrifice contribute to the exchange?','Bonus attack ↔ bonus block',[
    row('Offensive Fuel',3,2,'','Commit before reveal for attack.',{bonus:'Gain +3 attack this round.'}),
    row('Mixed Fuel',3,2,'','A smaller boost on both sides of the trade.',{bonus:'Gain +1 attack and +1 block this round.'}),
    row('Defensive Fuel',3,2,'','Commit before reveal for protection.',{bonus:'Gain +3 block this round.'})
  ],'Attack-fuel and reaction families did not directly represent the current cards’ defensive and mixed precommitted bonuses.',
  'Compare three bonus allocations with identical mains, reactions, and routes. Mixed fuel uses two points rather than three because two relevant thresholds can make flexibility valuable.',
  'Test lethal trades, surplus attack/block, defensive-bonus reads, and the opportunity cost of committing before seeing the opposing plan.');

  add('Purple','Reads','Hit into the heavy attack','How much offense should reading a large printed attack earn?','Printed-attack threshold ↔ offensive reward',[
    row('Challenge the Probe',3,1,'If opposing printed attack is 3+, gain +1 attack.','Broad trigger with modest offense.'),
    row('Challenge the Drive',3,1,'If opposing printed attack is 5+, gain +2 attack.','Middle threshold and reward.'),
    row('Challenge the All-In',3,1,'If opposing printed attack is 7+, gain +4 attack.','Narrowest read, largest offensive trade.')
  ],'The original high-attack family granted only block. Trade Blows, Rooted Guard, Counter Throw, and Read the Strike also turn that read into attack.',
  'Keep attack rewards distinct from defensive counters, while linking cards that combine both.',
  'Test the trade against a heavy hit: winning outgoing damage is not equivalent to surviving. Carry does not alter the printed trigger.');

  const byId=new Map(d.families.map(f=>[f.id,f]));
  // Every original main clause, objective, and spend is mapped independently.
  const specs=[
    [1,'F08',{main:['F15','F08']}],
    [2,'F21',{spend:['F21']}],
    [3,'F41',{main:['F41'],objective:['F61']}],
    [4,'F12',{main:[['F12','F07']],objective:['F61']}],
    [5,'F18',{spend:['F18']}],
    [6,'F11',{main:['F11'],objective:['F61']}],
    [7,'F42',{main:['F07'],spend:['F42']}],
    [8,'F12',{main:['F12'],objective:['F61']}],
    [9,'F38',{spend:[['F07','F38']]}],
    [10,'F43',{main:[['F43','F59'],['F44','F59']],objective:[['F43','F44','F59','F61']]}],
    [11,'F53',{main:['F53'],objective:[['F53','F61']]}],
    [12,'F65',{main:[['F11','F65']],objective:['F61']}],
    [13,'F11',{main:['F11'],objective:['F61']}],
    [14,'F45',{spend:['F45']}],
    [15,'F39',{main:['F14','F39'],objective:['F61']}],
    [16,'F12',{main:['F12','F21']}],
    [17,'F46',{main:['F46'],objective:['F46']}],
    [18,'F47',{main:['F54'],spend:['F47']}],
    [19,'F48',{main:['F65'],objective:['F48']}],
    [20,'F37',{main:['F53'],spend:['F37']}],
    [21,'F63',{main:['F63']}],
    [22,'F49',{objective:['F61'],spend:['F49']}],
    [23,'F02',{main:['F02']}],
    [24,'F15',{main:['F15'],objective:['F62']}],
    [25,'F11',{main:[['F11','F65']],objective:['F61']}],
    [26,'F54',{main:['F54']}],
    [27,'F63',{main:['F63'],objective:['F61']}],
    [28,'F12',{main:['F12']}],
    [29,'F55',{main:[['F13','F59'],['F55','F59']],spend:['F50']}],
    [30,'F34',{main:['F11'],spend:[['F18','F34']]}],
    [31,'F60',{main:['F60'],objective:['F62']}],
    [32,'F51',{main:['F15'],spend:['F51']}],
    [33,'F07',{main:['F07'],objective:['F61']}],
    [34,'F22',{main:['F15','F22'],objective:['F25']}],
    [35,'F65',{main:['F65']}],
    [36,'F52',{main:['F08','F52']}]
  ];
  const list=value=>Array.isArray(value)?value:[value];
  const bonusFamilies=text=>!text?['F58']:/^If /.test(text)?['F56']:/Your opponent gains/.test(text)?['F09']:/block as .*lower/.test(text)?['F07']:['F64'];
  const reactionFamilies=text=>!text?['F58']:/^If /.test(text)?['F57']:['F32'];
  const coverage=[];
  for(const [cardId,primary,mapping] of specs) {
    const card=live.cards.find(c=>c.id===cardId);if(!card)throw new Error(`Missing live card ${cardId}`);
    const facets={main:[],objective:[],spend:[],bonus:[],reaction:[],routes:[]};
    for(const clause of card.ability.split('\n').filter(Boolean)) {
      const kind=clause.startsWith('Objective:')?'objective':clause.startsWith('Spend ')?'spend':'main';
      const ids=mapping[kind]?.[facets[kind].length];
      if(!ids)throw new Error(`Unmapped ${card.name} ${kind}: ${clause}`);
      facets[kind].push({text:clause,families:list(ids)});
    }
    for(const kind of ['main','objective','spend'])if((mapping[kind]?.length||0)!==facets[kind].length)throw new Error(`Stale mapping for ${card.name} ${kind}`);
    facets.bonus.push({text:card.bonus||'Unavailable',families:bonusFamilies(card.bonus)});
    facets.reaction.push({text:card.reaction||'Unavailable',families:reactionFamilies(card.reaction)});
    facets.routes.push({text:card.switches.join(' / '),families:card.switches.includes(card.color)?['F36','F37','F40']:['F36','F40']});
    const entry={cardId,name:card.name,color:card.color,primary,facets};coverage.push(entry);
    for(const facet of Object.values(facets).flat())for(const id of facet.families) {
      const f=byId.get(id);if(!f)throw new Error(`Unknown coverage family ${id}`);
      f.sourceCards??=[];if(!f.sourceCards.some(s=>s.cardId===cardId))f.sourceCards.push({cardId,name:card.name,primary});
    }
    byId.get(primary).variants.push({id:`${primary}-L${cardId}`,name:card.name,color:card.color,attack:card.attack,block:card.block,text:card.ability,
      bonus:card.bonus,reaction:card.reaction,routes:card.switches,tags:[],cost:0,costLabel:card.ability.includes('Spend ')?'OPT TOKENS':'FREE',
      liveCardId:cardId,liveRulesVersion:live.rulesVersion,bet:'Current-card baseline: exact stats, main text, burns, and printed destinations from the local catalog. No draft tags or proposed rules are added.'});
  }
  // Resolve the one new shared draft reference without copying or changing a live card.
  for(const f of d.families)for(let i=0;i<f.variants.length;i++) {
    const v=f.variants[i];if(!v.referenceOf||!f.id.startsWith('F60'))continue;
    const sf=d.families.find(x=>x.variants.some(s=>s.id===v.referenceOf)),s=sf.variants.find(s=>s.id===v.referenceOf);
    f.variants[i]={...s,bonus:s.bonus??sf.bonus,reaction:s.reaction??sf.reaction,routes:s.routes??sf.routes,id:v.id,bet:v.bet,referenceOf:v.referenceOf};
  }
  d.coverage=coverage;d.liveSource={rulesVersion:live.rulesVersion,sha256:live.sha256};d.revision=3;
  d.entryCount=d.families.reduce((n,f)=>n+f.variants.length,0);d.liveCount=coverage.length;d.proposalCount=d.entryCount-d.liveCount;
  d.conventions.unshift('Revision 3 merges the refined proposals with every current v44 card. CURRENT badges reproduce the exact local catalog; DRAFT and REFERENCE badges remain proposals. A matching mechanic does not mean a draft is an identical replacement.');
  d.conventions.push('Every nonempty original main line, objective, spend, bonus, reaction, and printed-route set has a coverage mapping. Unavailable burn modes map explicitly to the mode-availability family. Empty objective/spend fields are shown as absent, not silently counted as mechanics.');
  d.conventions.push('Current baselines have their original colors and no invented move tags. Mixed-color families label each card individually; shared draft routes and burns do not override current-card definitions. Proposed status timing applies only to drafts, not retroactively to the current game.');
  d.conventions.push('The live catalog is a read-only static snapshot. The audit compares its hash and contents with the repository source. After a live card edit, refresh the snapshot and review the semantic mappings; automatic copying alone cannot prove a mapping is still appropriate.');
})();
