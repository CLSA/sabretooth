<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\opal_instance;
use cenozo\lib, cenozo\log, sabretooth\util;

class patch extends \cenozo\service\patch
{
  /**
   * Extends parent method
   */
  public function get_file_as_array()
  {
    $patch_array = parent::get_file_as_array();

    $this->original_patch_array = $patch_array;
    if( array_key_exists( 'active', $patch_array ) ) unset( $patch_array['active'] );
    if( array_key_exists( 'username', $patch_array ) ) unset( $patch_array['username'] );
    return $patch_array;
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    parent::execute();

    if( array_key_exists( 'active', $this->original_patch_array ) ||
        array_key_exists( 'username', $this->original_patch_array ) )
    {
      $leaf_record = $this->get_leaf_record();
      if( !is_null( $leaf_record ) )
      {
        $db_user = $leaf_record->get_user();
        if( array_key_exists( 'active', $this->original_patch_array ) )
        {
          try
          {
            $db_user->active = $this->original_patch_array['active'];
            $this->status->set_code( 204 );
          }
          catch( \cenozo\exception\argument $e )
          {
            $this->status->set_code( 400 );
            throw $e;
          }
        }

        if( array_key_exists( 'username', $this->original_patch_array ) )
        {
          try
          {
            $db_user->name = $this->original_patch_array['username'];
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
            $db_user->save();
          }
          catch( \cenozo\exception\notice $e )
          {
            $this->set_data( $e->get_notice() );
            $this->status->set_code( 406 );
          }
          catch( \cenozo\exception\database $e )
          {
            if( $e->is_duplicate_entry() )
            {
              $data = $e->get_duplicate_columns( $db_user->get_class_name() );
              if( 1 == count( $data ) && 'name' == $data[0] ) $data = array( 'username' );
              $this->set_data( $data );
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

  /**
   * TODO: document
   */
  private $original_patch_array = NULL;
}
