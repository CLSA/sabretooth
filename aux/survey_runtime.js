// The following was added in order to integrate with Sabretooth
/* NOTE: for now this is dissabled because it was causing error in certain situations
         (attempt to run compile-and-go script on a cleared scope)
jQuery(document).ready( function() {
  var $question = $( "div[id^=question]" );
  var html = $question.html();

  if( $question && html && -1 != html.indexOf( "{periodofday}" ) ) {
    var now = new Date();
    var hour = now.getHours();
    var period = hour < 12 ? "morning" : hour < 17 ? "afternoon" : "evening";
    $question.html( $question.html().replace( /{periodofday}/gi, period ) );
  }
} );
*/

// numpad hotkeys
var compound_number = "";
jQuery(document).ready( function() {
  $("*").keydown(function(event) {
    if( 13 == event.which ) {
      // enter key, click on the next button
      $( "#movenextbtn" ).click();
    } else if ( 96 <= event.which && event.which <= 105 ) {
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
});
