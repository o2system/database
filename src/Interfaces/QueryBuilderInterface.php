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

namespace O2System\Database\Interfaces;

// ------------------------------------------------------------------------

/**
 * Interface QueryBuilderInterface
 *
 * @package O2System\Database\Interfaces
 */
interface QueryBuilderInterface
{
    /**
     * QueryBuilderInterface::select
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
    public function select ( $field = '*', $escape = null );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::from
     *
     * Generates FROM SQL statement portions into Query Builder.
     *
     * @param string|array $table
     * @param bool         $overwrite Should we remove the first table existing?
     *
     * @return  static
     */
    public function from ( $table, $overwrite = false );

    public function union ( QueryBuilderInterface $select, $isUnionAll = false );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::into
     *
     * Add SELECT INTO SQL statement portions into Query Builder
     *
     * @param string      $table    Table name
     * @param string|null $database Other database name
     *
     * @return static
     */
    public function into ( $table, $database = null );

    /**
     * QueryBuilderInterface::first
     *
     * Add SELECT FIRST(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static|string
     */
    public function first ( $field, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::last
     *
     * Add SELECT LAST(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static|string
     */
    public function last ( $field, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::avg
     *
     * Add SELECT AVG(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static|string
     */
    public function avg ( $field, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::count
     *
     * Add SELECT COUNT(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static|string
     */
    public function count ( $field, $alias = '' );

    /**
     * QueryBuilderInterface::max
     *
     * Add SELECT MAX(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static|string
     */
    public function max ( $field, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::min
     *
     * Add SELECT MIN(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static|string
     */
    public function min ( $field, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::sum
     *
     * Add SELECT SUM(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static|string
     */
    public function sum ( $field, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::ucase
     *
     * Add SELECT UCASE(field) AS alias statement
     *
     * @see http://www.w3schools.com/sql/sql_func_ucase.asp
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static|string
     */
    public function ucase ( $field, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::lcase
     *
     * Add SELECT LCASE(field) AS alias statement
     *
     * @see http://www.w3schools.com/sql/sql_func_lcase.asp
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static|string
     */
    public function lcase ( $field, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::mid
     *
     * Add SELECT MID(field) AS alias statement
     *
     * @see http://www.w3schools.com/sql/sql_func_mid.asp
     *
     * @param string   $field             Required. The field to extract characters from
     * @param int      $start             Required. Specifies the starting position (starts at 1)
     * @param null|int $length            Optional. The number of characters to return. If omitted, the MID() function
     *                                    returns the rest of the text
     * @param string   $alias             Field alias
     * @param bool     $isReturnStatement Whether including into select active record or returning string
     *
     * @return static|string
     */
    public function mid ( $field, $start = 1, $length = null, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::len
     *
     * Add SELECT LEN(field) AS alias statement
     *
     * @see http://www.w3schools.com/sql/sql_func_len.asp
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static|string
     */
    public function len ( $field, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::round
     *
     * Add SELECT ROUND(field) AS alias statement
     *
     * @see http://www.w3schools.com/sql/sql_func_round.asp
     *
     * @param string $field    Required. The field to round.
     * @param int    $decimals Required. Specifies the number of decimals to be returned.
     * @param string $alias    Field alias
     *
     * @return static|string
     */
    public function round ( $field, $decimals = 0, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::format
     *
     * Add SELECT FORMAT(field, format) AS alias statement
     *
     * @see http://www.w3schools.com/sql/sql_func_format.asp
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static|string
     */
    public function format ( $field, $format, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::now
     *
     * Add / Create SELECT NOW() SQL statement
     *
     * @return static|string
     */
    public function now ();

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::date
     *
     * Add / Create SELECT DATE(field) AS alias SQL statement
     *
     * @see http://www.w3schools.com/sql/func_date.asp
     *
     * @param string $field Field name
     * @param string $alias Field name alias
     *
     * @return static|string
     */
    public function date ( $field, $alias = null );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::extract
     *
     * Add / Create SELECT EXTRACT(unit FROM field) AS alias SQL statement
     *
     * @see http://www.w3schools.com/sql/func_extract.asp
     *
     * @param string $field Field name
     * @param string $unit  UPPERCASE unit value
     * @param string $alias Alias field name.
     *
     * @return static|string
     */
    public function dateExtract ( $field, $unit, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::dateAdd
     *
     * Add / Create SELECT DATE_ADD(field, INTERVAL expression type) AS alias SQL statement
     *
     * @see http://www.w3schools.com/sql/func_date.asp
     *
     * @param string $field    Field name
     * @param string $interval Number of interval expression
     * @param string $alias    Field alias
     *
     * @return string|static
     * @throws \O2System\Kernel\Spl\Exceptions\Logic\InvalidArgumentException
     */
    public function dateAdd ( $field, $interval, $alias = '' );

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::date_sub
     *
     * Add / Create SELECT DATE_SUB(field, INTERVAL expression type) AS alias SQL statement
     *
     * @see http://www.w3schools.com/sql/func_date.asp
     *
     * @param string      $field      Field name
     * @param int         $expression Number of interval expression
     * @param string|null $type       UPPERCASE interval expression type
     *
     * @return static|string
     */
    public function dateSub ( $field, $expression, $type = null );

    /**
     * DATEDIFF
     *
     * Add / Create SELECT DATEDIFF(datetime_start, datetime_end) AS alias SQL statement
     *
     * @see http://www.w3schools.com/sql/func_datediff_mysql.asp
     *
     * @param array       $fields [datetime_start => datetime_end]
     * @param string|null $alias  Field alias
     *
     * @return static|string
     */
    public function dateDiff ( array $fields, $alias );

    /**
     * QueryBuilderInterface::distinct
     *
     * Sets a flag which tells the query string compiler to add DISTINCT
     * keyword on SELECT statement
     *
     * @param    bool $distinct
     *
     * @return    static
     */
    public function distinct ( $distinct = true );

    /**
     * QueryBuilderInterface::join
     *
     * Add JOIN SQL statement portions into Query Builder
     *
     * @param string    $table     Table name
     * @param null      $condition Join conditiions: table.column = other_table.column
     * @param string    $type      UPPERCASE join type LEFT|LEFT_OUTER|RIGHT|RIGHT_OUTER|INNER|OUTER|FULL|JOIN
     * @param null|bool $escape    Whether not to try to escape identifiers
     *
     * @return static
     */
    public function join ( $table, $condition = null, $type = 'LEFT', $escape = null );

    /**
     * QueryBuilderInterface::where
     *
     * Add WHERE SQL statement portions into Query Builder
     *
     * @param string|array $field Field name, array of [field => value] (grouped where)
     * @param null|string  $value Field criteria or UPPERCASE grouped type AND|OR
     *
     * @return static
     */
    public function where ( $field, $value = null );

    /**
     * OR WHERE
     *
     * Add OR WHERE SQL statement portions into Query Builder
     *
     * @param string|array $field Field name, array of [field => value] (grouped where)
     * @param null|string  $value Field criteria or UPPERCASE grouped type AND|OR
     *
     * @return static
     */
    public function orWhere ( $field, $value = null );

    /**
     * HAVING
     *
     * Separates multiple calls with 'AND'.
     *
     * @param    string $field
     * @param    string $value
     * @param    bool   $escape
     *
     * @return    static
     */
    public function having ( $field, $value = null, $escape = null );

    /**
     * OR HAVING
     *
     * Separates multiple calls with 'OR'.
     *
     * @param    string $field
     * @param    string $value
     * @param    bool   $escape
     *
     * @return    static
     */
    public function orHaving ( $field, $value = null, $escape = null );

    /**
     * QueryBuilderInterface::whereIn
     *
     * Add WHERE IN SQL statement portions into Query Builder
     *
     * @param string $field  Field name
     * @param array  $values Array of values criteria
     *
     * @return static
     */
    public function whereIn ( $field, $values = [ ] );

    /**
     * QueryBuilderInterface::orWhereIn
     *
     * Add OR WHERE IN SQL statement portions into Query Builder
     *
     * @param string $field  Field name
     * @param array  $values Array of values criteria
     *
     * @return static
     */
    public function orWhereIn ( $field, $values = [ ] );

    /**
     * QueryBuilderInterface::whereNotIn
     *
     * Add WHERE NOT IN SQL statement portions into Query Builder
     *
     * @param string $field  Field name
     * @param array  $values Array of values criteria
     *
     * @return static
     */
    public function whereNotIn ( $field, $values = [ ] );

    /**
     * QueryBuilderInterface::orWhereNotIn
     *
     * Add OR WHERE NOT IN SQL statement portions into Query Builder
     *
     * @param string $field  Field name
     * @param array  $values Array of values criteria
     *
     * @return static
     */
    public function orWhereNotIn ( $field, $values = [ ] );

    /**
     * QueryBuilderInterface::whereBetween
     *
     * Add WHERE BETWEEN SQL statement portions into Query Builder
     *
     * @param string $field  Field name
     * @param array  $values Array of between values
     *
     * @return static
     */
    public function whereBetween ( $field, array $values = [ ] );

    /**
     * QueryBuilderInterface::orWhereBetween
     *
     * Add OR WHERE BETWEEN SQL statement portions into Query Builder
     *
     * @param string $field  Field name
     * @param array  $values Array of between values
     *
     * @return static
     */
    public function orWhereBetween ( $field, array $values = [ ] );

    /**
     * QueryBuilderInterface::whereNotBetween
     *
     * Add WHERE NOT BETWEEN SQL statement portions into Query Builder
     *
     * @param string $field  Field name
     * @param array  $values Array of between values
     *
     * @return static
     */
    public function whereNotBetween ( $field, array $values = [ ] );

    /**
     * QueryBuilderInterface::orWhereNotBetween
     *
     * Add OR WHERE NOT BETWEEN SQL statement portions into Query Builder
     *
     * @param string $field  Field name
     * @param array  $values Array of between values
     *
     * @return static
     */
    public function orWhereNotBetween ( $field, array $values = [ ] );

    /**
     * QueryBuilderInterface::like
     *
     * Generates a %LIKE% SQL statement portions of the query.
     * Separates multiple calls with 'AND'.
     *
     * @param string    $field         Field name
     * @param string    $match         Field criteria match
     * @param string    $wildcard      UPPERCASE positions of wildcard character BOTH|LEFT|RIGHT
     * @param bool      $caseSensitive Whether perform case sensitive LIKE or not
     * @param null|bool $escape        Whether not to try to escape identifiers
     *
     * @return static
     */
    public function like ( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true, $escape = null );

    /**
     * QueryBuilderInterface::orLike
     *
     * Add OR LIKE SQL statement portions into Query Builder
     *
     * @param string    $field         Field name
     * @param string    $match         Field criteria match
     * @param string    $wildcard      UPPERCASE positions of wildcard character BOTH|LEFT|RIGHT
     * @param bool      $caseSensitive Whether perform case sensitive LIKE or not
     * @param null|bool $escape        Whether not to try to escape identifiers
     *
     * @return static
     */
    public function orLike ( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true, $escape = null );

    /**
     * QueryBuilderInterface::notLike
     *
     * Add NOT LIKE SQL statement portions into Query Builder
     *
     * @param string    $field         Field name
     * @param string    $match         Field criteria match
     * @param string    $wildcard      UPPERCASE positions of wildcard character BOTH|LEFT|RIGHT
     * @param bool      $caseSensitive Whether perform case sensitive LIKE or not
     * @param null|bool $escape        Whether not to try to escape identifiers
     *
     * @return static
     */
    public function notLike ( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true, $escape = null );

    /**
     * QueryBuilderInterface::orNotLike
     *
     * Add OR NOT LIKE SQL statement portions into Query Builder
     *
     * @param string    $field         Field name
     * @param string    $match         Field criteria match
     * @param string    $wildcard      UPPERCASE positions of wildcard character BOTH|LEFT|RIGHT
     * @param bool      $caseSensitive Whether perform case sensitive LIKE or not
     * @param null|bool $escape        Whether not to try to escape identifiers
     *
     * @return static
     */
    public function orNotLike ( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true, $escape = null );

    /**
     * QueryBuilderInterface::offset
     *
     * Add OFFSET SQL statement into Query Builder.
     *
     * @param    int $offset OFFSET value
     *
     * @return    static
     */
    public function offset ( $offset );

    /**
     * QueryBuilderInterface::limit
     *
     * Add LIMIT,OFFSET SQL statement into Query Builder.
     *
     * @param    int $limit  LIMIT value
     * @param    int $offset OFFSET value
     *
     * @return    static
     */
    public function limit ( $limit, $offset = null );

    /**
     * Page
     *
     * Auto Set LIMIT, OFFSET SQL statement by page number and entries.
     *
     * @param int  $page    Page number
     * @param null $entries Num entries of each page
     *
     * @return static
     */
    public function page ( $page = 1, $entries = null );

    /**
     * QueryBuilderInterface::groupBy
     *
     * Add GROUP BY SQL statement into Query Builder.
     *
     * @param $field
     *
     * @return $this
     */
    public function groupBy ( $field );

    /**
     * QueryBuilderInterface::orderBy
     *
     * Add ORDER BY SQL statement portions into Query Builder.
     *
     * @param        $field
     * @param string $direction
     *
     * @return $this
     */
    public function orderBy ( $field, $direction = 'ASC' );

    /**
     * QueryBuilderInterface::get
     *
     * Perform execution of SQL Query Builder and run ConnectionInterface::query()
     *
     * @param null|int $limit
     * @param null|int $offset
     *
     * @return mixed
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function get ( $limit = null, $offset = null );

    /**
     * QueryBuilderInterface::getWhere
     *
     * Perform execution of SQL Query Builder and run ConnectionInterface::query()
     *
     * @param array    $where
     * @param null|int $limit
     * @param null|int $offset
     *
     * @return mixed
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function getWhere ( array $where = [ ], $limit = null, $offset = null );

    /**
     * QueryBuilderInterface::countAll
     *
     * Perform execution of count all records of a table.
     *
     * @access  public
     * @return int|string
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function countAll ();

    /**
     * QueryBuilderInterface::countAllResult
     *
     * Perform execution of count all result from Query Builder along with WHERE, LIKE, HAVING, GROUP BY, and LIMIT SQL
     * statement.
     *
     * @param bool $reset Whether perform reset Query Builder or not
     *
     * @return int
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     * @internal param string $table Table name
     * @access   public
     */
    public function countAllResults ( $reset = true );

    /**
     * QueryBuilderInterface::insert
     *
     * Execute INSERT SQL Query
     *
     * @param array $sets
     *
     * @return bool
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function insert ( array $sets = [ ] );

    /**
     * QueryBuilderInterface::insertBatch
     *
     * Execute INSERT batch SQL Query
     *
     * @param array $sets      Array of data sets[][field => value]
     * @param int   $batchSize Maximum batch size
     *
     * @return bool
     */
    public function insertBatch ( array $sets, $batchSize = 1000 );

    /**
     * QueryBuilderInterface::update
     *
     * Execute UPDATE SQL Query
     *
     * @param array    $sets  Array of data sets[][field => value]
     * @param array    $where WHERE [field => match]
     * @param null|int $limit Limit
     *
     * @return bool
     */
    public function update ( array $sets, array $where = [ ], $limit = null );

    /**
     * QueryBuilderInterface::updateBatch
     *
     * Execute UPDATE batch SQL Query
     *
     * @param array  $sets      Array of data sets[][field => value]
     * @param string $index     Index field
     * @param int    $batchSize Maximum batch size
     *
     * @return bool
     */
    public function updateBatch ( array $sets, $index = null, $batchSize = 1000 );

    /**
     * QueryBuilderInterface::replace
     *
     * Execute REPLACE SQL Query
     *
     * @param array $sets Array of data sets [field => value]
     *
     * @return bool
     */
    public function replace ( array $sets );

    /**
     * QueryBuilderInterface::replaceBatch
     *
     * Execute REPLACE batch SQL Query
     *
     * @param string $table     Table Name
     * @param array  $sets      Array of data sets[][field => value]
     * @param int    $batchSize Maximum batch size
     *
     * @return bool
     */
    public function replaceBatch ( array $sets, $batchSize = 1000 );

    /**
     * delete
     *
     * Execute DELETE SQL Query
     *
     * @param null|array $where
     * @param null|int   $limit
     *
     * @return string
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function delete ( $where = null, $limit = null );

    /**
     * QueryBuilderInterface::deleteBatch
     *
     * Execute DELETE batch SQL Query
     *
     * @param array $where     WHERE (field => [match, ...])
     * @param int   $batchSize Maximum batch size
     *
     * @return bool
     */
    public function deleteBatch ( array $where, $batchSize = 1000 );

    public function bind ( $field, $value );

    public function binds ( array $binds );

    public function set ( $field, $value );

    public function sets ( array $fields );
}