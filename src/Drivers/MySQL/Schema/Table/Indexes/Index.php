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

namespace O2System\Database\Drivers\MySQL\Schema\Table\Indexes;

// ------------------------------------------------------------------------

/**
 * Class Index
 *
 * @package O2System\Database\Drivers\MySQL\Schema\Table\Indexes
 */
class Index
{
    public $name;
    public $fields = [];
    public $type;
    public $method;

    public function __construct( $name )
    {
        $this->setName( $name );
    }

    public function setName( $name )
    {
        $this->name = 'idx_' . underscore( $name );

        return $this;
    }

    public function addField( $field )
    {
        $this->fields[] = underscore( $field );

        return $this;
    }

    public function setType( $type )
    {
        if ( in_array( $type, [ 'Normal', 'Unique', 'Full Text' ], true ) ) {
            $this->type = $type;
        }

        return $this;
    }

    public function setMethod( $method )
    {
        if ( in_array( $method, [ 'BTREE', 'HASH' ], true ) ) {
            $this->method = $method;
        }

        return $this;
    }
}