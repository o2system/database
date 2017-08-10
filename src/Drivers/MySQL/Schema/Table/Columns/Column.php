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

namespace O2System\Database\Drivers\MySQL\Schema\Table\Columns;

// ------------------------------------------------------------------------

/**
 * Class Column
 *
 * @package O2System\Database\Drivers\MySQL\Schema\Table\Columns
 */
class Column
{
    public $name;
    public $type;
    public $length = 0;
    public $decimals = 0;
    public $notNull = false;
    public $primaryKey = false;
    public $default;
    public $comment;
    public $autoIncrement = false;
    public $unsigned = false;
    public $zerofill = false;

    public function __construct( $name )
    {
        $this->setName( $name );
    }

    public function setName( $name )
    {
        $this->name = underscore( $name );

        return $this;
    }
}