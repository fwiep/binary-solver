<?php
/**
 * Binary puzzle
 *
 * PHP version 7
 *
 * @category BinaryPuzzle
 * @package  BinaryPuzzle
 * @author   Frans-Willem Post (FWieP) <fwiep@fwiep.nl>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://www.fwiep.nl/
 */
namespace FWieP\BinaryPuzzle;
/**
 * Binary puzzle
 *
 * @category BinaryPuzzle
 * @package  BinaryPuzzle
 * @author   Frans-Willem Post (FWieP) <fwiep@fwiep.nl>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://www.fwiep.nl/
 */
class Puzzle
{
    private const PUZLLE_ROW = 1;
    private const PUZLLE_COL = 2;
    
    public const STRATEGY_NONE = 0;
    public const STRATEGY_1 = 1;
    public const STRATEGY_2 = 2;
    public const STRATEGY_3 = 4;
    public const STRATEGY_4 = 8;
    public const STRATEGY_5 = 16;
    public const STRATEGY_ALL = 31;
    public const STRATEGY_ALL_BUT_5 = 15;
    
    private $_unsolved = '';
    private $_width = 0;
    private $_height = 0;
    private $_amountMaxWide = 0;
    private $_amountMaxHigh = 0;
    private $_cells = [];
    private $_steps = [];
    private $_allPossibleRows = [];
    private $_allPossibleCols = [];
    
    /**
     * Creates a new instance
     * 
     * @param string $in the puzzle's source
     * 
     * @return bool
     */
    public function __construct(string $in) 
    {   
        $in = preg_replace("/[^01.\n]/", '', $in);
        $lines = explode("\n", trim($in));
        $width = strlen($lines[0]);
        $height = count($lines);
        $oneline = str_replace("\n", '', $in);
        
        // Puzzels bigger than 16x16 are too big to solve...
        if (strlen($oneline) > 256) {
            return false;
        }
        // Ensure that both width and height are even
        if ($width % 2 != 0 || $height % 2 != 0) {
            return false;
        }
        $this->_unsolved = $oneline;
        $this->_width = $width;
        $this->_height = $height;
        $this->_amountMaxWide = intdiv($width, 2);
        $this->_amountMaxHigh = intdiv($height, 2);
        $this->_cells = str_split($oneline);
        
        $this->_allPossibleRows = $this->_getAllPossibilities(
            $width, self::PUZLLE_ROW
        );
        $this->_allPossibleCols = $this->_getAllPossibilities(
            $height, self::PUZLLE_COL
        );
    }

