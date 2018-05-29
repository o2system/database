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

namespace O2System\Database\NoSql\Datastructures;

// ------------------------------------------------------------------------

/**
 * Class QueryBuilderCache
 *
 * @package O2System\Database\Sql\Datastructures
 */
class QueryBuilderCache
{
    /**
     * QueryBuilderCache::$storage
     *
     * Query builder cache.
     *
     * @var array
     */
    protected $storage
        = [
            'select'       => [],
            'from'         => null,
            'join'         => [],
            'where'        => [],
            'orWhere'      => [],
            'whereNot'     => [],
            'orWhereNot'   => [],
            'whereIn'      => [],
            'orWhereIn'    => [],
            'whereNotIn'   => [],
            'orWhereNotIn' => [],
            'having'       => [],
            'orHaving'     => [],
            'between'      => [],
            'orBetween'    => [],
            'notBetween'   => [],
            'orNotBetween' => [],
            'like'         => [],
            'notLike'      => [],
            'orLike'       => [],
            'orNotLike'    => [],
            'limit'        => 0,
            'offset'       => 0,
            'groupBy'      => [],
            'orderBy'      => [],
            'sets'         => [],
        ];

    // ------------------------------------------------------------------------

    /**
     * QueryBuilderCache::__get
     *
     *
     * @param  string $property
     *
     * @return array
     */
    public function &__get($property)
    {
        return $this->storage[ $property ];
    }

    // ------------------------------------------------------------------------

    /**
     * QueryBuilderCache::store
     *
     * @param  string|bool $index
     * @param  array|bool  $value
     *
     * @return array
     */
    public function store($index, $value)
    {
        if (array_key_exists($index, $this->storage)) {
            if (is_array($this->storage[ $index ])) {
                if (is_array($value)) {
                    $this->storage[ $index ] = array_merge($this->storage[ $index ], $value);
                } else {
                    array_push($this->storage[ $index ], $value);
                }
            } elseif (is_bool($this->storage[ $index ])) {
                $this->storage[ $index ] = (bool)$value;
            } else {
                $this->storage[ $index ] = $value;
            }
        }

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * QueryBuilderCache::setStatement
     *
     * Set Statement Query Builder cache
     *
     * @param string $statement
     */
    public function setStatement($statement)
    {
        $this->statement = trim($statement);
    }

    // ------------------------------------------------------------------------

    /**
     * QueryBuilderCache::getStatement
     *
     * Get Statement Query Builder cache
     * @return string
     */
    public function getStatement()
    {
        return $this->statement;
    }

    // ------------------------------------------------------------------------

    /**
     * QueryBuilderCache::reset
     *
     * Reset Query Builder cache.
     *
     * @return  static
     */
    public function reset()
    {
        $this->resetGetter();
        $this->resetModifier();

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * QueryBuilderCache::resetGetter
     *
     * Resets the query builder values.  Called by the get() function
     *
     * @return  void
     */
    public function resetGetter()
    {
        $this->resetRun(
            [
                'select'       => [],
                'from'         => null,
                'join'         => [],
                'where'        => [],
                'orWhere'      => [],
                'whereNot'     => [],
                'orWhereNot'   => [],
                'whereIn'      => [],
                'orWhereIn'    => [],
                'whereNotIn'   => [],
                'orWhereNotIn' => [],
                'having'       => [],
                'orHaving'     => [],
                'between'      => [],
                'orBetween'    => [],
                'notBetween'   => [],
                'orNotBetween' => [],
                'like'         => [],
                'notLike'      => [],
                'orLike'       => [],
                'orNotLike'    => [],
                'limit'        => 0,
                'offset'       => 0,
                'groupBy'      => [],
                'orderBy'      => [],
            ]
        );
    }

    // ------------------------------------------------------------------------

    /**
     * QueryBuilderCache::resetRun
     *
     * Resets the query builder values.  Called by the get() function
     *
     * @param   array $cacheKeys An array of fields to reset
     *
     * @return  void
     */
    protected function resetRun(array $cacheKeys)
    {
        foreach ($cacheKeys as $cacheKey => $cacheDefaultValue) {
            $this->storage[ $cacheKey ] = $cacheDefaultValue;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * QueryBuilderCache::resetModifier
     *
     * Resets the query builder "modifier" values.
     *
     * Called by the insert() update() insertBatch() updateBatch() and delete() functions
     *
     * @return  void
     */
    public function resetModifier()
    {
        $this->resetRun(
            [
                'from'         => null,
                'join'         => [],
                'where'        => [],
                'orWhere'      => [],
                'whereNot'     => [],
                'orWhereNot'   => [],
                'whereIn'      => [],
                'orWhereIn'    => [],
                'whereNotIn'   => [],
                'orWhereNotIn' => [],
                'having'       => [],
                'orHaving'     => [],
                'between'      => [],
                'orBetween'    => [],
                'notBetween'   => [],
                'orNotBetween' => [],
                'like'         => [],
                'notLike'      => [],
                'orLike'       => [],
                'orNotLike'    => [],
                'limit'        => 0,
                'sets'         => [],
            ]
        );
    }
}
