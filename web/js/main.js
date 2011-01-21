// style all links, submits and buttons with jquery-ui
$( document ).ready( function() {
  $( "button, input:submit, a" ).button();
  $( "a" ).click( function() { return false; } );
} );

// setup the top extruder
$( document ).ready( function() {
  $( "#top_extruder" ).buildMbExtruder( {
    positionFixed: false,
    width: 400,
    sensibility: 500,
    extruderOpacity: 1,
    autoCloseTime: 0,
    hidePanelsOnClose: true,
    onExtOpen: function() {},
    onExtContentLoad: function() {},
    onExtClose: function() {}
  } );
} );

function load_widget( widget ) { $( "#widget_" + widget ).load( '?widget=' + widget ); }

// load the settings widget
$( document ).ready( function() { load_widget( "settings" ); } );
