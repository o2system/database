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

namespace O2System\Database\Datastructures\Result\Fields;

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
     * SimpleSerializeField constructor.
     *
     * @param array $data
     */
    public function __construct( $data = [] )
    {
        parent::__construct( [] );

        if ( ! empty( $data ) ) {
            foreach ( $data as $key => $value ) {
                $this->__set( $key, $value );
            }
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Magic Method __set
     *
     * @param $index
     *
     * @param $value
     */
    public function __set( $index, $value )
    {
        $this->offsetSet( $index, $value );
    }

    // ------------------------------------------------------------------------

    /**
     * magic Method __toArray
     *
     * @return array
     */
    public function __toArray()
    {
        return $this->getArrayCopy();
    }
}