    /**
     * Whether the (unsolved) puzzle is valid
     * 
     * @return bool
     */
    private function _validPuzzle() : bool
    {
        foreach ($this->_getRows() as $row) {
            if (!$this->_isValid($row, self::PUZLLE_ROW)) {
                return false;
            }
        }
        foreach ($this->_getColumns() as $col) {
            if (!$this->_isValid($col, self::PUZLLE_COL)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Try to solve the puzzle using given strategy(s)
     * 
     * @param int $strategies the strategies to use
     * 
     * @return bool
     */
    public function solve(int $strategies = self::STRATEGY_ALL) : bool 
    {
        $doStrategy1 = ($strategies & self::STRATEGY_1) > 0;
        $doStrategy2 = ($strategies & self::STRATEGY_2) > 0;
        $doStrategy3 = ($strategies & self::STRATEGY_3) > 0;
        $doStrategy4 = ($strategies & self::STRATEGY_4) > 0;
        $doStrategy5 = ($strategies & self::STRATEGY_5) > 0;
        
        if (!$this->_width || !$this->_height) {
            return false;
        }
        while (true) {
            if ($this->_isSolved()) {
                return true;
            }
            $oldCells = $this->_getCellsString();

            for ($i = 1; $i <= 5; $i++) {
                if (!${'doStrategy'.$i}) {
                    continue;
                }
                foreach ($this->_getRows() as $ix => $item) {
                    if ($this->_executeStrategy($item, $i, self::PUZLLE_ROW, $ix)) {
                        continue 3;
                    }
                }
                foreach ($this->_getColumns() as $ix => $item) {
                    if ($this->_executeStrategy($item, $i, self::PUZLLE_COL, $ix)) {
                        continue 3;
                    }
                }
            }
            if (strcmp($oldCells, $this->_getCellsString()) === 0) {
                return false;
            }
        }
        return false;
    }
    
    /**
     * Executes the given strategy on given row
     * 
     * @param string $oldItem  the row/column to process 
     * @param int    $strategy the strategy to execute
     * @param int    $rowcol   whether to process rows or columns
     * @param int    $ix       the row/column index
     * 
     * @return bool whether the execution yielded any result (change)
     */
    private function _executeStrategy(
        string $oldItem, int $strategy, int $rowcol = -1, int $ix = -1
    ) : bool {
        $newItem = $oldItem;
        
        switch ($strategy) {
        case 1:
            $newItem = $this->_strat1($oldItem, $rowcol);
            break;
        case 2:
            $newItem = $this->_strat2($oldItem, $rowcol); 
            break;
        case 3:
            $newItem = $this->_strat3($oldItem, $rowcol); 
            break;
        case 4:
            $newItem = $this->_strat4($oldItem, $rowcol); 
            break;
        case 5:
            $newItem = $this->_strat5($oldItem, $rowcol); 
            break;
        }
        if (!$this->_isValid($newItem, $rowcol)) {
            return false;
        }
        if (strcmp($newItem, $oldItem) !== 0) {

            // See if new row causes the puzzle's columns to become invalid
            // or if new column causes the puzzle's rows to become invalid
            $dummyThis = clone $this;

            switch ($rowcol) {
            case self::PUZLLE_ROW:
                $dummyThis->_setRow($newItem, $ix);
                break;
            case self::PUZLLE_COL:
                $dummyThis->_setColumn($newItem, $ix);
                break;
            }
            if (!$dummyThis->_validPuzzle()) {
                return false;
            }
            $step = new PuzzleStep($strategy);
            
            switch ($rowcol) {
            case self::PUZLLE_ROW:
                $step->setRowIndex($ix);
                $this->_setRow($newItem, $ix);
                break;
            case self::PUZLLE_COL:
                $step->setColIndex($ix);
                $this->_setColumn($newItem, $ix);
                break;
            }
            $step->setOldRowValue($oldItem);
            $step->setNewRowValue($newItem);
            $this->_steps[] = $step;
            
            return true;
        }
        return false;
    }
    
    /**
     * Gets all possibilities for the given row/column size
     * 
     * @param int $size   the size
     * @param int $rowcol wether to check for valid row or column
     * 
     * @return array
     */
    private function _getAllPossibilities(int $size, int $rowcol) : array
    {
        $maxBinary = str_repeat('1', $size);
        
        $all = range(0, bindec($maxBinary));
        $all = array_map(
            function ($x) use ($size) {
                return sprintf("%0${size}b", $x);
            }, $all
        );
        $all = array_filter(
            $all, function ($x) use ($rowcol) {
                return $this->_isValid($x, $rowcol);
            }
        );
        return $all;
    }
    
    /**
     * Gets an HTML representation of given cells
     * 
     * @param array $cells  the cells
     * @param int   $width  width of the rendered table 
     * @param int   $height height of the rendered table 
     * 
     * @return string
     */
    private static function _getGridHTML(
        array $cells, int $width, int $height
    ) : string {
        
        $rows = array_chunk($cells, $width);
        $dom = new \DOMDocument();
        
        $pzlTbl = $dom->createElement('table');
        $pzlTbl->setAttribute(
            'class', 'pzl table table-striped table-sm table-responsive'
        );
        $dom->appendChild($pzlTbl);
        
        foreach ($rows as $k => $row) {
            $rowTr = $dom->createElement('tr');
            $rowTr->setAttribute('class', 'row'.$k);
            
            foreach ($row as $k2 => $c) {
                $cellTd = $dom->createElement('td', $c);
                $cellTd->setAttribute('class', 'pzlcell cell'.$k2.'_'.$k);
                $rowTr->appendChild($cellTd);
            }
            $pzlTbl->appendChild($rowTr);
        }
        return $dom->saveHTML();
    }
    
    /**
     * Gets a HTML representation of the puzzle's unsolved state
     * 
     * @return string
     */
    public function getUnsolvedHTML() : string
    {
        if ($this->_unsolved && $this->_width && $this->_height) {
            return self::_getGridHTML(
                str_split($this->_unsolved),
                $this->_width,
                $this->_height
            );
        }
        return '';
    }
    
    /**
     * Gets the steps
     * 
     * @return PuzzleStep[]
     */
    public function getSteps() : array
    {
        return $this->_steps;
    }
    
    /**
     * Whether a given row/column is valid, according to the game's rules
     *
     * @param string $str    the row/column to check
     * @param int    $rowcol whether to process rows or columns
     *
     * @return bool
     */
    private function _isValid(string $str, int $rowcol) : bool
    {
        if (strcmp($str, '') === 0) {
            return false;
        }
        if (stripos($str, '000') !== false) {
            return false;
        }
        if (stripos($str, '111') !== false) {
            return false;
        }
        switch ($rowcol) {
        case self::PUZLLE_ROW:
            return $this->_isValidRow($str);
            break;
        case self::PUZLLE_COL:
            return $this->_isValidColumn($str);
            break;
        }
        return false;
    }
    
    /**
     * Whether a given row is valid, according to the game's rules
     * 
     * @param string $row the row to check
     * 
     * @return bool
     */
    private function _isValidRow(string $row) : bool 
    {
        $amount0 = substr_count($row, '0');
        $amount1 = substr_count($row, '1');
        
        if ($amount0 > $this->_amountMaxWide) {
            return false;
        }
        if ($amount1 > $this->_amountMaxWide) {
            return false;
        }
        return true;
    }
    
    /**
     * Whether a given column is valid, according to the game's rules
     *
     * @param string $col the column to check
     *
     * @return bool
     */
    private function _isValidColumn(string $col) : bool
    {   
        $amount0 = substr_count($col, '0');
        $amount1 = substr_count($col, '1');
        
        if ($amount0 > $this->_amountMaxHigh) {
            return false;
        }
        if ($amount1 > $this->_amountMaxHigh) {
            return false;
        }
        return true;
    }
    
    /**
     * Gets all cells as one concatenated string
     * 
     * @return string
     */
    private function _getCellsString() : string 
    {
        return implode('', $this->_cells);
    }
    
    /**
     * Whether the puzzle is solved, as in: has no open cells
     * 
     * @return bool
     */
    private function _isSolved() : bool 
    {
        return (strpos($this->_getCellsString(), '.') === false);
    }
    
    /**
     * Gets all rows
     * 
     * @return array
     */
    private function _getRows() : array 
    {
        return array_map('implode', array_chunk($this->_cells, $this->_width));
    }
    
    /**
     * Sets a given row at given index
     *
     * @param string $row the string of values to set
     * @param int    $ix  the index to set the string as row
     *
     * @return void
     */
    private function _setRow(string $row, int $ix) : void 
    {
        array_splice(
            $this->_cells, $ix * $this->_width,
            $this->_width, str_split($row, 1)
        );
        return;
    }
    
    /**
     * Gets all columns
     * 
     * @return array
     */
    private function _getColumns() : array 
    {
        $o = array_fill(0, $this->_width, '');
        for ($i = 0; $i < $this->_width; $i++) {
            for ($j = 0; $j < $this->_height; $j++) {
                $o[$i] .= $this->_cells[$j*$this->_width+$i];
            }
        }
        return $o;
    }
    
    /**
     * Sets a given column at given index
     * 
     * @param string $col the string of values to set
     * @param int    $ix  the index to set the string as column
     * 
     * @return void
     */
    private function _setColumn(string $col, int $ix) : void 
    {
        $colValues = str_split($col);
        for ($i = 0; $i < $this->_height; $i++) {
            $this->_cells[$i*$this->_width+$ix] = $colValues[$i];
        }
        return;
    }
    
    /**
     * Performs solving steps for strategy #1
     *
     * @param string $str    the row/column to process
     * @param int    $rowcol whether to process rows or columns
     *
     * @return string the processed row/column
     */
    private static function _strat1(string $str, int $rowcol = -1) : string 
    {
        $replaceCount = 0;
        
        $str = preg_replace('/00\./', '001', $str, 1, $replaceCount);
        if ($replaceCount > 0) {
            return $str;
        }
        $str = preg_replace('/\.00/', '100', $str, 1, $replaceCount);
        if ($replaceCount > 0) {
            return $str;
        }
        $str = preg_replace('/11\./', '110', $str, 1, $replaceCount);
        if ($replaceCount > 0) {
            return $str;
        }
        $str = preg_replace('/\.11/', '011', $str, 1, $replaceCount);
        if ($replaceCount > 0) {
            return $str;
        }
        $str = preg_replace('/0\.0/', '010', $str, 1, $replaceCount);
        if ($replaceCount > 0) {
            return $str;
        }
        $str = preg_replace('/1\.1/', '101', $str, 1, $replaceCount);
        if ($replaceCount > 0) {
            return $str;
        }
        return $str; 
    }
    
    /**
     * Performs solving steps for strategy #2
     *
     * @param string $str    the row/column to process
     * @param int    $rowcol whether to process rows or columns
     *
     * @return string the processed row/column
     */
    private function _strat2(string $str, int $rowcol = -1) : string 
    {
        $amountMax = -1;
        $size = -1;
        
        switch ($rowcol) {
        case self::PUZLLE_ROW:
            $amountMax = $this->_amountMaxWide;
            $size = $this->_width;
            break;
        case self::PUZLLE_COL:
            $amountMax = $this->_amountMaxHigh;
            $size = $this->_height;
            break;
        default:
            return $str;
            break;
        }
        $amount0 = substr_count($str, '0');
        $amount1 = substr_count($str, '1');
        $amountXX = substr_count($str, 'xx');
        $amountEmp = substr_count($str, '.');
        
        $amount0 += $amountXX;
        $amount1 += $amountXX;
        
        if ($amountEmp == 1) {
            $toInsert = $amount0 < $amount1 ? '0' : '1';
            $str = str_replace('.', $toInsert, $str);
            return $str;
        }
        if ($amount0 == $amountMax) {
            $str = str_replace('.', '1', $str);
            return $str;
        }
        if ($amount1 == $amountMax) {
            $str = str_replace('.', '0', $str);
            return $str;
        }
        
        if ($amount0 == $amountMax - 1) {
            $options = array();
            
            for ($i = 0; $i < $size; $i++) {
                if (substr($str, $i, 1) != '.') {
                    continue;
                }
                $tmpOption = substr_replace($str, '0', $i, 1);
                $tmpOption = str_replace('.', '1', $tmpOption);
                if ($this->_isValid($tmpOption, $rowcol)) {
                    $options[] = $tmpOption;
                } else {
                    $tmpOption = substr_replace($str, '1', $i, 1);
                    return $tmpOption;
                }
            }
            if (count($options) == 1) {
                $str = reset($options);
                return $str;
            }
        }
        if ($amount1 == $amountMax - 1) {
            $options = [];
            
            for ($i = 0; $i < $size; $i++) {
                if (substr($str, $i, 1) != '.') {
                    continue;
                }
                $tmpOption = substr_replace($str, '1', $i, 1);
                $tmpOption = str_replace('.', '0', $tmpOption);
                if ($this->_isValid($tmpOption, $rowcol)) {
                    $options[] = $tmpOption;
                } else {
                    $tmpOption = substr_replace($str, '0', $i, 1);
                    return $tmpOption;
                }
            }
            if (count($options) == 1) {
                $str = reset($options);
                return $str;
            }
        }
        return $str;
    }

    /**
     * Performs solving steps for strategy #3
     *
     * @param string $str    the row/column to process
     * @param int    $rowcol whether to process rows or columns
     *
     * @return string the processed row/column
     */
    private function _strat3(string $str, int $rowcol = -1) : string 
    {
        $optie = '';
        
        if (strpos($str, '0..1') !== false) {
            $optie = preg_replace('/0\.\.1/', '0xx1', $str);
        }
        if (strpos($str, '1..0') !== false) {
            $optie = preg_replace('/1\.\.0/', '1xx0', $str);
        }
        if (!$optie) {
            return $str;
        }
        $newItem = $this->_strat2($optie, $rowcol);
        $newItem = str_replace('xx', '..', $newItem);
        
        return $newItem;
    }
    
    /**
     * Performs solving steps for strategy #4
     *
     * @param string $str    the row/column to process
     * @param int    $rowcol whether to process rows or columns
     *
     * @return string the processed row/column
     */
    private function _strat4(string $str, int $rowcol = -1) : string 
    {
        if (substr_count($str, '.') != 2) {
            return $str;
        }
        $optieA = $str;
        $optieA = preg_replace('/\./', '1', $optieA, 1);
        $optieA = preg_replace('/\./', '0', $optieA, 1);
        
        $optieB = $str;
        $optieB = preg_replace('/\./', '0', $optieB, 1);
        $optieB = preg_replace('/\./', '1', $optieB, 1);
        
        switch ($rowcol) {
        case self::PUZLLE_ROW:
            $items = $this->_getRows();
            break;
        case self::PUZLLE_COL:
            $items = $this->_getColumns();
            break;
        default:
            $items = [];
            break;
        }
        foreach ($items as $ix2 => $item2) {
            
            if ($item2 == $optieA) {
                $newItem = $optieB;
            } else if ($item2 == $optieB) {
                $newItem = $optieA;
            } else {
                $newItem = '';
            }
            if ($this->_isValid($newItem, $rowcol)) {
                return $newItem;
            } else {
                continue;
            }
        }
        return $str;
    }

    /**
     * Performs solving steps for strategy #5
     * 
     * @param string $str    the row/column to process
     * @param int    $rowcol whether to process rows or columns
     * 
     * @return string the processed row/column
     */
    private function _strat5(string $str, int $rowcol = -1) : string 
    {
        switch ($rowcol) {
        case self::PUZLLE_ROW:
            $size = $this->_width;
            $allPossibles = $this->_allPossibleRows;
            $items = $this->_getRows();
            break;
            
        case self::PUZLLE_COL:
            $size = $this->_height;
            $allPossibles = $this->_allPossibleCols;
            $items = $this->_getColumns();
            break;
        }
        $possibles = array_filter(
            $allPossibles,
            function ($x) use ($str, $items) {
                $xChars = str_split($x);
                $strChars = str_split($str);
                $charsCount = count($xChars);
                
                if (count($xChars) != count($strChars)) {
                    return false;
                }
                for ($i = 0; $i < $charsCount; $i++) {
                    if ($strChars[$i] == '.') {
                        continue;
                    }
                    if ($strChars[$i] != $xChars[$i]) {
                        return false;
                    }
                }
                return (array_search($x, $items) === false);
            }
        );
        for ($i = 0; $i < $size; $i++) {
            if (substr($str, $i, 1) != '.') {
                continue;
            }
            $valuesOnIndexI = array_reduce(
                $possibles,
                function ($a, $b) use ($i) {
                    return $a.substr($b, $i, 1);
                }, ''
            );
            if (strpos($valuesOnIndexI, '0') !== false
                && strpos($valuesOnIndexI, '1') === false
            ) {
                $str = substr_replace($str, '0', $i, 1);
                return $str;
            }
            if (strpos($valuesOnIndexI, '1') !== false
                && strpos($valuesOnIndexI, '0') === false
            ) {
                $str = substr_replace($str, '1', $i, 1);
                return $str;
            }
        }
        return $str;
    }
}