"""Author every functional Outsiders identity; fail closed on unhandled families."""
import ast,json,hashlib
from pathlib import Path
HERE=Path(__file__).parent
for filename,names in [('build_mon_abilities.py',['clean']),('build_arc_abilities.py',['opt']),('build_dyn_abilities.py',['pick','multi'])]:
 src=(HERE/filename).read_text()
 for node in ast.parse(src).body:
  if isinstance(node,ast.FunctionDef) and node.name in names:exec(ast.get_source_segment(src,node))
existing={}
for path in sorted(HERE.glob('*_abilities.json')):
 if path.name!='out_abilities.json':
  for c in json.loads(path.read_text()):existing[c['cardId']]=c
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
VICTIM="$victim = intval(FaBGetState()['defender']);"
def hit(code):return "if (FaBFaiHeroHit()) { "+VICTIM+code+" }"
def tag(expr,tip,power,extra=''):
 return pick(expr,tip,False)+f"FaBDYNTag($chosen, 'WTR_POWER:{power}');"+(f"FaBDYNTag($chosen, '{extra}');" if extra else '')
def pay(cost,tip='Pay_resources'):
 return f"$paid = false; if (FaBAvailablePitch($player) >= {cost}) {{ $mode = await $player.Modal(1, 1, \"Decline&{tip}\", \"Optional_payment\"); if ($mode === '1') {{ while (intval(GetResources($player)) < {cost}) {{ "+pick('FaBARCPitchChoices($player)','Pitch_to_pay',False)+"FaBARCPitchForEffect($player, $chosen); } "+f"AddResources($player, intval(GetResources($player)) - {cost}); $paid = true; }} }}"
