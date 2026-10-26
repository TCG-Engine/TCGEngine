<?php
// Isolated actual-function component: no game bootstrap, source writes or native game.
// CLI: php native_ability_frame_component.php [GameLogic.php] [mutant] [shared-extractor.php]
$source = $argv[1] ?? dirname(__DIR__,4).'/FaBSim/Custom/GameLogic.php';
$mutant = $argv[2] ?? 'none';
$extractor = $argv[3] ?? __DIR__.'/extract_component_functions.php';
$expectedSource = '82a10388fff92d2aa82a4958ca8b43b6ae9de29f876a0c284557b27dfcb4666d';
$expectedExtractor = 'ca10febfe5f102c0eca748c0fc82f83b0b1a814e4789d7f955b7fe82aca85199';
$allowedMutants = ['none','classifier-allowlist','frame-allowlist','ignore-frame-only'];
$component_checks = 0; $component_function_hashes = []; $completedContracts = [];
$activeContract = 'input guards';

function FaBGetState() { return $GLOBALS['componentState']; }
function FaBSetState($state) { $GLOBALS['componentState'] = $state; }
function GetDecisionQueueVariables() { return json_encode($GLOBALS['componentVariables']); }
function GetDecisionQueue($seat) { return $GLOBALS['componentQueues'][$seat]; }
function FaBSeatOrder() { return [1,2,3,4]; }
function FrameComponentReset(?array $pending = null): void {
    $GLOBALS['componentState'] = $pending === null ? [] : ['resolvingReaction'=>$pending];
    $GLOBALS['componentVariables'] = [];
    $GLOBALS['componentQueues'] = [1=>[],2=>[],3=>[],4=>[]];
    $GLOBALS['fabReactionResolutionDepth'] = 0;
}
function FrameComponentPending(): array {
    return ['resolutionClass'=>'ARC_ABILITY','kind'=>'ABILITY','cardID'=>'dreadbore',
        'sourceMZ'=>'p1Weapons-0','baselineFrameKeys'=>[],'ownedFrameKeys'=>[]];
}
function FrameComponentContract(string $name, callable $body): void {
    $GLOBALS['activeContract'] = $name;
    $body();
    $GLOBALS['completedContracts'][] = $name;
}
function FrameComponentLayer(string $kind='ABILITY', array $params=['arcSpec'=>[]], string $card='dreadbore'): object {
    return (object)['Kind'=>$kind,'CardID'=>$card,'Params'=>$params,'SourceZone'=>'Weapons','SourceUniqueID'=>5];
}
function FrameComponentSetFrame(string $key, string $sourceMZ='p1Weapons-0'): void {
    $GLOBALS['componentVariables']['__awaitFrames'][$key] = ['locals'=>['mzID'=>$sourceMZ]];
}

