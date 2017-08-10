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

use O2System\Spl\Iterators\ArrayIterator;

/**
 * Class ForeignKeys
 *
 * @package O2System\Database\Drivers\MySQL\Schema\Table
 */
class PrimaryKeys extends ArrayIterator
{
    public function addKey( $name )
    {
        $name = underscore( $name );
        $this->offsetSet( $name, $name );

        return $this;
    }
}