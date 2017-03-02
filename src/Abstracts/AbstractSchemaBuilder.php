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

namespace O2System\Database\Abstracts;

// ------------------------------------------------------------------------

/**
 * Class AbstractSchemaBuilder
 *
 * @package O2System\Database\Abstracts
 */
abstract class AbstractSchemaBuilder
{
    /**
     * AbstractSchemaBuilder::$conn
     *
     * Query Builder database connection instance.
     *
     * @var AbstractConnection
     */
    protected $conn;

    // ------------------------------------------------------------------------

    /**
     * AbstractSchemaBuilder::__construct.
     *
     * @param \O2System\Database\Abstracts\AbstractConnection $conn
     */
    public function __construct ( AbstractConnection $conn )
    {
        $this->conn = $conn;
    }

    // ------------------------------------------------------------------------
}