# Overview
A reference file for how UI Schemas are used by the engine.

# GameSchema.txt
The GameSchema.txt files in each subapp directory will tell you how the UI generator creates the UIs.

## Game Schema Example
For SWUDeck, the schema at the time of writing looks like this:

SWUDeck
Auto
None
AssetOwner
Header: title=Home, icon=/TCGEngine/Assets/Images/blueDiamond.png, link=/TCGEngine/SharedUI/MainMenu.php
Header: title=Edit, link=/TCGEngine/NextTurn.php?gameName>$gameName&playerID>1&folderPath>SWUDeck
Header: title=Stats, link=/TCGEngine/SWUDeck/DeckStats.php?gameName>$gameName
Header: title=Print, link=/TCGEngine/SWUDeck/CreatePDF.php?gameName>$gameName, target=blank
Header: title=Visibility, module=AssetVisibility, AssetType=1
Header: title=Versions, module=Versions, Zones=myLeader;myBase;myMainDeck;mySideboard
PageBackground: /TCGEngine/Assets/Images/gamebg.jpg
AssetReflection: SWUDeck
Initialization: Initialize.php
ServerInclude: /Custom/DeckValidation.php
ServerInclude: /Custom/CustomInput.php
ClientInclude: /TCGEngine/SWUDeck/Custom/Filters.js
ClientInclude: /TCGEngine/SWUDeck/Custom/ClientActions.js
Leader - CardID:string
Display: Visibility=Self, Mode=All, Left=40%, Top=10px, Split=None
AddValidation: ValidateLeaderAddition
Base - CardID:string
Display: Visibility=Self, Mode=All, Left=62%, Top=10px, Split=None
AddValidation: ValidateBaseAddition
MainDeck - CardID:string
Display: Visibility=Self, Mode=Tile, Left=26%, Top=20%, Bottom=130px, Split=None
Click: Remove(myCards)
AddValidation: ValidateMainDeckAddition
Widgets: CustomInput=<&<<<&V&+&Notes
Heatmap: Property=myStats, FunctionMap={"Play_Win_Rate":{"Function":"CardPlayWinRate","ColorMap":"HigherIsBetter"},"Resource_Rate":{"Function":"CardResourceRatio","ColorMap":"LowerIsBetter"},"Hypergeo":{"Function":"HyperGeo","ColorMap":"HigherIsBetter"},"Karabast_Implementation":{"Function":"KarabastImplemented","ColorMap":"HigherIsBetter"},"Petranaki_Implementation":{"Function":"PetranakiImplemented","ColorMap":"HigherIsBetter"}}
Sort: Property=mySort
CardPane - Value:string
Display: Visibility=Self, Mode=Panel, Left=10px, Top=10px, Width=25%, Bottom=10px, Split=None
Leaders - CardID:string
Display: Visibility=Self, Mode=Pane(CardPane), Left=10px, Top=10px, Split=None
Click: Swap(myLeader)
Bases - CardID:string
Display: Visibility=Self, Mode=Pane(CardPane), Left=10px, Top=10px, Split=None
Click: Swap(myBase)
Cards - CardID:string
Display: Visibility=Self, Mode=Pane(CardPane), Left=10px, Top=10px, Split=None
Click: Add(myMainDeck)
DragMode: Clone
Filter: InAspectFilter
Widgets: CustomInput=V&>&>>>&Notes
Sideboard - CardID:string
Display: Visibility=Self, Mode=Tile, Left=26%, Bottom=5%, Split=None
Click: Remove(mySideboard)
Widgets: CustomInput=<&<<<&^&+&Notes
Heatmap: Property=myStats, FunctionMap={"Play_Win_Rate":{"Function":"CardPlayWinRate","ColorMap":"HigherIsBetter"},"Resource_Rate":{"Function":"CardResourceRatio","ColorMap":"LowerIsBetter"},"Hypergeo":{"Function":"HyperGeo","ColorMap":"HigherIsBetter"},"Karabast_Implementation":{"Function":"KarabastImplemented","ColorMap":"HigherIsBetter"},"Petranaki_Implementation":{"Function":"PetranakiImplemented","ColorMap":"HigherIsBetter"}}
Sort: Property=mySort
Deck - CardID:string
Display: Visibility=Self, Mode=Count(MainDeck), Left=26%, Top=16%, Split=None
Widgets: CustomInput=Hand Draw
Stats - Value:string
Display: Visibility=Public, Mode=Radio(Value), Left=39%, Top=16%
Widgets: Value=Play_Win_Rate&Resource_Rate&Hypergeo
Sort - Value:string
Display: Visibility=Public, Mode=Radio(Value), Left=75%, Top=12%
Widgets: Value=Title&Cost&Aspect&Power&HP&SetNum&SWUDB
CardNotes - CardID:string, Notes:string
Display: Visibility=Self, Mode=None
Versions - Version:string
Display: Visibility=Self, Mode=None

and the generated UI look like this:

