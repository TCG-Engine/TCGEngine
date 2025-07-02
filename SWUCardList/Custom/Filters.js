
window.customFilter = true;

function InAspectFilter(cardID) {
  if(!window.myLeaderData || !window.myBaseData) {
    return false;
  }
  var leaderArr = window.myLeaderData.split(" ");
  var baseArr = window.myBaseData.split(" ");
  var leaderAspects = Cardaspect(leaderArr[0]).split(",");
  var baseAspects = Cardaspect(baseArr[0]).split(",");
  var cardAspects = Cardaspect(cardID).split(",");
  if(cardAspects[0] == "") return false;
  for (var i = 0; i < cardAspects.length; i++) {
    if(!leaderAspects.includes(cardAspects[i]) && !baseAspects.includes(cardAspects[i])) return true;
  }
  return false;
}

function HyperGeo(cardID) {
  var deckData = window.myMainDeckData.split("<|>");
  for(var i = 0; i < deckData.length; i++) {
    deckData[i] = deckData[i].split(" ")[0];
  }
  switch(cardID) {
    case "3498814896"://Mon Mothma
      var numRebels = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(TraitContains(deckData[i], "Rebel")) ++numRebels;
      }
      return hypergeoWrapper(1, 5, numRebels-1, deckData.length-1);
    case "9266336818"://Grand Moff Tarkin
      var numImperials = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(TraitContains(deckData[i], "Imperial")) ++numImperials;
      }
      return hypergeoWrapper(2, 5, numImperials-1, deckData.length-1);
    case "3974134277"://Prepare for Takeoff
      var numVehicles = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(TraitContains(deckData[i], "Vehicle")) ++numVehicles;
      }
      return hypergeoWrapper(2, 8, numVehicles, deckData.length-1);
    case "5035052619"://Jabba the Hutt
      var numTricks = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(TraitContains(deckData[i], "Trick")) ++numTricks;
      }
      return hypergeometric(1, 8, numTricks, deckData.length-1);
    case "5696041568"://Triple Dark Raid
      var numVehicles = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(TraitContains(deckData[i], "Vehicle")) ++numVehicles;
      }
      return hypergeoWrapper(1, 7, numVehicles, deckData.length-1);
    case "8096748603"://Steela Gerrera
      var numTactics = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(TraitContains(deckData[i], "Tactic")) ++numTactics;
      }
      return hypergeoWrapper(1, 8, numTactics, deckData.length-1);
    case "1386874723"://Omega
      var numClones = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(TraitContains(deckData[i], "Clone")) ++numClones;
      }
      return hypergeoWrapper(1, 5, numClones-1, deckData.length-1);
    case "8506660490"://Darth Vader, Commanding the First Legion
      var numMatch = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(!AspectContains(deckData[i], "Villainy")) continue;
        if(Cardcost(deckData[i]) > 3) continue;
        if(Cardtype(deckData[i]) != "Unit") continue;
        ++numMatch;
      }
      return hypergeoWrapper(1, 10, numMatch, deckData.length-1);
    case "3407775126"://Recruit
      var numUnits = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(Cardtype(deckData[i]) == "Unit") ++numUnits;
      }
      return hypergeoWrapper(1, 5, numUnits, deckData.length-1);
    case "1565760222"://Remnant Reserves
      var numUnits = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(Cardtype(deckData[i]) == "Unit") ++numUnits;
      }
      return hypergeoWrapper(3, 5, numUnits, deckData.length-1);
    case "9151673075"://Cobb Vanth
      var numMatch = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(Cardcost(deckData[i]) > 2) continue;
        if(Cardtype(deckData[i]) != "Unit") continue;
        ++numMatch;
      }
      return hypergeoWrapper(1, 10, numMatch, deckData.length-1);
    case "9642863632"://Bounty Hunter's Quarry
      var numMatch = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(Cardcost(deckData[i]) > 3) continue;
        if(Cardtype(deckData[i]) != "Unit") continue;
        ++numMatch;
      }
      return hypergeoWrapper(1, 5, numMatch, deckData.length-1);
    case "1141018768"://Commission
      var numMatch = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(TraitContains(deckData[i], "Bounty Hunter") || TraitContains(deckData[i], "Item") || TraitContains(deckData[i], "Transport")) ++numMatch;
      }
      return hypergeoWrapper(1, 5, numMatch, deckData.length-1);
    case "6420322033"://Enticing Reward
      var numMatch = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(Cardtype(deckData[i]) == "Unit") continue;
        ++numMatch;
      }
      return hypergeoWrapper(2, 10, numMatch-1, deckData.length-1);
    case "6884078296"://Greef Karga
      var numMatch = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(Cardtype(deckData[i]) != "Upgrade") continue;
        ++numMatch;
      }
      return hypergeoWrapper(1, 5, numMatch, deckData.length-1);
    case "7138400365"://The Invisible Hand
      var numMatch = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(TraitContains(deckData[i], "Droid")) ++numMatch;
      }
      return hypergeoWrapper(1, 8, numMatch, deckData.length-1);
    case "0524529055"://Snap Wexley
      var numMatch = 0;
      for(var i = 0; i < deckData.length; i++) {
        if(TraitContains(deckData[i], "Resistance")) ++numMatch;
      }
      return hypergeoWrapper(1, 5, numMatch, deckData.length-1);
    case "8968669390"://U-Wing Reinforcement
      var costArr = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
      for(var i = 0; i < deckData.length; i++) {
        var cost = Cardcost(deckData[i]);
        if(Cardtype(deckData[i]) == "Unit" && cost <= 7) {
          costArr[cost]++;
        }
      }
      var chance = hypergeoWrapper(1, 10, costArr[7], deckData.length - 1);
      chance += (1-chance) * hypergeoWrapper(1, 10, costArr[6], deckData.length - 1) * hypergeoWrapper(1, 9, costArr[1], deckData.length - 1);
      chance += (1-chance) * hypergeoWrapper(1, 10, costArr[5], deckData.length - 1) * hypergeoWrapper(1, 9, costArr[2], deckData.length - 1);
      chance += (1-chance) * hypergeoWrapper(1, 10, costArr[5], deckData.length - 1) * hypergeoWrapper(1, 9, costArr[1], deckData.length - 1) * hypergeoWrapper(1, 8, costArr[1], deckData.length - 1);
      chance += (1-chance) * hypergeoWrapper(1, 10, costArr[4], deckData.length - 1) * hypergeoWrapper(1, 9, costArr[3], deckData.length - 1);
      chance += (1-chance) * hypergeoWrapper(1, 10, costArr[4], deckData.length - 1) * hypergeoWrapper(1, 9, costArr[2], deckData.length - 1) * hypergeoWrapper(1, 8, costArr[1], deckData.length - 1);
      chance += (1-chance) * hypergeoWrapper(1, 10, costArr[3], deckData.length - 1) * hypergeoWrapper(1, 9, costArr[2], deckData.length - 1) * hypergeoWrapper(1, 8, costArr[2], deckData.length - 1);
      chance += (1-chance) * hypergeoWrapper(1, 10, costArr[3], deckData.length - 1) * hypergeoWrapper(1, 9, costArr[3], deckData.length - 1) * hypergeoWrapper(1, 8, costArr[1], deckData.length - 1);
      return chance;
    default: break;
  }
  return -1;
}

