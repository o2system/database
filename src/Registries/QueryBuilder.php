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

namespace O2System\Database\Registries;

// ------------------------------------------------------------------------

/**
 * Class QueryBuilder
 *
 * @package O2System\Database\Registries
 */
class QueryBuilder
{
    /**
     * Record Table
     *
     * var string
     */
    public $table;

    /**
     * Record Table Aliases
     *
     * var string
     */
    public $tableAliases = [ ];

    /**
     * Record Union
     *
     * @var array
     */
    public $union = [ ];

    /**
     * Record Select Into
     *
     * var string
     */
    public $into;

    /**
     * Record Select
     *
     * @var array
     */
    public $select = [ ];

    /**
     * Record From
     *
     * @var array
     */
    public $from = [ ];

    /**
     * Record Join
     *
     * var array
     */
    public $join = [ ];

    /**
     * Record Where Clauses
     *
     * var array
     */
    public $where = [ ];

    /**
     * Record Or Where Clauses
     *
     * var array
     */
    public $orWhere = [ ];

    /**
     * Record Where Clauses
     *
     * var array
     */
    public $like = [ ];

    /**
     * Record Or Where Clauses
     *
     * var array
     */
    public $orLike = [ ];

    /**
     * Record Where Having Clauses
     *
     * var array
     */
    public $having = [ ];

    /**
     * Record Group By
     *
     * var array
     */
    public $groupBy = [ ];

    /**
     * Record Group By
     *
     * var array
     */
    public $orderBy = [ ];

    /**
     * Record Limit
     *
     * @var bool|int
     */
    public $limit = false;

    /**
     * Record Offset
     *
     * @var bool|int
     */
    public $offset = false;

    /**
     * Record Sets
     *
     * @var array
     */
    public $sets = [ ];

    /**
     * Record Binds
     *
     * @var array
     */
    public $binds = [ ];

    /**
     * Record Distinct Flag
     *
     * @var bool
     */
    public $isDistinct = false;

    /**
     * Record Union All Flag
     *
     * @var bool
     */
    public $isUnionAll = false;

    /**
     * Is Grouped Flag
     *
     * @var bool
     */
    public $isGrouped = false;

    /**
     * SQL String
     *
     * @var string
     */
    public $SQLstring;

    /**
     * SQL String
     *
     * @var string
     */
    public $SQLCompiledString;

    //--------------------------------------------------------------------
}