<?php
/**
 * role_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget role add
 * 
 * @package sabretooth\ui
 */
class role_add extends base_view
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
    parent::__construct( 'role', 'add', $args );

    // define all columns defining this record
    $this->item['name'] =
      array( 'heading' => 'Name',
             'type' => 'string',
             'value' => '' );
  }
}
?>
