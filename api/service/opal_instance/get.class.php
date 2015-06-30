<?php
/**
 * get.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\opal_instance;
use cenozo\lib, cenozo\log, sabretooth\util;

class get extends \cenozo\service\get
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    // remove active and username columns (they are inserted manually in the execute method)
    $this->original_select = clone $this->select;
    if( $this->select->has_column( 'active' ) ) $this->select->remove_column_by_column( 'active' );
    if( $this->select->has_column( 'username' ) ) $this->select->remove_column_by_column( 'username' );
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    parent::execute();

    // add user columns, if needed
    if( $this->original_select->has_column( 'active' ) || $this->original_select->has_column( 'username' ) )
    {
      $select = lib::create( 'database\select' );
      if( $this->original_select->has_column( 'active' ) ) $select->add_column( 'active' );
      if( $this->original_select->has_column( 'username' ) ) $select->add_column( 'name', 'username' );
      $this->data = array_merge(
        $this->data,
        $this->get_leaf_record()->get_user()->get_column_values( $select ) );
    }
  }

  /**
   * TODO: document
   */
  private $original_select = NULL;
}
