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

namespace O2System\Database\Drivers\MySQL;

// ------------------------------------------------------------------------

use O2System\Database\Interfaces\QueryBuilderInterface;

/**
 * Class QueryBuilder
 *
 * @package O2System\Database\Drivers\MySQL
 */
class QueryBuilder extends Driver implements QueryBuilderInterface
{
    /**
     * Identifier escape character
     *
     * @var    string
     */
    protected $escapeCharacter = '`';
}