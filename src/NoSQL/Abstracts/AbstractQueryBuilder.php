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

namespace O2System\Database\NoSQL\Abstracts;

// ------------------------------------------------------------------------

use O2System\Database\NoSQL\Datastructures\QueryBuilderCache;

/**
 * Class AbstractQueryBuilder
 *
 * @package O2System\Database\Abstracts
 */
abstract class AbstractQueryBuilder
{
    /**
     * AbstractQueryBuilder::testMode
     *
     * If true, no queries will actually be
     * ran against the database.
     *
     * @var bool
     */
    public $testMode = false;

    /**
     * AbstractQueryBuilder::$conn
     *
     * Query database connection instance.
     *
     * @var AbstractConnection
     */
    protected $conn;

    /**
     * AbstractQueryBuilder::$builderCache
     *
     * Query builder cache instance.
     *
     * @var \O2System\Database\NoSQL\Datastructures\QueryBuilderCache
     */
    protected $builderCache;

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::__construct.
     *
     * @param AbstractConnection $conn
     */
    public function __construct( AbstractConnection $conn )
    {
        $this->conn = $conn;
        $this->builderCache = new QueryBuilderCache();
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::select
     *
     * Add SELECT SQL statement portions into Query Builder.
     *
     * @param string|array $field        String of field name
     *                                   Array list of string field names
     *                                   Array list of static
     * @param null|bool    $escape       Whether not to try to escape identifiers
     *
     * @return static
     */
    public function select( $field )
    {
        if ( strpos( $field, ',' ) !== false ) {
            $field = explode( ', ', $field );
        } else {
            $field = [ $field ];
        }

        $field = array_map( 'trim', $field );

        foreach ( $field as $key ) {
            $this->builderCache->store( 'select', $key );
        }

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::collection
     *
     * @param string $collection Collection name.
     *
     * @return static
     */
    public function collection( $collection )
    {
        $this->from( $collection );

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::from
     *
     * Generates FROM SQL statement portions into Query Builder.
     *
     * @param string $collection Collection name
     *
     * @return  static
     */
    public function from( $collection )
    {
        $this->builderCache->store( 'from', trim( $collection ) );

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::join
     *
     * Add JOIN SQL statement portions into Query Builder.
     *
     * @param string $collection Collection name
     * @param null   $condition  Join conditions: table.column = other_table.column
     *
     * @return static
     */
    public function join( $collection, $condition = null )
    {

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::where
     *
     * Add WHERE SQL statement portions into Query Builder
     *
     * @param string|array $field Input name, array of [field => value] (grouped where)
     * @param null|string  $value Input criteria or UPPERCASE grouped type AND|OR
     *
     * @return static
     */
    public function where( $field, $value = null )
    {
        if ( is_array( $field ) ) {
            foreach ( $field as $key => $value ) {
                $this->builderCache->store( 'where', [ $key => $value ] );
            }
        } else {
            $this->builderCache->store( 'where', [ $field => $value ] );
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orWhere
     *
     * Add OR WHERE SQL statement portions into Query Builder
     *
     * @param string|array $field Input name, array of [field => value] (grouped where)
     * @param null|string  $value Input criteria or UPPERCASE grouped type AND|OR
     *
     * @return static
     */
    public function orWhere( $field, $value = null )
    {
        if ( is_array( $field ) ) {
            foreach ( $field as $key => $value ) {
                $this->builderCache->store( 'orWhere', [ $key => $value ] );
            }
        } else {
            $this->builderCache->store( 'orWhere', [ $field => $value ] );
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::having
     *
     * Separates multiple calls with 'AND'.
     *
     * @param    string $field
     * @param    string $value
     *
     * @return    static
     */
    public function having( $field, $value = null )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orHaving
     *
     * Separates multiple calls with 'OR'.
     *
     * @param    string $field
     * @param    string $value
     *
     * @return    static
     */
    public function orHaving( $field, $value = null )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::whereIn
     *
     * Add WHERE IN SQL statement portions into Query Builder
     *
     * @param string    $field  Input name
     * @param array     $values Array of values criteria
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function whereIn( $field, array $values = [] )
    {
        if( count( $values ) ) {
            $this->builderCache->store( 'whereIn', [ $field => $values ] );
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orWhereIn
     *
     * Add OR WHERE IN SQL statement portions into Query Builder
     *
     * @param string    $field  Input name
     * @param array     $values Array of values criteria
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function orWhereIn( $field, array $values = [] )
    {
        if( count( $values ) ) {
            $this->builderCache->store( 'orWhereIn', [ $field => $values ] );
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::whereNotIn
     *
     * Add WHERE NOT IN SQL statement portions into Query Builder
     *
     * @param string $field  Input name
     * @param array  $values Array of values criteria
     *
     * @return static
     */
    public function whereNotIn( $field, array $values = [] )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orWhereNotIn
     *
     * Add OR WHERE NOT IN SQL statement portions into Query Builder
     *
     * @param string $field  Input name
     * @param array  $values Array of values criteria
     *
     * @return static
     */
    public function orWhereNotIn( $field, array $values = [] )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::whereBetween
     *
     * Add WHERE BETWEEN SQL statement portions into Query Builder
     *
     * @param string $field  Input name
     * @param array  $values Array of between values
     *
     * @return static
     */
    public function whereBetween( $field, array $values = [] )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orWhereBetween
     *
     * Add OR WHERE BETWEEN SQL statement portions into Query Builder
     *
     * @param string $field  Input name
     * @param array  $values Array of between values
     *
     * @return static
     */
    public function orWhereBetween( $field, array $values = [] )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::whereNotBetween
     *
     * Add WHERE NOT BETWEEN SQL statement portions into Query Builder
     *
     * @param string $field  Input name
     * @param array  $values Array of between values
     *
     * @return static
     */
    public function whereNotBetween( $field, array $values = [] )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orWhereNotBetween
     *
     * Add OR WHERE NOT BETWEEN SQL statement portions into Query Builder
     *
     * @param string $field  Input name
     * @param array  $values Array of between values
     *
     * @return static
     */
    public function orWhereNotBetween( $field, array $values = [] )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::like
     *
     * Generates a %LIKE% SQL statement portions of the query.
     * Separates multiple calls with 'AND'.
     *
     * @param string $field         Input name
     * @param string $match         Input criteria match
     * @param string $wildcard      UPPERCASE positions of wildcard character BOTH|LEFT|RIGHT
     * @param bool   $caseSensitive Whether perform case sensitive LIKE or not
     *
     * @return static
     */
    public function like( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orLike
     *
     * Add OR LIKE SQL statement portions into Query Builder
     *
     * @param string $field         Input name
     * @param string $match         Input criteria match
     * @param string $wildcard      UPPERCASE positions of wildcard character BOTH|LEFT|RIGHT
     * @param bool   $caseSensitive Whether perform case sensitive LIKE or not
     *
     * @return static
     */
    public function orLike( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::notLike
     *
     * Add NOT LIKE SQL statement portions into Query Builder
     *
     * @param string $field         Input name
     * @param string $match         Input criteria match
     * @param string $wildcard      UPPERCASE positions of wildcard character BOTH|LEFT|RIGHT
     * @param bool   $caseSensitive Whether perform case sensitive LIKE or not
     *
     * @return static
     */
    public function notLike( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orNotLike
     *
     * Add OR NOT LIKE SQL statement portions into Query Builder
     *
     * @param string $field         Input name
     * @param string $match         Input criteria match
     * @param string $wildcard      UPPERCASE positions of wildcard character BOTH|LEFT|RIGHT
     * @param bool   $caseSensitive Whether perform case sensitive LIKE or not
     *
     * @return static
     */
    public function orNotLike( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::groupBy
     *
     * Add GROUP BY SQL statement into Query Builder.
     *
     * @param string    $field
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function groupBy( $field )
    {
        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orderBy
     *
     * Add ORDER BY SQL statement portions into Query Builder.
     *
     * @param string $field
     * @param string $direction
     *
     * @return static
     */
    public function orderBy( $field, $direction = 'ASC' )
    {
        $this->builderCache->store( 'orderBy', [ $field => strtoupper( $direction ) ] );

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::page
     *
     * Add Set LIMIT, OFFSET SQL statement by page number and entries.
     *
     * @param int  $page    Page number
     * @param null $entries Num entries of each page
     *
     * @return static
     */
    public function page( $page = 1, $entries = null )
    {
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::limit
     *
     * Add LIMIT,OFFSET SQL statement into Query Builder.
     *
     * @param    int $limit  LIMIT value
     * @param    int $offset OFFSET value
     *
     * @return    static
     */
    public function limit( $limit, $offset = 0 )
    {
        $this->builderCache->store( 'limit', $limit );
        $this->offset( $offset );

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::offset
     *
     * Add OFFSET SQL statement into Query Builder.
     *
     * @param    int $offset OFFSET value
     *
     * @return    static
     */
    public function offset( $offset )
    {
        $this->builderCache->store( 'offset', $offset );

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::get
     *
     * Perform execution of SQL Query Builder and run ConnectionInterface::query()
     *
     * @param null|int $limit
     * @param null|int $offset
     *
     * @return bool|\O2System\Database\DataObjects\Result
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function get( $limit = null, $offset = null )
    {
        if ( isset( $limit ) ) {
            $this->limit( $limit, $offset );
        }

        $result = $this->conn->query( $this->builderCache );

        if ( $result ) {
            $result->setTotalRows( $this->countAllResults( true ) );
        }

        return $result;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::getWhere
     *
     * Perform execution of SQL Query Builder and run ConnectionInterface::query()
     *
     * @param array    $where
     * @param null|int $limit
     * @param null|int $offset
     *
     * @return bool|\O2System\Database\DataObjects\Result
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function getWhere( array $where = [], $limit = null, $offset = null )
    {
        $this->where( $where );

        if ( isset( $limit ) ) {
            $this->limit( $limit, $offset );
        }

        $result = $this->conn->query( $this->builderCache );

        if ( $result ) {
            $result->setTotalRows( $this->countAllResults( true ) );
        }

        return $result;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::countAll
     *
     * Returns numbers of query result.
     *
     * @access  public
     * @return int|string
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    abstract public function countAll();

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::countAllResult
     *
     * Returns numbers of total documents.
     *
     * @param bool $reset Whether perform reset Query Builder or not
     *
     * @return int
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @access   public
     */
    abstract public function countAllResults( $reset = true );

    //--------------------------------------------------------------------

    public function push( array $sets )
    {

    }

    //--------------------------------------------------------------------

    public function pull()
    {

    }
}