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

use Countable;
use SeekableIterator;

class Result implements SeekableIterator, Countable
{
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
    private $rows = [ ];

    // ------------------------------------------------------------------------

    public function __construct ( array $rows )
    {
        $this->rows = $rows;
    }

    public function first ()
    {
        $this->seek( 0 );

        return new Row( $this->rows[ $this->position ] );
    }

    /**
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
    public function seek ( $position )
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

    /**
     * Count elements of an object
     *
     * @link  http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     *        </p>
     *        <p>
     *        The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count ()
    {
        return count( $this->rows );
    }

    // ------------------------------------------------------------------------

    public function last ()
    {
        $this->seek( $this->count() - 1 );

        return new Row( $this->rows[ $this->position ] );
    }

    // ------------------------------------------------------------------------

    /**
     * Return the current element
     *
     * @link  http://php.net/manual/en/iterator.current.php
     * @return Row
     * @since 5.0.0
     */
    public function current ()
    {
        $this->seek( $this->position );

        return new Row( $this->rows[ $this->position ] );
    }

    // ------------------------------------------------------------------------

    /**
     * Move forward to next element
     *
     * @link  http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next ()
    {
        ++$this->position;
        $this->seek( $this->position );
    }

    // ------------------------------------------------------------------------

    /**
     * Move backward to previous element
     *
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function previous ()
    {
        --$this->position;
        $this->seek( $this->position );
    }

    // ------------------------------------------------------------------------

    /**
     * Return the key of the current element
     *
     * @link  http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key ()
    {
        return (int) $this->position;
    }

    // ------------------------------------------------------------------------

    /**
     * Checks if current position is valid
     *
     * @link  http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     *        Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid ()
    {
        return isset( $this->rows[ $this->position ] );
    }

    // ------------------------------------------------------------------------

    /**
     * Rewind the Iterator to the first element
     *
     * @link  http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind ()
    {
        $this->seek( 0 );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractResult::isEmpty
     *
     * Checks if the array storage is empty.
     *
     * @return bool
     */
    public function isEmpty ()
    {
        return ( $this->count() == 0 ? true : false );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractResult::getArrayCopy
     *
     * Creates a copy of result rows.
     *
     * @return array A copy of the result rows.
     */
    public function getArrayCopy ()
    {
        return $this->rows;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractResult::__toSerialize
     *
     * Convert rows into PHP serialize array
     *
     * @see http://php.net/manual/en/function.serialize.php
     *
     * @param int $options JSON encode options, default JSON_PRETTY_PRINT
     * @param int $depth   Maximum depth of JSON encode. Must be greater than zero.
     *
     * @return string
     */
    public function __toSerialize ()
    {
        return serialize( $this->rows );
    }

    // --------------------------------------------------------------------

    /**
     * AbstractResult::__toJSON
     *
     * @see http://php.net/manual/en/function.json-encode.php
     *
     * @param int $options JSON encode options, default JSON_PRETTY_PRINT
     * @param int $depth   Maximum depth of JSON encode. Must be greater than zero.
     *
     * @return string
     */
    public function __toJSON ( $options = JSON_PRETTY_PRINT, $depth = 512 )
    {
        $depth = $depth == 0 ? 512 : $depth;

        return call_user_func_array( 'json_encode', [ $this->rows, $options, $depth ] );
    }

    // --------------------------------------------------------------------

    /**
     * AbstractResult::__toString
     *
     * Convert result rows into JSON String
     *
     * @return string
     */
    public function __toString ()
    {
        return (string) json_encode( $this->rows );
    }
}