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

namespace O2System\Database\Drivers\MySQL\Schema;

// ------------------------------------------------------------------------

use O2System\Database\Drivers\MySQL\Schema\Table\Attributes;
use O2System\Database\Drivers\MySQL\Schema\Table\Columns;
use O2System\Database\Drivers\MySQL\Schema\Table\ForeignKeys;
use O2System\Database\Drivers\MySQL\Schema\Table\Indexes;
use O2System\Database\Drivers\MySQL\Schema\Table\PrimaryKeys;

/**
 * Class Table
 *
 * @package O2System\Database\Datastructures
 */
class Table
{
    public $name;
    public $attributes;
    public $columns;
    public $indexes;
    public $foreignKeys;

    public function __construct( $name )
    {
        $this->setName( $name );
        $this->attributes = new Attributes();
        $this->columns = new Columns();
        $this->primaryKeys = new PrimaryKeys();
        $this->foreignKeys = new ForeignKeys();
        $this->indexes = new Indexes();
    }

    public function setName( $name )
    {
        $this->name = underscore( $name );

        return $this;
    }
}