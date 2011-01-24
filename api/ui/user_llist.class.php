<?php
/**
 * user_llist.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 */

namespace sabretooth\ui;

/**
 * user_llist llist
 * 
 * @package sabretooth\ui
 */
class user_llist extends llist
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function __construct()
  {
    parent::__construct();

    // define all template variables for this llist
    $this->title =  "User list" ;
    $this->checkable =  true ;
    $this->viewable =  true ;
    $this->editable =  true ;
    $this->removable =  true ;

    $this->columns = array( "name", "role", "last activity" );
    $this->rows = array(
      array(
        'id' => 1000001,
        'columns' => array( "patrick", "operator", "two minutes" )
      ),
      array(
        'id' => 1000002,
        'columns' => array( "ron", "multiple", "never" )
      ),
      array(
        'id' => 1000003,
        'columns' => array( "val", "clerk", "about two weeks" )
      ),
      array(
        'id' => 1000004,
        'columns' => array( "patrick", "operator", "two minutes" )
      ),
      array(
        'id' => 1000005,
        'columns' => array( "ron", "multiple", "never" )
      ),
      array(
        'id' => 1000006,
        'columns' => array( "val", "clerk", "about two weeks" )
      ),
      array(
        'id' => 1000007,
        'columns' => array( "patrick", "operator", "two minutes" )
      ),
      array(
        'id' => 1000008,
        'columns' => array( "ron", "multiple", "never" )
      ),
      array(
        'id' => 1000009,
        'columns' => array( "val", "clerk", "about two weeks" )
      ),
      array(
        'id' => 1000010,
        'columns' => array( "patrick", "operator", "two minutes" )
      ),
      array(
        'id' => 1000011,
        'columns' => array( "ron", "multiple", "never" )
      ),
      array(
        'id' => 1000012,
        'columns' => array( "val", "clerk", "about two weeks" )
      ) );
  }
}
?>
