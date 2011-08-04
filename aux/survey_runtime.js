// The following was added in order to integrate with Sabretooth
jQuery(document).ready( function() {
  $question = $( "div[id^=question]" )
  if( $question && $question.html() ) {
    var now = new Date();
    var hour = now.getHours();
    var period = hour < 12 ? "morning" : hour < 17 ? "afternoon" : "evening";
    $question.html( $question.html().replace( /{periodofday}/gi, period ) );
  }
} );
