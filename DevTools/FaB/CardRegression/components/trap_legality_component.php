<?php
// Actual helper/CanPlay extraction; only external metadata and unrelated legality APIs are controlled.
$options = [];
foreach (array_slice($argv, 1) as $arg) {
    $pair = explode('=', $arg, 2);
    if (count($pair) !== 2 || !in_array($pair[0], ['source','extractor','metadata','dictionary','mutant'], true)) throw new RuntimeException('Use bare named key=value arguments.');
    $options[$pair[0]] = $pair[1];
}
$componentSourceFile = $options['source'] ?? dirname(__DIR__,4).'/FaBSim/Custom/CRUCards.php';
$componentExtractorFile = $options['extractor'] ?? __DIR__.'/extract_component_functions.php';
$componentMetadataFile = $options['metadata'] ?? __DIR__.'/trap_explicit_play_text_cases.json';
$componentDictionaryFile = $options['dictionary'] ?? dirname(__DIR__,4).'/FaBSim/GeneratedCode/GeneratedCardDictionaries.php';
$mutant = $options['mutant'] ?? 'none';
$expectedSource = 'b747479f996a5207246c171efce73f899471b9b9608c116241b10086c2c7c292';
$expectedExtractor = 'ca10febfe5f102c0eca748c0fc82f83b0b1a814e4789d7f955b7fe82aca85199';
$expectedMetadata = '6f9ac9ba83a38b9c5be822bf40ee7bec52061ad33abe9156375ac853ba266f81';
$expectedDictionary = '1be127d10c8f7fe3fa10b8aa098678fa43b2790910c95666226b18eb79ea7c60';
foreach ([[$componentSourceFile,$expectedSource],[$componentExtractorFile,$expectedExtractor],[$componentMetadataFile,$expectedMetadata],[$componentDictionaryFile,$expectedDictionary]] as [$path,$want]) {
    if (!is_file($path) || !hash_equals($want, hash_file('sha256',$path))) throw new RuntimeException('Component input hash mismatch: '.$path);
}
if (!in_array($mutant,['none','obsolete-trap-gate','drop-explicit-restriction'],true)) throw new RuntimeException('Unknown controlled mutant.');
require $componentExtractorFile;
$selected = json_decode(file_get_contents($componentMetadataFile), true, 512, JSON_THROW_ON_ERROR);
if (($selected['source_sha256'] ?? '') !== $expectedDictionary) throw new RuntimeException('Selected metadata dictionary binding mismatch.');
$GLOBALS['trapBoundaryRows'] = [];
foreach ($selected['rows'] as $row) $GLOBALS['trapBoundaryRows'][$row['card_id']] = $row;
$GLOBALS['component_checks'] = 0; $GLOBALS['component_function_hashes'] = [];
$groups = []; $currentGroup = 'startup';

function CardFunctional_text_plain($id) { return $GLOBALS['trapBoundaryRows'][$id]['functional_text_plain'] ?? ''; }
function CardName($id) { return $GLOBALS['trapBoundaryRows'][$id]['name'] ?? ''; }
function FaBHasType($o,$type) { return in_array($type, $GLOBALS['trapBoundaryRows'][$o->CardID]['types'] ?? [], true); }
function FaBWTRBase($id) { return preg_replace('/_(?:red|yellow|blue)$/D','',$id); }
function FaBGetState() { return ['window'=>'REACTION','combatOpen'=>true]; }
function FaBCRUCount($p,$type) { return 0; } // Other earned global restrictions absent in this boundary fixture.
function GetCurrentPhase() { return 'MAIN'; }
function GetTurnPlayer() { return 1; }
function FaBWTRIsAttackAction($o) { return FaBHasType($o,'Action') && FaBHasType($o,'Attack'); }
function CardPower($id) { return 0; }
function FaBCRUBlockLegal($p,$f) { return true; } // Unchanged DR timing/block legality is outside this text check.
function FaBCRUTargets($p,$kind) { return 'controlled-unrelated-legal-target'; }
function FaBARCSelect(...$args) { return 'controlled-unrelated-legal-choice'; }
function TrapBoundaryCanPlay(string $id,string $zone):bool {
    return FaBCRUCanPlay(4,['object'=>(object)['CardID'=>$id,'UniqueID'=>6,'Owner'=>4,'Controller'=>4],'zone'=>$zone,'player'=>4]);
}
function TrapBoundaryMetadata(string $id,array $changes,callable $fn):void {
    $before = $GLOBALS['trapBoundaryRows'][$id];
    try { $GLOBALS['trapBoundaryRows'][$id] = array_replace($before,$changes); $fn(); }
    finally { $GLOBALS['trapBoundaryRows'][$id] = $before; }
}
function TrapBoundaryCase(string $name,callable $fn):void {
    global $currentGroup,$groups;
    $currentGroup=$name; $fn(); $groups[]=$name;
}

