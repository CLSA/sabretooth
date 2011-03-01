<?php
/**
 * operation_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget operation list
 * 
 * @package sabretooth\ui
 */
class operation_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the operation list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'operation', $args );
    
    $session = \sabretooth\session::self();

    $this->add_column( 'type', 'type', true );
    $this->add_column( 'subject', 'subject', true );
    $this->add_column( 'name', 'name', true );
    $this->add_column( 'restricted', 'restricted', false );
    $this->add_column( 'description', 'description', false, 'left' );
  }

  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    foreach( $this->get_record_list() as $record )
    {
      $this->add_row( $record->id,
        array( 'type' => $record->type,
               'subject' => $record->subject,
               'name' => $record->name,
               'restricted' => $record->restricted ? 'yes' : 'no',
               'description' => $record->description ) );
    }

    $this->finish_setting_rows();
  }
}
?>