function hypergeoWrapper(successes, sampleSize, populationSuccesses, populationSize) {
  var sum = 0;
  for (var i = successes; i <= sampleSize; i++) {
    var prob = hypergeometric(i, sampleSize, populationSuccesses, populationSize);
    if(prob >= 0) sum += prob;
  }
  return sum;
}

function hypergeometric(successes, sampleSize, populationSuccesses, populationSize) {
  // Basic edge case validation:
  if (sampleSize > populationSize) return -1;
  if (successes > populationSuccesses) return -1;
  if (sampleSize - successes > populationSize - populationSuccesses) return -1;
  if (successes < 0 || sampleSize < 0 || populationSuccesses < 0 || populationSize < 0) return -1;

  // Factorial function (still recursive and not optimal for large n)
  function factorial(n) {
    if (n < 0) throw new Error("Negative factorial is not defined.");
    return n ? n * factorial(n - 1) : 1;
  }

  function combination(n, k) {
    // If k > n or k < 0, return 0
    if (k > n || k < 0) return 0;
    return factorial(n) / (factorial(k) * factorial(n - k));
  }

  const successComb = combination(populationSuccesses, successes);
  const failureComb = combination(populationSize - populationSuccesses, sampleSize - successes);
  const totalComb = combination(populationSize, sampleSize);

  if(totalComb == 0) return -1;

  return (successComb * failureComb) / totalComb;
}

function TraitContains(cardID, trait) {
  var traits = Cardtrait(cardID);
  if(traits == null) return false;
  return traits.split(",").includes(trait);
}

function AspectContains(cardID, aspect) {
  var aspects = Cardaspect(cardID);
  if(aspects == null) return false;
  return aspects.split(",").includes(aspect);
}