"""Super Slam source. Missing card families are reported, never marked implemented."""
import ast, json, re, hashlib
from pathlib import Path
HERE = Path(__file__).parent
for file,names in [('build_mon_abilities.py',['clean']),('build_dyn_abilities.py',['pick']),('build_hvy_abilities.py',['choose','many','refs','token','clash']),('build_out_abilities.py',['pay'])]:
    src=(HERE/file).read_text()
    for node in ast.parse(src).body:
        if isinstance(node,ast.FunctionDef) and node.name in names:exec(ast.get_source_segment(src,node))
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
V="$victim = intval(FaBGetState()['defender']);"
def hit(code,condition='true'):return 'if (FaBFaiHeroHit() && '+condition+') { '+V+code+' }'
def crowd(cheer=True):return 'FaBSUPCrowd($player, '+('true' if cheer else 'false')+');'
def nextpower(n,tag='',target=''):return f"FaBSUPNext($player, {n}, '{tag}', '{target}');"
def destroy(expr,may=False,p='$player'):return choose(expr,'Destroy_card',may,p)+"FaBDYNDestroyChoice($chosen);"
def discard(p='$victim'):return choose(refs('Hand',p),'Discard_card',False,p)+f"FaBDiscardChoice({p}, $chosen);"
def bottom(zone,p='$victim'):return choose(refs(zone,p),'Bottom_card',False,p)+"FaBHVYBottomChoice($chosen);"
def resource_amount():
    return '$maximum = min(3, FaBAvailablePitch($player)); $amountPaid = await $player.NumberChoose(0, $maximum, "Pay_up_to_three_resources"); $amountPaid = intval($amountPaid); while (intval(GetResources($player)) < $amountPaid) { '+choose('FaBARCPitchChoices($player)','Pitch_to_pay',False)+'FaBARCPitchForEffect($player, $chosen); } AddResources($player, intval(GetResources($player)) - $amountPaid);'
def optional_bottom_clash(p='$player'):
    return f"$revealed = FaBSUPClashCard($clash, {p}); "+choose('$revealed','Bottom_revealed_card',True,p)+"FaBHVYBottomChoice($chosen);"
existing={}
for name in ['wtr','arc','cru','mon','ele','evr','upr','dyn','out','dtd','evo','hvy','mst','ros','arakni','hnt','amx','sea','mpg','boltyn']:
    for e in json.loads((HERE/(name+'_abilities.json')).read_text()):
        merged={a['macroName']:a for a in existing.get(e['cardId'],{}).get('abilities',[])}
        merged.update({a['macroName']:a for a in e['abilities']});existing[e['cardId']]={'cardId':e['cardId'],'abilities':list(merged.values())}
