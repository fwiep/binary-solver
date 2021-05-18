<?php
/**
 * Single step in solving a binary puzzle
 *
 * PHP version 7
 *
 * @category PuzzleStep
 * @package  BinaryPuzzle
 * @author   Frans-Willem Post (FWieP) <fwiep@fwiep.nl>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://www.fwiep.nl/
 */
namespace FWieP\BinaryPuzzle;
/**
 * Single step in solving a binary puzzle
 *
 * @category PuzzleStep
 * @package  BinaryPuzzle
 * @author   Frans-Willem Post (FWieP) <fwiep@fwiep.nl>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://www.fwiep.nl/
 */
class PuzzleStep implements \JsonSerializable
{
    private $_rowIndex = -1;
    private $_colIndex = -1;
    private $_oldRowValue = '';
    private $_newRowValue = '';
    
    public $coordinateX = array();
    public $coordinateY = array();
    public $oldCellValue = array();
    public $newCellValue = array();
    public $strategy = -1;
    
    /**
     * Sets the row-index
     * 
     * @param int $ix the index to set
     * 
     * @return void 
     */
    public function setRowIndex(int $ix) : void
    {
        $this->_rowIndex = $ix;
    }
    
    /**
     * Sets the col-index
     *
     * @param int $ix the index to set
     *
     * @return void
     */
    public function setColIndex(int $ix) : void
    {
        $this->_colIndex = $ix;
    }
    
    /**
     * Sets the row's old value
     * 
     * @param string $str the row to set
     * 
     * @return void
     */
    public function setOldRowValue(string $str) : void
    {
        $this->_oldRowValue = $str;
    }
    
    /**
     * Sets the row's new value
     *
     * @param string $str the row to set
     *
     * @return void
     */
    public function setNewRowValue(string $str) : void
    {
        $this->_newRowValue = $str;
    }
    
    /**
     * Performs pre-serialization
     * 
     * @return PuzzleStep
     */
    public function jsonSerialize() : PuzzleStep
    {
        $this->_setIndexes();
        return $this;
    }
    
    /**
     * Sets the 0-based coordinates of the puzzle cells this step modifies
     * 
     * @return void
     */
    private function _setIndexes() : void
    {
        $this->coordinateX = array();
        $this->coordinateY = array();
        
        if (!$this->_oldRowValue || !$this->_newRowValue) {
            return;
        }
        if (strlen($this->_oldRowValue) != strlen($this->_newRowValue)) {
            return;
        }
        $secondIndex = -1;
        $length = strlen($this->_oldRowValue);
        
        for ($i = 0; $i < $length; $i++) {
            $oldChar = substr($this->_oldRowValue, $i, 1);
            $newChar = substr($this->_newRowValue, $i, 1);
            
            if (strcmp($newChar, $oldChar) !== 0) {
                $this->oldCellValue[] = $oldChar;
                $this->newCellValue[] = $newChar;
                
                if ($this->_rowIndex > -1) {
                    $this->coordinateX[] = $i;
                    $this->coordinateY[] = $this->_rowIndex;
                }
                if ($this->_colIndex > -1) {
                    $this->coordinateX[] = $this->_colIndex;
                    $this->coordinateY[] = $i;
                }
                continue;
            }
        }
        return;
    }
    /**
     * Creates a new instance
     * 
     * @param int $strategy the puzzle strategy being applied
     */
    public function __construct(int $strategy)
    {
        $this->strategy = $strategy;
    }
}