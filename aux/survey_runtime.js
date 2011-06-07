// The following was added in order to integrate with Sabretooth
jQuery(document).ready( function() {
  var sabretooth_datum_url = "../sabretooth/datum.php";
  $question = $( "div[id^=question]" )
  if( $question && $question.html() ) {
    jQuery.getJSON( sabretooth_datum_url + "?subject=self&name=primary",
                    function( json ) {
      if( json.success ) {
        $question.html( $question.html().replace( /{operator:first_name}/gi, json.data.first_name ) );
        $question.html( $question.html().replace( /{operator:last_name}/gi, json.data.last_name ) );
      }
    } );
    jQuery.getJSON( sabretooth_datum_url + "?subject=participant&name=primary&id=assignment",
                    function( json ) {
      if( json.success ) {
        $question.html( $question.html().replace( /{participant:first_name}/gi, json.data.first_name ) );
        $question.html( $question.html().replace( /{participant:last_name}/gi, json.data.last_name ) );
        $question.html( $question.html().replace( /{participant:street}/gi, json.data.street ) );
        $question.html( $question.html().replace( /{participant:city}/gi, json.data.city ) );
        $question.html( $question.html().replace( /{participant:region}/gi, json.data.region ) );
        $question.html( $question.html().replace( /{participant:postcode}/gi, json.data.postcode ) );
      }
    } );
    
    var now = new Date();
    var hour = now.getHours();
    var period = hour < 12 ? "morning" : hour < 17 ? "afternoon" : "evening";
    $question.html( $question.html().replace( /{periodofday}/gi, period ) );
  }
} );
