<?php
/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2009 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel_Calculation
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license	http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version	1.7.0, 2009-08-10
 */


/** PHPExcel root directory */
if (!defined('PHPEXCEL_ROOT')) {
	/**
	 * @ignore
	 */
	define('PHPEXCEL_ROOT', dirname(__FILE__) . '/../');
}

/** Matrix */
require_once PHPEXCEL_ROOT . 'PHPExcel/Shared/JAMA/Matrix.php';

/** PHPExcel_Calculation_Function */
require_once PHPEXCEL_ROOT . 'PHPExcel/Calculation/Function.php';

/** PHPExcel_Calculation_Functions */
require_once PHPEXCEL_ROOT . 'PHPExcel/Calculation/Functions.php';


/**
 * PHPExcel_Calculation (Singleton)
 *
 * @category   PHPExcel
 * @package	PHPExcel_Calculation
 * @copyright  Copyright (c) 2006 - 2009 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Calculation {

	/**	Constants				*/
	/**	Regular Expressions		*/
	//	Numeric operand
	const CALCULATION_REGEXP_NUMBER		= '[-+]?\d*\.?\d+(e[-+]?\d+)?';
	//	String operand
	const CALCULATION_REGEXP_STRING		= '"(?:[^"]|"")*"';
	//	Opening bracket
	const CALCULATION_REGEXP_OPENBRACE	= '\(';
	//	Function
	const CALCULATION_REGEXP_FUNCTION	= '([A-Z][A-Z0-9\.]*)[\s]*\(';
	//	Cell reference (cell or range of cells, with or without a sheet reference)
	const CALCULATION_REGEXP_CELLREF	= '(((\w*)|(\'.*\')|(\".*\"))!)?\$?([a-z]+)\$?(\d+)(:\$?([a-z]+)\$?(\d+))?';
	//	Named Range of cells
	const CALCULATION_REGEXP_NAMEDRANGE	= '(((\w*)|(\'.*\')|(\".*\"))!)?([_A-Z][_A-Z0-9]*)';
	//	Error
	const CALCULATION_REGEXP_ERROR		= '\#[^!]+!';


	/** constants */
	const RETURN_ARRAY_AS_VALUE = 'value';
	const RETURN_ARRAY_AS_ARRAY = 'array';

	private static $returnArrayAsType	= self::RETURN_ARRAY_AS_ARRAY;

	/**
	 *	Instance of this class
	 *
	 *	@access	private
	 *	@var PHPExcel_Calculation
	 */
	private static $_instance;


	/**
	 *	Calculation cache
	 *
	 *	@access	private
	 *	@var array
	 */
	private $_calculationCache = array ();


	/**
	 *	Calculation cache enabled
	 *
	 *	@access	private
	 *	@var boolean
	 */
	private $_calculationCacheEnabled = true;


	/**
	 *	Calculation cache expiration time
	 *
	 *	@access	private
	 *	@var float
	 */
	private $_calculationCacheExpirationTime = 0.01;


	/**
	 *	List of operators that can be used within formulae
	 *
	 *	@access	private
	 *	@var array
	 */
	private $_operators			= array('+', '-', '*', '/', '^', '&', '%', '_', '>', '<', '=', '>=', '<=', '<>');


	/**
	 *	List of binary operators (those that expect two operands)
	 *
	 *	@access	private
	 *	@var array
	 */
	private $_binaryOperators	= array('+', '-', '*', '/', '^', '&', '>', '<', '=', '>=', '<=', '<>');

	public $suppressFormulaErrors = false;
	public $formulaError = null;
	public $writeDebugLog = false;
	private $debugLogStack = array();
	public $debugLog = array();


	//	Constant conversion from text name/value to actual (datatyped) value
	private $_ExcelConstants = array('TRUE'		=> True,
									 'FALSE'	=> False,
									 'NULL'		=> Null
									);

	//	PHPExcel functions
	private $_PHPExcelFunctions = array(	// PHPExcel functions
				'ABS'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'abs',
												 'argumentCount'	=>	'1'
												),
				'ACCRINT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::ACCRINT',
												 'argumentCount'	=>	'4-7'
												),
				'ACCRINTM'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::ACCRINTM',
												 'argumentCount'	=>	'3-5'
												),
				'ACOS'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'acos',
												 'argumentCount'	=>	'1'
												),
				'ACOSH'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'acosh',
												 'argumentCount'	=>	'1'
												),
				'ADDRESS'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CELL_ADDRESS',
												 'argumentCount'	=>	'2-5'
												),
				'AMORDEGRC'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'6,7'
												),
				'AMORLINC'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'6,7'
												),
				'AND'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOGICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LOGICAL_AND',
												 'argumentCount'	=>	'1+'
												),
				'AREAS'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1'
												),
				'ASC'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1'
												),
				'ASIN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'asin',
												 'argumentCount'	=>	'1'
												),
				'ASINH'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'asinh',
												 'argumentCount'	=>	'1'
												),
				'ATAN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'atan',
												 'argumentCount'	=>	'1'
												),
				'ATAN2'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::REVERSE_ATAN2',
												 'argumentCount'	=>	'2'
												),
				'ATANH'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'atanh',
												 'argumentCount'	=>	'1'
												),
				'AVEDEV'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::AVEDEV',
												 'argumentCount'	=>	'1+'
												),
				'AVERAGE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::AVERAGE',
												 'argumentCount'	=>	'1+'
												),
				'AVERAGEA'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::AVERAGEA',
												 'argumentCount'	=>	'1+'
												),
				'AVERAGEIF'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'2,3'
												),
				'AVERAGEIFS'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3+'
												),
				'BAHTTEXT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1'
												),
				'BESSELI'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::BESSELI',
												 'argumentCount'	=>	'2'
												),
				'BESSELJ'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::BESSELJ',
												 'argumentCount'	=>	'2'
												),
				'BESSELK'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::BESSELK',
												 'argumentCount'	=>	'2'
												),
				'BESSELY'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::BESSELY',
												 'argumentCount'	=>	'2'
												),
				'BETADIST'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::BETADIST',
												 'argumentCount'	=>	'3-5'
												),
				'BETAINV'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::BETAINV',
												 'argumentCount'	=>	'3-5'
												),
				'BIN2DEC'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::BINTODEC',
												 'argumentCount'	=>	'1'
												),
				'BIN2HEX'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::BINTOHEX',
												 'argumentCount'	=>	'1,2'
												),
				'BIN2OCT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::BINTOOCT',
												 'argumentCount'	=>	'1,2'
												),
				'BINOMDIST'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::BINOMDIST',
												 'argumentCount'	=>	'4'
												),
				'CEILING'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CEILING',
												 'argumentCount'	=>	'2'
												),
				'CELL'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1,2'
												),
				'CHAR'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CHARACTER',
												 'argumentCount'	=>	'1'
												),
				'CHIDIST'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CHIDIST',
												 'argumentCount'	=>	'2'
												),
				'CHIINV'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CHIINV',
												 'argumentCount'	=>	'2'
												),
				'CHITEST'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'2'
												),
				'CHOOSE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CHOOSE',
												 'argumentCount'	=>	'2+'
												),
				'CLEAN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TRIMNONPRINTABLE',
												 'argumentCount'	=>	'1'
												),
				'CODE'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::ASCIICODE',
												 'argumentCount'	=>	'1'
												),
				'COLUMN'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::COLUMN',
												 'argumentCount'	=>	'-1'
												),
				'COLUMNS'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1'
												),
				'COMBIN'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::COMBIN',
												 'argumentCount'	=>	'2'
												),
				'COMPLEX'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::COMPLEX',
												 'argumentCount'	=>	'2,3'
												),
				'CONCATENATE'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CONCATENATE',
												 'argumentCount'	=>	'1+'
												),
				'CONFIDENCE'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CONFIDENCE',
												 'argumentCount'	=>	'3'
												),
				'CONVERT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CONVERTUOM',
												 'argumentCount'	=>	'3'
												),
				'CORREL'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CORREL',
												 'argumentCount'	=>	'2'
												),
				'COS'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'cos',
												 'argumentCount'	=>	'1'
												),
				'COSH'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'cosh',
												 'argumentCount'	=>	'1'
												),
				'COUNT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::COUNT',
												 'argumentCount'	=>	'1+'
												),
				'COUNTA'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::COUNTA',
												 'argumentCount'	=>	'1+'
												),
				'COUNTBLANK'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::COUNTBLANK',
												 'argumentCount'	=>	'1'
												),
				'COUNTIF'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::COUNTIF',
												 'argumentCount'	=>	'2'
												),
				'COUNTIFS'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'2'
												),
				'COUPDAYBS'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3,4'
												),
				'COUPDAYS'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3,4'
												),
				'COUPDAYSNC'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3,4'
												),
				'COUPNCD'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3,4'
												),
				'COUPNUM'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3,4'
												),
				'COUPPCD'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3,4'
												),
				'COVAR'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::COVAR',
												 'argumentCount'	=>	'2'
												),
				'CRITBINOM'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CRITBINOM',
												 'argumentCount'	=>	'3'
												),
				'CUBEKPIMEMBER'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_CUBE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'CUBEMEMBER'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_CUBE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'CUBEMEMBERPROPERTY'	=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_CUBE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'CUBERANKEDMEMBER'		=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_CUBE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'CUBESET'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_CUBE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'CUBESETCOUNT'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_CUBE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'CUBEVALUE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_CUBE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'CUMIPMT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CUMIPMT',
												 'argumentCount'	=>	'6'
												),
				'CUMPRINC'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CUMPRINC',
												 'argumentCount'	=>	'6'
												),
				'DATE'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DATE',
												 'argumentCount'	=>	'3'
												),
				'DATEDIF'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DATEDIF',
												 'argumentCount'	=>	'3'
												),
				'DATEVALUE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DATEVALUE',
												 'argumentCount'	=>	'1'
												),
				'DAVERAGE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATABASE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'DAY'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DAYOFMONTH',
												 'argumentCount'	=>	'1'
												),
				'DAYS360'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DAYS360',
												 'argumentCount'	=>	'2,3'
												),
				'DB'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DB',
												 'argumentCount'	=>	'4,5'
												),
				'DCOUNT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATABASE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'DCOUNTA'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATABASE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'DDB'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DDB',
												 'argumentCount'	=>	'4,5'
												),
				'DEC2BIN'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DECTOBIN',
												 'argumentCount'	=>	'1,2'
												),
				'DEC2HEX'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DECTOHEX',
												 'argumentCount'	=>	'1,2'
												),
				'DEC2OCT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DECTOOCT',
												 'argumentCount'	=>	'1,2'
												),
				'DEGREES'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'rad2deg',
												 'argumentCount'	=>	'1'
												),
				'DELTA'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DELTA',
												 'argumentCount'	=>	'1,2'
												),
				'DEVSQ'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DEVSQ',
												 'argumentCount'	=>	'1+'
												),
				'DGET'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATABASE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'DISC'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DISC',
												 'argumentCount'	=>	'4,5'
												),
				'DMAX'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATABASE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'DMIN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATABASE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'DOLLAR'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DOLLAR',
												 'argumentCount'	=>	'1,2'
												),
				'DOLLARDE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DOLLARDE',
												 'argumentCount'	=>	'2'
												),
				'DOLLARFR'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DOLLARFR',
												 'argumentCount'	=>	'2'
												),
				'DPRODUCT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATABASE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'DSTDEV'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATABASE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'DSTDEVP'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATABASE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'DSUM'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATABASE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'DURATION'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'5,6'
												),
				'DVAR'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATABASE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'DVARP'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATABASE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'EDATE'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::EDATE',
												 'argumentCount'	=>	'2'
												),
				'EFFECT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::EFFECT',
												 'argumentCount'	=>	'2'
												),
				'EOMONTH'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::EOMONTH',
												 'argumentCount'	=>	'2'
												),
				'ERF'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::ERF',
												 'argumentCount'	=>	'1,2'
												),
				'ERFC'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::ERFC',
												 'argumentCount'	=>	'1'
												),
				'ERROR.TYPE'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::ERROR_TYPE',
												 'argumentCount'	=>	'1'
												),
				'EVEN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::EVEN',
												 'argumentCount'	=>	'1'
												),
				'EXACT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'2'
												),
				'EXP'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'exp',
												 'argumentCount'	=>	'1'
												),
				'EXPONDIST'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::EXPONDIST',
												 'argumentCount'	=>	'3'
												),
				'FACT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::FACT',
												 'argumentCount'	=>	'1'
												),
				'FACTDOUBLE'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::FACTDOUBLE',
												 'argumentCount'	=>	'1'
												),
				'FALSE'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOGICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LOGICAL_FALSE',
												 'argumentCount'	=>	'0'
												),
				'FDIST'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3'
												),
				'FIND'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SEARCHSENSITIVE',
												 'argumentCount'	=>	'2,3'
												),
				'FINDB'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SEARCHSENSITIVE',
												 'argumentCount'	=>	'2,3'
												),
				'FINV'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3'
												),
				'FISHER'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::FISHER',
												 'argumentCount'	=>	'1'
												),
				'FISHERINV'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::FISHERINV',
												 'argumentCount'	=>	'1'
												),
				'FIXED'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::FIXEDFORMAT',
												 'argumentCount'	=>	'1-3'
												),
				'FLOOR'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::FLOOR',
												 'argumentCount'	=>	'2'
												),
				'FORECAST'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::FORECAST',
												 'argumentCount'	=>	'3'
												),
				'FREQUENCY'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'2'
												),
				'FTEST'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'2'
												),
				'FV'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::FV',
												 'argumentCount'	=>	'3-5'
												),
				'FVSCHEDULE'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'2'
												),
				'GAMMADIST'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::GAMMADIST',
												 'argumentCount'	=>	'4'
												),
				'GAMMAINV'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::GAMMAINV',
												 'argumentCount'	=>	'3'
												),
				'GAMMALN'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::GAMMALN',
												 'argumentCount'	=>	'1'
												),
				'GCD'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::GCD',
												 'argumentCount'	=>	'1+'
												),
				'GEOMEAN'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::GEOMEAN',
												 'argumentCount'	=>	'1+'
												),
				'GESTEP'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::GESTEP',
												 'argumentCount'	=>	'1,2'
												),
				'GETPIVOTDATA'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'2+'
												),
				'GROWTH'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::GROWTH',
												 'argumentCount'	=>	'1-4'
												),
				'HARMEAN'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::HARMEAN',
												 'argumentCount'	=>	'1+'
												),
				'HEX2BIN'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::HEXTOBIN',
												 'argumentCount'	=>	'1,2'
												),
				'HEX2DEC'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::HEXTODEC',
												 'argumentCount'	=>	'1'
												),
				'HEX2OCT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::HEXTOOCT',
												 'argumentCount'	=>	'1,2'
												),
				'HLOOKUP'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3,4'
												),
				'HOUR'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::HOUROFDAY',
												 'argumentCount'	=>	'1'
												),
				'HYPERLINK'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1,2'
												),
				'HYPGEOMDIST'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::HYPGEOMDIST',
												 'argumentCount'	=>	'4'
												),
				'IF'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOGICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::STATEMENT_IF',
												 'argumentCount'	=>	'1-3'
												),
				'IFERROR'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOGICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::STATEMENT_IFERROR',
												 'argumentCount'	=>	'1'
												),
				'IMABS'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMABS',
												 'argumentCount'	=>	'1'
												),
				'IMAGINARY'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMAGINARY',
												 'argumentCount'	=>	'1'
												),
				'IMARGUMENT'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMARGUMENT',
												 'argumentCount'	=>	'1'
												),
				'IMCONJUGATE'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMCONJUGATE',
												 'argumentCount'	=>	'1'
												),
				'IMCOS'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMCOS',
												 'argumentCount'	=>	'1'
												),
				'IMDIV'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMDIV',
												 'argumentCount'	=>	'2'
												),
				'IMEXP'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMEXP',
												 'argumentCount'	=>	'1'
												),
				'IMLN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMLN',
												 'argumentCount'	=>	'1'
												),
				'IMLOG10'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMLOG10',
												 'argumentCount'	=>	'1'
												),
				'IMLOG2'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMLOG2',
												 'argumentCount'	=>	'1'
												),
				'IMPOWER'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMPOWER',
												 'argumentCount'	=>	'2'
												),
				'IMPRODUCT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMPRODUCT',
												 'argumentCount'	=>	'1+'
												),
				'IMREAL'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMREAL',
												 'argumentCount'	=>	'1'
												),
				'IMSIN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMSIN',
												 'argumentCount'	=>	'1'
												),
				'IMSQRT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMSQRT',
												 'argumentCount'	=>	'1'
												),
				'IMSUB'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMSUB',
												 'argumentCount'	=>	'2'
												),
				'IMSUM'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IMSUM',
												 'argumentCount'	=>	'1+'
												),
				'INDEX'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::INDEX',
												 'argumentCount'	=>	'1-4'
												),
				'INDIRECT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1,2'
												),
				'INFO'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1'
												),
				'INT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::INTVALUE',
												 'argumentCount'	=>	'1'
												),
				'INTERCEPT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::INTERCEPT',
												 'argumentCount'	=>	'2'
												),
				'INTRATE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::INTRATE',
												 'argumentCount'	=>	'4,5'
												),
				'IPMT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IPMT',
												 'argumentCount'	=>	'4-6'
												),
				'IRR'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1,2'
												),
				'ISBLANK'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IS_BLANK',
												 'argumentCount'	=>	'1'
												),
				'ISERR'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IS_ERR',
												 'argumentCount'	=>	'1'
												),
				'ISERROR'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IS_ERROR',
												 'argumentCount'	=>	'1'
												),
				'ISEVEN'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IS_EVEN',
												 'argumentCount'	=>	'1'
												),
				'ISLOGICAL'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IS_LOGICAL',
												 'argumentCount'	=>	'1'
												),
				'ISNA'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IS_NA',
												 'argumentCount'	=>	'1'
												),
				'ISNONTEXT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IS_NONTEXT',
												 'argumentCount'	=>	'1'
												),
				'ISNUMBER'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IS_NUMBER',
												 'argumentCount'	=>	'1'
												),
				'ISODD'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IS_ODD',
												 'argumentCount'	=>	'1'
												),
				'ISPMT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'4'
												),
				'ISREF'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1'
												),
				'ISTEXT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::IS_TEXT',
												 'argumentCount'	=>	'1'
												),
				'JIS'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1'
												),
				'KURT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::KURT',
												 'argumentCount'	=>	'1+'
												),
				'LARGE'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LARGE',
												 'argumentCount'	=>	'2'
												),
				'LCM'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LCM',
												 'argumentCount'	=>	'1+'
												),
				'LEFT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LEFT',
												 'argumentCount'	=>	'1,2'
												),
				'LEFTB'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LEFT',
												 'argumentCount'	=>	'1,2'
												),
				'LEN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::STRINGLENGTH',
												 'argumentCount'	=>	'1'
												),
				'LENB'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::STRINGLENGTH',
												 'argumentCount'	=>	'1'
												),
				'LINEST'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LINEST',
												 'argumentCount'	=>	'1-4'
												),
				'LN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'log',
												 'argumentCount'	=>	'1'
												),
				'LOG'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LOG_BASE',
												 'argumentCount'	=>	'1,2'
												),
				'LOG10'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'log10',
												 'argumentCount'	=>	'1'
												),
				'LOGEST'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LOGEST',
												 'argumentCount'	=>	'1-4'
												),
				'LOGINV'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LOGINV',
												 'argumentCount'	=>	'3'
												),
				'LOGNORMDIST'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LOGNORMDIST',
												 'argumentCount'	=>	'3'
												),
				'LOOKUP'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LOOKUP',
												 'argumentCount'	=>	'2,3'
												),
				'LOWER'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LOWERCASE',
												 'argumentCount'	=>	'1'
												),
				'MATCH'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MATCH',
												 'argumentCount'	=>	'2,3'
												),
				'MAX'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MAX',
												 'argumentCount'	=>	'1+'
												),
				'MAXA'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MAXA',
												 'argumentCount'	=>	'1+'
												),
				'MDETERM'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MDETERM',
												 'argumentCount'	=>	'1'
												),
				'MDURATION'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'5,6'
												),
				'MEDIAN'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MEDIAN',
												 'argumentCount'	=>	'1+'
												),
				'MID'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MID',
												 'argumentCount'	=>	'3'
												),
				'MIDB'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MID',
												 'argumentCount'	=>	'3'
												),
				'MIN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MIN',
												 'argumentCount'	=>	'1+'
												),
				'MINA'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MINA',
												 'argumentCount'	=>	'1+'
												),
				'MINUTE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MINUTEOFHOUR',
												 'argumentCount'	=>	'1'
												),
				'MINVERSE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MINVERSE',
												 'argumentCount'	=>	'1'
												),
				'MIRR'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3'
												),
				'MMULT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MMULT',
												 'argumentCount'	=>	'2'
												),
				'MOD'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MOD',
												 'argumentCount'	=>	'2'
												),
				'MODE'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MODE',
												 'argumentCount'	=>	'1+'
												),
				'MONTH'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MONTHOFYEAR',
												 'argumentCount'	=>	'1'
												),
				'MROUND'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MROUND',
												 'argumentCount'	=>	'2'
												),
				'MULTINOMIAL'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::MULTINOMIAL',
												 'argumentCount'	=>	'1+'
												),
				'N'						=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1'
												),
				'NA'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::NA',
												 'argumentCount'	=>	'0'
												),
				'NEGBINOMDIST'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::NEGBINOMDIST',
												 'argumentCount'	=>	'3'
												),
				'NETWORKDAYS'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::NETWORKDAYS',
												 'argumentCount'	=>	'2+'
												),
				'NOMINAL'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::NOMINAL',
												 'argumentCount'	=>	'2'
												),
				'NORMDIST'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::NORMDIST',
												 'argumentCount'	=>	'4'
												),
				'NORMINV'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::NORMINV',
												 'argumentCount'	=>	'3'
												),
				'NORMSDIST'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::NORMSDIST',
												 'argumentCount'	=>	'1'
												),
				'NORMSINV'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::NORMSINV',
												 'argumentCount'	=>	'1'
												),
				'NOT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOGICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LOGICAL_NOT',
												 'argumentCount'	=>	'1'
												),
				'NOW'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DATETIMENOW',
												 'argumentCount'	=>	'0'
												),
				'NPER'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::NPER',
												 'argumentCount'	=>	'3-5'
												),
				'NPV'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::NPV',
												 'argumentCount'	=>	'2+'
												),
				'OCT2BIN'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::OCTTOBIN',
												 'argumentCount'	=>	'1,2'
												),
				'OCT2DEC'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::OCTTODEC',
												 'argumentCount'	=>	'1'
												),
				'OCT2HEX'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_ENGINEERING,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::OCTTOHEX',
												 'argumentCount'	=>	'1,2'
												),
				'ODD'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::ODD',
												 'argumentCount'	=>	'1'
												),
				'ODDFPRICE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'8,9'
												),
				'ODDFYIELD'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'8,9'
												),
				'ODDLPRICE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'7,8'
												),
				'ODDLYIELD'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'7,8'
												),
				'OFFSET'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::OFFSET',
												 'argumentCount'	=>	'3,5'
												),
				'OR'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOGICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LOGICAL_OR',
												 'argumentCount'	=>	'1+'
												),
				'PEARSON'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::CORREL',
												 'argumentCount'	=>	'2'
												),
				'PERCENTILE'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::PERCENTILE',
												 'argumentCount'	=>	'2'
												),
				'PERCENTRANK'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::PERCENTRANK',
												 'argumentCount'	=>	'2,3'
												),
				'PERMUT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::PERMUT',
												 'argumentCount'	=>	'2'
												),
				'PHONETIC'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1'
												),
				'PI'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'pi',
												 'argumentCount'	=>	'0'
												),
				'PMT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::PMT',
												 'argumentCount'	=>	'3-5'
												),
				'POISSON'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::POISSON',
												 'argumentCount'	=>	'3'
												),
				'POWER'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::POWER',
												 'argumentCount'	=>	'2'
												),
				'PPMT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::PPMT',
												 'argumentCount'	=>	'4-6'
												),
				'PRICE'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'6,7'
												),
				'PRICEDISC'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::PRICEDISC',
												 'argumentCount'	=>	'4,5'
												),
				'PRICEMAT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::PRICEMAT',
												 'argumentCount'	=>	'5,6'
												),
				'PROB'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3,4'
												),
				'PRODUCT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::PRODUCT',
												 'argumentCount'	=>	'1+'
												),
				'PROPER'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::PROPERCASE',
												 'argumentCount'	=>	'1'
												),
				'PV'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::PV',
												 'argumentCount'	=>	'3-5'
												),
				'QUARTILE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::QUARTILE',
												 'argumentCount'	=>	'2'
												),
				'QUOTIENT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::QUOTIENT',
												 'argumentCount'	=>	'2'
												),
				'RADIANS'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'deg2rad',
												 'argumentCount'	=>	'1'
												),
				'RAND'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::RAND',
												 'argumentCount'	=>	'0'
												),
				'RANDBETWEEN'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::RAND',
												 'argumentCount'	=>	'2'
												),
				'RANK'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::RANK',
												 'argumentCount'	=>	'2,3'
												),
				'RATE'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3-6'
												),
				'RECEIVED'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::RECEIVED',
												 'argumentCount'	=>	'4-5'
												),
				'REPLACE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::REPLACE',
												 'argumentCount'	=>	'4'
												),
				'REPLACEB'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::REPLACE',
												 'argumentCount'	=>	'4'
												),
				'REPT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'str_repeat',
												 'argumentCount'	=>	'2'
												),
				'RIGHT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::RIGHT',
												 'argumentCount'	=>	'1,2'
												),
				'RIGHTB'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::RIGHT',
												 'argumentCount'	=>	'1,2'
												),
				'ROMAN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::ROMAN',
												 'argumentCount'	=>	'1,2'
												),
				'ROUND'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'round',
												 'argumentCount'	=>	'2'
												),
				'ROUNDDOWN'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::ROUNDDOWN',
												 'argumentCount'	=>	'2'
												),
				'ROUNDUP'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::ROUNDUP',
												 'argumentCount'	=>	'2'
												),
				'ROW'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::ROW',
												 'argumentCount'	=>	'-1'
												),
				'ROWS'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1'
												),
				'RSQ'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::RSQ',
												 'argumentCount'	=>	'2'
												),
				'RTD'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1+'
												),
				'SEARCH'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SEARCHINSENSITIVE',
												 'argumentCount'	=>	'2,3'
												),
				'SEARCHB'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SEARCHINSENSITIVE',
												 'argumentCount'	=>	'2,3'
												),
				'SECOND'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SECONDOFMINUTE',
												 'argumentCount'	=>	'1'
												),
				'SERIESSUM'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SERIESSUM',
												 'argumentCount'	=>	'4'
												),
				'SIGN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SIGN',
												 'argumentCount'	=>	'1'
												),
				'SIN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'sin',
												 'argumentCount'	=>	'1'
												),
				'SINH'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'sinh',
												 'argumentCount'	=>	'1'
												),
				'SKEW'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SKEW',
												 'argumentCount'	=>	'1+'
												),
				'SLN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SLN',
												 'argumentCount'	=>	'3'
												),
				'SLOPE'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SLOPE',
												 'argumentCount'	=>	'2'
												),
				'SMALL'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SMALL',
												 'argumentCount'	=>	'2'
												),
				'SQRT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'sqrt',
												 'argumentCount'	=>	'1'
												),
				'SQRTPI'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SQRTPI',
												 'argumentCount'	=>	'1'
												),
				'STANDARDIZE'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::STANDARDIZE',
												 'argumentCount'	=>	'3'
												),
				'STDEV'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::STDEV',
												 'argumentCount'	=>	'1+'
												),
				'STDEVA'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::STDEVA',
												 'argumentCount'	=>	'1+'
												),
				'STDEVP'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::STDEVP',
												 'argumentCount'	=>	'1+'
												),
				'STDEVPA'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::STDEVPA',
												 'argumentCount'	=>	'1+'
												),
				'STEYX'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::STEYX',
												 'argumentCount'	=>	'2'
												),
				'SUBSTITUTE'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3,4'
												),
				'SUBTOTAL'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SUBTOTAL',
												 'argumentCount'	=>	'2+'
												),
				'SUM'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SUM',
												 'argumentCount'	=>	'1+'
												),
				'SUMIF'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SUMIF',
												 'argumentCount'	=>	'2,3'
												),
				'SUMIFS'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												),
				'SUMPRODUCT'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1+'
												),
				'SUMSQ'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SUMSQ',
												 'argumentCount'	=>	'1+'
												),
				'SUMX2MY2'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SUMX2MY2',
												 'argumentCount'	=>	'2'
												),
				'SUMX2PY2'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SUMX2PY2',
												 'argumentCount'	=>	'2'
												),
				'SUMXMY2'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SUMXMY2',
												 'argumentCount'	=>	'2'
												),
				'SYD'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::SYD',
												 'argumentCount'	=>	'4'
												),
				'T'						=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::RETURNSTRING',
												 'argumentCount'	=>	'1'
												),
				'TAN'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'tan',
												 'argumentCount'	=>	'1'
												),
				'TANH'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'tanh',
												 'argumentCount'	=>	'1'
												),
				'TBILLEQ'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TBILLEQ',
												 'argumentCount'	=>	'3'
												),
				'TBILLPRICE'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TBILLPRICE',
												 'argumentCount'	=>	'3'
												),
				'TBILLYIELD'			=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TBILLYIELD',
												 'argumentCount'	=>	'3'
												),
				'TDIST'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TDIST',
												 'argumentCount'	=>	'3'
												),
				'TEXT'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TEXTFORMAT',
												 'argumentCount'	=>	'2'
												),
				'TIME'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TIME',
												 'argumentCount'	=>	'3'
												),
				'TIMEVALUE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TIMEVALUE',
												 'argumentCount'	=>	'1'
												),
				'TINV'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TINV',
												 'argumentCount'	=>	'2'
												),
				'TODAY'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DATENOW',
												 'argumentCount'	=>	'0'
												),
				'TRANSPOSE'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TRANSPOSE',
												 'argumentCount'	=>	'1'
												),
				'TREND'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TREND',
												 'argumentCount'	=>	'1-4'
												),
				'TRIM'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TRIMSPACES',
												 'argumentCount'	=>	'1'
												),
				'TRIMMEAN'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TRIMMEAN',
												 'argumentCount'	=>	'2'
												),
				'TRUE'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOGICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::LOGICAL_TRUE',
												 'argumentCount'	=>	'0'
												),
				'TRUNC'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_MATH_AND_TRIG,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::TRUNC',
												 'argumentCount'	=>	'1,2'
												),
				'TTEST'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'4'
												),
				'TYPE'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1'
												),
				'UPPER'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::UPPERCASE',
												 'argumentCount'	=>	'1'
												),
				'USDOLLAR'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'2'
												),
				'VALUE'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_TEXT_AND_DATA,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'1'
												),
				'VAR'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::VARFunc',
												 'argumentCount'	=>	'1+'
												),
				'VARA'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::VARA',
												 'argumentCount'	=>	'1+'
												),
				'VARP'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::VARP',
												 'argumentCount'	=>	'1+'
												),
				'VARPA'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::VARPA',
												 'argumentCount'	=>	'1+'
												),
				'VDB'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'5-7'
												),
				'VERSION'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_INFORMATION,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::VERSION',
												 'argumentCount'	=>	'0'
												),
				'VLOOKUP'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_LOOKUP_AND_REFERENCE,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::VLOOKUP',
												 'argumentCount'	=>	'3,4'
												),
				'WEEKDAY'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DAYOFWEEK',
												 'argumentCount'	=>	'1,2'
												),
				'WEEKNUM'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::WEEKOFYEAR',
												 'argumentCount'	=>	'1,2'
												),
				'WEIBULL'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::WEIBULL',
												 'argumentCount'	=>	'4'
												),
				'WORKDAY'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::WORKDAY',
												 'argumentCount'	=>	'2+'
												),
				'XIRR'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'2,3'
												),
				'XNPV'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'3'
												),
				'YEAR'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::YEAR',
												 'argumentCount'	=>	'1'
												),
				'YEARFRAC'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_DATE_AND_TIME,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::YEARFRAC',
												 'argumentCount'	=>	'2,3'
												),
				'YIELD'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'6,7'
												),
				'YIELDDISC'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::YIELDDISC',
												 'argumentCount'	=>	'4,5'
												),
				'YIELDMAT'				=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_FINANCIAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::YIELDMAT',
												 'argumentCount'	=>	'5,6'
												),
				'ZTEST'					=> array('category'			=>	PHPExcel_Calculation_Function::CATEGORY_STATISTICAL,
												 'functionCall'		=>	'PHPExcel_Calculation_Functions::DUMMY',
												 'argumentCount'	=>	'?'
												)
			);


	//	Internal functions used for special control purposes
	private $_controlFunctions = array(
				'MKMATRIX'	=> array('argumentCount'	=>	'*',
									 'functionCall'		=>	array('self','_mkMatrix')
									)
			);




	/**
	 *	Get an instance of this class
	 *
	 *	@access	public
	 *	@return PHPExcel_Calculation
	 */
	public static function getInstance() {
		if (!isset(self::$_instance) || is_null(self::$_instance)) {
			self::$_instance = new PHPExcel_Calculation();
		}

		return self::$_instance;
	}	//	function getInstance()


	/**
	 *	__clone implementation. Cloning should not be allowed in a Singleton!
	 *
	 *	@access	public
	 *	@throws	Exception
	 */
	public final function __clone() {
		throw new Exception ( 'Cloning a Singleton is not allowed!' );
	}	//	function __clone()


	/**
	 *	Set the Array Return Type (Array or Value of first element in the array)
	 *
	 *	@access	public
	 *	@param	 string	$returnType			Array return type
	 *	@return	 boolean					Success or failure
	 */
	public static function setArrayReturnType($returnType) {
		if (($returnType == self::RETURN_ARRAY_AS_VALUE) ||
			($returnType == self::RETURN_ARRAY_AS_ARRAY)) {
			self::$returnArrayAsType = $returnType;
			return True;
		}
		return False;
	}	//	function setExcelCalendar()


	/**
	 *	Return the Array Return Type (Array or Value of first element in the array)
	 *
	 *	@access	public
	 *	@return	 string		$returnType			Array return type
	 */
	public static function getArrayReturnType() {
		return self::$returnArrayAsType;
	}	//	function getExcelCalendar()


	/**
	 *	Is calculation caching enabled?
	 *
	 *	@access	public
	 *	@return boolean
	 */
	public function getCalculationCacheEnabled() {
		return $this->_calculationCacheEnabled;
	}	//	function getCalculationCacheEnabled()


	/**
	 *	Enable/disable calculation cache
	 *
	 *	@access	public
	 *	@param boolean $pValue
	 */
	public function setCalculationCacheEnabled($pValue = true) {
		$this->_calculationCacheEnabled = $pValue;
		$this->clearCalculationCache();
	}	//	function setCalculationCacheEnabled()


	/**
	 *	Enable calculation cache
	 */
	public function enableCalculationCache() {
		$this->setCalculationCacheEnabled(true);
	}	//	function enableCalculationCache()


	/**
	 *	Disable calculation cache
	 */
	public function disableCalculationCache() {
		$this->setCalculationCacheEnabled(false);
	}	//	function disableCalculationCache()


	/**
	 *	Clear calculation cache
	 */
	public function clearCalculationCache() {
		$this->_calculationCache = array();
	}	//	function clearCalculationCache()


	/**
	 *	Get calculation cache expiration time
	 *
	 *	@return float
	 */
	public function getCalculationCacheExpirationTime() {
		return $this->_calculationCacheExpirationTime;
	}	//	getCalculationCacheExpirationTime()


	/**
	 *	Set calculation cache expiration time
	 *
	 *	@param float $pValue
	 */
	public function setCalculationCacheExpirationTime($pValue = 0.01) {
		$this->_calculationCacheExpirationTime = $pValue;
	}	//	function setCalculationCacheExpirationTime()




	/**
	 *	Wrap string values in quotes
	 *
	 *	@param mixed $value
	 *	@return mixed
	 */
	public static function _wrapResult($value) {
		if (is_string($value)) {
			//	Error values cannot be "wrapped"
			if (preg_match('/^'.self::CALCULATION_REGEXP_ERROR.'$/i', $value, $match)) {
				//	Return Excel errors "as is"
				return $value;
			}
			//	Return strings wrapped in quotes
			return '"'.$value.'"';
		//	Convert numeric errors to NaN error
		} else if((is_float($value)) && ((is_nan($value)) || (is_infinite($value)))) {
			return PHPExcel_Calculation_Functions::NaN();
		}

		return $value;
	}	//	function _wrapResult()


	/**
	 *	Remove quotes used as a wrapper to identify string values
	 *
	 *	@param mixed $value
	 *	@return mixed
	 */
	public static function _unwrapResult($value) {
		if (is_string($value)) {
			if ((strlen($value) > 0) && ($value{0} == '"') && (substr($value,-1) == '"')) {
				return substr($value,1,-1);
			}
		//	Convert numeric errors to NaN error
		} else if((is_float($value)) && ((is_nan($value)) || (is_infinite($value)))) {
			return PHPExcel_Calculation_Functions::NaN();
		}
		return $value;
	}	//	function _unwrapResult()




	/**
	 *	Calculate cell value (using formula from a cell ID)
	 *	Retained for backward compatibility
	 *
	 *	@access	public
	 *	@param	PHPExcel_Cell	$pCell	Cell to calculate
	 *	@return	mixed
	 *	@throws	Exception
	 */
	public function calculate(PHPExcel_Cell $pCell = null) {
		return $this->calculateCellValue($pCell);
	}	//	function calculate()


	/**
	 *	Calculate the value of a cell formula
	 *
	 *	@access	public
	 *	@param	PHPExcel_Cell	$pCell		Cell to calculate
	 *	@param	Boolean			$resetLog	Flag indicating whether the debug log should be reset or not
	 *	@return	mixed
	 *	@throws	Exception
	 */
	public function calculateCellValue(PHPExcel_Cell $pCell = null, $resetLog = true) {
		if ($resetLog) {
			//	Initialise the logging settings if requested
			$this->formulaError = null;
			$this->debugLog = array();
			$this->debugLogStack = array();
		}

		//	Read the formula from the cell
		if (is_null($pCell)) {
			return null;
		}
		$formula = $pCell->getValue();
		$cellID = $pCell->getCoordinate();

		//	Execute the calculation for the cell formula
		return self::_unwrapResult($this->_calculateFormulaValue($formula, $cellID, $pCell));
	}	//	function calculateCellValue(


	/**
	 *	Validate and parse a formula string
	 *
	 *	@param	string		$formula		Formula to parse
	 *	@return	array
	 *	@throws	Exception
	 */
	public function parseFormula($formula) {
		//	Basic validation that this is indeed a formula
		//	We return an empty array if not
		$formula = trim($formula);
		if ($formula{0} != '=') return array();
		$formula = trim(substr($formula,1));
		$formulaLength = strlen($formula);
		if ($formulaLength < 1) return array();

		//	Parse the formula and return the token stack
		return $this->_parseFormula($formula);
	}	//	function parseFormula()


	/**
	 *	Calculate the value of a formula
	 *
	 *	@param	string		$formula		Formula to parse
	 *	@return	mixed
	 *	@throws	Exception
	 */
	public function calculateFormula($formula, $cellID=null, PHPExcel_Cell $pCell = null) {
		//	Initialise the logging settings
		$this->formulaError = null;
		$this->debugLog = array();
		$this->debugLogStack = array();

		//	Disable calculation cacheing because it only applies to cell calculations, not straight formulae
		//	But don't actually flush any cache
		$resetCache = $this->getCalculationCacheEnabled();
		$this->_calculationCacheEnabled = false;
		//	Execute the calculation
		$result = self::_unwrapResult($this->_calculateFormulaValue($formula, $cellID, $pCell));
		//	Reset calculation cacheing to its previous state
		$this->_calculationCacheEnabled = $resetCache;

		return $result;
	}	//	function calculateFormula()


	/**
	 *	Parse a cell formula and calculate its value
	 *
	 *	@param	string			$formula	The formula to parse and calculate
	 *	@param	string			$cellID		The ID (e.g. A3) of the cell that we are calculating
	 *	@param	PHPExcel_Cell	$pCell		Cell to calculate
	 *	@return	mixed
	 *	@throws	Exception
	 */
	public function _calculateFormulaValue($formula, $cellID=null, PHPExcel_Cell $pCell = null) {
//		echo '<b>'.$cellID.'</b><br />';
		$cellValue = '';

		//	Basic validation that this is indeed a formula
		//	We simply return the "cell value" (formula) if not
		$formula = trim($formula);
		if ($formula{0} != '=') return self::_wrapResult($formula);
		$formula = trim(substr($formula,1));
		$formulaLength = strlen($formula);
		if ($formulaLength < 1) return self::_wrapResult($formula);

		// Is calculation cacheing enabled?
		if (!is_null($cellID)) {
			if ($this->_calculationCacheEnabled) {
				// Is the value present in calculation cache?
//				echo 'Testing cache value<br />';
				if (isset($this->_calculationCache[$pCell->getParent()->getTitle()][$pCell->getCoordinate()])) {
//					echo 'Value is in cache<br />';
					$this->_writeDebug($cellID,'Testing cache value');
					//	Is cache still valid?
					if ((time() + microtime()) - $this->_calculationCache[$pCell->getParent()->getTitle()][$pCell->getCoordinate()]['time'] < $this->_calculationCacheExpirationTime) {
//						echo 'Cache time is still valid<br />';
						$this->_writeDebug($cellID,'Retrieving value from cache');
						// Return the cached result
						$returnValue = $this->_calculationCache[$pCell->getParent()->getTitle()][$pCell->getCoordinate()]['data'];
//						echo 'Retrieving data value of '.$returnValue.' for '.$pCell->getCoordinate().' from cache<br />';
						if (is_array($returnValue)) {
							return array_shift(PHPExcel_Calculation_Functions::flattenArray($returnValue));
						}
						return $returnValue;
					} else {
//						echo 'Cache has expired<br />';
						$this->_writeDebug($cellID,'Cache value has expired');
						//	Clear the cache if it's no longer valid
						unset($this->_calculationCache[$pCell->getParent()->getTitle()][$pCell->getCoordinate()]);
					}
				}
			}
		}

		$this->debugLogStack[] = $cellID;
		//	Parse the formula onto the token stack and calculate the value
		$cellValue = $this->_processTokenStack($this->_parseFormula($formula), $cellID, $pCell);
		array_pop($this->debugLogStack);

		// Save to calculation cache
		if (!is_null($cellID)) {
			if ($this->_calculationCacheEnabled) {
				$this->_calculationCache[$pCell->getParent()->getTitle()][$pCell->getCoordinate()]['time'] = (time() + microtime());
				$this->_calculationCache[$pCell->getParent()->getTitle()][$pCell->getCoordinate()]['data'] = $cellValue;
			}
		}

		//	Return the calculated value
		if (is_array($cellValue)) {
			$cellValue = array_shift(PHPExcel_Calculation_Functions::flattenArray($cellValue));
		}

		return $cellValue;
	}	//	function _calculateFormulaValue()


	/**
	 *	Ensure that paired matrix operands are both matrices and of the same size
	 *
	 *	@param	mixed		$operand1	First matrix operand
	 *	@param	mixed		$operand2	Second matrix operand
	 */
	private static function _checkMatrixOperands(&$operand1,&$operand2,$resize = 1) {
		//	Examine each of the two operands, and turn them into an array if they aren't one already
		//	Note that this function should only be called if one or both of the operand is already an array
		if (!is_array($operand1)) {
			list($matrixRows,$matrixColumns) = self::_getMatrixDimensions($operand2);
			$operand1 = array_fill(0,$matrixRows,array_fill(0,$matrixColumns,$operand1));
			$resize = 0;
		} elseif (!is_array($operand2)) {
			list($matrixRows,$matrixColumns) = self::_getMatrixDimensions($operand1);
			$operand2 = array_fill(0,$matrixRows,array_fill(0,$matrixColumns,$operand2));
			$resize = 0;
		}

		//	Given two matrices of (potentially) unequal size, convert the smaller in each dimension to match the larger
		if ($resize == 2) {
			self::_resizeMatricesExtend($operand1,$operand2);
		} elseif ($resize == 1) {
			self::_resizeMatricesShrink($operand1,$operand2);
		}
	}	//	function _checkMatrixOperands()


	/**
	 *	Re-index a matrix with straight numeric keys starting from row 0, column 0
	 *
	 *	@param	mixed		$matrix		matrix operand
	 *	@return	array		The re-indexed matrix
	 */
	private static function _reindexMatrixDimensions($matrix) {
		foreach($matrix as $rowKey => $rowValue) {
			$matrix[$rowKey] = array_values($rowValue);
		}
		return array_values($matrix);
	}	//	function _getMatrixDimensions()


	/**
	 *	Read the dimensions of a matrix, and re-index it with straight numeric keys starting from row 0, column 0
	 *
	 *	@param	mixed		$matrix		matrix operand
	 *	@return	array		An array comprising the number of rows, and number of columns
	 */
	private static function _getMatrixDimensions(&$matrix) {
		$matrixRows = count($matrix);
		$matrixColumns = 0;
		foreach($matrix as $rowKey => $rowValue) {
			$colCount = count($rowValue);
			if ($colCount > $matrixColumns) {
				$matrixColumns = $colCount;
			}
			$matrix[$rowKey] = array_values($rowValue);
		}
		$matrix = array_values($matrix);
		return array($matrixRows,$matrixColumns);
	}	//	function _getMatrixDimensions()


	/**
	 *	Ensure that paired matrix operands are both matrices of the same size
	 *
	 *	@param	mixed		$matrix1	First matrix operand
	 *	@param	mixed		$matrix2	Second matrix operand
	 */
	private static function _resizeMatricesShrink(&$matrix1,&$matrix2) {
		list($matrix1Rows,$matrix1Columns) = self::_getMatrixDimensions($matrix1);
		list($matrix2Rows,$matrix2Columns) = self::_getMatrixDimensions($matrix2);

		if (($matrix2Columns < $matrix1Columns) || ($matrix2Rows < $matrix1Rows)) {
			if ($matrix2Columns < $matrix1Columns) {
				for ($i = 0; $i < $matrix1Rows; ++$i) {
					for ($j = $matrix2Columns; $j < $matrix1Columns; ++$j) {
						unset($matrix1[$i][$j]);
					}
				}
			}
			if ($matrix2Rows < $matrix1Rows) {
				for ($i = $matrix2Rows; $i < $matrix1Rows; ++$i) {
					unset($matrix1[$i]);
				}
			}
		}

		if (($matrix1Columns < $matrix2Columns) || ($matrix1Rows < $matrix2Rows)) {
			if ($matrix1Columns < $matrix2Columns) {
				for ($i = 0; $i < $matrix2Rows; ++$i) {
					for ($j = $matrix1Columns; $j < $matrix2Columns; ++$j) {
						unset($matrix2[$i][$j]);
					}
				}
			}
			if ($matrix1Rows < $matrix2Rows) {
				for ($i = $matrix1Rows; $i < $matrix2Rows; ++$i) {
					unset($matrix2[$i]);
				}
			}
		}
	}	//	function _resizeMatricesShrink()


	/**
	 *	Ensure that paired matrix operands are both matrices of the same size
	 *
	 *	@param	mixed		$matrix1	First matrix operand
	 *	@param	mixed		$matrix2	Second matrix operand
	 */
	private static function _resizeMatricesExtend(&$matrix1,&$matrix2) {
		list($matrix1Rows,$matrix1Columns) = self::_getMatrixDimensions($matrix1);
		list($matrix2Rows,$matrix2Columns) = self::_getMatrixDimensions($matrix2);

		if (($matrix2Columns < $matrix1Columns) || ($matrix2Rows < $matrix1Rows)) {
			if ($matrix2Columns < $matrix1Columns) {
				for ($i = 0; $i < $matrix2Rows; ++$i) {
					$x = $matrix2[$i][$matrix2Columns-1];
					for ($j = $matrix2Columns; $j < $matrix1Columns; ++$j) {
						$matrix2[$i][$j] = $x;
					}
				}
			}
			if ($matrix2Rows < $matrix1Rows) {
				$x = $matrix2[$matrix2Rows-1];
				for ($i = 0; $i < $matrix1Rows; ++$i) {
					$matrix2[$i] = $x;
				}
			}
		}

		if (($matrix1Columns < $matrix2Columns) || ($matrix1Rows < $matrix2Rows)) {
			if ($matrix1Columns < $matrix2Columns) {
				for ($i = 0; $i < $matrix1Rows; ++$i) {
					$x = $matrix1[$i][$matrix1Columns-1];
					for ($j = $matrix1Columns; $j < $matrix2Columns; ++$j) {
						$matrix1[$i][$j] = $x;
					}
				}
			}
			if ($matrix1Rows < $matrix2Rows) {
				$x = $matrix1[$matrix1Rows-1];
				for ($i = 0; $i < $matrix2Rows; ++$i) {
					$matrix1[$i] = $x;
				}
			}
		}
	}	//	function _resizeMatricesExtend()


	/**
	 *	Format details of an operand for display in the log (based on operand type)
	 *
	 *	@param	mixed		$value	First matrix operand
	 *	@return	mixed
	 */
	private static function _showValue($value) {
		if (is_array($value)) {
			$retVal = '';
			$i = 0;
			foreach($value as $row) {
				if (is_array($row)) {
					if ($i > 0) { $retVal .= '; '; }
					$j = 0;
					foreach($row as $column) {
						if ($j > 0) { $retVal .= ', '; }
						$retVal .= $column;
						++$j;
					}
				} else {
					if ($i > 0) { $retVal .= ', '; }
					$retVal .= $row;
				}
				++$i;
			}
			return '{ '.$retVal.' }';
		} elseif(is_bool($value)) {
			return ($value) ? 'TRUE' : 'FALSE';
		}

		return $value;
	}	//	function _showValue()


	/**
	 *	Format type and details of an operand for display in the log (based on operand type)
	 *
	 *	@param	mixed		$value	First matrix operand
	 *	@return	mixed
	 */
	private static function _showTypeDetails($value) {
		switch (gettype($value)) {
			case 'double'	:
			case 'float'	:
				$typeString = 'a floating point number';
				break;
			case 'integer'	:
				$typeString = 'an integer number';
				break;
			case 'boolean'	:
				$typeString = 'a boolean';
				break;
			case 'array'	:
				$typeString = 'a matrix';
				break;
			case 'string'	:
				if ($value == '') {
					return 'an empty string';
				} elseif ($value{0} == '#') {
					return 'a '.$value.' error';
				} else {
					$typeString = 'a string';
				}
				break;
			case 'NULL'	:
				return 'a null value';
		}
		return $typeString.' with a value of '.self::_showValue($value);
	}	//	function _showTypeDetails()


	private static function _convertMatrixReferences($formula) {
		static $matrixReplaceFrom = array('{',';','}');
		static $matrixReplaceTo = array('MKMATRIX(MKMATRIX(','),MKMATRIX(','))');

		//	Convert any Excel matrix references to the MKMATRIX() function
		if (strpos($formula,'{') !== false) {
			//	Open and Closed counts used for trapping mismatched braces in the formula
			$openCount = $closeCount = 0;
			//	If there is the possibility of braces within a quoted string, then we don't treat those as matrix indicators
			if (strpos($formula,'"') !== false) {
				//	So instead we skip replacing in any quoted strings by only replacing in every other array element after we've exploded
				//		the formula
				$temp = explode('"',$formula);
				$i = 0;
				foreach($temp as &$value) {
					//	Only count/replace in alternate array entries
					if (($i++ % 2) == 0) {
						$openCount += substr_count($value,'{');
						$closeCount += substr_count($value,'}');
						$value = str_replace($matrixReplaceFrom,$matrixReplaceTo,$value);
					}
				}
				unset($value);
				//	Then rebuild the formula string
				$formula = implode('"',$temp);
			} else {
				//	If there's no quoted strings, then we do a simple count/replace
				$openCount += substr_count($formula,'{');
				$closeCount += substr_count($formula,'}');
				$formula = str_replace($matrixReplaceFrom,$matrixReplaceTo,$formula);
			}
			//	Trap for mismatched braces and trigger an appropriate error
			if ($openCount < $closeCount) {
				if ($openCount > 0) {
					return $this->_raiseFormulaError("Formula Error: Mismatched matrix braces '}'");
				} else {
					return $this->_raiseFormulaError("Formula Error: Unexpected '}' encountered");
				}
			} elseif ($openCount > $closeCount) {
				if ($closeCount > 0) {
					return $this->_raiseFormulaError("Formula Error: Mismatched matrix braces '{'");
				} else {
					return $this->_raiseFormulaError("Formula Error: Unexpected '{' encountered");
				}
			}
		}

		return $formula;
	}	//	function _convertMatrixReferences()


	private static function _mkMatrix() {
		return func_get_args();
	}	//	function _mkMatrix()


	// Convert infix to postfix notation
	private function _parseFormula($formula) {
		if (($formula = self::_convertMatrixReferences(trim($formula))) === false) {
			return false;
		}

		//	Binary Operators
		//	These operators always work on two values
		//	Array key is the operator, the value indicates whether this is a left or right associative operator
		$operatorAssociativity	= array('^' => 0,															//	Exponentiation
										'*' => 0, '/' => 0, 												//	Multiplication and Division
										'+' => 0, '-' => 0,													//	Addition and Subtraction
										'&' => 1,															//	Concatenation
										'>' => 0, '<' => 0, '=' => 0, '>=' => 0, '<=' => 0, '<>' => 0		//	Comparison
								 	  );
		//	Comparison (Boolean) Operators
		//	These operators work on two values, but always return a boolean result
		$comparisonOperators	= array('>', '<', '=', '>=', '<=', '<>');

		//	Operator Precedence
		//	This list includes all valid operators, whether binary (including boolean) or unary (such as %)
		//	Array key is the operator, the value is its precedence
		$operatorPrecedence	= array('_' => 6,																//	Negation
									'%' => 5,																//	Percentage
									'^' => 4,																//	Exponentiation
									'*' => 3, '/' => 3, 													//	Multiplication and Division
									'+' => 2, '-' => 2,														//	Addition and Subtraction
									'&' => 1,																//	Concatenation
									'>' => 0, '<' => 0, '=' => 0, '>=' => 0, '<=' => 0, '<>' => 0			//	Comparison
								   );

		$regexpMatchString = '/^('.self::CALCULATION_REGEXP_FUNCTION.
							   '|'.self::CALCULATION_REGEXP_NUMBER.
							   '|'.self::CALCULATION_REGEXP_STRING.
							   '|'.self::CALCULATION_REGEXP_OPENBRACE.
							   '|'.self::CALCULATION_REGEXP_CELLREF.
							   '|'.self::CALCULATION_REGEXP_NAMEDRANGE.
							 ')/i';

		//	Start with initialisation
		$index = 0;
		$stack = new PHPExcel_Token_Stack;
		$output = array();
		$expectingOperator = false;					//	We use this test in syntax-checking the expression to determine when a
													//		- is a negation or + is a positive operator rather than an operation
		$expectingOperand = false;					//	We use this test in syntax-checking the expression to determine whether an operand
													//		should be null in a function call
		//	The guts of the lexical parser
		//	Loop through the formula extracting each operator and operand in turn
		while(True) {
//			echo 'Assessing Expression <b>'.substr($formula, $index).'</b><br />';
			$opCharacter = $formula{$index};	//	Get the first character of the value at the current index position
//			echo 'Initial character of expression block is '.$opCharacter.'<br />';
			if ((in_array($opCharacter, $comparisonOperators)) && (strlen($formula) > $index) && (in_array($formula{$index+1}, $comparisonOperators))) {
				$opCharacter .= $formula{++$index};
//				echo 'Initial character of expression block is comparison operator '.$opCharacter.'<br />';
			}

			//	Find out if we're currently at the beginning of a number, variable, cell reference, function, parenthesis or operand
			$isOperandOrFunction = preg_match($regexpMatchString, substr($formula, $index), $match);
//			echo '$isOperandOrFunction is '.(($isOperandOrFunction)?'True':'False').'<br />';

			if ($opCharacter == '-' && !$expectingOperator) {				//	Is it a negation instead of a minus?
//				echo 'Element is a Negation operator<br />';
				$stack->push('_');											//	Put a negation on the stack
				++$index;													//		and drop the negation symbol
			} elseif ($opCharacter == '%' && $expectingOperator) {
//				echo 'Element is a Percentage operator<br />';
				$stack->push('%');											//	Put a percentage on the stack
				++$index;
			} elseif ($opCharacter == '+' && !$expectingOperator) {			//	Positive (rather than plus) can be discarded?
//				echo 'Element is a Positive number, not Plus operator<br />';
				++$index;													//	Drop the redundant plus symbol
			} elseif (($opCharacter == '_') && (!$isOperandOrFunction)) {	//	We have to explicitly deny an underscore, because it's legal on
				return $this->_raiseFormulaError("Formula Error: Illegal character '_'");	//		the stack but not in the input expression
																			//	Note that _ is a valid first character in named ranges
																			//		and this will need modifying soon when we start integrating
																			//		with PHPExcel proper

			} elseif ((in_array($opCharacter, $this->_operators) or $isOperandOrFunction) && $expectingOperator) {	//	Are we putting an operator on the stack?
//				echo 'Element with value '.$opCharacter.' is an Operator<br />';
				while($stack->count() > 0 &&
					($o2 = $stack->last()) &&
					in_array($o2, $this->_operators) &&
					($operatorAssociativity[$opCharacter] ? $operatorPrecedence[$opCharacter] < $operatorPrecedence[$o2] : $operatorPrecedence[$opCharacter] <= $operatorPrecedence[$o2])) {
					$output[] = $stack->pop();								//	Swap operands and higher precedence operators from the stack to the output
				}
				$stack->push($opCharacter);	//	Finally put our current operator onto the stack
				++$index;
				$expectingOperator = false;

			} elseif ($opCharacter == ')' && $expectingOperator) {			//	Are we expecting to close a parenthesis?
//				echo 'Element is a Closing bracket<br />';
				$expectingOperand = false;
				while (($o2 = $stack->pop()) != '(') {						//	Pop off the stack back to the last (
					if (is_null($o2)) return $this->_raiseFormulaError('Formula Error: Unexpected closing brace ")"');
					else $output[] = $o2;
				}
				if (preg_match('/^'.self::CALCULATION_REGEXP_FUNCTION.'$/i', $stack->last(2), $matches)) {	//	Did this parenthesis just close a function?
					$functionName = $matches[1];										//	Get the function name
//					echo 'Closed Function is '.$functionName.'<br />';
					$argumentCount = $stack->pop();		//	See how many arguments there were (argument count is the next value stored on the stack)
//					if ($argumentCount == 0) {
//						echo 'With no arguments<br />';
//					} elseif ($argumentCount == 1) {
//						echo 'With 1 argument<br />';
//					} else {
//						echo 'With '.$argumentCount.' arguments<br />';
//					}
					$output[] = $argumentCount;			//	Dump the argument count on the output
					$output[] = $stack->pop();			//	Pop the function and push onto the output
					if (array_key_exists($functionName, $this->_controlFunctions)) {
//						echo 'Built-in function '.$functionName.'<br />';
						$expectedArgumentCount = $this->_controlFunctions[$functionName]['argumentCount'];
						$functionCall = $this->_controlFunctions[$functionName]['functionCall'];
					} elseif (array_key_exists($functionName, $this->_PHPExcelFunctions)) {
//						echo 'PHPExcel function '.$functionName.'<br />';
						$expectedArgumentCount = $this->_PHPExcelFunctions[$functionName]['argumentCount'];
						$functionCall = $this->_PHPExcelFunctions[$functionName]['functionCall'];
					} else {	// did we somehow push a non-function on the stack? this should never happen
						return $this->_raiseFormulaError("Formula Error: Internal error, non-function on stack");
					}
					//	Check the argument count
					$argumentCountError = False;
					if (is_numeric($expectedArgumentCount)) {
						if ($expectedArgumentCount < 0) {
//							echo '$expectedArgumentCount is between 0 and '.abs($expectedArgumentCount).'<br />';
							if ($argumentCount > abs($expectedArgumentCount)) {
								$argumentCountError = True;
								$expectedArgumentCountString = 'no more than '.abs($expectedArgumentCount);
							}
						} else {
//							echo '$expectedArgumentCount is numeric '.$expectedArgumentCount.'<br />';
							if ($argumentCount != $expectedArgumentCount) {
								$argumentCountError = True;
								$expectedArgumentCountString = $expectedArgumentCount;
							}
						}
					} elseif ($expectedArgumentCount != '*') {
						$isOperandOrFunction = preg_match('/(\d*)([-+,])(\d*)/',$expectedArgumentCount,$argMatch);
//						print_r($argMatch);
//						echo '<br />';
						switch ($argMatch[2]) {
							case '+' :
								if ($argumentCount < $argMatch[1]) {
									$argumentCountError = True;
									$expectedArgumentCountString = $argMatch[1].' or more ';
								}
								break;
							case '-' :
								if (($argumentCount < $argMatch[1]) || ($argumentCount > $argMatch[3])) {
									$argumentCountError = True;
									$expectedArgumentCountString = 'between '.$argMatch[1].' and '.$argMatch[3];
								}
								break;
							case ',' :
								if (($argumentCount != $argMatch[1]) && ($argumentCount != $argMatch[3])) {
									$argumentCountError = True;
									$expectedArgumentCountString = 'either '.$argMatch[1].' or '.$argMatch[3];
								}
								break;
						}
					}
					if ($argumentCountError) {
						return $this->_raiseFormulaError("Formula Error: Wrong number of arguments for $functionName() function: $argumentCount given, ".$expectedArgumentCountString." expected");
					}
				}
				++$index;

			} elseif ($opCharacter == ',') {			//	Is this the comma separator for function arguments?
//				echo 'Element is a Function argument separator<br />';
				while (($o2 = $stack->pop()) != '(') {
					if (is_null($o2)) return $this->_raiseFormulaError("Formula Error: Unexpected ','");
					else $output[] = $o2;	// pop the argument expression stuff and push onto the output
				}
				//	If we've a comma when we're expecting an operand, then what we actually have is a null operand;
				//		so push a null onto the stack
				if (($expectingOperand) || (!$expectingOperator)) {
					$output[] = $this->_ExcelConstants['NULL'];
				}
				// make sure there was a function
				if (!preg_match('/^'.self::CALCULATION_REGEXP_FUNCTION.'$/i', $stack->last(2), $matches))
					return $this->_raiseFormulaError("Formula Error: Unexpected ','");
				$stack->push($stack->pop()+1);	// increment the argument count
				$stack->push('(');	// put the ( back on, we'll need to pop back to it again
				$expectingOperator = false;
				$expectingOperand = true;
				++$index;

			} elseif ($opCharacter == '(' && !$expectingOperator) {
//				echo 'Element is an Opening Bracket<br />';
				$stack->push('(');
				++$index;

			} elseif ($isOperandOrFunction && !$expectingOperator) {	// do we now have a function/variable/number?
				$expectingOperator = true;
				$expectingOperand = false;
				$val = $match[1];
				$length = strlen($val);
//				echo 'Element with value '.$val.' is an Operand, Variable, Constant, String, Number, Cell Reference or Function<br />';

				if (preg_match('/^'.self::CALCULATION_REGEXP_FUNCTION.'$/i', $val, $matches)) {
					$val = preg_replace('/\s/','',$val);
//					echo 'Element '.$val.' is a Function<br />';
					if (array_key_exists(strtoupper($matches[1]), $this->_controlFunctions) || array_key_exists(strtoupper($matches[1]), $this->_PHPExcelFunctions)) {	// it's a func
						$stack->push(strtoupper($val));
						$ax = preg_match('/^\s*(\s*\))/i', substr($formula, $index+$length), $amatch);
						if ($ax) {
							$stack->push(0);
							$expectingOperator = true;
						} else {
							$stack->push(1);
							$expectingOperator = false;
						}
						$stack->push('(');
					} else {	// it's a var w/ implicit multiplication
						$val = $matches[1];
						$output[] = $val;
					}
				} elseif (preg_match('/^'.self::CALCULATION_REGEXP_CELLREF.'$/i', $val, $matches)) {
//					echo 'Element '.$val.' is a Cell reference<br />';
//					Watch for this case-change when modifying to allow cell references in different worksheets...
//						Should only be applied to the actual cell column, not the worksheet name
//					$cellRef = strtoupper($val);
//					$output[] = $cellRef;
					$output[] = $val;
//					$expectingOperator = false;
				} else {	// it's a variable, constant, string, number or boolean
//					echo 'Element is a Variable, Constant, String, Number or Boolean<br />';
					if ($opCharacter == '"') {
//						echo 'Element is a String<br />';
						$val = str_replace('""','"',$val);
					} elseif (is_numeric($val)) {
//						echo 'Element is a Number<br />';
						if ((strpos($val,'.') !== False) || (stripos($val,'e') !== False)) {
//							echo 'Casting '.$val.' to float<br />';
							$val = (float) $val;
						} else {
//							echo 'Casting '.$val.' to integer<br />';
							$val = (integer) $val;
						}
//					} elseif (array_key_exists(trim(strtoupper($val)), $this->_ExcelConstants)) {
//						$excelConstant = trim(strtoupper($val));
//						echo 'Element '.$val.' is an Excel Constant<br />';
//						$val = $this->_ExcelConstants[$excelConstant];
					}
					$output[] = $val;
				}
				$index += $length;

			} elseif ($opCharacter == ')') {	// miscellaneous error checking
				if ($expectingOperand) {
					$output[] = $this->_ExcelConstants['NULL'];
					$expectingOperand = false;
					$expectingOperator = True;
				} else {
					return $this->_raiseFormulaError("Formula Error: Unexpected ')'");
				}
			} elseif (in_array($opCharacter, $this->_operators) && !$expectingOperator) {
				return $this->_raiseFormulaError("Formula Error: Unexpected operator '$opCharacter'");
			} else {	// I don't even want to know what you did to get here
				return $this->_raiseFormulaError("Formula Error: An unexpected error occured");
			}
			//	Test for end of formula string
			if ($index == strlen($formula)) {
				//	Did we end with an operator?.
				//	Only valid for the % unary operator
				if ((in_array($opCharacter, $this->_operators)) && ($opCharacter != '%')) {
					return $this->_raiseFormulaError("Formula Error: Operator '$opCharacter' has no operands");
				} else {
					break;
				}
			}
			//	Ignore white space
			while (substr($formula, $index, 1) == ' ') {
				++$index;
			}
		}

		while (!is_null($opCharacter = $stack->pop())) {	// pop everything off the stack and push onto output
			if ($opCharacter == '(') return $this->_raiseFormulaError("Formula Error: Expecting ')'");	// if there are any opening braces on the stack, then braces were unbalanced
			$output[] = $opCharacter;
		}
		return $output;
	}	//	function _parseFormula()


	// evaluate postfix notation
	private function _processTokenStack($tokens, $cellID=null, PHPExcel_Cell $pCell = null) {
		if ($tokens == false) return false;

		$stack = new PHPExcel_Token_Stack;

		//	Loop through each token in turn
		foreach ($tokens as $token) {
//			echo '<b>Token is '.$token.'</b><br />';
			// if the token is a binary operator, pop the top two values off the stack, do the operation, and push the result back on the stack
			if (in_array($token, $this->_binaryOperators, true)) {
//				echo 'Token is a binary operator<br />';
				//	We must have two operands, error if we don't
				if (is_null($operand2 = $stack->pop())) return $this->_raiseFormulaError('Internal error - Operand value missing from stack');
				if (is_null($operand1 = $stack->pop())) return $this->_raiseFormulaError('Internal error - Operand value missing from stack');
				//	Log what we're doing
				$this->_writeDebug($cellID,'Evaluating '.self::_showValue($operand1).' '.$token.' '.self::_showValue($operand2));
				//	Process the operation in the appropriate manner
				switch ($token) {
					//	Comparison (Boolean) Operators
					case '>'	:			//	Greater than
					case '<'	:			//	Less than
					case '>='	:			//	Greater than or Equal to
					case '<='	:			//	Less than or Equal to
					case '='	:			//	Equality
					case '<>'	:			//	Inequality
						$this->_executeBinaryComparisonOperation($cellID,$operand1,$operand2,$token,$stack);
						break;
					//	Binary Operators
					case '+'	:			//	Addition
						$this->_executeNumericBinaryOperation($cellID,$operand1,$operand2,$token,'plusEquals',$stack);
						break;
					case '-'	:			//	Subtraction
						$this->_executeNumericBinaryOperation($cellID,$operand1,$operand2,$token,'minusEquals',$stack);
						break;
					case '*'	:			//	Multiplication
						$this->_executeNumericBinaryOperation($cellID,$operand1,$operand2,$token,'arrayTimesEquals',$stack);
						break;
					case '/'	:			//	Division
						$this->_executeNumericBinaryOperation($cellID,$operand1,$operand2,$token,'arrayRightDivide',$stack);
						break;
					case '^'	:			//	Exponential
						$this->_executeNumericBinaryOperation($cellID,$operand1,$operand2,$token,'power',$stack);
						break;
					case '&'	:			//	Concatenation
						//	If either of the operands is a matrix, we need to treat them both as matrices
						//		(converting the other operand to a matrix if need be); then perform the required
						//		matrix operation
						if ((is_array($operand1)) || (is_array($operand2))) {
							//	Ensure that both operands are arrays/matrices
							self::_checkMatrixOperands($operand1,$operand2);
							try {
								//	Convert operand 1 from a PHP array to a matrix
								$matrix = new Matrix($operand1);
								//	Perform the required operation against the operand 1 matrix, passing in operand 2
								$matrixResult = $matrix->concat($operand2);
								$result = $matrixResult->getArray();
							} catch (Exception $ex) {
								$this->_writeDebug($cellID,'JAMA Matrix Exception: '.$ex->getMessage());
								$result = '#VALUE!';
							}
						} else {
							$result = '"'.str_replace('""','"',self::_unwrapResult($operand1,'"').self::_unwrapResult($operand2,'"')).'"';
						}
						$this->_writeDebug($cellID,'Evaluation Result is '.self::_showTypeDetails($result));
						$stack->push($result);
						break;
				}

			// if the token is a unary operator, pop one value off the stack, do the operation, and push it back on
			} elseif (($token === "_") || ($token === "%")) {
//				echo 'Token is a unary operator<br />';
				if (is_null($arg = $stack->pop())) return $this->_raiseFormulaError('Internal error - Operand value missing from stack');
				if ($token === "_") {
//					echo 'Token is a negation operator<br />';
					$this->_writeDebug($cellID,'Evaluating Negation of '.self::_showValue($arg));
					$multiplier = -1;
				} else {
//					echo 'Token is a percentile operator<br />';
					$this->_writeDebug($cellID,'Evaluating Percentile of '.self::_showValue($arg));
					$multiplier = 0.01;
				}
				if (is_array($arg)) {
					self::_checkMatrixOperands($arg,$multiplier);
					try {
						$matrix1 = new Matrix($arg);
						$matrixResult = $matrix1->arrayTimesEquals($multiplier);
						$result = $matrixResult->getArray();
					} catch (Exception $ex) {
						$this->_writeDebug($cellID,'JAMA Matrix Exception: '.$ex->getMessage());
						$result = '#VALUE!';
					}

				} else {
					$result = $multiplier * $arg;
				}
				$this->_writeDebug($cellID,'Evaluation Result is '.self::_showTypeDetails($result));
				$stack->push($result);

			} elseif (preg_match('/^'.self::CALCULATION_REGEXP_CELLREF.'$/i', $token, $matches)) {
//				echo 'Element '.$token.' is a Cell reference<br />';
				if (isset($matches[8])) {
//					echo 'Reference is a Range of cells<br />';
					if (is_null($pCell)) {
//						We can't access the range, so return a REF error
						$cellValue = PHPExcel_Calculation_Functions::REF();
					} else {
						$cellRef = $matches[6].$matches[7].':'.$matches[9].$matches[10];
						if ($matches[2] > '') {
//							echo '$cellRef='.$cellRef.' in worksheet '.$matches[2].'<br />';
							$this->_writeDebug($cellID,'Evaluating Cell Range '.$cellRef.' in worksheet '.$matches[2]);
							$cellValue = $this->extractCellRange($cellRef, $pCell->getParent()->getParent()->getSheetByName($matches[2]), false);
							$this->_writeDebug($cellID,'Evaluation Result for cells '.$cellRef.' in worksheet '.$matches[2].' is '.self::_showTypeDetails($cellValue));
						} else {
//							echo '$cellRef='.$cellRef.' in current worksheet<br />';
							$this->_writeDebug($cellID,'Evaluating Cell Range '.$cellRef.' in current worksheet');
							$cellValue = $this->extractCellRange($cellRef, $pCell->getParent(), false);
							$this->_writeDebug($cellID,'Evaluation Result for cells '.$cellRef.' is '.self::_showTypeDetails($cellValue));
						}
					}
				} else {
//					echo 'Reference is a single Cell<br />';
					if (is_null($pCell)) {
//						We can't access the cell, so return a REF error
						$cellValue = PHPExcel_Calculation_Functions::REF();
					} else {
						$cellRef = $matches[6].$matches[7];
						if ($matches[2] > '') {
//							echo '$cellRef='.$cellRef.' in worksheet '.$matches[2].'<br />';
							$this->_writeDebug($cellID,'Evaluating Cell '.$cellRef.' in worksheet '.$matches[2]);
							if ($pCell->getParent()->cellExists($cellRef)) {
								$cellValue = $this->extractCellRange($cellRef, $pCell->getParent()->getParent()->getSheetByName($matches[2]), false);
							} else {
								$cellValue = null;
							}
							$this->_writeDebug($cellID,'Evaluation Result for cell '.$cellRef.' in worksheet '.$matches[2].' is '.self::_showTypeDetails($cellValue));
						} else {
//							echo '$cellRef='.$cellRef.' in current worksheet<br />';
							$this->_writeDebug($cellID,'Evaluating Cell '.$cellRef.' in current worksheet');
							if ($pCell->getParent()->cellExists($cellRef)) {
								$cellValue = $pCell->getParent()->getCell($cellRef)->getCalculatedValue(false);
							} else {
								$cellValue = '';
							}
							$this->_writeDebug($cellID,'Evaluation Result for cell '.$cellRef.' is '.self::_showTypeDetails($cellValue));
						}
					}
				}
				$stack->push($cellValue);

			// if the token is a function, pop arguments off the stack, hand them to the function, and push the result back on
			} elseif (preg_match('/^'.self::CALCULATION_REGEXP_FUNCTION.'$/i', $token, $matches)) {
//				echo 'Token is a function<br />';
				$functionName = $matches[1];
				$argCount = $stack->pop();
				if ($functionName != 'MKMATRIX') {
					$this->_writeDebug($cellID,'Evaluating Function '.$functionName.'() with '.(($argCount == 0) ? 'no' : $argCount).' argument'.(($argCount == 1) ? '' : 's'));
				}
				if ((array_key_exists($functionName, $this->_controlFunctions)) || (array_key_exists($functionName, $this->_PHPExcelFunctions))) {	// function
					if (array_key_exists($functionName, $this->_controlFunctions)) {
						$functionCall = $this->_controlFunctions[$functionName]['functionCall'];
					} elseif (array_key_exists($functionName, $this->_PHPExcelFunctions)) {
						$functionCall = $this->_PHPExcelFunctions[$functionName]['functionCall'];
					}
					// get the arguments for this function
//					echo 'Function '.$functionName.' expects '.$argCount.' arguments<br />';
					$args = array();
					for ($i = $argCount; $i > 0; --$i) {
						$arg = $stack->pop();
//						if (is_null($arg)) return $this->_raiseFormulaError("internal error");
						$args[$i] = $arg;
					}
					//	Reverse the order of the arguments
					ksort($args);
//					echo 'Arguments are: ';
//					print_r($args);
//					echo '<br />';
					if ($functionName != 'MKMATRIX') {
						$argArrayVals = array();
						foreach($args as &$arg) {
							$argArrayVals[] = self::_showValue($arg);
							$arg = self::_unwrapResult($arg);
						}
						unset($arg);
						$this->_writeDebug($cellID,'Evaluating '. $functionName.'( '.implode(', ',$argArrayVals).' )');
					}
					//	Process each argument in turn, building the return value as an array
//					if (($argCount == 1) && (is_array($args[1])) && ($functionName != 'MKMATRIX')) {
//						$operand1 = $args[1];
//						$this->_writeDebug($cellID,'Argument is a matrix: '.self::_showValue($operand1));
//						$result = array();
//						$row = 0;
//						foreach($operand1 as $args) {
//							if (is_array($args)) {
//								foreach($args as $arg) {
//									$this->_writeDebug($cellID,'Evaluating '. $functionName.'( '.self::_showValue($arg).' )');
//									$r = call_user_func_array($functionCall,$arg);
//									$this->_writeDebug($cellID,'Evaluation Result is '.self::_showTypeDetails($r));
//									$result[$row][] = $r;
//								}
//								++$row;
//							} else {
//								$this->_writeDebug($cellID,'Evaluating '. $functionName.'( '.self::_showValue($args).' )');
//								$r = call_user_func_array($functionCall,$args);
//								$this->_writeDebug($cellID,'Evaluation Result is '.self::_showTypeDetails($r));
//								$result[] = $r;
//							}
//						}
//					} else {
					//	Process the argument with the appropriate function call
						$result = call_user_func_array($functionCall,$args);
//					}
					if ($functionName != 'MKMATRIX') {
						$this->_writeDebug($cellID,'Evaluation Result is '.self::_showTypeDetails($result));
					}
					$stack->push(self::_wrapResult($result));
				}

			} else {
				// if the token is a number, boolean, string or an Excel error, push it onto the stack
				if (array_key_exists(strtoupper($token), $this->_ExcelConstants)) {
					$excelConstant = strtoupper($token);
//					echo 'Token is a PHPExcel constant: '.$excelConstant.'<br />';
					$stack->push($this->_ExcelConstants[$excelConstant]);
					$this->_writeDebug($cellID,'Evaluating Constant '.$excelConstant.' as '.self::_showTypeDetails($this->_ExcelConstants[$excelConstant]));
				} elseif ((is_null($token)) || ($token == '') || (is_bool($token)) || (is_numeric($token)) || ($token{0} == '"') || ($token{0} == '#')) {
//					echo 'Token is a number, boolean, string or an Excel error<br />';
					$stack->push($token);
				// if the token is a constant, push the constant value on the stack
				} elseif (preg_match('/^'.self::CALCULATION_REGEXP_NAMEDRANGE.'$/i', $token, $matches)) {
//					echo 'Token is a named range<br />';
					$namedRange = $matches[6];
//					echo 'Named Range is '.$namedRange.'<br />';
					$this->_writeDebug($cellID,'Evaluating Named Range '.$namedRange);
					$cellValue = $this->extractNamedRange($namedRange, $pCell->getParent(), false);
					$this->_writeDebug($cellID,'Evaluation Result for named range '.$namedRange.' is '.self::_showTypeDetails($cellValue));
					$stack->push($cellValue);
				} else {
					return $this->_raiseFormulaError("undefined variable '$token'");
				}
			}
		}
		// when we're out of tokens, the stack should have a single element, the final result
		if ($stack->count() != 1) return $this->_raiseFormulaError("internal error");
		$output = $stack->pop();
		if ((is_array($output)) && (self::$returnArrayAsType != self::RETURN_ARRAY_AS_ARRAY)) {
			return array_unshift(array_unshift($output));
		}
		return $output;
	}	//	function _processTokenStack()


	private function _validateBinaryOperand($cellID,&$operand,&$stack) {
		//	Numbers, matrices and booleans can pass straight through, as they're already valid
		if (is_string($operand)) {
			//	We only need special validations for the operand if it is a string
			//	Start by stripping off the quotation marks we use to identify true excel string values internally
			if ($operand > '' && $operand{0} == '"') { $operand = self::_unwrapResult($operand,'"'); }
			//	If the string is a numeric value, we treat it as a numeric, so no further testing
			if (!is_numeric($operand)) {
				//	If not a numeric, test to see if the value is an Excel error, and so can't be used in normal binary operations
				if ($operand > '' && $operand{0} == '#') {
					$stack->push($operand);
					$this->_writeDebug($cellID,'Evaluation Result is '.self::_showTypeDetails($operand));
					return false;
				} else {
				//	If not a numeric, then it's a text string, and so can't be used in mathematical binary operations
					$stack->push('#VALUE!');
					$this->_writeDebug($cellID,'Evaluation Result is a '.self::_showTypeDetails('#VALUE!'));
					return false;
				}
			}
		}

		//	return a true if the value of the operand is one that we can use in normal binary operations
		return true;
	}	//	function _validateBinaryOperand()


	private function _executeBinaryComparisonOperation($cellID,$operand1,$operand2,$operation,&$stack) {
		//	Validate the two operands

		//	If we're dealing with non-matrix operations, execute the necessary operation
		switch ($operation) {
			//	Greater than
			case '>':
				$result = ($operand1 > $operand2);
				break;
			//	Less than
			case '<':
				$result = ($operand1 < $operand2);
				break;
			//	Equality
			case '=':
				$result = ($operand1 == $operand2);
				break;
			//	Greater than or equal
			case '>=':
				$result = ($operand1 >= $operand2);
				break;
			//	Less than or equal
			case '<=':
				$result = ($operand1 <= $operand2);
				break;
			//	Inequality
			case '<>':
				$result = ($operand1 != $operand2);
				break;
		}

		//	Log the result details
		$this->_writeDebug($cellID,'Evaluation Result is '.self::_showTypeDetails($result));
		//	And push the result onto the stack
		$stack->push($result);
		return true;
	}	//	function _executeBinaryComparisonOperation()

	private function _executeNumericBinaryOperation($cellID,$operand1,$operand2,$operation,$matrixFunction,&$stack) {
		//	Validate the two operands
		if (!$this->_validateBinaryOperand($cellID,$operand1,$stack)) return false;
		if (!$this->_validateBinaryOperand($cellID,$operand2,$stack)) return false;

		//	If either of the operands is a matrix, we need to treat them both as matrices
		//		(converting the other operand to a matrix if need be); then perform the required
		//		matrix operation
		if ((is_array($operand1)) || (is_array($operand2))) {
			//	Ensure that both operands are arrays/matrices
			self::_checkMatrixOperands($operand1,$operand2);
			try {
				//	Convert operand 1 from a PHP array to a matrix
				$matrix = new Matrix($operand1);
				//	Perform the required operation against the operand 1 matrix, passing in operand 2
				$matrixResult = $matrix->$matrixFunction($operand2);
				$result = $matrixResult->getArray();
			} catch (Exception $ex) {
				$this->_writeDebug($cellID,'JAMA Matrix Exception: '.$ex->getMessage());
				$result = '#VALUE!';
			}
		} else {
			//	If we're dealing with non-matrix operations, execute the necessary operation
			switch ($operation) {
				//	Addition
				case '+':
					$result = $operand1+$operand2;
					break;
				//	Subtraction
				case '-':
					$result = $operand1-$operand2;
					break;
				//	Multiplication
				case '*':
					$result = $operand1*$operand2;
					break;
				//	Division
				case '/':
					if ($operand2 == 0) {
						//	Trap for Divide by Zero error
						$stack->push('#DIV/0!');
						$this->_writeDebug($cellID,'Evaluation Result is '.self::_showTypeDetails('#DIV/0!'));
						return false;
					} else {
						$result = $operand1/$operand2;
					}
					break;
				//	Power
				case '^':
					$result = pow($operand1,$operand2);
					break;
			}
		}

		//	Log the result details
		$this->_writeDebug($cellID,'Evaluation Result is '.self::_showTypeDetails($result));
		//	And push the result onto the stack
		$stack->push($result);
		return true;
	}	//	function _executeNumericBinaryOperation()


	private function _writeDebug($cellID,$message) {
		//	Only write the debug log if logging is enabled
		if ($this->writeDebugLog) {
//			$prefix = substr(implode(' -> ',$this->debugLogStack).' -> ',4+strlen($this->debugLogStack[0]));
			$prefix = implode(' -> ',$this->debugLogStack).' -> ';
			$this->debugLog[] = $prefix.$message;
		}
	}	//	function _writeDebug()


	// trigger an error, but nicely, if need be
	private function _raiseFormulaError($errorMessage) {
		$this->formulaError = $errorMessage;
		if (!$this->suppressFormulaErrors) throw new Exception($errorMessage);
		trigger_error($errorMessage, E_USER_ERROR);
	}	//	function _raiseFormulaError()


	/**
	 * Extract range values
	 *
	 * @param	string				$pRange		String based range representation
	 * @param	PHPExcel_Worksheet	$pSheet		Worksheet
	 * @return  mixed				Array of values in range if range contains more than one element. Otherwise, a single value is returned.
	 * @throws	Exception
	 */
	public function extractCellRange($pRange = 'A1', PHPExcel_Worksheet $pSheet = null, $resetLog=true) {
		// Return value
		$returnValue = array ( );

//		echo 'extractCellRange('.$pRange.')<br />';
		// Worksheet given?
		if (!is_null($pSheet)) {
			// Worksheet reference?
//			echo 'Current sheet name is '.$pSheet->getTitle().'<br />';
//			echo 'Range reference is '.$pRange.'<br />';
			if (strpos ($pRange, '!') !== false) {
//				echo '$pRange reference includes sheet reference<br />';
				$worksheetReference = PHPExcel_Worksheet::extractSheetTitle($pRange, true);
				$pSheet = $pSheet->getParent()->getSheetByName($worksheetReference[0]);
//				echo 'New sheet name is '.$pSheet->getTitle().'<br />';
				$pRange = $worksheetReference[1];
//				echo 'Adjusted Range reference is '.$pRange.'<br />';
			}

			// Extract range
			$aReferences = PHPExcel_Cell::extractAllCellReferencesInRange($pRange);
			if (count($aReferences) == 1) {
				return $pSheet->getCell($aReferences[0])->getCalculatedValue($resetLog);
			}

			// Extract cell data
			foreach ($aReferences as $reference) {
				// Extract range
				list($currentCol,$currentRow) = PHPExcel_Cell::coordinateFromString($reference);

				$returnValue[$currentCol][$currentRow] = $pSheet->getCell($reference)->getCalculatedValue($resetLog);
			}
		}

		// Return
		return $returnValue;
	}	//	function extractCellRange()


	/**
	 * Extract range values
	 *
	 * @param	string				$pRange		String based range representation
	 * @param	PHPExcel_Worksheet	$pSheet		Worksheet
	 * @return  mixed				Array of values in range if range contains more than one element. Otherwise, a single value is returned.
	 * @throws	Exception
	 */
	public function extractNamedRange($pRange = 'A1', PHPExcel_Worksheet $pSheet = null, $resetLog=true) {
		// Return value
		$returnValue = array ( );

//		echo 'extractNamedRange('.$pRange.')<br />';
		// Worksheet given?
		if (!is_null($pSheet)) {
			// Worksheet reference?
//			echo 'Current sheet name is '.$pSheet->getTitle().'<br />';
//			echo 'Range reference is '.$pRange.'<br />';
			if (strpos ($pRange, '!') !== false) {
//				echo '$pRange reference includes sheet reference<br />';
				$worksheetReference = PHPExcel_Worksheet::extractSheetTitle($pRange, true);
				$pSheet = $pSheet->getParent()->getSheetByName($worksheetReference[0]);
//				echo 'New sheet name is '.$pSheet->getTitle().'<br />';
				$pRange = $worksheetReference[1];
//				echo 'Adjusted Range reference is '.$pRange.'<br />';
			}

			// Named range?
			$namedRange = PHPExcel_NamedRange::resolveRange($pRange, $pSheet);
			if (!is_null($namedRange)) {
//				echo 'Named Range '.$pRange.' (';
				$pRange = $namedRange->getRange();
//				echo $pRange.') is in sheet '.$namedRange->getWorksheet()->getTitle().'<br />';
				if ($pSheet->getTitle() != $namedRange->getWorksheet()->getTitle()) {
					if (!$namedRange->getLocalOnly()) {
						$pSheet = $namedRange->getWorksheet();
					} else {
						return $returnValue;
					}
				}
			}

			// Extract range
			$aReferences = PHPExcel_Cell::extractAllCellReferencesInRange($pRange);
			if (count($aReferences) == 1) {
				return $pSheet->getCell($aReferences[0])->getCalculatedValue($resetLog);
			}

			// Extract cell data
			foreach ($aReferences as $reference) {
				// Extract range
				list($currentCol,$currentRow) = PHPExcel_Cell::coordinateFromString($reference);
//				echo 'NAMED RANGE: $currentCol='.$currentCol.' $currentRow='.$currentRow.'<br />';
				$returnValue[$currentRow][$currentCol] = $pSheet->getCell($reference)->getCalculatedValue($resetLog);
			}
//			print_r($returnValue);
//			echo '<br />';
			$returnValue = array_values($returnValue);
			foreach($returnValue as &$rr) {
				$rr = array_values($rr);
			}
			unset($rr);
//			print_r($returnValue);
//			echo '<br />';
		}

		// Return
		return $returnValue;
	}	//	function extractNamedRange()


	/**
	 * Is a specific function implemented?
	 *
	 * @param	string	$pFunction	Function Name
	 * @return	boolean
	 */
	public function isImplemented($pFunction = '') {
		$pFunction = strtoupper ($pFunction);
		if (isset ($this->_PHPExcelFunctions[$pFunction])) {
			return ($this->_PHPExcelFunctions[$pFunction]['functionCall'] != 'PHPExcel_Calculation_Functions::DUMMY');
		} else {
			return false;
		}
	}	//	function isImplemented()


	/**
	 * Get a list of all implemented functions as an array of function objects
	 *
	 * @return	array of PHPExcel_Calculation_Function
	 */
	public function listFunctions() {
		// Return value
		$returnValue = array();
		// Loop functions
		foreach($this->_PHPExcelFunctions as $functionName => $function) {
			if ($function['functionCall'] != 'PHPExcel_Calculation_Functions::DUMMY') {
				$returnValue[$functionName] = new PHPExcel_Calculation_Function($function['category'],
																				$functionName,
																				$function['functionCall']
																			   );
			}
		}

		// Return
		return $returnValue;
	}	//	function listFunctions()


	/**
	 * Get a list of implemented Excel function names
	 *
	 * @return	array
	 */
	public function listFunctionNames() {
		// Return value
		$returnValue = array();
		// Loop functions
		foreach ($this->_PHPExcelFunctions as $functionName => $function) {
			$returnValue[] = $functionName;
		}

		// Return
		return $returnValue;
	}	//	function listFunctionNames()

}	//	class PHPExcel_Calculation




// for internal use
class PHPExcel_Token_Stack {

	private $_stack = array();
	private $_count = 0;

	public function count() {
		return $this->_count;
	}

	public function push($value) {
		$this->_stack[$this->_count++] = $value;
	}

	public function pop() {
		if ($this->_count > 0) {
			return $this->_stack[--$this->_count];
		}
		return null;
	}

	public function last($n=1) {
		if ($this->_count-$n < 0) {
			return null;
		}
		return $this->_stack[$this->_count-$n];
	}

	function __construct() {
	}

}	//	class PHPExcel_Token_Stack
?>