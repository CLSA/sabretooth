// style all links, submits and buttons with jquery-ui
$( function() {
  $( "button, input:submit, a" ).button();
  $( "a" ).click( function() { return false; } );
} );

// setup the top extruder
$(document).ready( function() {
  $("#top_extruder").buildMbExtruder( {
    positionFixed:false,
    width:400,
    sensibility:500,
    extruderOpacity:1,
    autoCloseTime:0,
    hidePanelsOnClose:true,
    onExtOpen:function(){},
    onExtContentLoad:function(){},
    onExtClose:function(){}
  } );
} );
