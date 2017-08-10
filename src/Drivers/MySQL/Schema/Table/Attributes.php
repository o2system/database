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

/**
 * Class Attributes
 *
 * @package O2System\Database\Datastructures\Table
 */
class Attributes
{
    public $engine = 'InnoDB';
    public $autoIncrement;
    public $characterSet = 'utf8';
    public $collate = 'utf8_general_ci';
    public $rowFormat = 'COMPACT';
    public $comment;

    public function setEngine( $engine )
    {
        if ( in_array( $engine, [ 'MyISAM', 'InnoDB' ], true ) ) {
            $this->engine = $engine;
        }

        return $this;
    }

    public function setAutoIncrement( $autoIncrement )
    {
        $this->autoIncrement = (int)$autoIncrement;

        return $this;
    }

    public function setCharacterSet( $characterSet )
    {
        $this->characterSet = $characterSet;

        return $this;
    }

    public function setCollate( $collate )
    {
        $this->collate = $collate;

        return $this;
    }

    public function setRowFormat( $rowFormat )
    {
        if ( in_array( $rowFormat, [ 'COMPACT', 'COMPRESSED', 'DEFAULT', 'DYNAMIC', 'FIXED', 'REDUNDANT' ], true ) ) {
            $this->rowFormat = $rowFormat;
        }

        return $this;
    }

    public function setComment( $comment )
    {
        $this->comment = trim( $comment );

        return $this;
    }
}