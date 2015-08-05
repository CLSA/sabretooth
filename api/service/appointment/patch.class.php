<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\appointment;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * The base class of all patch services.
 */
class patch extends \cenozo\service\patch
{
  /**
   * Extends parent method
   */
  protected function validate()
  {
    parent::validate();

    // make sure to not allow existing appointments to have their date changed
    if( array_key_exists( 'datetime', $this->get_file_as_array() ) &&
        $this->get_leaf_record()->datetime < util::get_datetime_object() )
    {
      $this->data = 'The time of past appointments cannot be changed.';
      $this->status->set_code( 406 );
    }
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    parent::execute();

    $leaf_record = $this->get_leaf_record();
    if( !is_null( $leaf_record ) )
    {
      foreach( $this->get_file_as_array() as $key => $value )
      {
        try
        {
          $leaf_record->$key = $value;
          $this->status->set_code( 204 );
        }
        catch( \cenozo\exception\argument $e )
        {
          $this->status->set_code( 400 );
          throw $e;
        }
      }

      if( 300 > $this->status->get_code() )
      {
        try
        {
          $leaf_record->save();
        }
        catch( \cenozo\exception\notice $e )
        {
          $this->data = $e->get_notice();
          $this->status->set_code( 406 );
        }
        catch( \cenozo\exception\database $e )
        {
          if( $e->is_duplicate_entry() )
          {
            $this->data = $e->get_duplicate_columns( $leaf_record->get_class_name() );
            $this->status->set_code( 409 );
          }
          else
          {
            $this->status->set_code( $e->is_missing_data() ? 400 : 500 );
            throw $e;
          }
        }
      }
    }
  }
}
