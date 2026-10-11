"""Heavy Hitters saved card-editor source; every identity must be accounted for."""
import ast,json,hashlib,re
from pathlib import Path
HERE=Path(__file__).parent
for filename,names in [('build_mon_abilities.py',['clean']),('build_dyn_abilities.py',['pick']),('build_out_abilities.py',['pay']),('build_upr_abilities.py',['deal'])]:
 src=(HERE/filename).read_text()
 for node in ast.parse(src).body:
  if isinstance(node,ast.FunctionDef) and node.name in names:exec(ast.get_source_segment(src,node))
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
V="$victim = intval(FaBGetState()['defender']);"
def choose(expr,tip='Choose_card',may=True,p='$player',var='$chosen'):
 return f'{var} = "-"; $refs = {expr}; if ($refs !== "") {{ '+(f'{var} = await {p}.MZMultiChoose($refs, 0, 1, "{tip}");' if may else f'{var} = await {p}.MZChoose($refs, "{tip}");')+' }'
def many(expr,maximum,minimum=0,tip='Choose_cards'):
 return f'$minimum = {minimum}; $refs = {expr}; $chosen = "-"; $maximum = min({maximum}, count(array_filter(explode("&", $refs)))); if ($maximum >= $minimum && $maximum > 0) {{ $chosen = await $player.MZMultiChoose($refs, $minimum, $maximum, "{tip}"); }}'
def refs(z,p='$player',filters='[]'):return f"implode('&', FaBChoiceRefs({p}, '{z}', {filters}))"
def token(t,p='$player',n=1):return f"FaBHVYToken({p}, '{t}', {n}, $player);"
def hit(code):return 'if (FaBFaiHeroHit()) { '+V+code+' }'
def wager(tokens,optional=True):
 return 'if (FaBFaiHeroHit()) { '+V+('$mode = await $player.Modal(1, 1, "Wager&Decline", "Wager_with_defending_hero"); if ($mode === "0") { ' if optional else '')+"FaBHVYWager($player, $uid, $victim, "+str(tokens).replace('"',"'")+');'+(' }' if optional else '')+' }'
def clash(prize='',opponent="intval(FaBGetState()['attacker'])"):
 return f'$other = {opponent}; $clash = FaBHVYClash($player, $other); $retry = FaBHVYClashRetry($clash); while ($retry > 0) {{ '+choose('FaBHVYGold($retry)','Destroy_Gold_to_clash_again',True,'$retry')+"if (FaBHVYDestroyGold($retry, $chosen)) { $previews = FaBHVYClashPreviews($retry, $clash); "+choose('$previews','Bottom_one_revealed_card',False,'$retry')+"FaBHVYBottomPreview($chosen, $previews); $clash = FaBHVYClash($player, $other); } $retry = FaBHVYClashRetry($clash); } "+f"$winner = FaBHVYClashWon($clash, $player, '{prize}');"
original_deal=deal
def deal(*args,**kwargs):
 code=original_deal(*args,**kwargs)
 code=re.sub(r'MZMayChoose\((\$\w+), ',r'MZMultiChoose(\1, 0, 1, ',code)
 return code.replace("=== 'PASS'","=== '-'")
def physical(n):return UID+'$targetUID = FaBUPRHeroUID($victim); '+deal(n,physical=True)
def dice():
 return '$roll = EngineRandomInt(1, 6); $seats = FaBLiveSeats(); for ($i = 0; $i < count($seats); $i = $i + 1) { $roller = intval($seats[$i]); $gloves = FaBCRUEquipment($roller, "gamblers_gloves"); for ($j = 0; $j < count($gloves); $j = $j + 1) { $gloveUID = intval(FaBIdentityFromMZ($gloves[$j])["object"]->UniqueID); $tip = "Rolled_" . $roll; $mode = await $roller.Modal(1, 1, "Keep&Destroy_gloves_and_reroll", $tip); if ($mode === "1") { FaBMONDestroy($gloveUID); $roll = EngineRandomInt(1, 6); } } } FaBCRULegacyRoll($player, "", $roll);'