try {
    if (PHP_SAPI !== 'cli') throw new RuntimeException('CLI only.');
    if (!in_array($mutant, $allowedMutants, true)) throw new RuntimeException('Unknown mutant.');
    if (!is_file($source) || hash_file('sha256',$source) !== $expectedSource) throw new RuntimeException('Reviewed GameLogic byte hash mismatch.');
    if (!is_file($extractor) || hash_file('sha256',$extractor) !== $expectedExtractor) throw new RuntimeException('Reviewed shared extractor byte hash mismatch.');
    require $extractor;
    foreach (['FaBManagedVirtualResolutionClass','FaBTrackReactionAwaitFrames',
        'FaBReactionResolutionHasWork','FaBReactionResolutionIsPending'] as $name) {
        $changes = [];
        if ($name === 'FaBManagedVirtualResolutionClass' && $mutant === 'classifier-allowlist') {
            $changes["&& empty(\$stack->Params['rosTrigger'])) return 'ARC_ABILITY';"] =
                "&& empty(\$stack->Params['rosTrigger']) && in_array((string)\$stack->CardID, ['opal_amulet_blue','potion_of_deja_vu_blue'], true)) return 'ARC_ABILITY';";
        }
        if ($name === 'FaBTrackReactionAwaitFrames' && $mutant === 'frame-allowlist') {
            $changes["&& (\$pending['kind'] ?? '') === 'ABILITY';"] =
                "&& (\$pending['kind'] ?? '') === 'ABILITY' && in_array((string)\$pending['cardID'], ['opal_amulet_blue','potion_of_deja_vu_blue'], true);";
        }
        if ($name === 'FaBReactionResolutionHasWork' && $mutant === 'ignore-frame-only') {
            $changes["return is_array(\$variables) && !empty(\$variables['__awaitFrames']);"] = 'return false;';
        }
        ComponentLoadFunction($source,$name,$changes);
    }

    FrameComponentContract('01 native Dreadbore arcSpec ability admitted', function() {
        ComponentCheck(FaBManagedVirtualResolutionClass(FrameComponentLayer()) === 'ARC_ABILITY','native admission');
    });
    FrameComponentContract('02 admission uses native layer protocol rather than card allowlist', function() {
        foreach (['native_component_sentinel','opal_amulet_blue','potion_of_deja_vu_blue'] as $card)
            ComponentCheck(FaBManagedVirtualResolutionClass(FrameComponentLayer(card:$card)) === 'ARC_ABILITY','protocol-based admission');
    });
    FrameComponentContract('03 rosTrigger no-arcSpec and non-ability layers stay excluded', function() {
        foreach ([FrameComponentLayer(params:['arcSpec'=>[],'rosTrigger'=>true]),
            FrameComponentLayer(params:[]),FrameComponentLayer('ATTACK'),
            FrameComponentLayer('WEAPON'),FrameComponentLayer('ACTION'),FrameComponentLayer('TRIGGER')] as $layer)
            ComponentCheck(FaBManagedVirtualResolutionClass($layer) === '','separate layer path');
    });
    FrameComponentContract('04 marked Runechant trigger predicate remains exact', function() {
        $r = FrameComponentLayer('TRIGGER',['arcRunechant'=>true],'runechant'); $r->SourceZone='Arena';
        ComponentCheck(FaBManagedVirtualResolutionClass($r) === 'RUNECHANT_TRIGGER','marked Runechant');
        foreach (['unmarked','wrong-zone','zero-source','wrong-card'] as $variant) {
            $v=clone $r;
            if ($variant==='unmarked') $v->Params=[];
            if ($variant==='wrong-zone') $v->SourceZone='Graveyard';
            if ($variant==='zero-source') $v->SourceUniqueID=0;
            if ($variant==='wrong-card') $v->CardID='component_other';
            ComponentCheck(FaBManagedVirtualResolutionClass($v) === '','Runechant boundary');
        }
    });
    FrameComponentContract('05 actual tracker owns new Dreadbore ResolveAbility frame', function() {
        FrameComponentReset(FrameComponentPending());
        FrameComponentSetFrame('dreadbore:0:ResolveAbility#1');
        FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === ['dreadbore:0:ResolveAbility#1'],'new native ability frame ownership');
    });
    FrameComponentContract('06 pre-existing baseline frame stays unowned', function() {
        $p=FrameComponentPending();$p['baselineFrameKeys']=['dreadbore:0:ResolveAbility#1'];
        FrameComponentReset($p);FrameComponentSetFrame('dreadbore:0:ResolveAbility#1');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === [],'baseline exclusion');
    });
    FrameComponentContract('07 foreign card namespace stays unowned', function() {
        FrameComponentReset(FrameComponentPending());FrameComponentSetFrame('opal_amulet_blue:0:ResolveAbility#1');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === [],'namespace exclusion');
    });
    FrameComponentContract('08 ResolveCard frame cannot impersonate native ability frame', function() {
        FrameComponentReset(FrameComponentPending());FrameComponentSetFrame('dreadbore:0:ResolveCard#1');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === [],'macro namespace exclusion');
    });
    FrameComponentContract('09 matching namespace with foreign physical source stays unowned', function() {
        FrameComponentReset(FrameComponentPending());FrameComponentSetFrame('dreadbore:0:ResolveAbility#1','p2Weapons-0');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === [],'physical source exclusion');
    });
    FrameComponentContract('10 repeated tracking deduplicates while retaining earlier owned keys', function() {
        $p=FrameComponentPending();$p['ownedFrameKeys']=['dreadbore:0:ResolveAbility#1'];
        FrameComponentReset($p);FrameComponentSetFrame('dreadbore:0:ResolveAbility#1');FrameComponentSetFrame('dreadbore:0:ResolveAbility#2');
        FaBTrackReactionAwaitFrames();FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === ['dreadbore:0:ResolveAbility#1','dreadbore:0:ResolveAbility#2'],'deduplicated owned frames');
    });
    FrameComponentContract('11 Runechant marker still tracks ResolveCard not ResolveAbility', function() {
        $p=FrameComponentPending();$p['resolutionClass']='RUNECHANT_TRIGGER';$p['kind']='TRIGGER';$p['cardID']='runechant';$p['sourceMZ']='Stack-0';
        FrameComponentReset($p);FrameComponentSetFrame('runechant:0:ResolveCard#1','Stack-0');FrameComponentSetFrame('runechant:0:ResolveAbility#1','Stack-0');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === ['runechant:0:ResolveCard#1'],'unchanged trigger macro ownership');
    });
    FrameComponentContract('12 frame-only work holds resolution with every decision queue empty', function() {
        FrameComponentReset(FrameComponentPending());FrameComponentSetFrame('dreadbore:0:ResolveAbility#1');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBReactionResolutionHasWork(),'actual completion work gate sees frame without queue mask');
        ComponentCheck(FaBReactionResolutionIsPending(),'serialized resolution marker is pending');
    });
    FrameComponentContract('13 any seat static decision row holds completion', function() {
        FrameComponentReset();$GLOBALS['componentQueues'][4]=[(object)['Type'=>'CUSTOM','Param'=>'component-static']];
        ComponentCheck(FaBReactionResolutionHasWork(),'fourth-seat static work');
    });
    FrameComponentContract('14 callback depth independently prevents premature completion', function() {
        FrameComponentReset();$GLOBALS['fabReactionResolutionDepth']=1;
        ComponentCheck(FaBReactionResolutionHasWork() && FaBReactionResolutionIsPending(),'callback depth guards');
    });
    FrameComponentContract('15 drained queues and frames release work gate but preserve marker until finisher', function() {
        FrameComponentReset(FrameComponentPending());
        ComponentCheck(!FaBReactionResolutionHasWork() && FaBReactionResolutionIsPending(),'work gate drained while marker awaits actual finisher');
        FrameComponentReset();ComponentCheck(!FaBReactionResolutionIsPending(),'no marker or callback is pending');
    });
    echo json_encode(['status'=>'PASS','contracts'=>count($completedContracts),'checks'=>$component_checks,
        'mutant'=>$mutant,'source_sha256'=>$expectedSource,'extractor_sha256'=>$expectedExtractor,
        'actual_functions'=>$component_function_hashes,'completed_contracts'=>$completedContracts,
        'scope'=>'Actual extracted admission/tracker/work gates; external state and queues are controlled. No native game, completion-effect, AP-refund or per-card qualification.'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR,json_encode(['status'=>'FAIL','mutant'=>$mutant,'failed_contract'=>$activeContract,
        'error'=>$error->getMessage(),'completed_contracts'=>$completedContracts,
        'actual_functions'=>$component_function_hashes],JSON_UNESCAPED_SLASHES).PHP_EOL);
    exit(1);
}
