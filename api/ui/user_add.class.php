<?php
/**
 * user_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget user add
 * 
 * @package sabretooth\ui
 */
class user_add extends base_record
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', 'add', $args );
    
    // define all columns defining this record
    $this->item['name'] =
      array( 'heading' => 'Username',
             'type' => 'string',
             'value' => '' );
    $this->item['active'] =
      array( 'heading' => 'Active',
             'type' => 'boolean',
             'value' => true );
  }
}
?>