def inspect():return pick('FaBDYNHeroTargets($player)','Choose_hero_to_inspect',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); $uids = FaBDYNPeekTop($player, $victim); if (count($uids) > 0) { $orderParam = FaBARCOrderParam($uids, 'Top'); $order = await $player.Rearrange($orderParam); FaBDYNFinishPeek($victim, $uids, false); }"
def throw():return pick('FaBArakniDaggers($player)','Choose_dagger',False,var='$dagger')+"$daggerUID = intval(FaBIdentityFromMZ($dagger)['object']->UniqueID ?? 0);"+pick('FaBDYNHeroTargets($player)','Choose_hero_for_dagger',False)+"$dagger = FaBOUTSource($daggerUID); FaBOUTThrow($player, $dagger, $chosen);"
cards=json.loads((HERE/'out_catalog.json').read_text());out=[];pending=[]
for c in cards:
 id=c['id'];b=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id;v=4-int(c['pitch'] or 0);a=[];handled=False
 def add(m,code):
  global handled
  handled=True;a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing:out.append(existing[id]);continue
 if b in ['infect','sedate','wither','infecting_shot','sedation_shot','withering_shot']:
  token={'infect':'bloodrot_pox','infecting_shot':'bloodrot_pox','sedate':'inertia','sedation_shot':'inertia','wither':'frailty','withering_shot':'frailty'}[b];add('Hit',hit(f"FaBOUTToken($victim, '{token}');"))
 if b=='death_touch':add('Hit',hit('$mode = await $player.Modal(1, 1, "Frailty&Inertia&Bloodrot_Pox", "Choose_disease"); FaBOUTDisease($victim, $mode);'))
 if b=='virulent_touch':add('ChainLinkResolved',hit("if (FaBWTRDefendedFromHand(FaBGetState())) { FaBOUTToken($victim, 'bloodrot_pox'); }"))
 if b in ['lace_with_bloodrot','lace_with_frailty','lace_with_inertia','spike_with_bloodrot','spike_with_frailty','spike_with_inertia']:
  token=b.split('_')[-1];token='bloodrot_pox' if token=='bloodrot' else token
  add('ResolveCard',f"FaBOUTNext($player, 'arrow', 3, 'OUT_DISEASE:{token}');" if b.startswith('lace') else tag("FaBOUTTargets($player, 'stealth')",'Choose_stealth_attack',3,'OUT_DISEASE:'+token))
 if b=='razors_edge':add('ResolveCard',tag("FaBOUTTargets($player, 'stealth')",'Choose_stealth_attack',v))
 if b=='short_and_sharp':add('ResolveCard',tag("FaBOUTTargets($player, 'short')",'Choose_dagger_or_small_attack',v))
 if b=='knives_out':add('ResolveCard',"FaBOUTAdd($player, 'DAGGER_POWER');")
 if b=='plunge':add('Hit',"FaBOUTNext($player, 'dagger', 1);")
 if b=='deadly_duo':add('Hit',"FaBOUTNext($player, 'small', 2, '', true);")
 if b=='toxic_tips':add('ResolveAbility',"FaBOUTNext($player, 'aa', 0, 'OUT_TOXIC_TIPS');")
 if b=='toxicity':add('ResolveCard',f"FaBOUTNext($player, 'hybrid', 0, 'OUT_TOXICITY:{v+2}');")
 if b in ['fletch_a_red_tail','fletch_a_yellow_tail','fletch_a_blue_tail']:
  pitch={'fletch_a_red_tail':1,'fletch_a_yellow_tail':2,'fletch_a_blue_tail':3}[b];add('ResolveCard',f"FaBOUTNext($player, 'arrow', {5-pitch}, 'OUT_FLETCH:{pitch}');")
 if b=='melting_point':
  add('ResolveCard',"FaBOUTNext($player, 'arrow', 4, 'OUT_MELTING');")
  add('Hit',VICTIM+pick('FaBOUTMeltingTargets($victim)','Destroy_one_power_weapon',False)+"FaBDYNDestroyChoice($chosen);")
 if b=='spreading_plague':add('ResolveCard',VICTIM+"$n = count(FaBOUTDefenders()); FaBOUTToken($victim, 'bloodrot_pox', $n);")
 if b in ['bloodrot_trap','frailty_trap','inertia_trap','boulder_trap','buzzsaw_trap','collapsing_trap','pendulum_trap','spike_pit_trap','tarpit_trap']:
  body=""
  if b in ['bloodrot_trap','frailty_trap','inertia_trap']:body="FaBOUTToken($victim, '"+('bloodrot_pox' if b=='bloodrot_trap' else b.split('_')[0])+"');"
  if b=='boulder_trap':body=pick("FaBDYNEquipment($victim)",'Weaken_equipment',False)+"FaBWeakenEquipment($chosen);"
  if b=='buzzsaw_trap':body="$attackUID = intval(FaBGetState()['attackUID']); FaBTagUID($attackUID, 'OUT_NO_GAIN');"
  if b=='collapsing_trap':body='FaBOUTCollapse($player);'
  if b=='pendulum_trap':body='FaBOUTMill($victim, 2);'
  if b=='spike_pit_trap':body='FaBOUTSpikePit($player);'
  if b=='tarpit_trap':body="FaBOUTAdd($player, 'TARPIT');"
  add('Defended',UID+"$triggers = FaBOUTTrapCondition(FaBIdentityFromMZ($mzID)['object']); if ($triggers) { $victim = intval(FaBGetState()['attacker']); FaBOUTTrapTriggered($player); "+body+" }")
 if b in ['uzuri','uzuri_switchblade']:
  add('PrepareCard',UID+pick("implode('&', FaBChoiceRefs($player, 'Hand'))",'Banish_card_face_down',False)+"FaBOUTBanishCost($player, $uid, $chosen); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',"$stackUID = intval(DecisionQueueController::GetVariable('fabAbilityStackUID')); FaBOUTSwap($player, $stackUID);")
 if b in ['riptide','riptide_lurker_of_the_deep']:add('ResolveAbility',pick("implode('&', FaBChoiceRefs($player, 'Hand'))",'Load_card_face_down')+"if ($chosen !== 'PASS') { FaBARCLoadArsenal($player, $chosen, false); }")
 if b=='barbed_castaway':add('ResolveAbility',"$index = intval(DecisionQueueController::GetVariable('arcAbilityIndex')); if ($index === 0) { "+pick("implode('&', FaBChoiceRefs($player, 'Hand', ['type'=>'Arrow']))",'Load_arrow')+"FaBARCLoadArsenal($player, $chosen, true); } else { "+pick('FaBOUTArsenal($player, true, true)','Aim_face_down_arrow')+"FaBOUTAim($chosen); }")
 if b=='blade_cuff':add('ResolveAbility',"FaBOUTAdd($player, 'DAGGER_POWER');")
 if b=='flick_knives':add('ResolveAbility',throw())
 if b=='hurl':
  add('PrepareCard',UID+pay(1,'Pay_to_throw_dagger')+"if ($paid) { FaBTagUID($uid, 'OUT_HURL'); } FaBFinishPreparedCard($uid);")
  add('AttackDeclared',"$paid = in_array('OUT_HURL', (array)FaBIdentityFromMZ($mzID)['object']->TurnEffects, true); if ($paid) { "+throw()+" }")
 if b=='fisticuffs':add('ResolveAbility',tag("FaBOUTTargets($player, 'aa')",'Choose_attack',1))
 if b=='fleet_foot_sandals':add('ResolveAbility',pick("FaBOUTTargets($player, 'tiny')",'Choose_small_attack',False)+"FaBDYNTag($chosen, 'GO_AGAIN');")
 if b=='silverwind_shuriken':add('ResolveAbility',tag("FaBOUTTargets($player, 'combo')",'Choose_combo_attack',1))
 if b=='mask_of_many_faces':add('ResolveAbility',"$name = await $player.NameCard(\"CARD_NAMES\", \"Name_next_attack\"); FaBOUTAdd($player, 'NEXT_NAME', 1, ['name'=>$name]);")
 if b=='head_leads_the_tail':add('AttackDeclared',"$name = await $player.NameCard(\"CARD_NAMES\", \"Name_another_card\"); FaBOUTHeadName($player, $mzID, $name);")
 if b=='be_like_water':add('Hit',UID+pay(1,'Pay_to_gain_name')+"if ($paid) { $mode = await $player.Modal(1, 1, \"Head_Jab&Surging_Strike&Twin_Twisters\", \"Choose_name\"); FaBOUTWaterName($uid, $mode); }")
 if b=='mask_of_shifting_perspectives':
  add('ResolveAbility',"FaBOUTAdd($player, 'SHIFTING');")
  add('Hit',pick("implode('&', FaBChoiceRefs($player, 'Hand'))",'Bottom_card_to_draw')+"if ($chosen !== 'PASS') { FaBOUTBottomCost($player, $chosen); DoDrawCard($player, 1); }")
 if b=='mask_of_malicious_manifestations':
  add('PrepareCard',UID+pick('FaBOUTHandAndArsenal($player)','Bottom_card_as_cost',False)+"FaBOUTBottomCost($player, $chosen); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',"FaBOUTRevealUntil($player, true);")
 if b=='gore_belching':add('AttackDeclared',UID+"FaBOUTRevealUntil($player, false, $uid);")
 if b=='driftwood_quiver':add('ResolveAbility',pick('FaBOUTArsenal($player)','Bottom_arsenal_card',False)+"FaBOUTBottomCost($player, $chosen);")
 if b=='trench_of_sunken_treasure':
  add('PrepareCard',UID+pick('FaBOUTArsenal($player, false, true)','Bottom_face_down_arsenal_as_cost',False)+"FaBOUTBottomCost($player, $chosen); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',"AddResources($player, intval(GetResources($player)) + 1);")
 if b=='quiver_of_rustling_leaves':add('ResolveAbility',UID+"FaBOUTRustling($player, $uid);")
 if b=='quiver_of_abyssal_depths':add('ResolveAbility',"$uids = []; $names = []; $i = 0; while ($i < 3) { "+pick('FaBOUTQuiverTargets($player, $names)','Choose_arrow_with_different_name')+"if ($chosen === 'PASS') { $i = 3; } else { $uids[] = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID); $names = array_merge($names, FaBOUTNames(FaBIdentityFromMZ($chosen)['object'])); $i = $i + 1; } } FaBOUTQuiverShuffle($player, $uids);")
 if b=='crows_nest':add('ResolveAbility',UID+pay(1,'Pay_for_aim')+"if ($paid) { $ref = FaBOUTSource($uid); FaBDYNCounter($ref, 'AIM', 1); }")
 if b=='spire_sniping':add('StartTurn',"$uids = FaBARCStageTop($player, 2); if (count($uids) > 0) { $param = FaBARCOrderParam($uids, 'Top'); $order = await $player.Rearrange($param); FaBARCFinishOrder($player, $uids, $order); }")
 if b=='redback_shroud':
  add('ResolveAbility',"FaBOUTAdd($player, 'REACTION_COST');")
  add('StartTurn',UID+multi("implode('&', FaBMONArena($player, 'silver'))",2,'Destroy_two_Silvers',2)+"FaBDYNReequip($player, $uid, $chosen);")
 if b=='silken_gi':add('ResolveAbility',"FaBOUTAdd($player, 'SILKEN'); FaBOUTNext($player, 'aa', -1);")
 if b=='threadbare_tunic':add('ResolveAbility',"AddResources($player, intval(GetResources($player)) + 1);")
 if b.startswith('seekers_'):add('ResolveAbility',"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 1);"+opt(1))
 if b=='peace_of_mind':add('ResolveCard',f"FaBOUTAdd($player, 'PEACE', {v+1}, ['persistentUntilUsed'=>true]); FaBOUTToken($player, 'ponder');")
 if b=='brush_off':add('ResolveCard',f"FaBOUTAdd($player, 'BRUSH', {v});")
 if b=='vambrace_of_determination':
  add('ResolveAbility',"FaBOUTAdd($player, 'VAMBRACE');")
  add('Defended',UID+pay(1,'Pay_for_defense')+"if ($paid) { FaBTagUID($uid, 'WTR_DEFENSE:1'); FaBTagUID($uid, 'DESTROY_ON_CHAIN_CLOSE'); }")
 if b in ['scout_the_periphery','wayfinders_crest']:add('ResolveCard' if b=='scout_the_periphery' else 'Defended',inspect()+(f"FaBOUTNext($player, 'arsenal', {v});" if b=='scout_the_periphery' else ''))
 if b=='premeditate':add('ResolveCard',"FaBOUTNext($player, 'arsenal', 3); FaBOUTAdd($player, 'PREMEDITATE');")
 if b=='spring_load':add('AttackDeclared',UID+"if (FaBHandCount($player) === 0) { FaBTagUID($uid, 'WTR_POWER:3'); }")
 if b=='infectious_host':add('AttackDeclared',hit("FaBOUTInfectious($player, $victim);"))
 if b=='stab_wound':add('Hit',hit("$n = intval(FaBGetState()['outDaggerHits'][$player] ?? 0); FaBARCLoseLife($victim, $n, $player);"))
 if b=='destructive_deliberation':add('Hit',hit("FaBOUTToken($player, 'ponder');"))
 if b=='amnesia':add('Hit',hit("FaBOUTLongEffect($player, $victim, 'AMNESIA');"))
 if b=='humble':add('Hit',hit("FaBOUTHumble($player, $victim);"))
 if b=='dishonor':add('Hit',hit("FaBOUTDishonor($player, $victim);"))
 if b=='infiltrate':add('Hit',hit("FaBOUTInfiltrate($player, $victim);"))
 if b=='barbed_undertow':add('Hit',UID+hit("if (FaBDYNAimed($uid)) { $mode = await $player.Modal(1, 1, \"Red&Yellow&Blue\", \"Choose_pitch_color\"); $color = intval($mode) + 1; FaBOUTLongEffect($player, $victim, 'NO_PITCH_' . $color); }"))
 if b=='cut_down_to_size':add('Hit',hit("if (FaBHandCount($victim) >= 4) { "+pick("implode('&', FaBChoiceRefs($victim, 'Hand'))",'Discard_card',False,who='$victim')+"FaBDiscardChoice($victim, $chosen); }"))
 if b=='shake_down':add('Hit',hit("if (FaBOUTReaction($player)) { $mode = await $player.Modal(1, 1, \"Red&Yellow&Blue\", \"Choose_color\"); $color = intval($mode) + 1; $uids = FaBDYNPrivateHand($player, $victim); FaBOUTRevealUIDs($victim, $uids); "+pick('FaBOUTPitchRefs($uids, $color)','Banish_card_of_chosen_color',False)+"FaBDYNBanishChoice($chosen, $player); FaBEVRForgetHand($uids); }"))
 if b=='one_two_punch':add('Hit',UID+hit("if (FaBOUTCombo($player, 'one_two_punch', FaBIdentityFromMZ($mzID)['object'])) { DoDamage($player, $mzID, $victim, 2, 'PHYSICAL'); }"))
 if b=='recoil':add('Hit',hit("$combo = FaBOUTCombo($player, 'recoil', FaBIdentityFromMZ($mzID)['object']); if ($combo) { "+pick("implode('&', FaBChoiceRefs($victim, 'Hand'))",'Put_card_on_top',False,who='$victim')+"FaBPlaceChosenOnDeck($victim, $chosen, true); }"))
 if b=='spinning_wheel_kick':add('Hit',UID+"if (FaBOUTCombo($player, 'spinning_wheel_kick', FaBIdentityFromMZ($mzID)['object'])) { FaBARCToDeck($player, $uid, false); }")
 if b=='bonds_of_ancestry':add('AttackDeclared',"$combo = FaBOUTCombo($player, 'bonds_of_ancestry', FaBIdentityFromMZ($mzID)['object']); if ($combo) { "+pick("implode('&', FaBChoiceRefs($player, 'Graveyard', ['keyword'=>'Combo']))",'Banish_combo_to_search')+"$names = FaBOUTBondsBanish($player, $chosen); if (count($names) > 0) { "+pick('FaBOUTSearchName($player, $names)','Search_same_name',True)+"FaBOUTBondsPlay($player, $chosen); FaBFinishSearch($player); } }")
 if b=='give_and_take':add('ResolveAbility',UID+pick('FaBOUTGiveTargets($player, $uid)','Put_action_on_top')+"FaBOUTTopChoice($player, $chosen);")
 if b=='looking_for_a_scrap':add('PrepareCard',UID+pick('FaBOUTScrapTargets($player)','Banish_one_power_card')+"FaBOUTScrap($player, $uid, $chosen); FaBFinishPreparedCard($uid);")
 if b=='burdens_of_the_past':add('ResolveCard',pick('FaBDYNHeroTargets($player)','Choose_hero',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); FaBOUTAdd($victim, 'BURDENS'); $n = count(FaBChoiceRefs($victim, 'Graveyard', ['type'=>'Defense Reaction'])); if ($n >= 10) { DoDrawCard($player, 1); }")
 if b=='wreck_havoc':add('Hit',hit(pick('FaBOUTArsenal($victim)','Turn_arsenal_face_up')+"FaBOUTFlip($chosen); "+pick("implode('&', FaBChoiceRefs($victim, 'Arsenal', ['type'=>'Defense Reaction']))",'Destroy_defense_reaction',False)+"FaBDYNDestroyChoice($chosen);"))
 if b=='concealed_blade':
  add('ResolveCard',tag("FaBOUTTargets($player, 'hybrid')",'Choose_Assassin_or_Ninja_attack',1,'OUT_CONCEALED'))
  add('Hit',pick('FaBOUTInventoryDaggers($player)','Equip_inventory_dagger',False)+"FaBOUTEquipDagger($player, $chosen);")
 if b=='wander_with_purpose':add('Hit',existing['katsu']['abilities'][0]['abilityCode'])
 if b=='visit_the_floating_dojo':add('ResolveCard',"$uids = []; "+pick("FaBOUTDojoTargets($player, true)",'Choose_Surging_Strike',False)+"$uids = FaBOUTStageDojo($player, $chosen, $uids); "+pick("FaBOUTDojoTargets($player, false)",'Choose_combo_card',False)+"$uids = FaBOUTStageDojo($player, $chosen, $uids); if (count($uids) > 0) { $param = FaBARCOrderParam($uids); $order = await $player.Rearrange($param); FaBARCFinishOrder($player, $uids, $order); }")
 if b.startswith('codex_of_'):
  code="$seats = FaBLiveSeats(); $i = 0; while ($i < count($seats)) { $seat = intval($seats[$i]); $loaded = false; if (FaBSeatIsLive($seat)) { "
  if b=='codex_of_inertia':code+="$chosen = FaBChoiceRefs($seat, 'Deck')[0] ?? ''; $loaded = FaBOUTCodexLoad($seat, $chosen, 'Deck');"
  else:
   zone='Hand' if b=='codex_of_bloodrot' else 'Graveyard';expr=f"implode('&', FaBChoiceRefs($seat, '{zone}'"+(", ['attackAction'=>true]" if zone=='Graveyard' else '')+"))"
   code+=pick(expr,'Put_card_in_arsenal',False,who='$seat')+f"$loaded = FaBOUTCodexLoad($seat, $chosen, '{zone}');"
  if b!='codex_of_bloodrot':code+="if ($loaded) { "+pick("implode('&', FaBChoiceRefs($seat, 'Hand'))",'Discard_card',False,who='$seat')+"FaBDiscardChoice($seat, $chosen); }"
  code+=" } $i = $i + 1; } FaBOUTCodexTokens($player, '"+('bloodrot_pox' if b=='codex_of_bloodrot' else b.split('_')[-1])+"');";add('ResolveCard',code)
 if b=='bloodrot_pox':add('ResolveAbility',UID+"FaBMONDestroy($uid);"+pay(3,'Pay_three_to_avoid_damage')+"if (!$paid) { DoDamage($player, '', $player, 2, 'PHYSICAL'); }")
 if b=='bloodrot_pox':
  code="$refs = FaBOUTEndTargets($player); while ($refs !== '') { "+pick('FaBOUTEndTargets($player)','Choose_end_phase_trigger',False)+"$uid = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID); $token = FaBIdentityFromMZ($chosen)['object']->CardID; FaBMONDestroy($uid); if ($token === 'ponder') { DoDrawCard($player, 1); } if ($token === 'bloodrot_pox') { "+pay(3,'Pay_three_to_avoid_damage')+"if (!$paid) { DoDamage($player, '', $player, 2, 'PHYSICAL'); } } if ($token === 'inertia') { $uids = FaBOUTInertiaStage($player); if (count($uids) > 0) { $param = FaBARCOrderParam($uids, 'Bottom'); $order = await $player.Rearrange($param); FaBARCFinishOrder($player, $uids, $order); } } $refs = FaBOUTEndTargets($player); }"
  add('EndTurn',code)
 if b=='inertia':add('ResolveAbility',UID+"FaBMONDestroy($uid); $uids = FaBOUTInertiaStage($player); if (count($uids) > 0) { $param = FaBARCOrderParam($uids, 'Bottom'); $order = await $player.Rearrange($param); FaBARCFinishOrder($player, $uids, $order); }")
 if b=='frailty':add('ResolveAbility',UID+"FaBMONDestroy($uid);")
 if b in ['arakni_solitary_confinement','amplifying_arrow','back_heel_kick','back_stab','bleed_out','cracked_bauble','cyclone_roundhouse','descendent_gustwave','down_and_dirty','falcon_wing','feisty_locals','freewheeling_renegades','isolate','malign','murkmire_grapnel','nerve_scalpel','orbitoclast','plague_hive','prowl','scale_peeler','skybound_shot','sneak_attack','widowmaker']:handled=True
 if not handled:pending.append(id)
 out.append(dict(cardId=id,abilities=a))
old={c['cardId']:c for c in json.loads((HERE/'out_abilities.json').read_text())} if (HERE/'out_abilities.json').exists() else {}
for c in out:
 for a in c['abilities']:
  prior=next((x for x in old.get(c['cardId'],{}).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior and prior['abilityCode']!=a['abilityCode']:a['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
if pending:print('UNHANDLED:',pending);raise SystemExit(1)
(HERE/'out_abilities.json').write_text(json.dumps(out,indent=2)+'\n');print('OUT identities:',len(out),'Unhandled:',len(pending))