existing={c['cardId']:c for p in sorted(HERE.glob('*_abilities.json')) if p.name!='hvy_abilities.json' for c in json.loads(p.read_text())}
old={c['cardId']:c for c in json.loads((HERE/'hvy_abilities.json').read_text())} if (HERE/'hvy_abilities.json').exists() else {}
out=[];pending=[]
for c in json.loads((HERE/'hvy_catalog.json').read_text()):
 id=c['id'];b=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id;v=4-int(c['pitch'] or 0);txt=c['functional_text_plain'];a=[];handled=False
 def add(m,code):
  global handled
  handled=True;prior=next((x for x in a if x['macroName']==m),None)
  if prior:prior['abilityCode']+='\n'+clean(code)
  else:a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing and b not in ['might','vigor','colossal_bearing','bare_fangs','wild_ride']:
  out.append(existing[id]);continue
 if 'Beat Chest' in txt:add('PrepareCard',UID+choose('FaBHVYBeatChoices($player)','Discard_six_power_to_beat_chest')+"FaBHVYBeat($player, $chosen); FaBFinishPreparedCard($uid);")
 if b in ['assault_and_battery','pound_town','rawhide_rumble']:
  body=token('agility' if b=='assault_and_battery' else 'might') if b!='rawhide_rumble' else 'FaBRequestIntimidate($player);'
  add('AttackDeclared',"if (FaBHVYCount($player, 'BEAT')) { "+body+' }')
 if b=='bonebreaker_bellow':add('ResolveCard',f"FaBHVYNext($player, {v} + (FaBHVYCount($player, 'BEAT') ? 2 : 0), 'Brute');")
 if b.startswith('wage_') or b=='bet_big':add('AttackDeclared',UID+wager(['gold','might','vigor'] if b=='bet_big' else [b[5:]]))
 if b.startswith('clash_of_') or b.startswith('test_of_'):add('Defended',clash('gold' if b=='test_of_strength' else b.split('_')[-1]))
 if b=='stonewall_impasse':add('Defended',UID+clash()+"if ($winner === $player) { FaBTagUID($uid, 'WTR_DEFENSE:1'); }")
 if b=='millers_grindstone':add('Hit',hit(UID+clash('', '$victim')+"if ($winner === $player) { FaBHVYDestroyRevealed($clash, $victim); } else { if ($winner === $victim) { FaBHVYGrindstone($uid); } }"))
 if b=='trounce':add('Defended',clash()+"$firstWinner = $winner; FaBHVYBottomClash($clash); "+clash()+"if ($winner > 0 && $firstWinner === $winner) { "+token('gold','$winner')+token('might','$winner')+token('vigor','$winner')+' }')
 if b in ['agile_windup','mighty_windup','vigorous_windup']:add('ResolveAbility',token({'agile_windup':'agility','mighty_windup':'might','vigorous_windup':'vigor'}[b]))
 if b=='ripple_away':add('ResolveAbility',"FaBHVYAdd($player, 'RIPPLE');")
 if b in ['big_bop','bigger_than_big']:
  add('ResolveCard',"FaBMoveUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'Arena', $player);")
  add('StartTurn',UID+f"FaBMONDestroy($uid); FaBHVYNext($player, {v+2}, 'Guardian', 'HVY_WAGER:{'vigor' if b=='big_bop' else 'might'}');")
 if b in ['lead_with_heart','lead_with_power','lead_with_speed']:
  classes,t={'lead_with_heart':('Guardian|Warrior','vigor'),'lead_with_power':('Brute|Guardian','might'),'lead_with_speed':('Brute|Warrior','agility')}[b];add('ResolveCard',f"FaBHVYNext($player, {v}, '{classes}');"+token(t))
 if b in ['edge_ahead','hold_em','draw_swords','engaged_swiftblade','money_where_ya_mouth_is']:
  tag={'edge_ahead':'HVY_WAGER:agility','hold_em':'HVY_WAGER:vigor','engaged_swiftblade':'HVY_ENGAGED','money_where_ya_mouth_is':'HVY_WAGER:gold'}.get(b,'');classes='' if b=='money_where_ya_mouth_is' else 'Warrior'
  add('ResolveCard',f"FaBHVYNext($player, {v}, '{classes}', '{tag}');"+('DoDrawCard($player, 1);' if b=='draw_swords' else ''))
 if b in ['agile_engagement','vigorous_engagement','cut_the_deck','blade_flurry','fatal_engagement','take_the_upper_hand']:
  kind='weapon' if b=='blade_flurry' else ('' if b in ['fatal_engagement','take_the_upper_hand'] else 'warrior');power=v-1 if b=='blade_flurry' else v+2 if b=='fatal_engagement' else v
  code=choose(f"FaBHVYAttackTargets($player, '{kind}')",'Choose_attack',False)+f"FaBDYNTag($chosen, 'WTR_POWER:{power}');"
  if b=='blade_flurry':code+=f"FaBWTRAddEffect($player, 'NEXT_WEAPON', {power});"
  if b in ['agile_engagement','vigorous_engagement']:code+="if (FaBHVYActionBlock(FaBGetState())) { "+token('agility' if b=='agile_engagement' else 'vigor')+' }'
  if b=='cut_the_deck':code+="if (FaBHVYActionBlock(FaBGetState())) { DoDrawCard($player, 1); "+choose("FaBHVYHandArsenal($player)",'Bottom_hand_or_arsenal',False)+"FaBHVYBottomChoice($chosen); }"
  add('ResolveCard',code)
 if b=='commanding_performance':add('ResolveCard',"FaBHVYNext($player, 3, 'Warrior'); FaBHVYAdd($player, 'COMMANDING');")
 if b in ['command_respect','concuss']:add('Hit',hit(UID+"if (FaBHVYAbove($uid)) { "+("FaBHVYDestroyArsenal($victim);" if b=='command_respect' else choose(refs('Hand','$victim'),'Discard_a_card',False,'$victim')+'FaBDiscardChoice($victim, $chosen);')+' }'))
 if b in ['performance_bonus','down_but_not_out']:add('Hit',hit(("if (FaBARCCard(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'hvyDown')) { "+token('agility')+token('might')+token('vigor')+' }') if b=='down_but_not_out' else token('gold')))
 if b=='runner_runner':add('AttackDeclared',"if (FaBAttackHasGoAgain(FaBGetState(), FaBIdentityFromMZ($mzID)['object'])) { "+token('agility')+' }')
 if b in ['bare_fangs','wild_ride']:add('AttackDeclared',UID+"DoDrawCard($player, 1); $discard = FaBRandomHandUID($player); $six = $discard > 0 && FaBHVYOwnedPower($player, FaBFindUID($discard)['object']) >= 6; if ($discard > 0) { FaBDiscardChoice($player, FaBDTDSource($discard)); } if ($six) { FaBTagUID($uid, '"+('WTR_POWER:2' if b=='bare_fangs' else 'GO_AGAIN')+"'); }")
 if b=='show_no_mercy':add('AttackDeclared','FaBRequestIntimidate($player);')
 if b=='cast_bones':add('ResolveCard','FaBHVYCastBones($player);')
 if b=='starting_stake':add('ResolveCard',"if (count(FaBChoiceRefs($player, 'Arena', ['base'=>'gold'])) === 0) { "+token('gold')+' }')
 if b in ['smashback_alehorn','pint_of_strong_and_stout','goblet_of_bloodrun_wine']:
  ts={'smashback_alehorn':['agility','might'],'pint_of_strong_and_stout':['might','vigor'],'goblet_of_bloodrun_wine':['agility','vigor']}[b];add('ResolveCard',''.join(token(t) for t in ts))
 if b in ['flat_trackers','gauntlet_of_might','vigor_girth']:add('ResolveAbility',token({'flat_trackers':'agility','gauntlet_of_might':'might','vigor_girth':'vigor'}[b]))
 if b in ['balance_of_justice','glory_seeker']:add('ResolveAbility','DoDrawCard($player, 1);')
 if b=='sheltered_cove':add('ResolveAbility',"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 2);")
 if b in ['battered_not_broken','slap_happy','take_it_on_the_chin']:add('ResolveCard',f"FaBHVYAdd($player, 'PREVENT_TOKEN', 2, ['token'=>'{ {'battered_not_broken':'might','slap_happy':'vigor','take_it_on_the_chin':'agility'}[b]}']);")
 if b=='no_fear':
  add('PrepareCard',UID+many('FaBHVYSix($player)',99,0,'Banish_six_power_cards')+'FaBHVYNoFear($player, $uid, $chosen); FaBFinishPreparedCard($uid);')
  add('ResolveCard',UID+"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 2 + intval(FaBARCCard($uid, 'hvyFear')));")
 if b in ['double_down','the_golden_son','raise_an_army']:
  add('PrepareCard',UID+many('FaBHVYGold($player)',99 if b=='raise_an_army' else 1,"(FaBAvailablePitch($player) < intval(FaBGetState()['pendingPayment']['cost'] ?? 0) ? 1 : 0)" if b=='double_down' else 0,'Destroy_Gold')+f"FaBHVYCostGold($player, $uid, $chosen, {'true' if b=='double_down' else 'false'}); FaBFinishPreparedCard($uid);")
  if b=='double_down':add('ResolveCard',"FaBHVYAdd($player, 'DOUBLE_DOWN'); FaBHVYAdd($player, 'NEXT_WAGER', 3);")
  if b=='the_golden_son':add('AttackDeclared',UID+"if (FaBARCCard($uid, 'hvyGold')) { FaBTagUID($uid, 'WTR_POWER:3'); FaBTagUID($uid, 'OVERPOWER'); }")
  if b=='raise_an_army':add('ResolveCard',UID+token('cintari_sellsword',n="intval(FaBARCCard($uid, 'hvyGold'))"))
 if b in ['betsy','betsy_skin_in_the_game']:add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('hvyAttackUID')); "+pay(2)+"if ($paid) { FaBTagUID($uid, 'WTR_POWER:1'); FaBTagUID($uid, 'OVERPOWER'); }")
 if b=='grains_of_bloodspill':add('ResolveAbility',pay(1)+"if ($paid) { "+token('vigor')+' }')
 if b in ['kassai','kassai_of_the_golden_sand','hood_of_red_sand']:
  each=1 if b=='hood_of_red_sand' else 2
  add('PrepareCard',UID+many(refs('Graveyard',filters="['pitch'=>1]"),each,each,'Banish_red')+'$reds = $chosen; '+many(refs('Graveyard',filters="['pitch'=>2]"),each,each,'Banish_yellow')+f"FaBHVYBanishColors($player, $reds . '&' . $chosen, {each}); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',choose("FaBHVYAttackTargets($player, 'sword')",'Choose_sword_attack',False)+"FaBDYNTag($chosen, 'WTR_DRAW_HIT');" if each==1 else "FaBHVYAdd($player, 'KASSAI_GOLD');")
 if b=='good_time_chapeau':
  add('PrepareCard',UID+choose('FaBHVYGold($player)','Destroy_Gold',False)+"FaBHVYDestroyGold($player, $chosen); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',"FaBHVYNext($player, 0, '', 'HVY_WAGER_ALWAYS:might,vigor');")
 if b=='prized_galea':add('ResolveAbility',"$uid = intval(FaBGetState()['attackUID']);"+wager(['gold'],False))
 if b=='knucklehead':add('ResolveAbility',dice()+"FaBHVYIntellect($player, $roll);")
 if b=='monstrous_veil':add('ResolveAbility',"DoDrawCard($player, 1); FaBDiscardRandom($player, 1);")
 if b=='mini_meataxe':add('AttackDeclared',"DoDrawCard($player, 1); FaBDiscardRandom($player, 1);")
 if b=='reckless_charge':add('ResolveCard',dice()+"AddActionPoints($player, intval(GetActionPoints($player)) + intdiv($roll, 2)); if (FaBDTDCount($player, 'ROLL_SIX')) { DoDrawCard($player, 1); }")
 if b=='standing_order':
  code=UID+choose(refs('Arsenal'),'Bottom_arsenal_for_power_and_defense')+"if ($chosen !== '-') { FaBHVYBottomChoice($chosen); FaBTagUID($uid, 'WTR_POWER:2'); FaBTagUID($uid, 'WTR_DEFENSE:2'); }"
  add('AttackDeclared',code);add('Defended',code)
 if b=='hearty_block':add('Defended',"if (FaBMONArena($player, 'vigor')) { FaBCRUGainLife($player, 1); }")
 if b=='run_into_trouble':add('Defended',"if (FaBMONArena($player, 'agility')) { $victim = intval(FaBGetState()['attacker']); "+physical(1)+' }')
 if b=='wall_of_meat_and_muscle':add('Defended',"if (FaBMONArena($player, 'might')) { "+choose(refs('Graveyard',filters="['attackAction'=>true]"),'Top_attack_from_graveyard')+"FaBDTDTopChoice($player, $chosen); }")
 if b=='pack_call':add('Defended',"FaBHVYPackCall($player);")
 if b=='colossal_bearing':add('Hit',hit("if (FaBAttackPower(FaBGetState()) >= 13) { "+choose('FaBHVYSmallEquipment($victim)','Destroy_equipment',False)+"FaBDYNDestroyChoice($chosen); }"))
 if b=='smack_of_reality':add('Hit',hit("if (FaBAttackPower(FaBGetState()) >= 13) { FaBHVYDestroyAuraTokens($victim); }"))
 if b=='pay_up':add('Hit',hit(choose(refs('Arena','$victim',"['base'=>'gold']"),'Take_Gold',False)+"if ($chosen !== '-') { FaBUPRSteal($player, $chosen); } else { "+physical(1)+' }'))
 if b=='dissolve_reality':add('ResolveCard',"FaBHVYDissolve($player);")
 if b=='stacked_in_your_favor':
  add('ResolveCard',UID+"FaBMoveUID($uid, 'Arena', $player);")
  add('StartTurn',UID+"FaBMONDestroy($uid); DoDrawCard($player, 1); "+choose(refs('Hand'),'Top_card_from_hand',False)+"FaBDTDTopChoice($player, $chosen);")
 if b=='money_where_ya_mouth_is':add('ResolveAbility',UID+"$tokens = (array)DecisionQueueController::GetVariable('hvyTokens'); $mode = '0'; if (!DecisionQueueController::GetVariable('hvyMandatory')) { $mode = await $player.Modal(1, 1, \"Wager&Decline\", \"Wager_with_defending_hero\"); } if ($mode === '0') { "+V+"FaBHVYWager($player, $uid, $victim, $tokens); }")
 if b=='aether_arc':add('ResolveCard',UID+"$seats = FaBOpponents($player); $damaged = 0; for ($i = 0; $i < count($seats); $i = $i + 1) { $targetUID = FaBUPRHeroUID(intval($seats[$i])); "+deal(1)+"if ($dealt > 0) { $damaged = $damaged + 1; } } "+token('ponder',n='$damaged'))
 if b=='ancestral_harmony':add('ResolveCard',"FaBHVYAdd($player, 'COMBO_POWER'); FaBHVYHarmony($player);")
 if b=='coercive_tendency':add('ResolveCard',V+"$uids = FaBHVYPeek($player, $victim, 3); if (count($uids) > 0) { $orderParam = FaBARCOrderParam($uids, 'Top'); $order = await $player.Rearrange($orderParam); FaBHVYFinishPeek($player, $victim, $uids, $order); $before = FaBDYNCount($player, 'CONTRACTS'); $top = FaBChoiceRefs($victim, 'Deck')[0] ?? ''; FaBDYNBanishChoice($top, $player); if (FaBDYNCount($player, 'CONTRACTS') > $before) { FaBHVYAdd($player, 'ASSASSIN_CHAIN'); } }")
 if b=='deathmatch_arena':add('ResolveCard',UID+"FaBMoveUID($uid, 'Arena', $player);")
 if b=='evo_magneto':
  add('ResolveCard',"FaBEVOTransform($player, $mzID);")
  add('Defended',UID+"$previews = FaBEVOUnderPreview($player, $uid); "+choose('$previews','Destroy_material_to_take_item')+"$paid = FaBEVODestroyUnder($player, $uid, $chosen, $previews); if ($paid) { $victim = intval(FaBGetState()['attacker']); "+choose("FaBHVYCheapItems($victim)",'Take_item',False)+"FaBUPRSteal($player, $chosen); }")
 if b=='graven_call':
  add('PrepareCard',UID+many(refs('Arena',filters="['base'=>'silver']"),2,2,'Destroy_two_Silver')+"FaBHVYDestroyChoices($chosen); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',UID+"$weapons = FaBHVYWeaponReplacements($player); if ($weapons !== '') { "+choose('$weapons','Replace_weapon',False)+"FaBDYNDestroyChoice($chosen); } FaBHVYGraven($player, $uid);")
 if b=='judge_jury_executioner':add('Hit',hit(UID+"if (FaBDYNAimed($uid)) { "+choose(refs('Hand','$victim'),'Keep_one_card',False,'$victim')+"FaBHVYDiscardExcept($victim, $chosen); }"))
 if b=='seduce_secrets':add('ResolveCard',choose('FaBDYNHeroTargets($player)','Inspect_hero',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); $hand = FaBDYNPrivateHand($player, $victim); $uids = FaBHVYPeek($player, $victim, 1); $shown = array_merge($hand, $uids); if (count($shown) > 0) { $orderParam = FaBARCOrderParam($shown, 'Cards'); $ignored = await $player.Rearrange($orderParam); } FaBHVYClearPreviews(FaBEVRUIDRefs($hand)); FaBDYNFinishPeek($victim, $uids, false); if (DecisionQueueController::GetVariable('fabSourceZone') === 'Arsenal') { DoDrawCard($player, 1); }")
 if b=='send_packing':
  add('AttackDeclared',UID+"if (FaBFaiHeroHit()) { FaBHVYSendPacking($player, $uid); }")
  add('ChainLinkResolved',UID+"FaBHVYReturnPacking($uid);")
 if b=='shift_the_tide_of_battle':add('ResolveCard',choose("FaBHVYAttackTargets($player, 'warriorAbove')",'Choose_attack',False)+"FaBDYNTag($chosen, 'GO_AGAIN'); FaBHVYAdd($player, 'SHIFT');")
 if b=='talk_a_big_game':add('ResolveCard',"$n = await $player.NumberChoose(0, 1000, \"Choose_damage_threshold\"); FaBHVYAdd($player, 'TALK', intval($n));")
 if b in ['reel_in','sonata_galaxia','up_the_ante']:
  add('PrepareCard',UID+"$maxX = FaBHVYMaxX($player, $uid); "+("$maxX = min(3, $maxX); " if b=='up_the_ante' else '')+"$x = await $player.NumberChoose(0, $maxX, \"Choose_X\"); FaBHVYSetX($uid, intval($x)); FaBFinishPreparedCard($uid);")
  if b=='reel_in':add('ResolveCard',UID+"$uids = FaBHVYPeek($player, $player, intval(FaBARCCard($uid, 'evoX')) + 1); if (count($uids) > 0) { $orderParam = FaBARCOrderParam($uids, 'Cards'); $ignored = await $player.Rearrange($orderParam); } "+many('FaBHVYTraps($uids)',4,0,'Take_up_to_four_traps')+"FaBHVYReel($player, $uids, $chosen); if (FaBELEArsenalSpace($player)) { "+choose(refs('Hand'),'Reload')+"FaBARCLoadArsenal($player, $chosen, false); }")
  if b=='sonata_galaxia':add('ResolveCard',UID+"$x = intval(FaBARCCard($uid, 'evoX')); "+choose('FaBHVYSonataRefs($player, $x)','Put_Runeblade_aura_into_arena')+"FaBHVYPutAura($player, $chosen); FaBFinishSearch($player); if ($x >= 2) { FaBTagUID($uid, 'GO_AGAIN'); }")
  if b=='up_the_ante':add('ResolveCard',UID+"$n = min(4, intval(FaBARCCard($uid, 'evoX')) + 1); $modes = await $player.Modal($n, $n, \"Wager_Agility&Wager_Gold&Wager_Vigor&Increase_power\", \"Choose_modes\"); $attackUID = intval(FaBGetState()['attackUID']); "+V+"FaBHVYAnte($player, $attackUID, $victim, $modes);")
 if b in ['cintari_sellsword','gauntlets_of_iron_will','nasty_surprise']:handled=True
 if b in ['might','vigor']:handled=True
 if b in ['kayo','kayo_armed_and_dangerous','victor_goldmane','victor_goldmane_high_and_mighty','olympia','olympia_prized_fighter','aurum_aegis','ball_breaker','high_riser','hot_streak','luminaris_angels_glow','apex_bonebreaker','golden_glare','beckon_applause','raw_meat','stand_ground','bloodied_oval','headliner_helm','grandstand_legplates','stadium_centerpiece','ticket_puncher','confront_adversity','embrace_adversity','overcome_adversity','face_adversity','boast','primed_to_fight','rising_energy','rising_power','rising_speed','beast_mode','over_the_top','thunk','wallop','lay_down_the_law','parry_blade']:handled=True
 if not handled:pending.append(id)
 out.append(dict(cardId=id,abilities=a))
if pending:print('Unhandled',pending);raise SystemExit(1)
for c in out:
 for a in c['abilities']:
  prior=next((x for x in old.get(c['cardId'],existing.get(c['cardId'],{})).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior is None:prior=next((x for x in existing.get(c['cardId'],{}).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior and prior.get('previousCodeHash'):a['previousCodeHash']=prior['previousCodeHash']
  if prior and prior['abilityCode']!=a['abilityCode']:a['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
(HERE/'hvy_abilities.json').write_text(json.dumps(out,indent=2)+'\n');print('HVY:',len(out),'identities;',sum(len(c['abilities']) for c in out),'macros')