try {
    ComponentLoadFunction($componentSourceFile,'FaBCRUTrapRequiresArsenal');
    $guard = "    if(FaBHasType(\$o,'Trap')&&\$f['zone']!=='Arsenal'&&FaBCRUTrapRequiresArsenal(\$o))return false;";
    $replace=[];
    if ($mutant==='obsolete-trap-gate') $replace[$guard]="    if(FaBHasType(\$o,'Trap')&&\$f['zone']!=='Arsenal')return false;";
    if ($mutant==='drop-explicit-restriction') $replace[$guard]='    /* controlled mutant: erase explicit origin gate */';
    ComponentLoadFunction($componentSourceFile,'FaBCRUCanPlay',$replace);
    TrapBoundaryCase('01_modern_instant_trap_hand_allowed',function(){
        ComponentCheck(TrapBoundaryCanPlay('anaphylactic_shock_blue','Hand')===true,'Shock has no implicit Trap arsenal restriction');
    });
    TrapBoundaryCase('02_modern_instant_trap_arsenal_allowed',function(){
        ComponentCheck(TrapBoundaryCanPlay('anaphylactic_shock_blue','Arsenal')===true,'Shock Arsenal route unaffected by this gate');
    });
    foreach (['tripwire_trap_red','pitfall_trap_yellow','rockslide_trap_blue'] as $index=>$card) {
        TrapBoundaryCase(sprintf('%02d_legacy_%s_explicit_origin',$index+3,$card),function()use($card){
            ComponentCheck(TrapBoundaryCanPlay($card,'Hand')===false,$card.' explicit Hand rejection remains');
            ComponentCheck(TrapBoundaryCanPlay($card,'Arsenal')===true,$card.' Arsenal remains allowed by this gate');
        });
    }
    TrapBoundaryCase('06_unrestricted_modern_dr_trap_hand_allowed',function(){
        ComponentCheck(TrapBoundaryCanPlay('boulder_trap_yellow','Hand')===true,'Modern Trap subtype adds no implicit play-origin restriction');
    });
    TrapBoundaryCase('07_legacy_named_leading_sentence',function(){
        TrapBoundaryMetadata('pitfall_trap_yellow',['functional_text_plain'=>'Pitfall Trap can only be played from arsenal.'],function(){
            ComponentCheck(FaBCRUTrapRequiresArsenal((object)['CardID'=>'pitfall_trap_yellow'])===true,'Equivalent legacy catalog CardName form retained');
        });
    });
    TrapBoundaryCase('08_explicit_this_from_your_arsenal',function(){
        TrapBoundaryMetadata('pitfall_trap_yellow',['functional_text_plain'=>'This can only be played from your arsenal.'],function(){
            ComponentCheck(FaBCRUTrapRequiresArsenal((object)['CardID'=>'pitfall_trap_yellow'])===true,'Explicit own This/your form retained');
        });
    });
    TrapBoundaryCase('09_quoted_grant_is_not_own_restriction',function(){
        TrapBoundaryMetadata('pitfall_trap_yellow',['functional_text_plain'=>'Give target card "This can only be played from arsenal."'],function(){
            ComponentCheck(FaBCRUTrapRequiresArsenal((object)['CardID'=>'pitfall_trap_yellow'])===false,'Quoted another-card grant not own restriction');
        });
    });
    TrapBoundaryCase('10_conditional_not_unconditional_own_restriction',function(){
        TrapBoundaryMetadata('pitfall_trap_yellow',['functional_text_plain'=>'If you control a bow, this can only be played from arsenal.'],function(){
            ComponentCheck(FaBCRUTrapRequiresArsenal((object)['CardID'=>'pitfall_trap_yellow'])===false,'Conditional text not promoted to unconditional gate');
        });
    });
    TrapBoundaryCase('11_activated_ability_not_own_restriction',function(){
        TrapBoundaryMetadata('pitfall_trap_yellow',['functional_text_plain'=>'Action — 0: This can only be played from arsenal.'],function(){
            ComponentCheck(FaBCRUTrapRequiresArsenal((object)['CardID'=>'pitfall_trap_yellow'])===false,'Activated layer text not promoted to own play gate');
        });
    });
    TrapBoundaryCase('12_nontrap_cohort_unchanged',function(){
        TrapBoundaryMetadata('anaphylactic_shock_blue',['types'=>['Assassin','Instant'],'functional_text_plain'=>'This can only be played from arsenal.'],function(){
            ComponentCheck(TrapBoundaryCanPlay('anaphylactic_shock_blue','Hand')===true,'This repair only changes the existing Trap cohort; other global play gates not modeled');
        });
    });
    echo json_encode(['status'=>'PASS','mutant'=>$mutant,'groups'=>count($groups),'checks'=>$GLOBALS['component_checks'],'passed_groups'=>$groups,'source_sha256'=>$expectedSource,'metadata_sha256'=>$expectedMetadata,'dictionary_sha256'=>$expectedDictionary,'actual_function_hashes'=>$GLOBALS['component_function_hashes'],'scope'=>'Actual shared text/helper and FaBCRUCanPlay only, explicit metadata/state/unrelated-legality boundary doubles. No engine/CardGeneratedCanPlay/DR timing/native game proof.'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    echo json_encode(['status'=>'FAIL','mutant'=>$mutant,'group'=>$currentGroup,'groups_completed'=>count($groups),'checks'=>$GLOBALS['component_checks'],'error'=>$e->getMessage(),'actual_function_hashes'=>$GLOBALS['component_function_hashes']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(1);
}