var _my_CardPane_activePane = 0;
var _their_CardPane_activePane = 0;
function generatedDragStart() {
  var zone = null;
  zone = document.getElementById("myLeader");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirLeader");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myBase");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirBase");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myMainDeck");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirMainDeck");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myCardPane");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirCardPane");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myLeaders");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirLeaders");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myBases");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirBases");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myCards");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirCards");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("mySideboard");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirSideboard");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myDeck");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirDeck");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myStats");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirStats");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("mySort");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirSort");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myCardNotes");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirCardNotes");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myVersions");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirVersions");
  if(!!zone) zone.classList.add("droppable");
}
function generatedDragEnd() {
  var zone = null;
  zone = document.getElementById("myLeader");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirLeader");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myBase");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirBase");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myMainDeck");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirMainDeck");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myCardPane");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirCardPane");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myLeaders");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirLeaders");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myBases");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirBases");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myCards");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirCards");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("mySideboard");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirSideboard");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myDeck");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirDeck");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myStats");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirStats");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("mySort");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirSort");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myCardNotes");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirCardNotes");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myVersions");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirVersions");
  if(!!zone) zone.classList.remove("droppable");
}
function GetZoneWidgets(zoneName) {
  switch(zoneName) {
    case 'Leader':
      return [];
    case 'Base':
      return [];
    case 'MainDeck':
      return {"CustomInput":[{"Action":"<"},{"Action":"<<<"},{"Action":"V"},{"Action":"+"},{"Action":"Notes"}]};
    case 'CardPane':
      return [];
    case 'Leaders':
      return [];
    case 'Bases':
      return [];
    case 'Cards':
      return {"CustomInput":[{"Action":"V"},{"Action":">"},{"Action":">>>"},{"Action":"Notes"}]};
    case 'Sideboard':
      return {"CustomInput":[{"Action":"<"},{"Action":"<<<"},{"Action":"^"},{"Action":"+"},{"Action":"Notes"}]};
    case 'Deck':
      return {"CustomInput":[{"Action":"Hand Draw"}]};
    case 'Stats':
      return {"Value":[{"Action":"Play_Win_Rate"},{"Action":"Resource_Rate"},{"Action":"Hypergeo"}]};
    case 'Sort':
      return {"Value":[{"Action":"Title"},{"Action":"Cost"},{"Action":"Aspect"},{"Action":"Power"},{"Action":"HP"},{"Action":"SetNum"},{"Action":"SWUDB"}]};
    case 'CardNotes':
      return [];
    case 'Versions':
      return [];
    default:
      return {};
  }
}
function GetZoneClickActions(zoneName) {
  switch(zoneName) {
    case 'Leader':
      return [];
    case 'Base':
      return [];
    case 'MainDeck':
      return [{"Action":"Remove","Parameters":["myCards"]}];
    case 'CardPane':
      return [];
    case 'Leaders':
      return [{"Action":"Swap","Parameters":["myLeader"]}];
    case 'Bases':
      return [{"Action":"Swap","Parameters":["myBase"]}];
    case 'Cards':
      return [{"Action":"Add","Parameters":["myMainDeck"]}];
    case 'Sideboard':
      return [{"Action":"Remove","Parameters":["mySideboard"]}];
    case 'Deck':
      return [];
    case 'Stats':
      return [];
    case 'Sort':
      return [];
    case 'CardNotes':
      return [];
    case 'Versions':
      return [];
    default:
      return {};
  }
}
function GetZoneData(zoneName) {
  switch(zoneName) {
    case 'Leader': case 'myLeader': case 'theirLeader':
      return {"Name":"Leader","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"All","Split":"None","Row":-1,"Left":"40%","Right":-1,"Top":"10px","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[],"ClickActions":[],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"ValidateLeaderAddition","DisplayParameters":[]};
    case 'Base': case 'myBase': case 'theirBase':
      return {"Name":"Base","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"All","Split":"None","Row":-1,"Left":"62%","Right":-1,"Top":"10px","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[],"ClickActions":[],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"ValidateBaseAddition","DisplayParameters":[]};
    case 'MainDeck': case 'myMainDeck': case 'theirMainDeck':
      return {"Name":"MainDeck","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Tile","Split":"None","Row":-1,"Left":"26%","Right":-1,"Top":"20%","Bottom":"130px","Width":-1,"Macros":[],"Widgets":[{"LinkedProperty":"CustomInput","Actions":[{"Action":"<"},{"Action":"<<<"},{"Action":"V"},{"Action":"+"},{"Action":"Notes"}]}],"ClickActions":[{"Action":"Remove","Parameters":["myCards"]}],"DragMode":"Normal","Filters":[],"Heatmaps":[{"Property":"myStats","FunctionMap":"{\"Play_Win_Rate\":{\"Function\":\"CardPlayWinRate\",\"ColorMap\":\"HigherIsBetter\"},\"Resource_Rate\":{\"Function\":\"CardResourceRatio\",\"ColorMap\":\"LowerIsBetter\"},\"Hypergeo\":{\"Function\":\"HyperGeo\",\"ColorMap\":\"HigherIsBetter\"},\"Karabast_Implementation\":{\"Function\":\"KarabastImplemented\",\"ColorMap\":\"HigherIsBetter\"},\"Petranaki_Implementation\":{\"Function\":\"PetranakiImplemented\",\"ColorMap\":\"HigherIsBetter\"}}"}],"Sort":{"Property":"mySort"},"AddValidation":"ValidateMainDeckAddition","DisplayParameters":[]};
    case 'CardPane': case 'myCardPane': case 'theirCardPane':
      return {"Name":"CardPane","Properties":[{"Name":"Value","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Panel","Split":"None","Row":-1,"Left":"10px","Right":-1,"Top":"10px","Bottom":"10px","Width":"25%","Macros":[],"Widgets":[],"ClickActions":[],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":[]};
    case 'Leaders': case 'myLeaders': case 'theirLeaders':
      return {"Name":"Leaders","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Pane","Split":"None","Row":-1,"Left":"10px","Right":-1,"Top":"10px","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[],"ClickActions":[{"Action":"Swap","Parameters":["myLeader"]}],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":["CardPane"]};
    case 'Bases': case 'myBases': case 'theirBases':
      return {"Name":"Bases","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Pane","Split":"None","Row":-1,"Left":"10px","Right":-1,"Top":"10px","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[],"ClickActions":[{"Action":"Swap","Parameters":["myBase"]}],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":["CardPane"]};
    case 'Cards': case 'myCards': case 'theirCards':
      return {"Name":"Cards","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Pane","Split":"None","Row":-1,"Left":"10px","Right":-1,"Top":"10px","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[{"LinkedProperty":"CustomInput","Actions":[{"Action":"V"},{"Action":">"},{"Action":">>>"},{"Action":"Notes"}]}],"ClickActions":[{"Action":"Add","Parameters":["myMainDeck"]}],"DragMode":"Clone","Filters":["InAspectFilter"],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":["CardPane"]};
    case 'Sideboard': case 'mySideboard': case 'theirSideboard':
      return {"Name":"Sideboard","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Tile","Split":"None","Row":-1,"Left":"26%","Right":-1,"Top":-1,"Bottom":"5%","Width":-1,"Macros":[],"Widgets":[{"LinkedProperty":"CustomInput","Actions":[{"Action":"<"},{"Action":"<<<"},{"Action":"^"},{"Action":"+"},{"Action":"Notes"}]}],"ClickActions":[{"Action":"Remove","Parameters":["mySideboard"]}],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":[]};
    case 'Deck': case 'myDeck': case 'theirDeck':
      return {"Name":"Deck","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Count","Split":"None","Row":-1,"Left":"26%","Right":-1,"Top":"16%","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[{"LinkedProperty":"CustomInput","Actions":[{"Action":"Hand Draw"}]}],"ClickActions":[],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":["MainDeck"]};
    case 'Stats': case 'myStats': case 'theirStats':
      return {"Name":"Stats","Properties":[{"Name":"Value","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Public","DisplayMode":"Radio","Split":"Auto","Row":-1,"Left":"39%","Right":-1,"Top":"16%","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[{"LinkedProperty":"Value","Actions":[{"Action":"Play_Win_Rate"},{"Action":"Resource_Rate"},{"Action":"Hypergeo"}]}],"ClickActions":[],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":["Value"]};
    case 'Sort': case 'mySort': case 'theirSort':
      return {"Name":"Sort","Properties":[{"Name":"Value","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Public","DisplayMode":"Radio","Split":"Auto","Row":-1,"Left":"75%","Right":-1,"Top":"12%","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[{"LinkedProperty":"Value","Actions":[{"Action":"Title"},{"Action":"Cost"},{"Action":"Aspect"},{"Action":"Power"},{"Action":"HP"},{"Action":"SetNum"},{"Action":"SWUDB"}]}],"ClickActions":[],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":["Value"]};
    case 'CardNotes': case 'myCardNotes': case 'theirCardNotes':
      return {"Name":"CardNotes","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""},{"Name":"Notes","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"None","Split":"Auto","Row":-1,"Left":-1,"Right":-1,"Top":-1,"Bottom":-1,"Width":-1,"Macros":[],"Widgets":[],"ClickActions":[],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":[]};
    case 'Versions': case 'myVersions': case 'theirVersions':
      return {"Name":"Versions","Properties":[{"Name":"Version","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"None","Split":"Auto","Row":-1,"Left":-1,"Right":-1,"Top":-1,"Bottom":-1,"Width":-1,"Macros":[],"Widgets":[],"ClickActions":[],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":[]};
    default:
      return {};
  }
}
function GetPaneData(zoneName) {
  switch(zoneName) {
    case 'CardPane':
      return ['Leaders','Bases','Cards'];
    default:
      return [];
  }
}
function AssetReflectionPath() {
  return 'SWUDeck';
}

