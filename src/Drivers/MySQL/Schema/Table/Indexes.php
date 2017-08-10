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

namespace O2System\Database\Drivers\MySQL\Schema\Table;

// ------------------------------------------------------------------------

use O2System\Database\Drivers\MySQL\Schema\Table\Indexes\Index;
use O2System\Spl\Iterators\ArrayIterator;

/**
 * Class Indexes
 *
 * @package O2System\Database\Drivers\MySQL\Schema\Table
 */
class Indexes extends ArrayIterator
{
    /**
     * Indexes::createIndex
     *
     * @param string $name
     *
     * @return Index
     */
    public function createIndex( $name )
    {
        $index = new Index( $name );

        $this->offsetSet( $index->name, $index );

        return $this->last();
    }
}