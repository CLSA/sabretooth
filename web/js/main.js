/**
 * Updates the shortcut buttons based on cookies
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */
function update_shortcuts() {
  // the home button should only be enabled if the main slot is NOT displaying the home widget
  $( "#self_shortcuts_home" ).attr( "disabled", undefined == $.cookie( "slot__main__widget" ) ||
                                                "self_home" == $.cookie( "slot__main__widget" ) )
                             .button( "refresh" );

  // the prev and next buttons should only be enabled if there are prev and next widgets available
  $( "#self_shortcuts_prev" ).attr( "disabled", undefined == $.cookie( "slot__main__prev" ) )
                             .button( "refresh" );
  $( "#self_shortcuts_next" ).attr( "disabled", undefined == $.cookie( "slot__main__next" ) )
                             .button( "refresh" );
}

/**
 * Creates a modal confirm dialog.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param string title The title of the dialog
 * @param string message The message to put in the dialog
 * @param callback on_confirm A function to execute if the "ok" button is pushed.
 */
function confirm_dialog( title, message, on_confirm, cancel_button ) {
  if( undefined == cancel_button ) cancel_button = true;

  $dialog = $( "#confirm_slot" );
  var buttons = new Object;
  buttons.Ok = function() {
    on_confirm();
    $dialog.dialog( "close" );
  };
  if( cancel_button ) buttons.Cancel = function() { $dialog.dialog( "close" ); };

  $dialog.html( message );
  $dialog.dialog( {
    closeOnEscape: cancel_button,
    title: title,
    modal: true,
    dialogClass: "alert",
    width: 450,
    buttons: buttons,
    open: function( event, ui ) { $( ".ui-dialog-titlebar-close", $(this).parent() ).hide(); }
  } );
}

/**
 * Creates a modal error dialog.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param string title The title of the dialog
 * @param string message The message to put in the dialog
 */
function error_dialog( title, message ) {
  $( "#error_slot" ).html( message );
  $( "#error_slot" ).dialog( {
    title: title,
    modal: true,
    dialogClass: "error",
    width: 450,
    open: function () {
      $(this).parents( ".ui-dialog:first" )
             .find( ".ui-dialog-titlebar" )
             .addClass( "ui-state-error" );
    },
    buttons: { Ok: function() { $(this).dialog( "close" ); } }
  } );
}

/**
 * Request information from the server.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param string subject The subject of the pull.
 * @param string name The name of the pull.
 * @param object args The arguments to pass to the operation object
 * @return mixed The requested data or null if there was an error.
 */
function ajax_pull( subject, name, args ) {
  if( undefined == args ) args = new Object();
  var request = jQuery.ajax( {
    url: subject + "/" + name,
    async: false,
    type: "GET",
    data: jQuery.param( args ),
    complete: function( request, result ) { ajax_complete( request, 'R' ) },
    dataType: "json"
  } );
  var response = jQuery.parseJSON( request.responseText );
  return response.success ? response.data : null;
}

/**
 * Request a push (write) operation from the web service.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param string subject The subject of the push.
 * @param string name The name of the push.
 * @param object args The arguments to pass along with the push.
 * @return bool Whether or not the push completed successfully
 */
function ajax_push( subject, name, args ) {
  if( undefined == args ) args = new Object();
  var request = jQuery.ajax( {
    url: subject + "/" + name,
    async: false,
    type: "POST",
    data: jQuery.param( args ),
    complete: function( request, result ) { ajax_complete( request, 'W' ) },
    dataType: "json"
  } );
  var response = jQuery.parseJSON( request.responseText );
  return response.success;
}

/**
 * Loads a url into a slot.
 * 
 * This function is used by slot_load, slot_prev, slot_next and slot_refresh.
 * It should not be used directly anywhere else.
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param string slot The slot to place the loaded content into.
 * @param string subject The widget's subject.
 * @param string name The widget's name.
 * @param object args The arguments to pass along with the push.
 */
function ajax_slot( slot, action, subject, name, args ) {
  $.loading( {
    onAjax: true,
    mask: true,
    img: "img/loading.gif",
    delay: 300, // ms
    align: "center"
  } );
  
  var url = "slot/" + slot + "/" + action;
  if( subject && name ) url += "/" + subject + "/" + name;
  if( undefined != args ) url += "?" + jQuery.param( args );

  $( "#" + slot + "_slot" ).html( "" );
  $( "#" + slot + "_slot" ).load( url, null,
    function( response, status, request ) { ajax_complete( request, 'I' ) }
  );
}

/**
 * Loads a widget from the server.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param string slot The slot to place the widget into.
 * @param string subject The widget's subject.
 * @param string name The widget's name.
 * @param JSON-array $args The arguments to pass to the widget object
 * @param string no_namespace If true then namespaces are being set manually
 */
function slot_load( slot, subject, name, args, no_namespace ) {
  // build the url (args is an associative array)
  var namespace_args = new Object();
  if( undefined !== args )
  {
    if( undefined === no_namespace || false == no_namespace )
    {
      var namespace = subject + "_" + name;
      namespace_args[namespace] = args;
    }
    else
    {
      namespace_args = args;
    }
  }

  ajax_slot( slot, 'load', subject, name, namespace_args );
}

/**
 * Bring the slot back to the previous widget.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param string slot The slot to affect.
 */
function slot_prev( slot ) {
  ajax_slot( slot, 'prev' );
}

/**
 * Bring the slot to the current widget (after using slot_prev)
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param string slot The slot to rewind.
 */
function slot_next( slot ) {
  ajax_slot( slot, 'next' );
}

/**
 * Reload the slot's current widget.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param string slot The slot to rewind.
 */
function slot_refresh( slot ) {
  ajax_slot( slot, 'refresh' );
}

/**
 * Process the request from an ajax request, displaying errors if necessary.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @param XMLHttpRequest request The request send back from the server.
 * @param string code A code describing the type of ajax request
 *        (W for push/write, R for pull/read and I for widget/interface)
 */
function ajax_complete( request, code ) {
  if( 400 == request.status ) {
    // application is reporting an error, details are in responseText
    var response = jQuery.parseJSON( request.responseText );
    var error_code =
      code + '.' +
      ( undefined == response.error_type ? 'X' : response.error_type.substr( 0, 1 ) ) + '.' +
      ( undefined == response.error_code ? 'X' : response.error_code );

    if( 'Permission' == response.error_type ) {
      error_dialog(
        'Access Denied',
        '<p>You do not have permission to perform the selected operation.</p>' +
        '<p class="error_code">Error code: ' + error_code + '</p>' );
    }
    else if( 'Notice' == response.error_type ) {
      error_dialog(
        'Notice',
        '<p>' + response.error_message + '</p>' +
        '<p class="error_code">Error code: ' + error_code + '</p>' );
    }
    else { // any other error...
      error_dialog(
        response.error_type + ' Error',
        '<p>' +
        '  The server was unable to complete your request.<br>' +
        '  Please notify a superior with the error code.' +
        '</p>' +
        '<p class="error_code">Error code: ' + error_code + '</p>' );
    }
  }
  else if( 200 != request.status ) {
    // the web server has sent an error
    error_dialog(
      'Networking Error',
      '<p>' +
      '  There was an error while trying to communicate with the server.<br>' +
      '  Please notify a superior with the error code.' +
      '</p>' +
      '<p class="error_code">Error code: ' + code + '.200</p>' );
  }
}
