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

// loads a widget from the server
function load_widget( slot, widget, args )
{
  // add a loading indicator
  $( "#" + slot + "_slot" ).loading( {
    onAjax: true,
    mask: true,
    img: "img/loading.gif",
    delay: 0,
    align: "center"
  } );

  // build the url (args is an associative array)
  var url = "?widget=" + widget;
  if( args != undefined )
  {
    url += "&" + jQuery.param( args );
  }
  $( "#" + slot + "_slot" ).load( url );
//  $( "#" + slot + "_slot" ).loading( false );
}

// request an operation be performed to the server
function send_operation( operation, action, args )
{
  jQuery.ajax( {
    url: "action.php",
    async: false,
    type: "POST",
    data: { "operation": operation,
            "action": action,
            "args": args == undefined ? undefined : jQuery.param( args ) },
    complete: function( request, result ) {
                // TODO:
                // make sure request.status is 200
                // check responseText for error handling (see action.php catch blocks)
              },
    dataType: 'json'
  } );
}

// load the settings widget
$( document ).ready( function() { load_widget( "settings", "settings" ); } );
$( document ).ready( function() { load_widget( "shortcuts", "shortcuts" ); } );
