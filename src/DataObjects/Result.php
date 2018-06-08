<?php
/**
 * This file is part of the O2System PHP Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author         Steeve Andrian Salim
 * @copyright      Copyright (c) Steeve Andrian Salim
 */

// ------------------------------------------------------------------------

namespace O2System\Database\DataObjects;

// ------------------------------------------------------------------------

use O2System\Database\DataObjects\Result\Info;
use O2System\Database\DataObjects\Result\Row;
use O2System\Spl\Datastructures\Traits\ArrayConversionTrait;

/**
 * Class Result
 *
 * @package O2System\Database\DataObjects
 */
class Result implements
    \ArrayAccess,
    \SeekableIterator,
    \Countable,
    \Serializable,
    \JsonSerializable
{
    use ArrayConversionTrait;

    /**
     * Result::$position
     *
     * SeekableIterator Position
     *
     * @access  protected
     * @type    int
     */
    private $position = 0;

    /**
     * Result::$rows
     *
     * List of Result Rows
     *
     * @access  private
     * @type    array
     */
    private $rows = [];

    /**
     * Result::$numRows
     *
     * Number of rows
     *
     * @access private
     * @var int
     */
    private $numRows = 0;

    /**
     * Result::$totalRows
     *
     * Total of rows
     *
     * @access private
     * @var int
     */
    private $totalRows = 0;

    // ------------------------------------------------------------------------

    /**
     * Result::__construct
     *
     * @param array $rows
     */
    public function __construct(array $rows)
    {
        $this->totalRows = $this->numRows = count($rows);

        $this->rows = new \SplFixedArray($this->numRows);

        foreach ($rows as $key => $row) {
            $this->rows[ $key ] = new Result\Row($row);
        }
    }

    // ------------------------------------------------------------------------

    public function setTotalRows($totalRows)
    {
        $this->totalRows = (int)$totalRows;
    }

    // ------------------------------------------------------------------------

    /**
     * Result::first
     *
     * Gets first result row data.
     *
     * @return \O2System\Database\DataObjects\Result\Row
     */
    public function first()
    {
        $this->seek(0);

        if($this->count()) {
            return $this->rows[ $this->position ];
        }

        return new Row();
    }

    // ------------------------------------------------------------------------

    /**
     * Result::seek
     *
     * Seeks to a position
     *
     * @link  http://php.net/manual/en/seekableiterator.seek.php
     *
     * @param int $position <p>
     *                      The position to seek to.
     *                      </p>
     *
     * @return void
     * @since 5.1.0
     */
    public function seek($position)
    {
        if ($position < 0) {
            $position = 0;
        } elseif ($position > $this->count()) {
            $position = $this->count();
        }

        if (isset($this->rows[ $position ])) {
            $this->position = $position;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Result::count
     *
     * Count elements of an object
     *
     * @link  http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     *        </p>
     *        <p>
     *        The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->numRows;
    }

    // ------------------------------------------------------------------------

    /**
     * Result::last
     *
     * Gets last result row data.
     *
     * @return \O2System\Database\DataObjects\Result\Row
     */
    public function last()
    {
        $this->seek($this->count() - 1);

        if($this->count()) {
            return $this->rows[ $this->position ];
        }

        return new Row();
    }

    // ------------------------------------------------------------------------

    /**
     * Result::current
     *
     * Return the current element
     *
     * @link  http://php.net/manual/en/iterator.current.php
     * @return \O2System\Database\DataObjects\Result\Row
     * @since 5.0.0
     */
    public function current()
    {
        $this->seek($this->position);

        if($this->count()) {
            return $this->rows[ $this->position ];
        }

        return new Row();
    }

    // ------------------------------------------------------------------------

    /**
     * Result::next
     *
     * Move forward to next element.
     *
     * @link  http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        ++$this->position;
        $this->seek($this->position);
    }

    // ------------------------------------------------------------------------

    /**
     * Result::previous
     *
     * Move backward to previous element.
     *
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function previous()
    {
        --$this->position;
        $this->seek($this->position);
    }

    // ------------------------------------------------------------------------

    /**
     * Result::key
     *
     * Return the key of the current element.
     *
     * @link  http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return (int)$this->position;
    }

    // ------------------------------------------------------------------------

    /**
     * Result::valid
     *
     * Checks if current position is valid.
     *
     * @link  http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     *        Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        if($this->count()) {
            return isset($this->rows[ $this->position ]);
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Result::rewind
     *
     * Rewind the Iterator to the first element.
     *
     * @link  http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->seek(0);
    }

    // ------------------------------------------------------------------------

    /**
     * Result::isEmpty
     *
     * Checks if the array storage is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->count() == 0 ? true : false);
    }

    // ------------------------------------------------------------------------

    /**
     * Result::getArrayCopy
     *
     * Creates a copy of result rows.
     *
     * @return array A copy of the result rows.
     */
    public function getArrayCopy()
    {
        return $this->rows;
    }

    // ------------------------------------------------------------------------

    /**
     * Result::offsetExists
     *
     * Whether a offset exists
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return (bool)isset($this->rows[ $offset ]);
    }

    // ------------------------------------------------------------------------

    /**
     * Result::offsetGet
     *
     * Offset to retrieve
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        if (isset($this->rows[ $offset ])) {
            return $this->rows[ $offset ];
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Result::offsetSet
     *
     * Offset to set
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        if ($value instanceof Row) {
            $this->rows[ $offset ] = $value;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Result::offsetUnset
     *
     * Offset to unset
     *
     * @link  http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        if (isset($this->rows[ $offset ])) {
            unset($this->rows[ $offset ]);
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Result::serialize
     *
     * String representation of object
     *
     * @link  http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize($this->rows);
    }

    // ------------------------------------------------------------------------

    /**
     * Result::unserialize
     *
     * Constructs the object
     *
     * @link  http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized <p>
     *                           The string representation of the object.
     *                           </p>
     *
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        $this->rows = unserialize($serialized);
    }

    // ------------------------------------------------------------------------

    /**
     * Result::jsonSerialize
     *
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *        which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->rows;
    }

    // ------------------------------------------------------------------------

    /**
     * Result::getInfo
     *
     * @return \O2System\Database\DataObjects\Result\Info
     */
    public function getInfo()
    {
        return new Info([
            'rows'   => $this->countAll(),
            'founds' => $this->count(),
        ]);
    }

    // ------------------------------------------------------------------------

    /**
     * $result::countAll
     *
     * Count all elements
     *
     * @return int Total row as an integer.
     *        </p>
     *        <p>
     *        The return value is cast to an integer.
     *
     */
    public function countAll()
    {
        return $this->totalRows;
    }
}