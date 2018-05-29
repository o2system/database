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

namespace O2System\Database\DataObjects\Result\Columns;

// ------------------------------------------------------------------------

use O2System\Spl\Datastructures\SplArrayObject;

/**
 * Class DataSerialize
 *
 * @package O2System\DB\Datastructures\Columns
 */
class DataSerialize extends SplArrayObject
{
    /**
     * DataSerialize::__construct
     *
     * SimpleSerializeField constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        parent::__construct([]);

        if ( ! empty($data)) {
            foreach ($data as $key => $value) {
                $this->__set($key, $value);
            }
        }
    }

    // ------------------------------------------------------------------------

    /**
     * DataSerialize::__set
     *
     * Magic Method __set
     *
     * @param $index
     *
     * @param $value
     */
    public function __set($index, $value)
    {
        $this->offsetSet($index, $value);
    }

    // ------------------------------------------------------------------------

    /**
     * DataSerialize::__toArray
     *
     * magic Method __toArray
     *
     * @return array
     */
    public function __toArray()
    {
        return $this->getArrayCopy();
    }
}