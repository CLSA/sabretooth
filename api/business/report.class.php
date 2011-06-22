<?php
/**
 * report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\business
 * @filesource
 */

namespace sabretooth\business;
use sabretooth\log, sabretooth\util;
use sabretooth\database as db;
use sabretooth\exception as exc;

include 'PHPExcel/PHPExcel.php';
include 'PHPExcel/PHPExcel/Writer/Excel2007.php';

/**
 * Creates a report.
 * 
 * @package sabretooth\business
 */
class report extends \sabretooth\base_object
{
  /**
   * Constructor.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function __construct()
  {
    $this->php_excel = new \PHPExcel();
    $this->php_excel->getActiveSheet()->getPageSetup()->setHorizontalCentered( true );
  }

  public function set_cell( $coordinate, $value, $align = NULL, $bold = false )
  {
    $column = preg_replace( '/[^A-Za-z]/', '', $coordinate );
    $row = preg_replace( '/[^0-9]/', '', $coordinate );
    try
    {
      $cell_obj = $this->php_excel->getActiveSheet()->setCellValue( $coordinate, $value, true );
      if( !is_null( $align ) )
      {
        $this->php_excel->getActiveSheet()->getStyle( $coordinate )->getAlignment()->setHorizontal(
          'center' == $align ? \PHPExcel_Style_Alignment::HORIZONTAL_CENTER :
          ( 'right' == $align ? \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
                              : \PHPExcel_Style_Alignment::HORIZONTAL_LEFT ) );
      }
      if( $bold )
      {
        $this->php_excel->getActiveSheet()->getStyle( $coordinate )->getFont()->setBold( true );
      }
      $this->php_excel->getActiveSheet()->getColumnDimension( $column )->setAutoSize( true );
    }
    catch( \Exception $e )
    {
      throw new exc\runtime( 'Error while setting cell value in report.', __METHOD__, $e );
    }

    return $cell_obj;
  }
  
  public function set_fill( $range, $color = 'FFFFFFFF' )
  {
    $fill_obj = $this->php_excel->getActiveSheet()->getStyle(  $range )->getFill();
    $fill_obj->setFillType( \PHPExcel_Style_Fill::FILL_SOLID );
    $fill_obj->getStartColor()->setARGB( $color );
  }

  public function merge_cells( $range )
  {
    try
    {
      $this->php_excel->getActiveSheet()->mergeCells( $range );
    }
    catch( \Exception $e )
    {
      throw new exc\runtime( 'Error while merging cells in report.', __METHOD__, $e );
    }
  }

  public function get_file( $format )
  {
    // create the desired file writer type 
    if( 'xlsx' == $format )
    {
      $writer = new \PHPExcel_Writer_Excel2007( $this->php_excel );
    } 
    else if( 'xls' == $format )
    {
      $writer = new \PHPExcel_Writer_Excel5( $this->php_excel );
    } 
    else if( 'html' == $format )
    {
      $writer = new \PHPExcel_Writer_HTML( $this->php_excel );
    } 
    else if( 'pdf' == $format )
    {
      $writer = new \PHPExcel_Writer_PDF( $this->php_excel );
    } 
    else // csv
    {
      $writer = new \PHPExcel_Writer_CSV( $this->php_excel );
    } 
    
    ob_start();
    $writer->save( 'php://output' );
    $data = ob_get_contents();
    ob_end_clean();
    return $data;
  }

  private $php_excel = NULL;
}
?>
