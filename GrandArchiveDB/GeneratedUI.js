var _my_CardPane_activePane = 0;
var _their_CardPane_activePane = 0;
function generatedDragStart() {
  var zone = null;
  zone = document.getElementById("myCommander");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirCommander");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myReserveDeck");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirReserveDeck");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myMainDeck");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirMainDeck");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myCardPane");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirCardPane");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myCommanders");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirCommanders");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myReserves");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirReserves");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myCards");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirCards");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myNumReserve");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirNumReserve");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("myCountsDisplay");
  if(!!zone) zone.classList.add("droppable");
  zone = document.getElementById("theirCountsDisplay");
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
  zone = document.getElementById("myCommander");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirCommander");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myReserveDeck");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirReserveDeck");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myMainDeck");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirMainDeck");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myCardPane");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirCardPane");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myCommanders");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirCommanders");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myReserves");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirReserves");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myCards");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirCards");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myNumReserve");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirNumReserve");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("myCountsDisplay");
  if(!!zone) zone.classList.remove("droppable");
  zone = document.getElementById("theirCountsDisplay");
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
    case 'Commander':
      return [];
    case 'ReserveDeck':
      return {"CustomInput":[{"Action":"Notes"}]};
    case 'MainDeck':
      return {"CustomInput":[{"Action":"<"},{"Action":"<<<"},{"Action":"+"},{"Action":"Notes"}]};
    case 'CardPane':
      return [];
    case 'Commanders':
      return [];
    case 'Reserves':
      return {"CustomInput":[{"Action":"Notes"}]};
    case 'Cards':
      return {"CustomInput":[{"Action":">"},{"Action":">>>"},{"Action":"Notes"}]};
    case 'NumReserve':
      return [];
    case 'CountsDisplay':
      return {"CustomInput":[{"Action":"Hand Draw"}]};
    case 'Sort':
      return {"Value":[{"Action":"Name"},{"Action":"Type"},{"Action":"Cost"},{"Action":"Attack"},{"Action":"Health"}]};
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
    case 'Commander':
      return [{"Action":"Remove","Parameters":["myCommanders"]}];
    case 'ReserveDeck':
      return [{"Action":"Remove","Parameters":["myReserves"]}];
    case 'MainDeck':
      return [{"Action":"Remove","Parameters":["myCards"]}];
    case 'CardPane':
      return [];
    case 'Commanders':
      return [{"Action":"Add","Parameters":["myCommander"]}];
    case 'Reserves':
      return [{"Action":"Add","Parameters":["myReserveDeck"]}];
    case 'Cards':
      return [{"Action":"Add","Parameters":["myMainDeck"]}];
    case 'NumReserve':
      return [];
    case 'CountsDisplay':
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
    case 'Commander': case 'myCommander': case 'theirCommander': 
      return {"Name":"Commander","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"All","Split":"None","Row":-1,"Left":"31%","Right":-1,"Top":"40px","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[],"ClickActions":[{"Action":"Remove","Parameters":["myCommanders"]}],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"ValidateCommanderAddition","DisplayParameters":[]};
    case 'ReserveDeck': case 'myReserveDeck': case 'theirReserveDeck': 
      return {"Name":"ReserveDeck","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Tile","Split":"None","Row":-1,"Left":"31%","Right":-1,"Top":"82%","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[{"LinkedProperty":"CustomInput","Actions":[{"Action":"Notes"}]}],"ClickActions":[{"Action":"Remove","Parameters":["myReserves"]}],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"ValidateDeckAddition","DisplayParameters":[]};
    case 'MainDeck': case 'myMainDeck': case 'theirMainDeck': 
      return {"Name":"MainDeck","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Tile","Split":"None","Row":-1,"Left":"31%","Right":-1,"Top":"23%","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[{"LinkedProperty":"CustomInput","Actions":[{"Action":"<"},{"Action":"<<<"},{"Action":"+"},{"Action":"Notes"}]}],"ClickActions":[{"Action":"Remove","Parameters":["myCards"]}],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":{"Property":"mySort"},"AddValidation":"ValidateDeckAddition","DisplayParameters":[]};
    case 'CardPane': case 'myCardPane': case 'theirCardPane': 
      return {"Name":"CardPane","Properties":[{"Name":"Value","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Panel","Split":"None","Row":-1,"Left":"10px","Right":-1,"Top":"10px","Bottom":"10px","Width":"30%","Macros":[],"Widgets":[],"ClickActions":[],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":[]};
    case 'Commanders': case 'myCommanders': case 'theirCommanders': 
      return {"Name":"Commanders","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Pane","Split":"None","Row":-1,"Left":"10px","Right":-1,"Top":"10px","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[],"ClickActions":[{"Action":"Add","Parameters":["myCommander"]}],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":["CardPane"]};
    case 'Reserves': case 'myReserves': case 'theirReserves': 
      return {"Name":"Reserves","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Pane","Split":"None","Row":-1,"Left":"10px","Right":-1,"Top":"10px","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[{"LinkedProperty":"CustomInput","Actions":[{"Action":"Notes"}]}],"ClickActions":[{"Action":"Add","Parameters":["myReserveDeck"]}],"DragMode":"Clone","Filters":["InFactionFilter"],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":["CardPane"]};
    case 'Cards': case 'myCards': case 'theirCards': 
      return {"Name":"Cards","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Pane","Split":"None","Row":-1,"Left":"10px","Right":-1,"Top":"10px","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[{"LinkedProperty":"CustomInput","Actions":[{"Action":">"},{"Action":">>>"},{"Action":"Notes"}]}],"ClickActions":[{"Action":"Add","Parameters":["myMainDeck"]}],"DragMode":"Clone","Filters":["InFactionFilter"],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":["CardPane"]};
    case 'NumReserve': case 'myNumReserve': case 'theirNumReserve': 
      return {"Name":"NumReserve","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Calculate","Split":"None","Row":-1,"Left":"31%","Right":-1,"Top":"79%","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[],"ClickActions":[],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":["ReserveDisplay"]};
    case 'CountsDisplay': case 'myCountsDisplay': case 'theirCountsDisplay': 
      return {"Name":"CountsDisplay","Properties":[{"Name":"CardID","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Self","DisplayMode":"Calculate","Split":"None","Row":-1,"Left":"31%","Right":-1,"Top":"10px","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[{"LinkedProperty":"CustomInput","Actions":[{"Action":"Hand Draw"}]}],"ClickActions":[],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":["CountsDisplay"]};
    case 'Sort': case 'mySort': case 'theirSort': 
      return {"Name":"Sort","Properties":[{"Name":"Value","Type":"string","DefaultValue":"\"-\""}],"Visibility":"Public","DisplayMode":"Radio","Split":"Auto","Row":-1,"Left":"63%","Right":-1,"Top":"16%","Bottom":-1,"Width":-1,"Macros":[],"Widgets":[{"LinkedProperty":"Value","Actions":[{"Action":"Name"},{"Action":"Type"},{"Action":"Cost"},{"Action":"Attack"},{"Action":"Health"}]}],"ClickActions":[],"DragMode":"Normal","Filters":[],"Heatmaps":[],"Sort":null,"AddValidation":"","DisplayParameters":["Value"]};
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
      return ['Commanders','Reserves','Cards'];
    default:
      return [];
  }
}

