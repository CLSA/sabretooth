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
  
  /**
   * Magic call method.
   * 
   * Magic call method which is used to set font/cell format type properties which are used when the
   * {@link set_cell} method is called.  The possible format type properties are listed in the
   * {@link current_values} class member.
   * @method mixed get_<format_type>() Returns the current value for the <format_type>.  If no
   *               value has been set the value will be NULL.
   * @method null set_<format_type>() Sets the the current value for the <format_type>.  If set to
   *              NULL then the default format value will be used.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function __call( $name, $args )
  {
    $exception = new exc\runtime(
      sprintf( 'Call to undefined function: %s::%s().',
               get_called_class(),
               $name ), __METHOD__ );
    
    $name_tokens = explode( '_', $name, 2 );
    if( 2 > count( $name_tokens ) ) throw $exception;
    
    // determine if we are getting or setting
    if( 'get' == $name_tokens[0] ) $setting = false;
    else if( 'set' == $name_tokens[0] ) $setting = true;
    else throw $exception;
    
    // make sure the second part of the token is one of the possible format values
    if( !array_key_exists( $name_tokens[1], $this->current_format ) ) throw $exception;
    $format_type = $name_tokens[1];

    // check the arguments
    if( ( !$setting && 0 != count( $args ) ) || // get takes 0 arguments
        (  $setting && 1 != count( $args ) ) )  // set takes 1 argument
      throw new exc\argument( 'args', $args, __METHOD__ );
    
    if( $setting )
    {
      $this->current_format[ $format_type ] = $args[0];
    }
    else
    {
      return $this->current_format[ $format_type ];
    }
  }

  /**
   * Set the value of a cell.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $coordinate A cell in "A1" format
   * @param string $value The value of the cell.  This can either be a string, number, date or time
   *               (which will be displayed as is) or an equation which should always start with =
   *               (ie: =A1+A2)
   * @return PHPExcel Cell object
   * @access public
   */
  public function set_cell( $coordinate, $value )
  {
    $column = preg_replace( '/[^A-Za-z]/', '', $coordinate );
    $row = preg_replace( '/[^0-9]/', '', $coordinate );
    try
    {
      // set the cell's value
      $cell_obj = $this->php_excel->getActiveSheet()->setCellValue( $coordinate, $value, true );
      $style_obj = $this->php_excel->getActiveSheet()->getStyle( $coordinate );

      // set the cell's format
      if( !is_null( $this->current_format['bold'] ) )
        $style_obj->getFont()->setBold( $this->current_format['bold'] );
      if( !is_null( $this->current_format['italic'] ) )
        $style_obj->getFont()->setItalic( $this->current_format['italic'] );
      if( !is_null( $this->current_format['size'] ) )
        $style_obj->getFont()->setSize( $this->current_format['size'] );
      if( !is_null( $this->current_format['foreground_color'] ) )
        $style_obj->getFont()->setColor( $this->current_format['foreground_color'] );
      if( !is_null( $this->current_format['background_color'] ) )
      {
        $style_obj->getFill()->setFillType( \PHPExcel_Style_Fill::FILL_SOLID );
        $style_obj->getFill()->getStartColor()->setRGB(
          $this->current_format['background_color'] );
      }
      if( !is_null( $this->current_format['horizontal_alignment'] ) )
        $style_obj->getAlignment()->setHorizontal( $this->current_format['horizontal_alignment'] );
      if( !is_null( $this->current_format['vertical_alignment'] ) )
        $style_obj->getAlignment()->setVertical( $this->current_format['vertical_alignment'] );

      // always automatically size the cell
      $this->php_excel->getActiveSheet()->getColumnDimension( $column )->setAutoSize( true );
    }
    catch( \Exception $e )
    {
      throw new exc\runtime( 'Error while setting cell value in report.', __METHOD__, $e );
    }

    return $cell_obj;
  }
  
  /**
   * Merges a range of cells into a single cell.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $range Two cells separated by a colon: "A1:B2" format
   * @access public
   */
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

  /**
   * Renders the report in the given format.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $format One of xlsx, xls, html, pdf or csv
   * @return string
   * @access public
   */
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
  
  /**
   * An array of cell default formatting
   * @var array
   * @access protected
   */
  protected $current_format = array( 'bold' => NULL,
                                     'italic' => NULL,
                                     'size' => NULL,
                                     'foreground_color' => NULL,
                                     'background_color' => NULL,
                                     'horizontal_alignment' => NULL,
                                     'vertical_alignment' => NULL );

  /**
   * The PHPExcel object used to create excel files
   * @var PHPExcel object
   * @access protected
   */
  protected $php_excel = NULL;
}
?>
