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

namespace O2System\Database\Sql\Datastructures;

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
            'select'        => [],
            'union'         => [],
            'unionAll'     => [],
            'into'          => false,
            'distinct'      => false,
            'from'          => [],
            'join'          => [],
            'where'         => [],
            'having'        => [],
            'between'       => [],
            'notBetween'   => [],
            'limit'         => false,
            'offset'        => false,
            'groupBy'       => [],
            'orderBy'       => [],
            'keys'          => [],
            'sets'          => [],
            'binds'         => [],
            'aliasedTables' => [],
            'noEscape'      => [],
            'bracketOpen'   => false,
            'bracketCount'  => 0,
        ];

    /**
     * QueryBuilderCache::$statement
     *
     * Query statement.
     *
     * @var string
     */
    protected $statement;

    // ------------------------------------------------------------------------

    public function &__get( $property )
    {
        return $this->storage[ $property ];
    }

    // ------------------------------------------------------------------------

    public function __set( $index, $value )
    {
       $this->storage[ $index ] = $value;
    }

    public function store( $index, $value )
    {
        if ( array_key_exists( $index, $this->storage ) ) {
            if ( is_array( $this->storage[ $index ] ) ) {
                array_push( $this->storage[ $index ], $value );
            } elseif ( is_bool( $this->storage[ $index ] ) ) {
                $this->storage[ $index ] = (bool)$value;
            } else {
                $this->storage[ $index ] = $value;
            }
        }

        return $this;
    }

    // ------------------------------------------------------------------------

    public function setStatement( $statement )
    {
        $this->statement = trim( $statement );
    }

    // ------------------------------------------------------------------------

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
                'select'        => [],
                'union'         => [],
                'unionAll'     => [],
                'into'          => false,
                'distinct'      => false,
                'from'          => [],
                'join'          => [],
                'where'         => [],
                'having'        => [],
                'between'       => [],
                'notBetween'   => [],
                'limit'         => false,
                'offset'        => false,
                'groupBy'       => [],
                'orderBy'       => [],
                'keys'          => [],
                'binds'         => [],
                'aliasedTables' => [],
                'noEscape'      => [],
                'bracketOpen'   => false,
                'bracketCount'  => 0,
            ]
        );
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
                'from'          => [],
                'binds'         => [],
                'sets'          => [],
                'join'          => [],
                'where'         => [],
                'having'        => [],
                'between'       => [],
                'not_between'   => [],
                'keys'          => [],
                'limit'         => false,
                'aliasedTables' => [],
                'noEscape'      => [],
                'bracketOpen'   => false,
                'bracketCount'  => 0,
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
    protected function resetRun( array $cacheKeys )
    {
        foreach ( $cacheKeys as $cacheKey => $cacheDefaultValue ) {
            $this->storage[ $cacheKey ] = $cacheDefaultValue;
        }

        $this->statement = null;
    }
}
