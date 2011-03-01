<?php
/**
 * sample_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget sample add
 * 
 * @package sabretooth\ui
 */
class sample_add extends base_view
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
    parent::__construct( 'sample', 'add', $args );
    
    // define all columns defining this record
    $this->item['name'] =
      array( 'heading' => 'Name',
             'type' => 'string',
             'value' => '' );
    $this->item['description'] =
      array( 'heading' => 'Description',
             'type' => 'text',
             'value' => '' );
  }
}
?>
