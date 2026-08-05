<?php
// Auto-generated turn controller by zzTurnGenerator.php

include_once __DIR__ . '/TurnStates.php';
include_once __DIR__ . '/../Core/DecisionQueueController.php';

function IsDecisionQueueEnabled() { return 1; }

function EvaluateTransition($input) {
  $currentPhase = GetCurrentPhase();
  // DecisionQueue check: block phase progression if any player has pending decisions
  $dqController = new DecisionQueueController();
  if (!$dqController->AllQueuesEmpty()) return 'PENDING_DECISION';
  if(!isset($currentPhase)) return $currentPhase;
  if (function_exists('EngineTransitionOverride')) {
    $__ov = EngineTransitionOverride($currentPhase, $input);
    if ($__ov !== null) return $__ov;
  }
  switch($currentPhase) {
    case 'SETUP_LOCATION':
      if(strtoupper(trim($input)) == 'AUTO') return 'SETUP_LOCATION';
      if(strtoupper(trim($input)) == 'READY') return 'SETUP_MULLIGAN';
      // AUTO fallback
      return 'SETUP_LOCATION';
      break;
    case 'SETUP_MULLIGAN':
      if(strtoupper(trim($input)) == 'AUTO') return 'SETUP_MULLIGAN';
      if(strtoupper(trim($input)) == 'READY') return 'FEED_COLLECT';
      // AUTO fallback
      return 'SETUP_MULLIGAN';
      break;
    case 'FEED_COLLECT':
      if(strtoupper(trim($input)) == 'AUTO') return 'FEED_BID';
      // AUTO fallback
      return 'FEED_BID';
      break;
    case 'FEED_BID':
      if(strtoupper(trim($input)) == 'AUTO') return 'FEED_BID';
      if(strtoupper(trim($input)) == 'READY') return 'FEED_RESOLVE';
      // AUTO fallback
      return 'FEED_BID';
      break;
    case 'FEED_RESOLVE':
      if(strtoupper(trim($input)) == 'AUTO') return 'HORROR';
      // AUTO fallback
      return 'HORROR';
      break;
    case 'HORROR':
      if(strtoupper(trim($input)) == 'AUTO') return 'HORROR';
      if(strtoupper(trim($input)) == 'PASS') return 'REFRESH_READY';
      // AUTO fallback
      return 'HORROR';
      // PASS fallback
      return 'REFRESH_READY';
      break;
    case 'REFRESH_READY':
      if(strtoupper(trim($input)) == 'AUTO') return 'REFRESH_FLIP';
      // AUTO fallback
      return 'REFRESH_FLIP';
      break;
    case 'REFRESH_FLIP':
      if(strtoupper(trim($input)) == 'AUTO') return 'REFRESH_FLIP';
      if(strtoupper(trim($input)) == 'READY') return 'REFRESH_HAND';
      // AUTO fallback
      return 'REFRESH_FLIP';
      break;
    case 'REFRESH_HAND':
      if(strtoupper(trim($input)) == 'AUTO') return 'REFRESH_HAND';
      if(strtoupper(trim($input)) == 'READY') return 'FEED_COLLECT';
      // AUTO fallback
      return 'REFRESH_HAND';
      break;
    default: break;
  }
  return $currentPhase;
}

function AdvanceTurnState($input, $params = null) {
  $next = EvaluateTransition($input);
  if($next !== GetCurrentPhase() && $next !== "PENDING_DECISION") { 
    SetCurrentPhase($next);
    if($params !== null) SetPhaseParameters(json_encode($params));
    return true;
  }
  return false;
}

function AutoAdvance() {
  $changed = false;
  while(true) {
    // Use EvaluateTransition('AUTO') to determine the next auto target for the current phase.
    $next = EvaluateTransition('AUTO');
    if($next === GetCurrentPhase() || $next == "PENDING_DECISION") break;
    SetCurrentPhase($next);
    ExecutePhase();
    $changed = true;
  }
  return $changed;
}

// Execute the current phase's code using a switch-based registry for performance.
function ExecutePhase() {
  $currentPhase = GetCurrentPhase();
  if(!isset($currentPhase)) return false;
  $phaseParamsStr = GetPhaseParameters();
  $storedParams = ($phaseParamsStr !== '') ? json_decode($phaseParamsStr, true) : null;
  SetPhaseParameters('');
  switch($currentPhase) {
    case 'SETUP_LOCATION':
      if(function_exists('HellbreakSetupLocationPhase')) { 
        if($storedParams !== null) HellbreakSetupLocationPhase(...$storedParams);
        else HellbreakSetupLocationPhase();
      }
      break;
    case 'SETUP_MULLIGAN':
      if(function_exists('HellbreakSetupMulliganPhase')) { 
        if($storedParams !== null) HellbreakSetupMulliganPhase(...$storedParams);
        else HellbreakSetupMulliganPhase();
      }
      break;
    case 'FEED_COLLECT':
      if(function_exists('HellbreakFeedingCollectPhase')) { 
        if($storedParams !== null) HellbreakFeedingCollectPhase(...$storedParams);
        else HellbreakFeedingCollectPhase();
      }
      break;
    case 'FEED_BID':
      if(function_exists('HellbreakFeedingBidPhase')) { 
        if($storedParams !== null) HellbreakFeedingBidPhase(...$storedParams);
        else HellbreakFeedingBidPhase();
      }
      break;
    case 'FEED_RESOLVE':
      if(function_exists('HellbreakFeedingResolvePhase')) { 
        if($storedParams !== null) HellbreakFeedingResolvePhase(...$storedParams);
        else HellbreakFeedingResolvePhase();
      }
      break;
    case 'HORROR':
      if(function_exists('HellbreakHorrorPhase')) { 
        if($storedParams !== null) HellbreakHorrorPhase(...$storedParams);
        else HellbreakHorrorPhase();
      }
      break;
    case 'REFRESH_READY':
      if(function_exists('HellbreakRefreshReadyPhase')) { 
        if($storedParams !== null) HellbreakRefreshReadyPhase(...$storedParams);
        else HellbreakRefreshReadyPhase();
      }
      break;
    case 'REFRESH_FLIP':
      if(function_exists('HellbreakRefreshFlipPhase')) { 
        if($storedParams !== null) HellbreakRefreshFlipPhase(...$storedParams);
        else HellbreakRefreshFlipPhase();
      }
      break;
    case 'REFRESH_HAND':
      if(function_exists('HellbreakRefreshHandPhase')) { 
        if($storedParams !== null) HellbreakRefreshHandPhase(...$storedParams);
        else HellbreakRefreshHandPhase();
      }
      break;
    default: break;
  }
  // Optional persistence hook that user code can implement to persist the current phase.
  if(function_exists('PersistCurrentPhase')) { PersistCurrentPhase($currentPhase); }
  return true;
}

// Advance by input and execute the new phase if it changed. Returns true if changed.
function AdvanceAndExecute($input) {
  $changed = AdvanceTurnState($input);
  if($changed) ExecutePhase();
  return $changed;
}

// Auto-advance along AUTO transitions and execute final phase if changed.
function AutoAdvanceAndExecute() {
  $changed = AutoAdvance();
  return $changed;
}

