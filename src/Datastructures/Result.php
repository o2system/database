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

namespace O2System\Database\Datastructures;

// ------------------------------------------------------------------------

use Countable;
use O2System\Spl\Datastructures\Traits\ArrayConversionTrait;
use SeekableIterator;

/**
 * Class Result
 *
 * @package O2System\Database\Datastructures
 */
class Result implements SeekableIterator, Countable
{
    use ArrayConversionTrait;

    /**
     * SeekableIterator Position
     *
     * @access  protected
     * @type    int
     */
    private $position = 0;

    /**
     * List of Result Rows
     *
     * @access  protected
     * @type    array
     */
    private $rows = [];

    // ------------------------------------------------------------------------

    /**
     * Result::__construct
     *
     * @param array $rows
     */
    public function __construct( array $rows )
    {
        $this->rows = $rows;
    }

    // ------------------------------------------------------------------------

    /**
     * Result::first
     *
     * Gets first result row data.
     *
     * @return \O2System\Database\Datastructures\Result\Row
     */
    public function first()
    {
        $this->seek( 0 );

        return new Result\Row( $this->rows[ $this->position ] );
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
    public function seek( $position )
    {
        if ( $position < 0 ) {
            $position = 0;
        } elseif ( $position > $this->count() ) {
            $position = $this->count();
        }

        if ( isset( $this->rows[ $position ] ) ) {
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
        return count( $this->rows );
    }

    // ------------------------------------------------------------------------

    /**
     * Result::last
     *
     * Gets last result row data.
     *
     * @return \O2System\Database\Datastructures\Result\Row
     */
    public function last()
    {
        $this->seek( $this->count() - 1 );

        return new Result\Row( $this->rows[ $this->position ] );
    }

    // ------------------------------------------------------------------------

    /**
     * Result::current
     *
     * Return the current element
     *
     * @link  http://php.net/manual/en/iterator.current.php
     * @return \O2System\Database\Datastructures\Result\Row
     * @since 5.0.0
     */
    public function current()
    {
        $this->seek( $this->position );

        return new Result\Row( $this->rows[ $this->position ] );
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
        $this->seek( $this->position );
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
        $this->seek( $this->position );
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
        return isset( $this->rows[ $this->position ] );
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
        $this->seek( 0 );
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
        return ( $this->count() == 0 ? true : false );
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
}