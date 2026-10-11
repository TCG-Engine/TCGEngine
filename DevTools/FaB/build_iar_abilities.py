"""IAR authoring. Never mark an unhandled rules text implemented."""
import ast
import hashlib
import json
import re
from pathlib import Path

HERE = Path(__file__).parent
for filename, names in [('build_mon_abilities.py', ['clean']), ('build_arc_abilities.py', ['opt']),
                        ('build_hvy_abilities.py', ['choose', 'many', 'refs', 'token', 'hit'])]:
    source = (HERE / filename).read_text()
    for node in ast.parse(source).body:
        if isinstance(node, ast.FunctionDef) and node.name in names:
            exec(ast.get_source_segment(source, node))

UID = '$uid = FaBPENUID($mzID);'
V = "$victim = intval(FaBGetState()['defender']);"
existing = {}
for name in ['wtr','arc','fai','professor','cru','ira','mon','boltyn','ele','evr','upr','dyn','out','dtd','evo','hvy','mst','ros','arakni','hnt','amx','sea','mpg','sup','pen','omn']:
    for entry in json.loads((HERE/(name+'_abilities.json')).read_text()):
        existing[entry['cardId']] = entry
catalog = json.loads((HERE/'iar_catalog.json').read_text())
previous = {e['cardId']:e for e in json.loads((HERE/'iar_abilities.json').read_text())} if (HERE/'iar_abilities.json').exists() else {}
out, pending = [], []

def banish_hand(optional=True, seat='$player'):
    return choose(refs('Hand',seat),'Banish_a_card',optional,seat)+f"$banished = FaBIARBanishHand({seat}, $chosen);"

def next_attack(power=0, tag='', kind='ANY'):
    return f"FaBIARNext($player, {power}, '{tag}', '{kind}');"

def blasmophet():
    return "$unique = FaBIARBlasmophet($player); if ($unique !== '') { $chosen = await $player.MZChoose($unique, 'Choose_Blasmophet_to_clear'); FaBIARUniqueClear($player, $chosen); }"

def pay_exact(amount):
    return f'while (intval(GetResources($player)) < {amount}) {{ '+choose('FaBARCPitchChoices($player)','Pitch_to_pay',False)+'if ($chosen === "-") { break; } FaBARCPitchForEffect($player, $chosen); } '+f'AddResources($player, max(0, intval(GetResources($player)) - {amount}));'

