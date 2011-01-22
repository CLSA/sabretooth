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

// load a widget from the server
function load_widget( slot, widget )
{
  // add a loading indicator
  $( "#" + slot + "_slot" ).loading( true, {
    onAjax: true,
    mask: true,
    img: 'img/loading.gif',
    delay: 500,
    align: 'center'
  } );
  $( "#" + slot + "_slot" ).load( '?widget=' + widget );
  $( "#" + slot + "_slot" ).loading( false );
}

// load the settings widget
$( document ).ready( function() { load_widget( "settings", "settings" ); } );
$( document ).ready( function() { load_widget( "main", "llist" ); } );
