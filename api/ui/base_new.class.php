<?php
/**
 * base_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * Base class for all "new" actions.
 * 
 * Abstract class which defines base functionality for all "new" actions.
 * @package sabretooth\ui
 */
class base_new extends action implements contains_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The widget's subject.
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'new', $args );
    $class_name = '\\sabretooth\\database\\'.$this->get_subject();
    $this->set_record( new $class_name() );
  }
  
  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    foreach( $this->get_argument( 'columns', array() ) as $column => $value )
    {
      $this->get_record()->$column = $value;
    }
       
    try
    {
      $this->get_record()->save();
    }
    catch( \sabretooth\exception\database $e )
    { // help describe exceptions to the user
      if( $e->is_duplicate_entry() )
      {
        throw new \sabretooth\exception\notice(
          'Unable to create the new '.$this->get_subject().' because it is not unique.', $e );
      }

      throw $e;
    }
  }
  
  /**
   * Method required by the contains_record interface.
   * @author Patrick Emond
   * @return database\active_record
   * @access public
   */
  public function get_record()
  {
    return $this->record;
  }

  /**
   * Method required by the contains_record interface.
   * @author Patrick Emond
   * @param database\active_record $record
   * @access public
   */
  public function set_record( $record )
  {
    $this->record = $record;
  }

  /**
   * The active record of the item being created.
   * @var active_record
   * @access private
   */
  private $record = NULL;
}
?>
