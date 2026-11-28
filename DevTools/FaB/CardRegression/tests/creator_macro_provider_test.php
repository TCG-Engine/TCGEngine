<?php
/** Exact actual provider migrations, idempotence and fail-closed source guards. */
$repo=$argv[1]??dirname(__DIR__,4);require $repo.'/FaBSim/Custom/CodeGeneration.php';
$rows=json_decode(<<<'CREATOR_OLD_CODES'
[
  {
    "card_id": "codex_of_bloodrot_yellow",
    "macro_name": "ResolveCard",
    "ability_code": "$seats = FaBLiveSeats();\n $i = 0;\n while ($i < count($seats)) {\n $seat = intval($seats[$i]);\n $loaded = false;\n if (FaBSeatIsLive($seat)) {\n $refs = implode('&', FaBChoiceRefs($seat, 'Hand'));\n $chosen = 'PASS';\n if ($refs !== '') {\n $chosen = await $seat.MZChoose($refs, \"Put_card_in_arsenal\");\n \n}\n$loaded = FaBOUTCodexLoad($seat, $chosen, 'Hand');\n \n}\n $i = $i + 1;\n \n}\n FaBOUTCodexTokens($player, 'bloodrot_pox');\n",
    "prereq_code": null,
    "ability_type": "macro"
  },
  {
    "card_id": "mindstate_of_tiger_blue",
    "macro_name": "StartTurn",
    "ability_code": "$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);\nFaBMONDestroy($uid);\n AddHand($player, CardID:'crouching_tiger');\n",
    "prereq_code": null,
    "ability_type": "macro"
  },
  {
    "card_id": "song_of_larinkmorth_white_blue",
    "macro_name": "ResolveCard",
    "ability_code": "$seats = FaBOpponents($player);\n for ($i = 0; $i < count($seats); $i = $i + 1) {\n $victim = intval($seats[$i]);\n FaBHVYToken($victim, 'frostbite', 1, $player);\n \n}\n",
    "prereq_code": null,
    "ability_type": "macro"
  }
]
CREATOR_OLD_CODES,true,512,JSON_THROW_ON_ERROR);
$n=0;foreach(['ResolveCard','StartTurn']as$macro){$pool=array_values(array_filter($rows,fn($r)=>$r['macro_name']===$macro));$new=FaBLocalCreatorRepairAbilities($macro,$pool);foreach($new as$r){if($r['ability_code']===$pool[array_search($r['card_id'],array_column($pool,'card_id'))]['ability_code'])throw new RuntimeException('No migration');++$n;}if(FaBLocalCreatorRepairAbilities($macro,$new)!==$new)throw new RuntimeException('Not idempotent');++$n;}
foreach($rows as$row){foreach(['unknown','duplicate','prereq']as$bad){$pool=array_values(array_filter($rows,fn($r)=>$r['macro_name']===$row['macro_name']));$i=array_search($row['card_id'],array_column($pool,'card_id'));if($bad==='unknown')$pool[$i]['ability_code']='unreviewed body';if($bad==='duplicate')$pool[]=$row;if($bad==='prereq')$pool[$i]['prereq_code']='return false;';$caught=false;try{FaBLocalCreatorRepairAbilities($row['macro_name'],$pool);}catch(RuntimeException$e){$caught=true;}if(!$caught)throw new RuntimeException('Unsafe source accepted: '.$bad);++$n;}}
if(FaBLocalCreatorRepairAbilities('Hit',$rows)!==$rows)throw new RuntimeException('Unrelated macro changed');++$n;echo 'PASS '.$n.' actual creator-provider guard checks'.PHP_EOL;
