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

// numpad hotkeys
var compound_number = "";
$("*").keydown(function(event) {
  if ( 96 <= event.which && event.which <= 105 ) {
    // translate from key code to number, then append to the compound number
    var num = event.which - 96;
    if( 1 == compound_number.length ) compound_number += num.toString();
    else compound_number = num.toString();
    num = parseInt( compound_number ) - 1;
    
    // select either a special response (97, 98 and 99) or the Nth radio box
    var selector = "97" == compound_number
                 ? "input[value^=OT]"
                 : "98" == compound_number
                 ? "input[value^=DK]"
                 : "99" == compound_number
                 ? "input[value^=RE]"
                 : "input[type=radio]:eq(" + num + ")";
    $( selector ).click();
    
    // select either a special response (97, 98 and 99) or the Nth radio box
    var selector = "97" == compound_number
                 ? "input[name$=OT]"
                 : "98" == compound_number
                 ? "input[name$=DK]"
                 : "99" == compound_number
                 ? "input[name$=RF]"
                 : "input[type=checkbox]:eq(" + num + ")";
    $( selector ).click();
  }
});