for c in catalog:
    id=c['id']; b=re.sub(r'_(red|yellow|blue)$','',id); v=4-int(c['pitch'] or 0); a=[]; handled=False
    def add(macro, code):
        global handled
        handled=True
        old=next((x for x in a if x['macroName']==macro),None)
        if old: old['abilityCode']+='\n'+clean(code)
        else: a.append(dict(macroName=macro,abilityCode=clean(code),isImplemented=True))
    if id in existing:
        out.append(existing[id]); continue
    if 'Usurp' in c['card_keywords']:
        add('PrepareCard',UID+choose('FaBIARRunechants()', 'Usurp_a_Runechant', False)+'FaBIARUsurp($player, $uid, $chosen); FaBFinishPreparedCard($uid);')
    if b in ['demonbound_gloomblade']: handled=True
    if b in ['breach_flesh','corporeal_chasm','shadowake_gloomblade']:
        add('Hit',token('gate_to_iarathael'))
    if b=='murmuring_gloomblade':
        add('AttackDeclared',token('runechant')); add('Hit',token('runechant'))
    if b in ['bloodsong_gloomblade','cullingsong_gloomblade','plundersong_gloomblade','restless_magister','restless_quartermaster']:
        zone='Arena' if b=='bloodsong_gloomblade' else ('Arsenal' if b in ['plundersong_gloomblade','restless_quartermaster'] else 'Hand')
        filt="['type'=>'Aura']" if zone=='Arena' else '[]'
        chooser='$player' if zone=='Arena' else '$victim'
        add('Hit',hit(choose(refs(zone,'$victim',filt),'Banish_a_card',zone=='Arena',chooser)+f"FaBMoveChoice($victim, $chosen, '{zone}', 'Banish');"))
    if b=='vexing_gloomblade':
        add('Hit',hit(UID+choose('FaBPENTargets($player)','Choose_arcane_target',False)+'$targetUID = FaBPENUID($chosen); $dealt = FaBUPRDeal($player, $uid, $targetUID, 2, "ARCANE");'))
    if b=='enshrine_sin': add('ResolveCard',opt(1)+token('runechant'))
    if b in ['gorging_shadowbeast','feasting_shadowbeast','feeding_frenzy','beckoning_hunger','ingest_the_unknown']:
        add('AttackDeclared',UID+'$power = FaBIARBanishTop($player);'+('FaBCRUSelfTagUID($uid, "WTR_POWER:" . $power);' if b=='ingest_the_unknown' else ''))
        if b=='beckoning_hunger':add('Hit',blasmophet())
    if b=='goremass_summoning':add('ResolveCard',"if (FaBMONCount($player, 'BANISHED_SIX')) { "+blasmophet()+" }")
    if b=='rumbling_hunger':add('Hit',UID+"if (FaBMONCount($player, 'BANISHED_SIX')) { "+blasmophet()+" FaBTagUID($uid, 'GO_AGAIN'); }")
    if b=='hellbound_assault':add('Hit',UID+"FaBMoveUID($uid, 'Banish', $player);")
    if b=='unbound_by_shadow':add('AttackDeclared',UID+"if (FaBARCCard($uid, 'monFromBanish', false)) { "+token('gate_to_iarathael')+' }')
    if b=='countdown_to_extinction':
        add('AttackDeclared',token('gate_to_iarathael'))
        add('Hit',choose("FaBStageSearch($player, ['base'=>'darkest_hour'])",'Find_Darkest_Hour')+"FaBMoveChoice($player, $chosen, 'Temp', 'Banish'); FaBFinishSearch($player);")
    if b=='become_the_shadow_lord':add('ResolveCard',banish_hand(False)+"FaBIARHandReward($player, $banished);")
    if b in ['embrace_ursur','shadowrealm_bloodhound','shadowrealm_ripper','shadowrealm_walker']:
        body=UID+banish_hand()
        if b=='embrace_ursur':body+="if (FaBHasType($banished, 'Runeblade')) { "+token('runechant')+' }'
        tag='GO_AGAIN' if b in ['embrace_ursur','shadowrealm_bloodhound'] else 'WTR_POWER:2'
        add('AttackDeclared',body+f"if (FaBHasType($banished, 'Shadow')) {{ FaBTagUID($uid, '{tag}'); }}")
    if b in ['acrid_stench','bone_mass','malignant_migration','ominous_toll']:
        body=choose(refs('Hand',filters="['type'=>'Zombie']"),'Discard_a_zombie')+"if ($chosen !== '-') { FaBDiscardChoice($player, $chosen); "
        body+= {'acrid_stench':'FaBIARCorpse($player);','bone_mass':next_attack(1),'ominous_toll':token('gate_to_iarathael'),
                'malignant_migration':choose(refs('Banish'),'Return_banished_card_to_graveyard',False)+"FaBIARReturnBanish($player, $chosen);"}[b]
        add('AttackDeclared',body+' }')
    if b in ['bonded_burial','mutual_sacrifice','bone_barrier']:
        body=UID+choose('FaBIARAllyCosts($player)','Destroy_or_discard_an_ally')+'if (FaBIARPayAlly($player, $chosen)) { '
        if b=='bone_barrier':body+="FaBTagUID($uid, 'WTR_DEFENSE:2');"
        elif b=='mutual_sacrifice':body+='FaBARCLoseLife($victim, 2, $player);'
        else:body+=choose(refs('Hand','$victim'),'Discard_a_card',False,'$victim')+'FaBDiscardChoice($victim, $chosen);'
        body+=' }';add('Defended' if b=='bone_barrier' else 'Hit',body if b=='bone_barrier' else hit(body))
    if b in ['commit_to_corruption','step_through_realms','otherworldly_sins','abyssal_bite','abyssal_force','abyssal_rush','battle_clearing_bellow','exorcism']:
        power={'commit_to_corruption':v,'step_through_realms':v+1,'otherworldly_sins':v,'abyssal_bite':1,'battle_clearing_bellow':6,'exorcism':3}.get(b,0)
        tag={'commit_to_corruption':'IAR_CORPSE','step_through_realms':'IAR_GATE','abyssal_force':'OVERPOWER','abyssal_rush':'IAR_HIT_GO','exorcism':'IAR_EXORCISM'}.get(b,'')
        kind='SHADOW' if b in ['step_through_realms','abyssal_bite','abyssal_force','abyssal_rush'] else ('SIX' if b=='battle_clearing_bellow' else ('SHADOW_OR_RUNEBLADE' if b=='otherworldly_sins' else 'ANY'))
        add('ResolveCard',next_attack(power,tag,kind)+(token('runechant') if b=='otherworldly_sins' else ''))
    if b=='battle_prep':add('ResolveCard',UID+opt(2)+f"if (FaBIARFrom($uid) === 'Arsenal') {{ "+next_attack(v)+' }')
    if b in ['headstrong_stampede','peak_power','boneseer_skullcap','rocktop_bellow']:
        body=UID+'$six = FaBIARRevealTop($player, '+('true' if b in ['rocktop_bellow','boneseer_skullcap'] else 'false')+');'
        if b in ['headstrong_stampede','peak_power']:body+="if ($six) { FaBTagUID($uid, '"+('GO_AGAIN' if b=='headstrong_stampede' else 'OVERPOWER')+"'); }"
        if b=='rocktop_bellow':body+=next_attack(v+1)+"if ($six) { "+next_attack(0,'OVERPOWER')+' }'
        add('Defended' if b=='boneseer_skullcap' else ('ResolveCard' if b=='rocktop_bellow' else 'AttackDeclared'),body)
    if b=='pull_from_beyond':add('ResolveCard',opt(2)+f"FaBIARPull($player, {int(c['pitch'])});")
    if b in ['rites_of_nightfall']:add('ResolveCard',token('gate_to_iarathael'))
    if b=='soul_of_existence':add('CardPitched','FaBARCLoseLife($player, 1, $player);')
    if b=='arknight_shard':add('CardPitched',token('runechant'))
    if b=='forbidden_harvest':add('ResolveCard',many('FaBIARBanishRefs($player)',3,tip='Turn_up_to_three_face_down')+'FaBIARHarvest($player, $chosen);')
    if b in ['shadowrealm_solace','shadowrealm_strength','shadowrealm_swiftness']:
        body=choose('FaBIARBanishRefs($player)','Return_banished_card_to_graveyard')+'if (FaBIARReturnBanish($player, $chosen)) { '
        body+= {'shadowrealm_solace':'FaBCRUGainLife($player, 1);','shadowrealm_strength':next_attack(3),'shadowrealm_swiftness':next_attack(0,'GO_AGAIN')}[b]
        add('ResolveCard',body+' }')
    if b=='shadowrealm_harrower':add('Hit',hit(UID+"if (FaBARCCard($uid, 'monFromBanish', false)) { FaBCRUGainLife($player, intval($amount)); }"))
    if b=='usurp_the_shadow_throne':add('Hit',hit('$count = FaBIARTurnDownAll($victim); FaBARCLoseLife($victim, $count, $player); FaBCRUGainLife($player, $count);'))
    if b=='corrupt_and_conquer':add('Hit',hit("FaBIARBanishZone($victim, 'Arsenal');"))
    if b=='devouring_doomwake':add('Hit',UID+'FaBIARDoomwake($uid);')
    if b=='dam_the_shadowake':add('Defended',"if (FaBIARShadowAttacker()) { "+token('gate_to_iarathael')+' }')
    if b=='circlet_of_eternal_end':add('Defended',"$victim = intval(FaBGetState()['attacker']);"+choose('FaBIARBanishRefs($victim)','Turn_attacker_banished_card_face_down',False)+'FaBIARTurnDown($victim, $chosen);')
    if b=='whispers_within':add('Defended',opt(1))
    if b=='restless_steed':add('Hit',UID+"FaBTagUID($uid, 'GO_AGAIN');")
    if b=='seven_sin_nebula':add('Hit',hit(token('runechant')))
    if b=='sinspeaker_gloomblade':add('AttackDeclared',UID+"if (FaBARCCard($uid, 'monFromBanish', false)) { "+choose('FaBIARSearchRune($player)','Find_Runechant_aura')+"FaBMoveChoice($player, $chosen, 'Temp', 'Arena'); FaBFinishSearch($player); }")
    if b=='embrace_sin':add('ResolveCard',next_attack(2)+"FaBIARAdd($player, 'EMBRACE');")
    if b=='promise_of_power':add('ResolveCard',"FaBIARAdd($player, 'PROMISE');")
    if b=='planar_chaos':add('ResolveCard',token('gate_to_iarathael')+"FaBIARAdd($player, 'PLANAR');")
    if b in ['gate_to_iarathael','malice','malice_domina_of_the_dead']:
        kind='Gate' if b=='gate_to_iarathael' else 'Zombie'
        add('PrepareCard',UID+choose(f"FaBIARPermissionChoices($player, '{kind}')",'Choose_card_to_play_this_turn',False)+f"FaBIARStorePermission($player, $uid, $chosen, '{kind}'); FaBFinishPreparedCard($uid);")
        add('ResolveAbility',"$stackUID = intval(DecisionQueueController::GetVariable('arcAbilityUID')); FaBIARGrantPermission($player, $stackUID);")
    if b in ['blood_harvest','cleave_the_heavens','consuming_appetite','consuming_lash','consuming_strength','fallen_herald','satiate_bloodthirst','tribute_to_greater_power','runic_disposition','runic_reaving']:
        body={'blood_harvest':'AddResources($player, intval(GetResources($player)) + 3);',
              'cleave_the_heavens':token('gate_to_iarathael'), 'consuming_appetite':"FaBIARAdd($player, 'BLASMOPHET_ATTACK');",
              'consuming_lash':next_attack(0,'GO_AGAIN'), 'consuming_strength':next_attack(2),
              'fallen_herald':"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 4);", 'satiate_bloodthirst':'FaBCRUGainLife($player, 1);',
              'tribute_to_greater_power':next_attack(0,'OVERPOWER'), 'runic_disposition':token('runechant'),'runic_reaving':token('runechant')}[b]
        add('ResolveAbility',body)
    if b=='consuming_command':add('ResolveCard',"FaBIARAdd($player, 'BLASMOPHET_ATTACK');")
    if b in ['grille_of_repentance','path_of_repentance','robe_of_repentance','hex_gauntlet']:
        add('ResolveAbility',choose("FaBIARBanishRefs($player, 'DEBT')",'Turn_blood_debt_card_face_down',False)+'FaBIARTurnDown($player, $chosen);')
    if b=='grasp_of_the_darknight':add('ResolveAbility',opt(1)+token('runechant'))
    if b in ['restless_cleric','restless_corporal','restless_looter','restless_plowman']:
        body={'restless_cleric':'FaBCRUGainLife($player, 1);',
              'restless_plowman':'AddResources($player, intval(GetResources($player)) + 1);',
              'restless_corporal':choose(refs('Banish'),'Return_banished_card_to_graveyard',False)+'FaBIARReturnBanish($player, $chosen);',
              'restless_looter':choose(refs('Hand'),'Discard_a_card',False)+'if (FaBDiscardChoice($player, $chosen)) { DoDrawCard($player, 1); }'}[b]
        add('ResolveAbility',body)
    if b=='rush_of_knowledge':add('AttackDeclared',choose(refs('Arena',filters="['base'=>'ponder']"),'Destroy_Ponder_to_draw')+"if ($chosen !== '-') { FaBMONDestroy(FaBPENUID($chosen)); DoDrawCard($player, 1); AddActionPoints($player, intval(GetActionPoints($player)) + 1); }")
    if b=='crushing_headache':add('Hit',hit('if (intval($amount) >= 4) { FaBIARHeadache($victim); }'))
    if b=='echoing_trap':add('Defended',"$victim = intval(FaBGetState()['attacker']); if (FaBIAREcho()) { "+choose(refs('Hand','$victim'),'Discard_a_card',False,'$victim')+'FaBDiscardChoice($victim, $chosen); }')
    if b=='dimenxxional_ferryman':add('ResolveCard',UID+choose("FaBIARDebtActions($player)",'Bottom_blood_debt_action',False)+'FaBIARFerryman($player, $uid, $chosen);')
    if b in ['runechant_of_envy','runechant_of_gluttony','runechant_of_greed','runechant_of_lust','runechant_of_pride','runechant_of_sloth','runechant_of_wrath']:
        add('ResolveAbility',"FaBIARRuneTrigger($player, '"+b+"', intval(DecisionQueueController::GetVariable('iarAttack')), (string)DecisionQueueController::GetVariable('rosEvent'));")
    if b in ['arknight_descendancy','open_the_gate_to_iarathael']:
        if b=='arknight_descendancy':add('ResolveAbility',"if (DecisionQueueController::GetVariable('rosEvent') === 'iarBanish') { $max = min(3, intval(GetHealth($player))); $n = await $player.NumberChoose(0, $max, \"Pay_up_to_three_life\"); $paid = intval($n); FaBARCLoseLife($player, $paid, $player); FaBARCCreateRunes($player, $paid); }")
        else:
            add('Hit',token('gate_to_iarathael'))
            add('ResolveAbility',token('gate_to_iarathael'))
    if b=='blasmophet_the_insatiable_hunger':
        permission=UID+"$card = FaBFindUID($uid); $options = FaBIARPermissionOptions($player, $card['object']); $other = FaBIAROtherPermission($player, $card['object']); if ($other) { $chosen = await $player.MZMayChoose($options, 'Choose_Blasmophet_or_use_other_permission'); } else { $chosen = await $player.MZChoose($options, 'Choose_Blasmophet_permission'); } FaBARCSetCard($uid, 'iarDeclaredPermission', FaBPENUID($chosen)); $card = FaBFindUID($uid); if ($card) { DoPlayCard($player, $card['mzID']); }"
        add('ResolveAbility',"if (DecisionQueueController::GetVariable('rosEvent') === 'iarPermission') { "+permission+" } else { $source = intval(DecisionQueueController::GetVariable('rosSource'));"+banish_hand()+"if (!FaBIARCount($player, 'BANISHED_DEBT')) { FaBMONDestroy($source); } }")
    if b in ['viserai_between_worlds','viserai_the_forsaken']:
        add('ResolveAbility',"FaBIARBanishTop($player); if (FaBIARCount($player, 'RUNES_CREATED') >= 3) { FaBIARTraverse($player, '"+id+"'); }")
    if b=='viserai_usurper':add('ResolveAbility',"$yes = await $player.YesNo('Traverse_back?'); if ($yes === 'YES') { FaBIARTraverse($player, 'viserai_usurper'); }")
    if b=='bridge_of_damnation':add('ResolveAbility',"$source = intval(DecisionQueueController::GetVariable('rosSource'));"+choose("FaBIARBanishRefs($player, 'Zombie')",'Return_zombie_or_destroy_Bridge')+"if (!FaBIARReturnBanish($player, $chosen)) { FaBMONDestroy($source); }")
    if b=='blessing_of_suraya':add('StartTurn',UID+"FaBMoveUID($uid, 'Soul', $player);")
    if b=='sigil_of_the_muse':add('ResolveAbility',"$source = intval(DecisionQueueController::GetVariable('rosSource')); FaBMONDestroy($source);"+token('ponder'))
    if b in ['darkest_hour','skeletal_puppetry']:
        filt="['type'=>'Ally']" if b=='skeletal_puppetry' else '[]'
        add('PrepareCard',UID+choose('FaBIARAlternativeChoices($player, $uid)','Pay_alternative_cost')+"if ($chosen !== '-') { FaBIARAlternative($player, $uid, $chosen, "+('true' if b=='skeletal_puppetry' else 'false')+"); } FaBFinishPreparedCard($uid);")
        # IAR release notes correct the upstream blue Darkest Hour transcription.
        add('ResolveCard',next_attack(v,'GO_AGAIN','ALLY') if b=='skeletal_puppetry' else next_attack(v+1,'','SHADOW'))
    if b in ['harbinger_of_destruction','tome_of_necrosis','favorable_winds']:
        choices='FaBIARAdditionalCosts($player, $uid)'
        pay={'harbinger_of_destruction':"$banished = FaBIARBanishHand($player, $chosen); if (FaBHasType($banished, 'Shadow')) { FaBTagUID($uid, 'IAR_GATE'); FaBTagUID($uid, 'IAR_GATE'); }",'tome_of_necrosis':'FaBIARPayAlly($player, $chosen);','favorable_winds':'FaBDiscardChoice($player, $chosen);'}[b]
        add('PrepareCard',UID+choose(choices,'Pay_additional_cost',False)+pay+'FaBFinishPreparedCard($uid);')
        if b=='tome_of_necrosis':add('ResolveCard','DoDrawCard($player, 1); FaBIARReadyHero($player);')
        if b=='favorable_winds':add('ResolveCard','DoDrawCard($player, 2);')
    if b=='permanent_interment':
        add('AttackDeclared',UID+'$max = min(3, FaBAvailablePitch($player)); $n = await $player.NumberChoose(0, $max, "Pay_up_to_three_resources"); $n = intval($n); '+pay_exact('$n')+many("FaBIARBanishRefs($player, 'Shadow')",'$n',"min($n, count(array_filter(explode('&', FaBIARBanishRefs($player, 'Shadow')))))",'Turn_Shadow_cards_face_down')+'$turned = FaBIARTurnDown($player, $chosen); FaBTagUID($uid, "WTR_POWER:" . $turned);')
    if b=='forsaken_strike':
        add('PrepareCard',UID+many(refs('Arena',filters="['type'=>'Zombie']"),3,tip='Destroy_up_to_three_zombies')+'$n = FaBIARPayZombies($player, $chosen); '+many(refs('Hand',filters="['type'=>'Zombie']"),3,tip='Discard_up_to_three_zombies')+'$n = $n + FaBIARPayZombies($player, $chosen); $modes = min(3, $n); if ($modes > 0) { $mode = await $player.Modal($modes, $modes, "Gate_on_attack&Power&Go_again", "Choose_different_zombie_rewards"); FaBIARForsaken($player, $uid, $mode); } FaBFinishPreparedCard($uid);')
        add('AttackDeclared',UID+"if (FaBIARTagged($uid, 'IAR_FORSKEN_GATE')) { "+token('gate_to_iarathael')+' }')
    if b=='sonata_dystopia':
        add('PrepareCard',UID+many('FaBIARRunechants($player)',999,tip='Destroy_Runechants')+'$n = FaBIARPayRunes($player, $chosen); FaBARCSetCard($uid, "iarSonata", $n); FaBFinishPreparedCard($uid);')
        add('ResolveCard',UID+'$n = intval(FaBARCCard($uid, "iarSonata")); FaBWTRAddEffect($player, "IAR_SONATA", $n);')
    if b in ['corpse_cover','rally_the_shadow_horde','appalling_bearers']:
        choices='FaBIARAllyCosts($player)' if b=='corpse_cover' else refs('Hand',filters="['type'=>'Zombie']" if b=='appalling_bearers' else '[]')
        cost='FaBIARPayAlly($player, $chosen);' if b=='corpse_cover' else ('FaBIARBanishHand($player, $chosen);' if b=='rally_the_shadow_horde' else 'FaBDiscardChoice($player, $chosen);')
        add('PrepareCard',UID+choose(choices,'Pay_activation_cost',False)+cost+'FaBFinishPreparedCard($uid);')
        add('ResolveAbility',"FaBTagUID(intval(DecisionQueueController::GetVariable('penAbilitySource')), 'WTR_DEFENSE:2');" if b=='rally_the_shadow_horde' else "FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 2);")
    if b=='hoodwink':
        add('PrepareCard',UID+many(refs('Hand'),999,tip='Discard_other_cards_for_prevention')+'FaBIARHoodwink($player, $uid, $chosen); FaBFinishPreparedCard($uid);')
        add('ResolveAbility',"$stackUID = intval(DecisionQueueController::GetVariable('arcAbilityUID')); FaBWTRAddEffect($player, 'ARC_PREVENT', intval(FaBARCCard($stackUID, 'iarHoodwink')));")
    if b=='apex_buster':
        add('PrepareCard',UID+choose('FaBIARApexTargets($player)','Choose_defending_card',False)+"FaBARCSetCard($uid, 'iarTarget', FaBPENUID($chosen)); FaBFinishPreparedCard($uid);")
        add('ResolveAbility',"$stackUID = intval(DecisionQueueController::GetVariable('arcAbilityUID')); FaBIARApex($player, intval(FaBARCCard($stackUID, 'iarTarget')));")
    if b=='cogwerx_prong_bot':
        add('ResolveAbility',token('golden_cog'))
        add('Hit',hit(choose('FaBIARCrankItems($player)','Add_steam_to_crank_item')+'FaBIARSteam($player, $chosen);'))
    if b=='deadly_spinneret':add('ResolveAbility','FaBIARSpinneret($player);')
    if b=='bravery_of_the_blade':
        add('PrepareCard',UID+choose('FaBMONAffordableHand($player, "", true)','Charge_your_soul')+'FaBMONCharge($player, $chosen); FaBFinishPreparedCard($uid);')
        add('GoAgainModifier',"return !HasNoAbilities($subjectObj) && FaBMONCount($player, 'CHARGED') ? 1 : 0;")
        add('Hit',"if (FaBMONCount($player, 'CHARGED')) { "+token('courage')+' }')
    if b=='banneret_of_swordsmanship':handled=True
    if b in ['ancient_earth_oak','ice_aged_oak']:
        add('Hit',hit(token('frostbite','$victim') if b=='ancient_earth_oak' else token('embodiment_of_earth'))+UID+"if (FaBFaiHeroHit() && FaBIARBond($uid, '"+('Earth' if b=='ancient_earth_oak' else 'Ice')+"')) { "+("FaBMoveUID($uid, 'Deck', $player);" if b=='ancient_earth_oak' else 'FaBIARFrostSlots($victim);')+' }')
        if b=='ancient_earth_oak':add('AttackPowerModifier',"return !HasNoAbilities($subjectObj) && FaBIARBond(intval($subjectObj->UniqueID), 'Earth') ? 2 : 0;")
        else:add('DominateModifier',"return !HasNoAbilities($subjectObj) && FaBIARBond(intval($subjectObj->UniqueID), 'Ice') ? 1 : 0;")
    if b=='astral_ambience':
        add('ResolveAbility',"if (DecisionQueueController::GetVariable('rosEvent') === 'omnFragment') { $uid = intval(DecisionQueueController::GetVariable('rosSource')); FaBOMNFragment($player, $uid); "+token('spectral_shield')+" } else { FaBTagUID(intval(DecisionQueueController::GetVariable('penAbilitySource')), 'GO_AGAIN'); }")
        add('PrepareCard',UID+"if (FaBIARIsAbility($uid)) { "+choose('FaBIARReadyShields($player)','Tap_a_Spectral_Shield',False)+'FaBIARTap($chosen); } FaBFinishPreparedCard($uid);')
    if b=='channel_stormgarden':
        add('ResolveCard',token('lightning_flow'))
        add('StartTurn',UID+"$flow = FaBELEFlow($uid); "+many("FaBELESelect($player, 'Pitch', 'Lightning')",'$flow',0,'Bottom_Lightning_cards_to_keep_Channel')+'FaBELEChannelPay($player, $uid, $chosen, $flow);')
    if b=='violent_gusto':
        add('AttackDeclared',UID+"if (FaBIARAttacksHero()) { "+V+choose(refs('Arena','$victim',"['type'=>'Aura']"),'Name_and_return_aura')+'FaBIARGusto($uid, $victim, $chosen); }')
        add('Hit',hit(UID+'FaBIARGustoHit($uid, $victim);'))
    if b=='chains_of_consecration':
        add('PrepareCard',UID+choose('FaBIARAllyTargets($player)','Choose_ally_to_prevent',False)+"FaBARCSetCard($uid, 'iarTarget', FaBPENUID($chosen)); FaBFinishPreparedCard($uid);")
        add('ResolveCard',UID+"FaBIARConsecrate(intval(FaBARCCard($uid, 'iarTarget')));")
    if b.startswith('mark_of_'):
        add('PrepareCard',UID+'FaBFinishPreparedCard($uid);')
        add('ResolveCard',UID+choose(refs('Arena',filters="['type'=>'Ally']"),'Bind_to_ally',False)+"FaBIARBind($player, $uid, $chosen);")
        body={'mark_of_pathstone':'FaBCRUGainLife($player, 1);','mark_of_ushering':token('gate_to_iarathael'),'mark_of_neverest':choose('FaBIARBanishRefs($player)','Turn_banished_card_face_down')+'if (FaBIARTurnDown($player, $chosen)) { FaBIARCorpse($player); }'}[b]
        add('ResolveAbility',body)
    if b=='danse_macabre':
        add('ResolveAbility',"$source = intval(DecisionQueueController::GetVariable('rosSource')); $ally = intval(DecisionQueueController::GetVariable('iarAttack')); if (FaBIARDanseReady($source) && FaBAvailablePitch($player) >= 2) { $yes = await $player.YesNo('Pay_two_and_tap_Danse_Macabre?'); if ($yes === 'YES') { "+pay_exact('2')+'FaBIARDanse($source, $ally); } }')
    if b=='stoke_vengeance':
        add('AttackDeclared',UID+"if (FaBIARPreviousAutumn()) { FaBTagUID($uid, 'GO_AGAIN'); FaBTagUID($uid, 'IAR_STOKE'); }")
        add('Hit',UID+"if (FaBIARTagged($uid, 'IAR_STOKE')) { FaBIARNext($player, 2, 'IAR_CHAIN_ONLY'); }")
    if b=='fresh_from_the_forge':
        add('ResolveCard','FaBIARSharpenDaggers($player); FaBIARAdd($player, "FORGE");')
        add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); $victim = intval(DecisionQueueController::GetVariable('rosTarget')); $yes = await $player.YesNo('Remove_power_counter_to_mark?'); if ($yes === 'YES') { FaBIARForgeMark($player, $uid, $victim); }")
    if b=='head_banging_chorus':handled=True
    if b=='vox_necropolis':add('ResolveAbility',"$source = intval(DecisionQueueController::GetVariable('rosSource'));"+choose('FaBOMNAttackTargets($player)','Choose_zombie_attack_target',False)+'FaBIARFreeZombieAttack($player, $source, $chosen);')
    if b in ['dark_arcanite_helm','dark_arcanite_plating','dark_arcanite_gloves','dark_arcanite_boots','restless_shieldmaiden','reach_of_the_abyss','vox_necropolis','wind_slicer']:handled=True
    # Shared rules are explicitly listed, not inferred from the presence of another macro.
    runtime = ['baalghor_omen_of_the_end','bloodfrenzy_gloomblade','blasmophets_boon','shadowrealm_harvester','shadowrealm_reaper','murmur_of_iarathael','rumbling_of_iarathael','tremor_of_iarathael','corrupted_corpse','restless_outlaw','restless_templar']
    if b=='baalghor_omen_of_the_end':add('ResolveAbility',"$pitched = intval(DecisionQueueController::GetVariable('rosSource')); $f = FaBFindUID($pitched); if ($f && $f['zone'] === 'Pitch') { FaBMoveUID($pitched, 'Banish', $player); }")
    if b in runtime:handled=True
    # Usurp alone is not sufficient coverage for cards with another effect.
    if 'Usurp' in c['card_keywords'] and len(a)==1 and b not in ['demonbound_gloomblade','bloodfrenzy_gloomblade']:handled=False
    if handled:out.append(dict(cardId=id,abilities=a))
    else:pending.append(dict(cardId=id,text=c['functional_text_plain']))

for entry in out:
    for ability in entry['abilities']:
        old=next((x for x in previous.get(entry['cardId'],{}).get('abilities',[]) if x['macroName']==ability['macroName']),None)
        if old and old['abilityCode'].strip()!=ability['abilityCode'].strip():ability['previousCodeHash']=hashlib.sha256(old['abilityCode'].strip().encode()).hexdigest()
        elif old and 'previousCodeHash' in old:ability['previousCodeHash']=old['previousCodeHash']
(HERE/'iar_abilities.json').write_text(json.dumps(out,indent=2)+'\n')
(HERE/'iar_pending.json').write_text(json.dumps(pending,indent=2)+'\n')
print(f'IAR: {len(out)}/{len(catalog)} authored; {len(pending)} pending')
if pending:raise RuntimeError('Incomplete IAR coverage. Do not present as a complete set.')
