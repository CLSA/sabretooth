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

/**
 * Creates a modal error dialog.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param string title The title of the dialog
 * @param string message The message to put in the dialog
 */
function error_dialog( title, message )
{
  $( "#error_slot" ).attr( "title", title );
  $( "#error_slot" ).html( message );
  $( "#error_slot" ).dialog( {
    modal: true,
    dialogClass: 'error',
    width: 450,
    open: function () {
      $( this ).parents( ".ui-dialog:first" ).find( ".ui-dialog-titlebar" ).addClass( "ui-state-error" );
    },
    buttons: { Ok: function() { $( this ).dialog( "close" ); } }
  } );
}

/**
 * Loads a widget from the server.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param string slot The slot to place the widget into.
 * @param string widget The widget's name (must be the name of a ui class)
 * @param JSON-array $args The arguments to pass to the widget object
 */
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
  var url = "widget.php?widget=" + widget;
  if( args != undefined )
  {
    url += "&" + jQuery.param( args );
  }
  $( "#" + slot + "_slot" ).load(
    url,
    null,
    function( response, status, request ) {
      if( 'error' == status )
      {
        // TODO: better error handling once HttpResponse is working in widget.php
        error_dialog(
          'Server Error',
          '<p>' +
          '  The server was unable to complete your request.<br>' +
          '  Please notify a supervisor with the error code.' +
          '</p>' +
          '<p style="text-align:right"><em>Error code: W-X</em></p>' );
      }
    }
  );
}

/**
 * Request an operation be performed to the server.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param string operation The name of the operation (must be the name of a business class)
 * @param string action The name of the operation's action (must be one of the operation's methods)
 * @param JSON-array args The arguments to pass to the operation object
 * @return bool Whether or not the operation completed successfully
 */
function send_operation( operation, action, args )
{
  var request = jQuery.ajax( {
    url: "action.php",
    async: false,
    type: "POST",
    data: { "operation": operation,
            "action": action,
            "args": args == undefined ? undefined : jQuery.param( args ) },
    complete: function( request, result ) {
                var response = jQuery.parseJSON( request.responseText );
                
                if( 200 != request.status )
                {
                  error_dialog(
                    'Networking Error',
                    '<p>' +
                    '  There was an error while trying to communicate with the server.<br>' +
                    '  Please notify a supervisor with the error code.' +
                    '</p>' +
                    '<p style="text-align:right"><em>Error code: A-200</em></p>' );
                }
                else if( false === response.success )
                {
                  if( 'permission' == response.error )
                  {
                    error_dialog(
                      'Access Denied',
                      '<p>' +
                      '  You do not have permission to perform the selected action.' +
                      '</p>' +
                      '<p style="text-align:right"><em>Error code: A-P</em></p>' );
                  }
                  else // any other error...
                  {
                    var code = 'X';
                    if( 'database' == response.error ) code = 'D';
                    else if( 'missing' == response.error ) code = 'M';
                    else if( 'runtime' == response.error ) code = 'R';
                    else if( 'unknown' == response.error ) code = 'U';
                    
                    error_dialog(
                      'Server Error',
                      '<p>' +
                      '  The server was unable to complete your request.<br>' +
                      '  Please notify a supervisor with the error code.' +
                      '</p>' +
                      '<p style="text-align:right"><em>Error code: A-' + code + '</em></p>' );
                  }
                }
              },
    dataType: 'json'
  } );
  var response = jQuery.parseJSON( request.responseText );
  return response.success;
}

// load the settings widget
$( document ).ready( function() { load_widget( "settings", "settings" ); } );
$( document ).ready( function() { load_widget( "shortcuts", "shortcuts" ); } );
