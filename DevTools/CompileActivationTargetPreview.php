<?php
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Exercise the actual compiler against proposed database source without touching
// the hosted database or the application's generated files.
$generator = file_get_contents(__DIR__.'/../zzGameCodeGenerator.php');
$start = strpos($generator, 'function GenerateMacroParamRetrievalCode(');
$end = strpos($generator, 'function ListenerEventParamVariableName(');
if($start === false || $end === false || $end <= $start) throw new RuntimeException('Compiler helpers moved; update preview boundary');
eval(substr($generator, $start, $end-$start));
$input = json_decode(file_get_contents(__DIR__.'/card-code-migrations/20261021-activation-targets.json'),true,512,JSON_THROW_ON_ERROR);
$output = "<?php\n"; $allHandlers = [];
foreach($input['cards'] as $plan) {
    $macros = ['ActivationTargets'=>$plan['declaration'],($plan['macro_name']==='PlayCard'?'DeclaredPlayResolution':'DeclaredCardResolution')=>$plan['after']];
    $macros = array_merge($macros,$plan['additionalMacros'] ?? []);
    foreach($macros as $macro=>$source) {
        $key = $plan['card_id'].':0'; $handlers = [];
        try { $code = TransformAwaitCode($source,$key.':'.$macro,$plan['name'],$handlers,['mzID']); }
        catch(Throwable $e) { throw new RuntimeException($plan['name'].': '.$e->getMessage(),0,$e); }
        $output .= '$'.lcfirst($macro).'Abilities['.var_export($key,true).'] = function($player) {'."\n".$code."\n};\n";
        $allHandlers = array_merge($allHandlers,$handlers);
    }
}
$nimue = json_decode(file_get_contents(__DIR__.'/card-code-migrations/20261021-nimue.json'),true,512,JSON_THROW_ON_ERROR);
foreach($nimue['cards']['l52lVIFvpy'] as $ability) {
    $macro=$ability['macroName']; $key='l52lVIFvpy:0'; $handlers=[];
    if(($ability['abilityType'] ?? '') === 'listener') {
        $params=['listenerMZ','eventPlayer','event_type','event_targets'];
        $code=TransformAwaitCode($ability['abilityCode'],$key.':Listener:'.$macro,'Nimue',$handlers,$params);
        $output .= '$macroListenerAbilities["CardActivationDeclared"]["l52lVIFvpy:0"] = function($player) {'."\n".$code."\n};\n";
        $output .= '$macroListenerZones["CardActivationDeclared"]["l52lVIFvpy:0"] = ["Field"];'."\n";
        $output .= '$macroListenerEventParamMap["CardActivationDeclared"] = ["cardID"=>"event_cardID","element"=>"event_element","type"=>"event_type","targets"=>"event_targets"];'."\n";
    } else {
        $code=TransformAwaitCode($ability['abilityCode'],$key.':'.$macro,'Nimue',$handlers,['mzID']);
        $output .= '$'.lcfirst($macro).'Abilities['.var_export($key,true).'] = function($player) {'."\n".$code."\n};\n";
    }
}
foreach($allHandlers as $name=>$entry) {
    if(($entry['kind'] ?? '') === 'function') $output .= $entry['code']."\n";
    else $output .= '$customDQHandlers['.var_export($name,true).'] = function($player,$parts,$lastDecision) {'."\n".$entry['code']."\n};\n";
}
$path = sys_get_temp_dir().'/ga-activation-target-preview.php';
file_put_contents($path,$output);
echo count($input['cards'])." card programs compiled to $path\n";