old={e['cardId']:e for e in json.loads((HERE/'sup_abilities.json').read_text())} if (HERE/'sup_abilities.json').exists() else {}
out=[];pending=[]
for c in json.loads((HERE/'sup_catalog.json').read_text()):
    id=c['id'];b=re.sub(r'_(red|yellow|blue)$','',id);v=4-int(c['pitch'] or 0);txt=c['functional_text_plain'];a=[];handled=False
    def add(m,code):
        global handled
        handled=True;prior=next((x for x in a if x['macroName']==m),None)
        if prior:prior['abilityCode']+='\n'+clean(code)
        else:a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
    if id in existing:out.append(existing[id]);continue
    if b in ['confidence','toughness','big_bully','empowering_ruckus','low_blow','darling_of_the_crowd','disdainful_delight','plate_of_tough_love','strong_stomach_for_adversity','tough_leather_boots','buckwild','flex_speed','flex_strength','show_of_strength','unwavering_resolve','power_play','two_steps_ahead']:handled=True
    if 'Suspense' in c['card_keywords']:
        code="$event = strval(DecisionQueueController::GetVariable('rosEvent')); $uid = intval(DecisionQueueController::GetVariable('rosSource')); if ($event === 'supZero') { FaBSUPZero($uid); }"
        leave={'act_of_glory':v+3,'edge_of_their_seats':v+2,'tension_in_the_air':v+1}
        if b in leave:code+="if ($event === 'supLeave') { "+nextpower(leave[b])+' }'
        if b=='hungry_for_more':code+=f"if ($event === 'supLeave') {{ FaBCRUGainLife($player, {v}); }}"
        if b=='in_the_palm_of_your_hand':code+="if ($event === 'supEnter' || $event === 'supLeave') { DoDrawCard($player, 1); }"
        if b=='dramatic_pause':code+="if ($event === 'supEnter') { "+choose('FaBSUPCombatChoices(true, true)','Choose_defending_action',False)+f"FaBDYNTag($chosen, 'WTR_DEFENSE:{v}'); }}"
        if b=='up_on_a_pedestal':code+="if ($event === 'supEnter' || $event === 'supLeave') { "+choose("FaBSUPGrave($player, 'pedestal')",'Return_attack_to_top')+"FaBSUPTop($player, $chosen); }"
        if b=='turn_heads':code+="if ($event === 'supLeave') { "+choose("FaBSUPHeroes($player, 'Brute')",'Tap_Brute_hero',False)+"FaBSUPTapHero($chosen); }"
        if b=='who_blinks_first':code+="if ($event === 'supLeave') { "+destroy("FaBSUPGuardianAuras($player)",True)+' }'
        add('ResolveAbility',code)
    if b in ['cheers','booze']:add('ResolveAbility',crowd(b=='cheers'))
    if b=='authority_of_ataya':add('CardPitched',"FaBSUPAdd($player, 'ATAYA');")
    if b in ['heroic_pose','villainous_pose']:add('ResolveCard',nextpower(v if b=='heroic_pose' else v+1)+crowd(b=='heroic_pose'))
    if b in ['cruel_ambition','humble_entrance']:add('ResolveCard',token('might' if b=='cruel_ambition' else 'toughness',n=v if b=='cruel_ambition' else v+2))
    if b=='heroic_grit':add('ResolveCard',nextpower(0,'SUP_GRIT')+token('toughness'))
    if b=='revolting_gesture':add('ResolveCard',nextpower(v)+token('might'))
    if b=='vigorous_roar':add('ResolveCard',nextpower(v)+"if (FaBHVYSix($player, 'Pitch') !== '') { "+token('vigor')+' }')
    if b=='bark_obscenities':add('ResolveCard',nextpower(v+1,target='Guardian'))
    if b=='prime_the_crowd':add('ResolveCard',"FaBWTRAddEffect($player, 'SUP_NEXT_AA', "+str(v+1)+"); $seats = FaBLiveSeats(); for ($i = 0; $i < count($seats); $i = $i + 1) { $seat = intval($seats[$i]); FaBSUPPrime($seat); }")
    if b in ['comeback_kid','jaws_of_victory','cries_of_encore','big_bully','mocking_blow']:
        cheer=b not in ['big_bully','mocking_blow'];add('AttackDeclared',hit('if (GetHealth($player) '+('<' if cheer else '>')+' GetHealth($victim)) { '+crowd(cheer)+' }'))
    if b in ['fight_from_behind','clench_the_upper_hand']:
        for macro in ['AttackDeclared','Defended']:add(macro,'if (FaBSUPAllLife($player, true)) { '+crowd(b=='fight_from_behind')+' }')
    if b in ['helm_of_the_adored','horns_of_the_despised']:add('Defended',crowd(b=='helm_of_the_adored'))
    if b=='turning_point':add('Defended',"if (GetHealth($player) < GetHealth(intval(FaBGetState()['attacker']))) { "+crowd()+' }')
    if b in ['escalate_order','escalate_violence']:
        t='toughness' if b=='escalate_order' else 'might';add('AttackDeclared',f"if (count(FaBMONArena($player, '{t}')) > 0) {{ "+token(t,n=3)+' }')
    if b in ['instill_fear','battered_beaten_and_broken']:add('AttackDeclared',hit('FaBIntimidate($player, $victim);'))
    if b in ['bask_in_your_own_greatness','bully_tactics','dig_in','boots_to_the_boards']:
        code=resource_amount()+("FaBIntimidate($player, $victim, $amountPaid);" if b=='bully_tactics' else token('might' if b=='bask_in_your_own_greatness' else 'toughness',n='$amountPaid'))
        add('Defended' if b in ['dig_in','boots_to_the_boards'] else 'AttackDeclared',hit(code) if b=='bully_tactics' else code)
    if b in ['bluster_buff','chest_puff','look_tuff','punch_above_your_weight']:
        add('AttackDeclared',UID+pay(3 if b=='punch_above_your_weight' else 1)+("if ($paid) { FaBCRUSelfTagUID($uid, 'WTR_POWER:5'); }" if b=='punch_above_your_weight' else "if (!$paid) { FaBCRUSelfTagUID($uid, 'WTR_POWER:-1'); }"))
    if b in ['high_pitched_howl','rough_up']:add('AttackDeclared',UID+"if (FaBHVYSix($player, 'Pitch') !== '') { "+(token('vigor') if b=='high_pitched_howl' else "FaBCRUSelfTagUID($uid, 'WTR_POWER:1');")+' }')
    if b=='asking_for_trouble':add('Defended',token('vigor',p="intval(FaBGetState()['attacker'])"))
    if b=='good_natured_brutality':add('Defended',UID+"if (FaBHandCount($player) === 0) { FaBTagUID($uid, 'WTR_DEFENSE:6'); "+crowd()+' }')
    if b in ['full_of_bravado','story_beats']:
        code="if (FaBSUPSuspense($player) !== '') { "+token('confidence')+' }' if b=='full_of_bravado' else choose('FaBSUPSuspense($player)','Choose_aura_of_suspense')+"if ($chosen !== '-') { $ref = $chosen; $mode = await $player.Modal(1, 1, \"Add_counter&Remove_counter\", \"Change_suspense\"); FaBSUPCounter($ref, $mode === '0' ? 1 : -1); }"
        add('AttackDeclared',code);add('Defended',code)
    if b in ['attention_grabbers','virtuoso_bodice']:add('Defended',UID+choose('FaBSUPSuspense($player, true)','Remove_suspense_counter')+"if (FaBSUPCounter($chosen, -1)) { "+("FaBTagUID($uid, 'WTR_DEFENSE:2');" if b=='attention_grabbers' else "AddResources($player, intval(GetResources($player)) + 2);")+' }')
    if b=='sit':add('Defended',UID+"if (FaBSUPAttackType('Brute')) { FaBTagUID($uid, 'WTR_DEFENSE:3'); }")
    if b=='toby_jugs':add('Defended',UID+pay(1)+"if ($paid) { FaBTagUID($uid, 'WTR_DEFENSE:2'); }")
    if b=='will_of_the_crowd':add('Defended',"if (FaBSUPCount($player, 'CHEER')) { FaBSUPBuffDefenders(3); }")
    if b=='shining_courage':add('ResolveCard',choose('FaBSUPCombatChoices(true, true)','Choose_defending_action')+f"FaBDYNTag($chosen, 'WTR_DEFENSE:{v}');"+crowd())
    if b in ['old_leather_and_vim','offensive_behavior','spew_obscenities','uplifting_performance']:
        pair={'old_leather_and_vim':['toughness','vigor'],'offensive_behavior':['might','vigor'],'spew_obscenities':['confidence','might'],'uplifting_performance':['confidence','toughness']}[b];add('Hit',hit(token(pair[0])+token(pair[1])))
    if b in ['goon_battery','goon_beatdown','goon_tactics']:
        code={'goon_battery':"FaBSUPTapOnly($victim);",'goon_beatdown':crowd(False),'goon_tactics':'FaBSEAMill($victim);'}[b];add('Hit',hit(code,'FaBSUPAuras($player) >= 3'))
    if b=='battered_beaten_and_broken':add('Hit',hit(destroy('FaBROSIntimidated($victim)'),'FaBSUPAuras($player) >= 3'))
    if b=='gang_robbery':add('AttackDeclared',hit(choose('FaBSUPTokens($victim)','Steal_aura_token',False)+"FaBSUPSteal($player, $chosen);"))
    if b=='steal_victory':add('Defended',"$victim = intval(FaBGetState()['attacker']); "+choose('FaBSUPTokens($victim)','Steal_aura_token',False)+"FaBSUPSteal($player, $chosen);")
    if b in ['bash_brute','bash_guardian','disturb_the_peace','fight_dirty','fight_fair','tame_the_beastly_behavior','tear_down_the_idols','turn_the_crowd_grateful','turn_the_crowd_hateful','challenge_the_alpha','mage_hunter_arrow']:
        cls={'bash_brute':'Brute','bash_guardian':'Guardian','disturb_the_peace':'Guardian','fight_dirty':'Revered','fight_fair':'Reviled','tame_the_beastly_behavior':'Reviled','tear_down_the_idols':'Revered','turn_the_crowd_grateful':'Reviled','turn_the_crowd_hateful':'Revered','challenge_the_alpha':'Brute','mage_hunter_arrow':'Runeblade|Wizard'}[b]
        code=destroy('FaBSUPTokens($victim)') if b.startswith('bash_') else destroy(refs('Arena','$victim',"['type'=>'Aura']"),b=='mage_hunter_arrow') if b in ['disturb_the_peace','mage_hunter_arrow'] else 'FaBSEAMill($victim);' if b=='fight_dirty' else UID+"FaBTagUID($uid, 'EVR_BOTTOM');" if b=='fight_fair' else bottom('Arsenal') if b=='tame_the_beastly_behavior' else discard() if b=='tear_down_the_idols' else crowd(b=='turn_the_crowd_grateful') if b.startswith('turn_the_crowd_') else choose(refs('Hand','$victim'),'Discard_card',False,'$victim')+"$six = FaBSUPChoicePower($victim, $chosen) >= 6; FaBDiscardChoice($victim, $chosen); if ($six) { FaBMPGLoseLife($player, 2); }"
        add('Hit',hit(f"if (FaBSUPHeroType($victim, '{cls}')) {{ "+code+' }'))
        if b=='tear_down_the_idols':add('AttackDeclared',hit("if (FaBSUPHeroType($victim, 'Revered')) { FaBIntimidate($player, $victim); }"))
    if b in ['short_shrift','small_problem','wee_wrecking_ball','cut_off_at_the_knees','cut_a_long_story_short','cut_the_small_talk','no_tall_tales','smashing_ground']:
        code={'short_shrift':discard(),'small_problem':destroy(refs('Arena','$victim',"['type'=>'Aura']")),'wee_wrecking_ball':destroy(refs('Arsenal','$victim')),'cut_off_at_the_knees':'FaBMPGMill($victim, 3);','cut_a_long_story_short':'FaBMPGDiscardHand($victim);','cut_the_small_talk':'FaBMPGDestroyAuras($victim);','no_tall_tales':"FaBWTRAddEffect($victim, 'NO_GO_AGAIN', 0, [], true);",'smashing_ground':destroy(refs('Arsenal','$victim'))}[b]
        cond='intval($amount) >= 4' if b in ['short_shrift','small_problem','wee_wrecking_ball'] else 'FaBAttackPower(FaBGetState()) >= '+('6' if b=='smashing_ground' else '13');add('Hit',hit(code,cond))
    if b in ['not_so_mighty','not_so_tuff']:
        t='might' if b=='not_so_mighty' else 'toughness';cls='Reviled' if b=='not_so_mighty' else 'Revered';add('Defended',f"$victim = intval(FaBGetState()['attacker']); if (FaBSUPHeroType($victim, '{cls}')) {{ "+choose(f"FaBSUPTokens($victim, '{t}')",'Destroy_token',False)+"if ($chosen !== '-') { FaBDYNDestroyChoice($chosen); "+token('toughness' if t=='might' else 'might')+' } }')
    if b in ['rip_up_their_virtues','renounce_violence']:
        t='toughness' if b=='rip_up_their_virtues' else 'might';add('ResolveCard',many(f"FaBSUPTokens($player, '{t}', true)",3)+"$count = count(FaBUPRUIDs($chosen)); FaBHVYDestroyChoices($chosen); "+token('might' if t=='toughness' else 'toughness',n='$count'))
    if b in ['give_em_a_piece_of_your_mind','shoot_your_mouth_off','take_that','whos_the_tough_guy']:handled=True # delayed close hook records each defending hero
    if b in ['familiar_stench','familiar_story']:handled=True
    if b in ['disarm','disembody','disperse','old_favorite']:
        add('AttackDeclared',"if (FaBSUPCount($player, 'CHEER')) { "+token('toughness')+' }')
        code=UID+"FaBTagUID($uid, 'EVR_BOTTOM');" if b=='old_favorite' else bottom({'disarm':'Hand','disembody':'Arena','disperse':'Arsenal'}[b],"$victim")
        add('Defended',"if (FaBCurrentDefense(FaBIdentityFromMZ($mzID)['object'], $player) >= 6) { $victim = intval(FaBGetState()['attacker']); "+code+' }')
    if b=='overcrowded':
        for m in ['AttackDeclared','Defended']:add(m,UID+"$n = FaBSUPTokenNames(); FaBCRUSelfTagUID($uid, 'WTR_POWER:'.$n); FaBTagUID($uid, 'WTR_DEFENSE:'.$n);")
    if b in ['tough_smashup','vigorous_smashup']:add('Defended',clash('toughness' if b=='tough_smashup' else 'vigor')+optional_bottom_clash())
    if b=='no_hero_stands_alone':add('Defended',clash()+"if ($winner > 0) { "+choose('FaBSUPCombatChoices(false)','Choose_combat_card',True,'$winner')+"FaBDYNTag($chosen, 'WTR_POWER:-3'); FaBDYNTag($chosen, 'WTR_DEFENSE:-3'); }")
    if b=='beat_the_same_drum':add('ResolveCard',"FaBSUPRepeatTokens($player);")
    if b=='arrogant_showboating':add('ResolveCard',"FaBSUPShowboat($player);")
    if b=='visit_the_boneyard':add('ResolveCard',choose("FaBHVYSix($player, 'Graveyard')",'Top_card_with_six_power',False)+"FaBSUPTop($player, $chosen);"+token('vigor'))
    if b in ['rapturous_applause','unexpected_backhand','overturn_the_results']:handled=True
    if b=='crowd_goes_wild':add('CostModifier',"return FaBSUPCount($player, 'CHEER') ? -3 : 0;")
    if b=='tempest_palm_gustwave':add('AttackPowerModifier',"return FaBComboActive(FaBGetState(), 'surging_strike_red') ? 2 : 0;")
    if b in ['tuffnut','tuffnut_bumbling_hulkster']:add('ResolveAbility',"if (FaBSUPPitchTop($player)) { "+crowd()+' }')
    if b in ['lyath_goldmane','lyath_goldmane_vile_savant']:add('ResolveAbility',crowd(False)+"FaBSUPAdd($player, 'LYATH_DEFENSE');")
    if b in ['kayo_strong_arm','kayo_underhanded_cheat']:add('ResolveAbility',choose("FaBSUPAttackAction($player)",'Choose_attack_action',False)+"FaBDYNTag($chosen, 'SUP_BASE_6');")
    if b in ['pleiades','pleiades_superstar']:
        add('PrepareCard',UID+choose('FaBSUPSuspense($player, true)','Remove_suspense_as_cost',False)+"FaBSUPCounter($chosen, -1); FaBFinishPreparedCard($uid);")
        add('ResolveAbility',choose('FaBSUPSuspense($player)','Add_suspense_counter')+"FaBSUPCounter($chosen, 1);")
    if b in ['hold_firm','mightybone_knuckles','overbearing_presence','stand_strong','wind_up_the_crowd']:
        t={'hold_firm':'toughness','mightybone_knuckles':'might','overbearing_presence':'vigor','stand_strong':'confidence','wind_up_the_crowd':'toughness'}[b];add('ResolveAbility',token(t,n=1 if b in ['stand_strong','wind_up_the_crowd'] else 3)+(token('vigor') if b=='wind_up_the_crowd' else ''))
    if b in ['gauntlets_of_tyrannical_rex','punching_gloves']:add('ResolveAbility',nextpower(1) if b=='gauntlets_of_tyrannical_rex' else "FaBWTRAddEffect($player, 'SUP_NEXT_AA', 2);")
    if b=='helm_of_hindsight':add('ResolveAbility',choose("FaBSUPGrave($player, 'attack')",'Return_attack_to_top',False)+"FaBSUPTop($player, $chosen);")
    if b=='tiara_of_suspense':add('ResolveAbility',choose('FaBSUPSuspense($player)','Add_suspense_counter',False)+"FaBSUPCounter($chosen, 1);")
    if b=='never_give_up':add('ResolveAbility',choose('FaBSUPCombatChoices(true, true)','Choose_defending_action',False)+"FaBDYNTag($chosen, 'WTR_DEFENSE:3');")
    if b=='mage_hunter_arrow':add('ResolveAbility',"FaBSUPAdd($player, 'ARROW_PREVENT', 3);")
    if b=='laughing_knee_slappers':handled=True
    if b=='concealed_object':add('ResolveAbility',"$event = strval(DecisionQueueController::GetVariable('rosEvent')); if ($event === 'supEnter') { "+crowd(False)+" } else { "+choose('FaBSUPAttackRef()','Choose_attack',False)+"FaBDYNTag($chosen, 'WTR_POWER:1'); }")
    if b=='outside_interference':add('ResolveAbility',choose("FaBSUPInventory($player)",'Reveal_inventory_attack')+"FaBRevealChoices($player, $chosen); FaBMoveChoice($player, $chosen, 'Inventory', 'Hand');")
    if b=='backspin_thrust':
        add('PrepareCard',UID+choose('FaBSUPTappedCogs($player)','Untap_cog_as_cost',False)+"FaBSEAUntap($chosen); FaBFinishPreparedCard($uid);")
        add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('arcAttackUID')); $mode = await $player.Modal(1, 1, \"Power&Go_again\", \"Enhance_attack\"); FaBTagUID($uid, $mode === '0' ? 'WTR_POWER:1' : 'GO_AGAIN');")
    if b=='sadistic_scowl':add('ResolveCard',nextpower(v+2)+choose('FaBDYNHeroTargets($player)','Choose_hero_to_intimidate',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); FaBIntimidate($player, $victim);")
    if b=='ironfist_revelation':add('Defended',choose('FaBSUPCrushArsenal($player)','Reveal_crush_in_arsenal')+"FaBSUPRevealCrush($chosen);")
    if b=='hit_the_gas':add('ResolveCard',many("FaBSUPHyperDrivers($player)",99)+"FaBSUPHitGas($player, $chosen);")
    if b=='energetic_impact':add('Defended',"if (FaBSUPTogetherSix($player, $mzID)) { "+token('vigor')+' }')
    if b=='right_behind_you':add('Defended',UID+"if (FaBSUPTogetherHand($player, $uid)) { FaBTagUID($uid, 'WTR_DEFENSE:1'); $mode = await $player.Modal(1, 1, \"Decline&Look_at_top_card\", \"Look_at_top_card\"); if ($mode === '1') { $uids = FaBHVYPeek($player, $player, 1); $orderParam = FaBARCOrderParam($uids, 'Top'); $order = await $player.Rearrange($orderParam); FaBHVYFinishPeek($player, $player, $uids, $order); } }")
    if b=='strongest_survive':add('Hit',hit(choose('FaBSUPRevealStronger($victim, intval($amount))','Reveal_stronger_card',True,'$victim')+"if ($chosen !== '-') { FaBRevealChoices($victim, $chosen); } else { "+discard()+' }'))
    if b=='song_of_sinew':add('ResolveCard',"$n = FaBSUPSongCount($player); $uids = FaBHVYPeek($player, $player, 4); $orderParam = FaBARCOrderParam($uids, 'Top'); $order = await $player.Rearrange($orderParam); FaBHVYFinishPeek($player, $player, $uids, $order); "+nextpower('$n'))
    if b=='unexpected_backhand':add('ResolveAbility',"$victim = intval(DecisionQueueController::GetVariable('rosTarget')); DoDamage($player, $mzID, $victim, 1, 'PHYSICAL');")
    if b in ['fix_the_match','reckless_stampede']:
        if b=='fix_the_match':add('AttackDeclared',choose('FaBStageSearch($player)','Choose_top_card',False)+"$chosenUID = FaBUPRUIDs($chosen)[0] ?? 0; FaBFinishSearch($player); FaBSUPTop($player, FaBDTDSource($chosenUID));")
        code=clash('might' if b=='fix_the_match' else '',"intval(DecisionQueueController::GetVariable('rosTarget'))")
        if b=='reckless_stampede':code+="if ($winner > 0) { $seats = FaBOpponents($winner); for ($i = 0; $i < count($seats); $i = $i + 1) { $target = intval($seats[$i]); DoDamage($winner, $mzID, $target, 1, 'PHYSICAL'); } } "+optional_bottom_clash()+optional_bottom_clash('$other')
        add('ResolveAbility',code)
    if b=='cheap_shot':add('ResolveCard',choose('FaBDYNHeroTargets($player)','Choose_hero',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); "+choose(refs('Hand','$victim'),'Discard_to_prevent_damage',True,'$victim')+"if ($chosen !== '-') { FaBDiscardChoice($victim, $chosen); } else { DoDamage($player, $mzID, $victim, 2, 'PHYSICAL'); }")
    if b=='truth_or_trickery':add('Defended',"$mode = await $player.Modal(1, 1, \"Decline&Look_and_choose_color\", \"Truth_or_trickery\"); if ($mode === '1') { $uids = FaBHVYPeek($player, $player, 1); if (count($uids) > 0) { $color = await $player.Modal(1, 1, \"Red&Yellow&Blue\", \"Choose_color\"); $attacker = intval(FaBGetState()['attacker']); $prompt = \"Is_the_top_card_\".([\"red\", \"yellow\", \"blue\"][intval($color)]); $guess = await $attacker.Modal(1, 1, \"No&Yes\", $prompt); $preview = FaBSUPTruthPreview($attacker, $uids); $seen = await $attacker.MZChoose($preview, \"Look_at_the_top_card\"); FaBHVYClearPreviews($preview); $correct = FaBSUPTruth($player, $attacker, $uids, intval($color) + 1, $guess === '1'); if (!$correct) { "+discard('$attacker')+' } } }')
    if b in ['numbskull_charm','thespian_charm','cheaters_charm','liars_charm']:
        cheer=b in ['numbskull_charm','thespian_charm'];names={'numbskull_charm':'confidence|might','thespian_charm':'might|vigor','cheaters_charm':'confidence|toughness','liars_charm':'toughness|vigor'}[b]
        code='$modes = await $player.Modal(0, 3, "'+('Destroy_token&Cheer&' if cheer else 'Steal_token&Boo&')+{'numbskull_charm':'Pitch_top','thespian_charm':'Return_aura','cheaters_charm':'Damage_or_discard','liars_charm':'Lose_hero_abilities'}[b]+'", "Choose_modes"); $modes = explode(",", $modes); if (in_array("0", $modes)) { '+choose(f"FaBSUPTokens($player, '{names}', true)" if cheer else f"FaBSUPStealTargets($player, '{names}')",'Choose_token',False)+('FaBDYNDestroyChoice($chosen);' if cheer else 'FaBSUPSteal($player, $chosen);')+' } if (in_array("1", $modes)) { '+crowd(cheer)+' } if (in_array("2", $modes)) { '
        if b=='numbskull_charm':code+="if (FaBSUPPitchTop($player)) { "+token('vigor')+' }'
        elif b=='thespian_charm':code+=choose(refs('Arena',filters="['type'=>'Aura']"),'Return_aura_to_hand',False)+"FaBSUPReturnOwner($chosen, 'Hand');"
        else:
            if b=='cheaters_charm':code+="if (FaBSUPOwnSixAttack($player)) { "
            code+=choose('FaBDYNHeroTargets($player)','Choose_hero',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); "+choose(refs('Hand','$victim'),'Discard_to_prevent_effect',True,'$victim')+"if ($chosen !== '-') { FaBDiscardChoice($victim, $chosen); } else { "+("DoDamage($player, $mzID, $victim, 2, 'PHYSICAL');" if b=='cheaters_charm' else "FaBWTRAddEffect($victim, 'NO_HERO_ABILITY', 1);")+' }'
            if b=='cheaters_charm':code+=' }'
        add('ResolveCard',code+' }')
    if b=='cutting_retort':add('AttackDeclared',hit(UID+resource_amount()+"$names = []; $count = 0; for ($i = 0; $i < $amountPaid; $i = $i + 1) { "+choose('FaBSUPDifferentTokens($victim, $names)','Destroy_different_token',False)+"if ($chosen !== '-') { $names[] = FaBIdentityFromMZ($chosen)['object']->CardID; FaBDYNDestroyChoice($chosen); $count = $count + 1; } } FaBCRUSelfTagUID($uid, 'WTR_POWER:'.$count);"))
    if b=='painful_passage':add('ResolveCard',choose(refs('Hand',filters="['attackAction'=>true]"),'Banish_attack')+"if ($chosen !== '-') { $uid = FaBUPRUIDs($chosen)[0]; $mode = await $player.Modal(1, 1, \"Power&Go_again\", \"Enhance_banished_card\"); FaBMoveUID($uid, 'Banish', $player); FaBTagUID($uid, $mode === '0' ? 'WTR_POWER:3' : 'GO_AGAIN'); }")
    if b in ['a_good_clean_fight','smash_with_big_rock','golden_gait','golden_galea','golden_gauntlets','golden_heart_plate','kick_the_hornets_nest']:handled=True
    if b=='adaptive_alpha_mold':add('ResolveAbility',"$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); $options = FaBSUPMoldOptions($player, $uid); if ($options !== '') { $mode = await $player.Modal(1, 1, $options, \"Choose_equipment_zone\"); FaBEVOModular($player, $uid, explode('&', $options)[intval($mode)] ?? ''); }")
    if b=='angelic_attendant':add('ResolveCard',UID+choose("FaBDTDRefs($player, 'Arena', 'figment')",'Awaken_figment',False)+"FaBDTDAwaken($player, $chosen); FaBMoveUID($uid, 'Soul', $player);")
    if b=='battlefield_beacon':add('AttackDeclared',"$n = FaBSUPSoulCount($player); $picked = []; for ($i = 0; $i < $n; $i = $i + 1) { $options = FaBSUPBeaconOptions($picked); if ($options !== '') { $mode = await $player.Modal(1, 1, $options, \"Create_token\"); $token = explode('&', $options)[intval($mode)] ?? ''; $picked[] = $token; FaBHVYToken($player, $token); } }")
    if b=='blood_follows_blade':add('ResolveCard',choose("FaBHVYAttackTargets($player, 'sword')",'Choose_sword_attack',False)+"FaBDYNTag($chosen, 'GO_AGAIN'); FaBDYNTag($chosen, 'SUP_SELLSWORD');")
    if b=='beat_of_the_ironsong':add('ResolveCard',choose('FaBSUPDawnblade($player)','Choose_Dawnblade_attack',False)+"$ref = $chosen; $n = FaBSUPDawnbladeModes($ref); if ($n > 0) { $modes = await $player.Modal($n, $n, \"Power&Go_again&No_defense_gain&Unpreventable\", \"Choose_Dawnblade_modes\"); FaBSUPDawnbladeApply($ref, $modes); }")
    if b=='catch_of_the_day':add('ResolveCard',"FaBSEANext($player, 'arrow', 2); FaBSUPAdd($player, 'CATCH');")
    if b=='channel_the_tranquil_domain':
        add('ResolveCard',UID+choose('FaBSUPAuraTargets($player, $uid)','Bottom_another_aura',False)+"FaBUPRBottom($chosen);")
        add('ResolveAbility',UID+choose('FaBSUPAuraTargets($player, $uid)','Bottom_another_aura',False)+"FaBUPRBottom($chosen);")
        add('StartTurn',UID+"$flow = FaBELEFlow($uid); "+many("FaBELESelect($player, 'Pitch', 'Earth')",'$flow')+"FaBELEChannelPay($player, $uid, $chosen, $flow);")
    if b=='gallow_end_of_the_line':
        add('PrepareCard',UID+choose("FaBSEARefs($player, 'watery', 'Hand')",'Discard_watery_grave_as_cost',False)+"FaBDiscardChoice($player, $chosen); FaBFinishPreparedCard($uid);")
        add('ResolveAbility',"FaBSUPAdd($player, 'GALLOW');")
    if b=='light_up_the_leaves':
        add('ResolveCard',UID+choose('FaBUPRAnyTargets($player)','Choose_arcane_target',False)+"$targetUID = FaBUPRUIDs($chosen)[0] ?? 0; FaBUPRDeal($player, $uid, $targetUID, 6, 'ARCANE');")
        add('PrepareCard',UID+choose("FaBSUPEarthCost($player, $uid)",'Discard_another_Earth_card',False)+"FaBDiscardChoice($player, $chosen); FaBFinishPreparedCard($uid);")
        add('ResolveAbility',choose('FaBSUPSources()','Choose_source_to_prevent',False)+"FaBSUPSourcePrevention($player, $chosen);")
    if b=='parched_terrain':add('ResolveAbility',UID+"$sand = FaBSUPAddSand($uid); "+many("implode('&', FaBChoiceRefs($player, 'Graveyard', ['pitch'=>1]))",'$sand')+"FaBSUPSand($uid, $chosen, $sand);")
    if b=='time_flies_when_youre_having_fun':
        add('ResolveCard',"FaBSUPAdd($player, 'TIME_FLIES'); if (DecisionQueueController::GetVariable('fabSourceZone') === 'Arsenal') { FaBWTRAddEffect($player, 'SUP_NEXT_AA', 3); }")
        add('ResolveAbility',"$victim = intval(DecisionQueueController::GetVariable('rosTarget')); "+destroy(refs('Arena','$victim',"['type'=>'Aura']"),True))
    if b=='take_the_bait':add('ResolveCard',choose('FaBStageSearch($player)','Choose_top_card',False)+"$chosenUID = FaBUPRUIDs($chosen)[0] ?? 0; FaBFinishSearch($player); FaBSUPTop($player, FaBDTDSource($chosenUID)); "+choose('FaBDYNHeroTargets($player, true)','Choose_opponent',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); FaBSUPBait($player, $victim);")
    if b=='bait':
        add('ResolveAbility',"$uid = intval(FaBGetState()['attackUID']); FaBTagUID($uid, 'WTR_POWER:1'); FaBTagUID($uid, 'GO_AGAIN');")
        add('ChainLinkResolved',"FaBSUPBaitResolved(FaBIdentityFromMZ($mzID)['object']);")
    if b=='cries_of_encore':add('Hit',hit("FaBSUPAdd($player, 'ENCORE');","FaBSUPCount($player, 'CHEER') > 0"))
    if b=='the_old_switcheroo':
        add('ResolveAbility',"$event = strval(DecisionQueueController::GetVariable('rosEvent')); if ($event === 'supSwitchWin') { $victim = intval(DecisionQueueController::GetVariable('rosTarget')); "+discard()+" } else { "+choose('FaBDYNHeroTargets($player)','Choose_hero_for_clash',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); FaBSUPAdd($player, 'SWITCH', 1, ['victim'=>$victim]); }")
    if b=='hunter_or_hunted':add('Defended',UID+"$victim = intval(FaBGetState()['attacker']); $name = await $player.NameCard(\"\", \"Name_a_card\"); FaBARCSetCard($uid, 'supContract', $name); if (FaBSUPHunterReveal($player, $victim, $name)) { $search = FaBSUPHunterSearch($player, $victim, $name); "+many('$search',3)+"FaBSUPHunterFinish($player, $victim, $chosen, $search); }")
    if not handled:pending.append(id);continue
    for ability in a:
        prior=next((x for x in old.get(id,{}).get('abilities',[]) if x['macroName']==ability['macroName']),None)
        if prior:
            if prior['abilityCode'].strip()!=ability['abilityCode'].strip():ability['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
            elif 'previousCodeHash' in prior:ability['previousCodeHash']=prior['previousCodeHash']
    out.append({'cardId':id,'abilities':a})
(HERE/'sup_abilities.json').write_text(json.dumps(out,indent=2)+'\n')

print(f'SUP: {len(out)} authored/reused identities, {len(pending)} pending; {sum(len(e["abilities"]) for e in out)} macros')
if pending:print('\n'.join(pending))

# Reuse the established go-fish choices while applying Catch of the Day to each trigger.
sea = {e['cardId']: e for e in json.loads((HERE/'sea_abilities.json').read_text())}
support=[]
for card_id, entry in sea.items():
    abilities=[]
    for original in entry['abilities']:
        if original['macroName']!='Hit' or 'FaBSEAGoFish(' not in original['abilityCode']:continue
        body=original['abilityCode']
        ability=dict(original)
        ability['abilityCode']=clean("$supFishRepeats = FaBSUPCount($player, 'CATCH') > 0 ? 2 : 1; for ($supFish = 0; $supFish < $supFishRepeats; $supFish = $supFish + 1) { "+body+" }")
        ability['previousCodeHash']=hashlib.sha256(body.strip().encode()).hexdigest()
        abilities.append(ability)
    if abilities:support.append({'cardId':card_id,'abilities':abilities})
(HERE/'sup_support_abilities.json').write_text(json.dumps(support,indent=2)+'\n')
if pending:raise SystemExit('Unimplemented SUP identities remain')
