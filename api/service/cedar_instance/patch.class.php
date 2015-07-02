<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\cedar_instance;
use cenozo\lib, cenozo\log, sabretooth\util;

class patch extends \cenozo\service\patch
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    $this->original_patch_array = $this->patch_array;
    if( array_key_exists( 'active', $this->patch_array ) ) unset( $this->patch_array['active'] );
    if( array_key_exists( 'username', $this->patch_array ) ) unset( $this->patch_array['username'] );
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
            $this->data = $e->get_notice();
            $this->status->set_code( 406 );
          }
          catch( \cenozo\exception\database $e )
          {
            if( $e->is_duplicate_entry() )
            {
              $this->data = $e->get_duplicate_columns( $db_user->get_class_name() );
              if( 1 == count( $this->data ) && 'name' == $this->data[0] ) $this->data = array( 'username' );
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
  private $original_select = NULL;
}
