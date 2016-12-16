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

namespace O2System\Database\Traits;

// ------------------------------------------------------------------------

use O2System\Database\Datastructures\Result;
use O2System\Database\Interfaces\QueryBuilderInterface;

/**
 * Class QueryBuilderTrait
 *
 * @package O2System\Database\Traits
 */
trait QueryBuilderTrait
{
    public    $arrayObjectConvension = 'serialize';

    /**
     * Protect identifiers flag
     *
     * @var    bool
     */
    protected $protectIdentifiers = true;

    /**
     * SQL Aggregate Functions
     *
     * SQL aggregate functions return a single value, calculated from values in a column.
     *
     * @access  protected
     * @type array
     */
    protected $sqlAggregateFunctions
        = [
            'AVG'   => 'AVG(%s)', // Returns the average value
            'COUNT' => 'COUNT(%s)', // Returns the number of rows
            'FIRST' => 'FIRST(%s)', // Returns the first value
            'LAST'  => 'LAST(%s)', // Returns the largest value
            'MAX'   => 'MAX(%s)', // Returns the largest value
            'MIN'   => 'MIN(%s)', // Returns the smallest value
            'SUM'   => 'SUM(%s)' // Returns the sum
        ];

    /**
     * SQL Scalar functions
     *
     * SQL scalar functions return a single value, based on the input value.
     *
     * @access  protected
     * @type array
     */
    protected $sqlScalarFunctions
        = [
            'UCASE'  => 'UCASE(%s)', // Converts a field to uppercase
            'LCASE'  => 'LCASE(%s)', // Converts a field to lowercase
            'MID'    => 'MID(%s)', // Extract characters from a text field
            'LENGTH' => 'LENGTH(%s)', // Returns the length of a text field
            'ROUND'  => 'ROUND(%s, %s)', // Rounds a numeric field to the number of decimals specified
            'FORMAT' => 'FORMAT(%s, %s)' // Formats how a field is to be displayed
        ];

    /**
     * SQL Date Functions
     *
     * SQL aggregate functions return a single value, calculated from values in a column.
     *
     * @access  protected
     * @type array
     */
    protected $sqlDateFunctions
        = [
            'NOW'          => 'NOW()', // Returns the current date and time
            'CURRENT_DATE' => 'CURDATE()', // Returns the current date
            'CURRENT_TIME' => 'CURTIME()', // 	Returns the current time
            'DATE'         => 'DATE(%s)', // Extracts the date part of a date or date/time expression
            'DATE_EXTRACT' => 'EXTRACT(%s FROM %s)', // Returns a single part of a date/time
            'DATE_ADD'     => 'DATE_ADD(%s, INTERVAL %s)', // Adds a specified time interval to a date
            'DATE_SUB'     => 'DATE_SUB(%s, INTERVAL %s)', // Subtracts a specified time interval from a date
            'DATE_DIFF'    => 'DATEDIFF(%s, %s)', // 	Returns the number of days between two dates
            'DATE_FORMAT'  => 'DATE_FORMAT(%s, %s)' // Displays date/time data in different formats
        ];

    /**
     * SQL Statements
     *
     * @access  protected
     * @type array
     */
    protected $sqlStatements
        = [
            'SELECT'            => 'SELECT %s',
            'SELECT_AS'         => '%s AS %s',
            'SELECT_DISTINCT'   => 'SELECT DISTINCT %s',
            'SELECT_AGGREGATE'  => '%s AS %s',
            'UNION'             => 'UNION',
            'UNION_ALL'         => 'UNION ALL',
            'INTO'              => 'INTO %s',
            'INTO_IN'           => 'INTO %s IN %s',
            'FROM'              => 'FROM %s',
            'FROM_AS'           => '%s AS %s',
            'JOIN'              => '%s',
            'WHERE'             => 'WHERE %s',
            'INSERT'            => 'INSERT INTO %s(%s) VALUES(%s)',
            'INSERT_BATCH'      => 'INSERT INTO %s(%s) VALUES%s;',
            'INSERT_INTO'       => 'INSERT INTO %s(%s) %s',
            'UPDATE'            => 'UPDATE %s SET %s %s',
            'UPDATE_BATCH'      => "UPDATE %s SET %s \r\nWHERE %s IN(%s)",
            'UPDATE_BATCH_CASE' => "\r\n%s = CASE \r\n%s \r\n ELSE %s END",
            // field = CASE case_field [WHEN %s THEN %s] END
            'UPDATE_BATCH_WHEN' => '  WHEN %s = %s THEN %s',
            'DELETE'            => 'DELETE FROM %s %s',
            'REPLACE'           => 'REPLACE INTO %s(%s) VALUES(%s)',
            'REPLACE_BATCH'     => 'REPLACE INTO %s(%s) VALUES%s;',
            'LIMIT'             => 'LIMIT %s',
            'LIMIT_OFFSET'      => 'LIMIT %s,%s',
            'GROUP_BY'          => 'GROUP BY %s',
            'HAVING'            => 'HAVING %s',
            'ORDER_BY'          => 'ORDER BY %s',
            'TRUNCATE'          => 'TRUNCATE %s',
        ];

    /**
     * SQL DATE value types
     *
     * @type array
     */
    protected $sqlDateTypes
        = [
            'MICROSECOND',
            'SECOND',
            'MINUTE',
            'HOUR',
            'DAY',
            'WEEK',
            'MONTH',
            'QUARTER',
            'YEAR',
            'SECOND_MICROSECOND',
            'MINUTE_MICROSECOND',
            'MINUTE_SECOND',
            'HOUR_MICROSECOND',
            'HOUR_SECOND',
            'HOUR_MINUTE',
            'DAY_MICROSECOND',
            'DAY_SECOND',
            'DAY_MINUTE',
            'DAY_HOUR',
            'YEAR_MONTH',
        ];

    /**
     * ORDER BY random keyword
     *
     * @var    array
     */
    protected $sqlRandomKeywords     = [ 'RAND()', 'RAND(%d)' ];

    protected $queryBuilderCache
                                     = [
            'select'        => [ ],
            'into'          => false,
            'distinct'      => false,
            'from'          => [ ],
            'join'          => [ ],
            'where'         => [ ],
            'groupBy'       => [ ],
            'between'       => [ ],
            'having'        => [ ],
            'limit'         => false,
            'offset'        => false,
            'orderBy'       => [ ],
            'keys'          => [ ],
            'sets'          => [ ],
            'binds'         => [ ],
            'aliasedTables' => [ ],
            'noEscape'      => [ ],
            'bracketOpen'   => false,
            'bracketCount'  => 0,
        ];

    protected $isSubQueryMode        = false;

    public function table ( $table )
    {
        return $this->from( $table, true );
    }

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
    public function from ( $table, $overwrite = false )
    {
        if ( $overwrite === true ) {
            $this->queryBuilderCache[ 'from' ] = [ ];
            $this->queryBuilderCache[ 'aliasedTables' ] = [ ];
        }

        if ( is_string( $table ) ) {
            $table = explode( ',', $table );
        }

        foreach ( $table as $name ) {
            $name = trim( $name );

            // Extract any aliases that might exist. We use this information
            // in the protectIdentifiers to know whether to add a table prefix
            $this->trackAliases( $name );

            $this->queryBuilderCache[ 'from' ][] = $this->protectIdentifiers( $name, true, null, false );
        }

        return $this;
    }

    /**
     * Track Aliases
     *
     * Used to track SQL statements written with aliased tables.
     *
     * @param    string    The table to inspect
     *
     * @return    void
     */
    protected function trackAliases ( $table )
    {
        if ( is_array( $table ) ) {
            foreach ( $table as $name ) {
                $this->trackAliases( $name );
            }

            return;
        }

        // Does the string contain a comma?  If so, we need to separate
        // the string into discreet statements
        if ( strpos( $table, ',' ) !== false ) {
            $this->trackAliases( explode( ',', $table ) );

            return;
        }

        // if a table alias is used we can recognize it by a space
        if ( strpos( $table, ' ' ) !== false ) {
            // if the alias is written with the AS keyword, remove it
            $table = preg_replace( '/\s+AS\s+/i', ' ', $table );

            // Grab the alias
            $table = trim( strrchr( $table, ' ' ) );

            // Store the alias, if it doesn't already exist
            if ( ! in_array( $table, $this->queryBuilderCache[ 'aliasedTables' ] ) ) {
                $this->queryBuilderCache[ 'aliasedTables' ][] = $table;
            }
        }
    }

    public function subQuery ()
    {
        $subQuery = clone $this;
        $subQuery->resetQuery();
        $subQuery->connID = null;
        $subQuery->isSubQueryMode = true;

        return $subQuery;
    }

    /**
     * Reset Query Builder values.
     *
     * Publicly-visible method to reset the QB values.
     *
     * @return    static
     */
    public function resetQuery ()
    {
        $this->resetSelect();
        $this->resetWrite();

        return $this;
    }

    /**
     * Resets the query builder values.  Called by the get() function
     *
     * @return    void
     */
    protected function resetSelect ()
    {
        $this->resetRun(
            [
                'binds'         => [ ],
                'select'        => [ ],
                'from'          => [ ],
                'join'          => [ ],
                'where'         => [ ],
                'groupBy'       => [ ],
                'having'        => [ ],
                'orderBy'       => [ ],
                'aliasedTables' => [ ],
                'noEscape'      => [ ],
                'distinct'      => false,
                'limit'         => false,
                'offset'        => false,
            ]
        );
    }

    /**
     * Resets the query builder values.  Called by the get() function
     *
     * @param    array    An array of fields to reset
     *
     * @return    void
     */
    protected function resetRun ( array $cacheKeys )
    {
        foreach ( $cacheKeys as $cacheKey => $cacheDefaultValue ) {
            $this->queryBuilderCache[ $cacheKey ] = $cacheDefaultValue;
        }
    }

    /**
     * Resets the query builder "write" values.
     *
     * Called by the insert() update() insertBatch() updateBatch() and delete() functions
     *
     * @return    void
     */
    protected function resetWrite ()
    {
        $this->resetRun(
            [
                'sets'    => [ ],
                'join'    => [ ],
                'where'   => [ ],
                'orderBy' => [ ],
                'keys'    => [ ],
                'limit'   => false,
            ]
        );
    }

    /**
     * union
     *
     * @param \O2System\Database\Interfaces\QueryBuilderInterface $select
     * @param bool                                                $isUnionAll
     *
     * @return static
     */
    public function union ( QueryBuilderInterface $select, $isUnionAll = false )
    {
        // TODO: Implement union() method.
    }

    /**
     * QueryBuilderInterface::into
     *
     * Add SELECT INTO SQL statement portions into Query Builder
     *
     * @param string      $tableName    Table name
     * @param string|null $databaseName Other database name
     *
     * @return static
     */
    public function into ( $tableName, $databaseName = null )
    {
        $this->queryBuilderCache[ 'into' ] = $this->protectIdentifiers(
            $tableName
        ) . empty( $databaseName )
            ? ''
            : ' IN ' . $this->escape( $databaseName );

        return $this;
    }

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
    public function first ( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'FIRST' );
    }

    /**
     * QueryBuilderInterface::prepareAggregateStatement
     *
     * Prepare string of SQL Aggregate Functions statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     * @param string $type  AVG|COUNT|FIRST|LAST|MAX|MIN|SUM
     *
     * @return string|static
     */
    protected function prepareAggregateStatement ( $field = '', $alias = '', $type = '' )
    {
        if ( array_key_exists( $type, $this->sqlAggregateFunctions ) ) {
            if ( $field !== '*' && $this->protectIdentifiers ) {
                $field = $this->protectIdentifiers( $field );
            }

            $alias = empty( $alias )
                ? strtolower( $type ) . '_' . $field
                : $alias;
            $sqlStatement = sprintf( $this->sqlAggregateFunctions[ $type ], $field )
                            . ' AS '
                            . $this->escapeIdentifiers( $alias );

            $this->select( $sqlStatement );
        }

        return $this;
    }

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
    public function select ( $field = '*', $escape = null )
    {
        // If the escape value was not set, we will base it on the global setting
        is_bool( $escape ) || $escape = $this->protectIdentifiers;

        if ( is_string( $field ) ) {
            $field = str_replace( ' as ', ' AS ', $field );

            if ( strpos( $field, '+' ) !== false || strpos( $field, '(' ) !== false ) {
                $field = [ $field ];
            } else {
                $field = explode( ',', $field );
            }

            foreach ( $field as $name ) {
                $name = trim( $name );

                $this->queryBuilderCache[ 'select' ][] = $name;
                $this->queryBuilderCache[ 'noEscape' ][] = $escape;
            }
        } elseif ( is_array( $field ) ) {
            foreach ( $field as $fieldName => $fieldAlias ) {
                if ( is_numeric( $fieldName ) ) {
                    $fieldName = $fieldAlias;
                } elseif ( is_string( $fieldName ) ) {
                    if ( is_string( $fieldAlias ) ) {
                        $fieldName = $fieldName . ' AS ' . $fieldAlias;
                    } elseif ( is_array( $fieldAlias ) ) {
                        $countFieldAlias = count( $fieldAlias );

                        for ( $i = 0; $i < $countFieldAlias; $i++ ) {
                            if ( $i == 0 ) {
                                $fieldAlias[ $i ] = $fieldAlias[ $i ] . "'+";
                            } elseif ( $i == ( $countFieldAlias - 1 ) ) {
                                $fieldAlias[ $i ] = "'+" . $fieldAlias[ $i ];
                            } else {
                                $fieldAlias[ $i ] = "'+" . $fieldAlias[ $i ] . "'+";
                            }
                        }

                        $fieldName = implode( ', ', $fieldAlias ) . ' AS ' . $fieldName;
                    } elseif ( $fieldAlias instanceof QueryBuilderInterface ) {
                        $fieldName = '( ' . $fieldAlias->getSqlStatement() . ' ) AS ' . $fieldName;
                    }
                }

                $this->select( $fieldName, $escape );
            }
        }


        return $this;
    }

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
    public function last ( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'LAST' );
    }

    // ------------------------------------------------------------------------

    /**
     * QueryBuilderInterface::avg
     *
     * Add SELECT AVG(field) AS alias statement
     *
     * @param string $field             Field name
     * @param string $alias             Field alias
     * @param bool   $isReturnStatement Whether including into select active record or returning string
     *
     * @return static|string
     */
    public function avg ( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'AVG' );
    }

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
    public function max ( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'MAX' );
    }

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
    public function min ( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'MIN' );
    }

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
    public function sum ( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'SUM' );
    }

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
    public function ucase ( $field, $alias = '' )
    {
        return $this->prepareScalarStatement( $field, $alias, 'UCASE' );
    }

    /**
     * QueryBuilderInterface::prepareScalarStatement
     *
     * Prepare string of SQL Scalar Functions statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     * @param string $type  UCASE|LCASE|MID|LEN|ROUND|FORMAT
     *
     * @return static|string
     */
    protected function prepareScalarStatement ( $field = '', $alias = '', $type = '' )
    {
        $alias = $alias === ''
            ? strtolower( $type ) . '_' . $field
            : $alias;

        if ( array_key_exists( $type, $this->sqlScalarFunctions ) ) {
            if ( $field !== '*' && $this->protectIdentifiers ) {
                $field = $this->protectIdentifiers( $field, true );
            }

            $sqlStatement = sprintf(
                $this->sqlScalarFunctions[ $type ],
                $field,
                $this->escapeIdentifiers( $alias )
            );

            $this->select( $sqlStatement );
        }

        return $this;
    }

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
    public function lcase ( $field, $alias = '' )
    {
        return $this->prepareScalarStatement( $field, $alias, 'LCASE' );
    }

    // ------------------------------------------------------------------------

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
     *
     * @return static|string
     */
    public function mid ( $field, $start = 1, $length = null, $alias = '' )
    {
        if ( array_key_exists( 'MID', $this->sqlScalarFunctions ) ) {
            $sqlStatement = $this->sqlScalarFunctions[ 'MID' ];

            if ( $this->protectIdentifiers ) {
                $field = $this->protectIdentifiers( $field, true );
            }

            $fields = [
                $field,
                $start,
            ];

            if ( isset( $length ) ) {
                array_push( $fields, intval( $length ) );
            }

            $alias = empty( $alias )
                ? 'mid_' . $field
                : $alias;

            $sqlStatement = sprintf( $sqlStatement, implode( ',', $fields ) )
                            . ' AS '
                            . $this->escapeIdentifiers( $alias );

            $this->select( $sqlStatement );
        }

        return $this;
    }

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
    public function len ( $field, $alias = '' )
    {
        return $this->prepareScalarStatement( $field, $alias, 'LENGTH' );
    }

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
    public function round ( $field, $decimals = 0, $alias = '' )
    {
        if ( array_key_exists( 'ROUND', $this->sqlScalarFunctions ) ) {
            $sqlStatement = $this->sqlScalarFunctions[ 'ROUND' ];

            $alias = empty( $alias )
                ? 'mid_' . $field
                : $alias;

            $sqlStatement = sprintf( $sqlStatement, $field, $decimals )
                            . ' AS '
                            . $this->escapeIdentifiers( $alias );

            $this->select( $sqlStatement );
        }

        return $this;
    }

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
    public function format ( $field, $format, $alias = '' )
    {
        if ( array_key_exists( 'FORMAT', $this->sqlScalarFunctions ) ) {
            $sqlStatement = $this->sqlScalarFunctions[ 'FORMAT' ];

            $alias = empty( $alias )
                ? 'mid_' . $field
                : $alias;

            $sqlStatement = sprintf( $sqlStatement, $field, $format )
                            . ' AS '
                            . $this->escapeIdentifiers( $alias );

            $this->select( $sqlStatement );
        }

        return $this;
    }

    /**
     * QueryBuilderInterface::now
     *
     * Add / Create SELECT NOW() SQL statement
     *
     * @return static|string
     */
    public function now ()
    {
        if ( isset( $this->sqlDateFunctions[ 'NOW' ] ) ) {
            $this->select( $this->sqlDateFunctions[ 'NOW' ] );
        }

        return $this;
    }

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
    public function dateExtract ( $field, $unit, $alias = '' )
    {
        $unit = strtoupper( $unit );

        if ( isset( $this->sqlDateFunctions[ 'DATE_EXTRACT' ] ) AND in_array( $unit, $this->sqlDateTypes ) ) {
            if ( is_array( $field ) ) {
                $fieldName = key( $field );
                $fieldAlias = $field[ $fieldName ];
            } elseif ( strpos( $field, ' AS ' ) !== false ) {
                $xField = explode( ' AS ', $field );
                $xField = array_map( 'trim', $xField );

                @list( $fieldName, $fieldAlias ) = $xField;
            } elseif ( strpos( $field, ' as ' ) !== false ) {
                $xField = explode( ' as ', $field );
                $xField = array_map( 'trim', $xField );

                @list( $fieldName, $fieldAlias ) = $xField;
            }

            if ( strpos( $fieldName, '.' ) !== false AND empty( $fieldAlias ) ) {
                $xFieldName = explode( '.', $fieldName );
                $xFieldName = array_map( 'trim', $xFieldName );

                $fieldAlias = end( $xFieldName );
            }

            $sqlStatement = sprintf(
                                $this->sqlDateFunctions[ 'DATE_EXTRACT' ],
                                $unit,
                                $this->protectIdentifiers( $fieldName )
                            ) . ' AS ' . $this->escapeIdentifiers( $fieldAlias );

            $this->queryBuilderCache[ 'select' ][ $sqlStatement ] = $sqlStatement;
        }

        return $this;
    }

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
    public function date ( $field, $alias = '' )
    {
        if ( array_key_exists( 'DATE', $this->sqlDateFunctions ) ) {
            $sqlStatement = $this->sqlDateFunctions[ 'DATE' ];

            $alias = empty( $alias )
                ? 'mid_' . $field
                : $alias;
            $sqlStatement = sprintf( $sqlStatement, $field )
                            . ' AS '
                            . $this->escapeIdentifiers( $alias );

            $this->select( $sqlStatement );
        }

        return $this;
    }

    // ------------------------------------------------------------------------

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
     */
    public function dateAdd ( $field, $interval, $alias = '' )
    {
        if ( $this->hasDateType( $interval ) ) {
            if ( array_key_exists( 'DATE_ADD', $this->sqlDateFunctions ) ) {
                $sqlStatement = $this->sqlDateFunctions[ 'DATE_ADD' ];

                $alias = empty( $alias )
                    ? 'date_add_' . $field
                    : $alias;
                $sqlStatement = sprintf( $sqlStatement, $field, $interval )
                                . ' AS '
                                . $this->escapeIdentifiers( $alias );

                $this->select( $sqlStatement );
            }
        }

        return $this;
    }

    /**
     * Has Date Type
     *
     * Validate whether the string has an SQL Date unit type
     *
     * @param $string
     *
     * @return bool
     */
    protected function hasDateType ( $string )
    {
        return (bool) preg_match(
            '/(' . implode( '|\s', $this->sqlDateTypes ) . '\s*\(|\s)/i',
            trim( $string )
        );
    }

    /**
     * QueryBuilderInterface::date_sub
     *
     * Add / Create SELECT DATE_SUB(field, INTERVAL expression type) AS alias SQL statement
     *
     * @see http://www.w3schools.com/sql/func_date.asp
     *
     * @param string $field    Field name
     * @param string $interval Number of interval expression
     * @param string $alias    Field alias
     *
     * @return static|string
     */
    public function dateSub ( $field, $interval, $alias = '' )
    {
        if ( $this->hasDateType( $interval ) ) {
            if ( array_key_exists( 'DATE_SUB', $this->sqlDateFunctions ) ) {
                $sqlStatement = $this->sqlDateFunctions[ 'DATE_SUB' ];

                $alias = empty( $alias )
                    ? 'date_sub_' . $field
                    : $alias;
                $sqlStatement = sprintf( $sqlStatement, $field, $interval )
                                . ' AS '
                                . $this->escapeIdentifiers( $alias );

                $this->select( $sqlStatement );
            }
        }

        return $this;
    }

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
    public function dateDiff ( array $fields, $alias )
    {
        if ( isset( $this->sqlDateFunctions[ 'DATE_DIFF' ] ) ) {
            $dateTimeStart = key( $fields );
            $dateTimeEnd = $fields[ $dateTimeStart ];

            if ( preg_match( "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $dateTimeStart ) ) {
                $dateTimeStart = $this->escape( $dateTimeStart );
            } elseif ( $this->protectIdentifiers ) {
                $dateTimeStart = $this->protectIdentifiers( $dateTimeStart );
            }

            if ( preg_match( "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $dateTimeEnd ) ) {
                $dateTimeEnd = $this->escape( $dateTimeEnd );
            } elseif ( $this->protectIdentifiers ) {
                $dateTimeEnd = $this->protectIdentifiers( $dateTimeEnd );
            }

            $sqlStatement = sprintf(
                                $this->sqlDateFunctions[ 'DATE_DIFF' ],
                                $dateTimeStart,
                                $dateTimeEnd
                            )
                            . ' AS '
                            . $this->escapeIdentifiers( $alias );

            $this->select( $sqlStatement );
        }

        return $this;
    }

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
    public function distinct ( $distinct = true )
    {
        $this->queryBuilderCache[ 'distinct' ] = is_bool( $distinct )
            ? $distinct
            : true;

        return $this;
    }

    //--------------------------------------------------------------------

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
    public function join ( $table, $condition = null, $type = 'LEFT', $escape = null )
    {
        if ( $type !== '' ) {
            $type = strtoupper( trim( $type ) );

            if ( ! in_array( $type, [ 'LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER' ], true ) ) {
                $type = '';
            } else {
                $type .= ' ';
            }
        }

        // Extract any aliases that might exist. We use this information
        // in the protectIdentifiers to know whether to add a table prefix
        $this->trackAliases( $table );

        is_bool( $escape ) || $escape = $this->protectIdentifiers;

        if ( ! $this->hasOperator( $condition ) ) {
            $condition = ' USING (' . ( $escape
                    ? $this->escapeIdentifiers( $condition )
                    : $condition ) . ')';
        } elseif ( $escape === false ) {
            $condition = ' ON ' . $condition;
        } else {
            // Split multiple conditions
            if ( preg_match_all( '/\sAND\s|\sOR\s/i', $condition, $joints, PREG_OFFSET_CAPTURE ) ) {
                $conditions = [ ];
                $joints = $joints[ 0 ];
                array_unshift( $joints, [ '', 0 ] );

                for ( $i = count( $joints ) - 1, $pos = strlen( $condition ); $i >= 0; $i-- ) {
                    $joints[ $i ][ 1 ] += strlen( $joints[ $i ][ 0 ] ); // offset
                    $conditions[ $i ] = substr( $condition, $joints[ $i ][ 1 ], $pos - $joints[ $i ][ 1 ] );
                    $pos = $joints[ $i ][ 1 ] - strlen( $joints[ $i ][ 0 ] );
                    $joints[ $i ] = $joints[ $i ][ 0 ];
                }
            } else {
                $conditions = [ $condition ];
                $joints = [ '' ];
            }

            $condition = ' ON ';
            for ( $i = 0, $c = count( $conditions ); $i < $c; $i++ ) {
                $operator = $this->getOperator( $conditions[ $i ] );
                $condition .= $joints[ $i ];
                $condition .= preg_match(
                    "/(\(*)?([\[\]\w\.'-]+)" . preg_quote( $operator ) . "(.*)/i",
                    $conditions[ $i ],
                    $match
                )
                    ? $match[ 1 ] . $this->protectIdentifiers(
                        $match[ 2 ]
                    ) . $operator . $this->protectIdentifiers( $match[ 3 ] )
                    : $conditions[ $i ];
            }
        }

        // Do we want to escape the table name?
        if ( $escape === true ) {
            $table = $this->protectIdentifiers( $table, true, null, false );
        }

        // Assemble the JOIN statement
        $this->queryBuilderCache[ 'join' ][] = $join = $type . 'JOIN ' . $table . $condition;

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * Tests whether the string has an SQL operator
     *
     * @param    string
     *
     * @return    bool
     */
    protected function hasOperator ( $string )
    {
        return (bool) preg_match(
            '/(<|>|!|=|\sIS NULL|\sIS NOT NULL|\sEXISTS|\sBETWEEN|\sLIKE|\sIN\s*\(|\s)/i',
            trim( $string )
        );
    }

    //--------------------------------------------------------------------

    /**
     * Returns the SQL string operator
     *
     * @param    string
     *
     * @return    string
     */
    protected function getOperator ( $string )
    {
        static $operator;

        if ( empty( $operator ) ) {
            $likeEscapeString = ( $this->likeEscapeString !== '' )
                ? '\s+' . preg_quote( trim( sprintf( $this->likeEscapeString, $this->likeEscapeCharacter ) ), '/' )
                : '';
            $operator = [
                '\s*(?:<|>|!)?=\s*',             // =, <=, >=, !=
                '\s*<>?\s*',                     // <, <>
                '\s*>\s*',                       // >
                '\s+IS NULL',                    // IS NULL
                '\s+IS NOT NULL',                // IS NOT NULL
                '\s+EXISTS\s*\(.*\)',        // EXISTS(sql)
                '\s+NOT EXISTS\s*\(.*\)',    // NOT EXISTS(sql)
                '\s+BETWEEN\s+',                 // BETWEEN value AND value
                '\s+IN\s*\(.*\)',            // IN(list)
                '\s+NOT IN\s*\(.*\)',        // NOT IN (list)
                '\s+LIKE\s+\S.*(' . $likeEscapeString . ')?',    // LIKE 'expr'[ ESCAPE '%s']
                '\s+NOT LIKE\s+\S.*(' . $likeEscapeString . ')?' // NOT LIKE 'expr'[ ESCAPE '%s']
            ];
        }

        return preg_match( '/' . implode( '|', $operator ) . '/i', $string, $match )
            ? $match[ 0 ]
            : false;
    }

    /**
     * OR WHERE
     *
     * Add OR WHERE SQL statement portions into Query Builder
     *
     * @param string|array $field  Field name, array of [field => value] (grouped where)
     * @param null|string  $value  Field criteria or UPPERCASE grouped type AND|OR
     * @param null|bool    $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function orWhere ( $field, $value = null, $escape = null )
    {
        return $this->prepareWhereHavingStatement( $field, $value, 'OR ', $escape, 'where' );
    }

    /**
     * WHERE, HAVING
     *
     * @used-by    where()
     * @used-by    orWhere()
     * @used-by    having()
     * @used-by    orHaving()
     *
     * @param    string $cacheKey 'QBWhere' or 'QBHaving'
     * @param    mixed  $field
     * @param    mixed  $value
     * @param    string $type
     * @param null|bool $escape   Whether not to try to escape identifiers
     *
     * @return    static
     */
    protected function prepareWhereHavingStatement ( $field, $value = null, $type = 'AND ', $escape = null, $cacheKey )
    {
        if ( ! is_array( $field ) ) {
            $field = [ $field => $value ];
        }

        // If the escape value was not set will base it on the global setting
        is_bool( $escape ) || $escape = $this->protectIdentifiers;

        foreach ( $field as $fieldName => $fieldValue ) {
            $prefix = ( count( $this->queryBuilderCache[ $cacheKey ] ) === 0 )
                ? $this->getBracketType( '' )
                : $this->getBracketType( $type );

            if ( $fieldValue !== null ) {
                $operator = $this->getOperator( $fieldName );
                $fieldName = trim( str_replace( $operator, '', $fieldName ) );

                $fieldBind = $this->bind( $fieldName, $fieldValue );

                if ( empty( $operator ) ) {
                    $fieldName .= ' =';
                } else {
                    $fieldName .= $operator;
                }
            } elseif ( ! $this->hasOperator( $fieldName ) ) {
                // value appears not to have been set, assign the test to IS NULL
                $fieldName .= ' IS NULL';
            } elseif ( preg_match( '/\s*(!?=|<>|IS(?:\s+NOT)?)\s*$/i', $fieldName, $match, PREG_OFFSET_CAPTURE ) ) {
                $fieldName = substr(
                                 $fieldName,
                                 0,
                                 $match[ 0 ][ 1 ]
                             ) . ( $match[ 1 ][ 0 ] === '='
                        ? ' IS NULL'
                        : ' IS NOT NULL' );
            } elseif ( $fieldValue instanceof QueryBuilderInterface ) {
                $fieldValue = $fieldValue->getSqlStatement();
            }

            $fieldValue = ! is_null( $fieldValue )
                ? ' :' . $fieldBind
                : $fieldValue;

            $this->queryBuilderCache[ $cacheKey ][] = [
                'condition' => $prefix . $fieldName . $fieldValue,
                'escape'    => $escape,
            ];
        }

        return $this;
    }

    /**
     * Group_get_type
     *
     * @used-by    bracketOpen()
     * @used-by    prepareLikeStatement()
     * @used-by    whereHaving()
     * @used-by    prepareWhereInStatement()
     *
     * @param    string $type
     *
     * @return    string
     */
    protected function getBracketType ( $type )
    {
        if ( $this->queryBuilderCache[ 'bracketOpen' ] ) {
            $type = '';
            $this->queryBuilderCache[ 'bracketOpen' ] = false;
        }

        return $type;
    }

    public function bind ( $field, $value )
    {
        if ( ! array_key_exists( $field, $this->queryBuilderCache[ 'binds' ] ) ) {
            $this->queryBuilderCache[ 'binds' ][ $field ] = $value;

            return $field;
        }

        $count = 0;

        while ( array_key_exists( $field . $count, $this->queryBuilderCache[ 'binds' ] ) ) {
            ++$count;
        }

        $this->queryBuilderCache[ 'binds' ][ $field . '_' . $count ] = $value;

        return $field . '_' . $count;
    }

    //--------------------------------------------------------------------

    /**
     * HAVING
     *
     * Separates multiple calls with 'AND'.
     *
     * @param    string $field
     * @param    string $value
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return    static
     */
    public function having ( $field, $value = null, $escape = null )
    {
        return $this->prepareWhereHavingStatement( $field, $value, 'AND ', $escape, 'having' );
    }

    /**
     * OR HAVING
     *
     * Separates multiple calls with 'OR'.
     *
     * @param    string $field
     * @param    string $value
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return    static
     */
    public function orHaving ( $field, $value = null, $escape = null )
    {
        return $this->prepareWhereHavingStatement( $field, $value, 'OR ', $escape, 'having' );
    }

    /**
     * QueryBuilderInterface::whereIn
     *
     * Add WHERE IN SQL statement portions into Query Builder
     *
     * @param string    $field  Field name
     * @param array     $values Array of values criteria
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function whereIn ( $field, $values = [ ], $escape = null )
    {
        return $this->prepareWhereInStatement( $field, $values, false, 'AND ', $escape );
    }

    /**
     * QueryBuilderInterface::prepareWhereInStatement
     *
     * Internal WHERE IN
     *
     * @used-by    WhereIn()
     * @used-by    orWhereIn()
     * @used-by    whereNotIn()
     * @used-by    orWhereNotIn()
     *
     * @param string    $field  The field to search
     * @param array     $values The values searched on
     * @param bool      $not    If the statement would be IN or NOT IN
     * @param string    $type   AND|OR
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return    static
     */
    protected function prepareWhereInStatement (
        $field = null,
        $values = null,
        $not = false,
        $type = 'AND ',
        $escape = null
    ) {
        if ( $field === null OR $values === null ) {
            return $this;
        }

        is_bool( $escape ) || $escape = $this->protectIdentifiers;

        $fieldKey = $field;

        if ( is_string( $values ) || is_numeric( $values ) ) {
            $values = [ $values ];
        }

        $not = ( $not )
            ? ' NOT'
            : '';

        $prefix = ( count( $this->queryBuilderCache[ 'where' ] ) === 0 )
            ? $this->getBracketType( '' )
            : $this->getBracketType( $type );

        if ( is_array( $values ) ) {
            $fieldValue = implode( ', ', array_values( $values ) );

            $fieldBind = $this->bind( $fieldKey, $fieldValue );

            if ( $escape === true ) {
                $fieldKey = $this->protectIdentifiers( $field );
            }

            $whereIn = [
                'condition' => $prefix . $fieldKey . $not . ' IN (:' . $fieldBind . ')',
                'escape'    => false,
            ];

        } elseif ( $values instanceof QueryBuilderInterface ) {

            if ( $escape === true ) {
                $fieldKey = $this->protectIdentifiers( $field );
            }

            $importBindsPattern = [ ];
            $importBindsReplacement = [ ];
            foreach ( $values->queryBuilderCache[ 'binds' ] as $bindKey => $bindValue ) {
                $importBindKey = $this->bind( $bindKey, $bindValue );

                $importBindsPattern[] = ':' . $bindKey;
                $importBindsReplacement[] = ':' . $importBindKey;
            }

            $sqlStatement = $values->getSqlStatement();
            $sqlStatement = str_replace( $importBindsPattern, $importBindsReplacement, $sqlStatement );

            $whereIn = [
                'condition' => $prefix . $fieldKey . $not . ' IN (' . $sqlStatement . ')',
                'escape'    => false,
            ];
        }

        $this->queryBuilderCache[ 'where' ][] = $whereIn;

        return $this;
    }

    /**
     * QueryBuilderInterface::orWhereIn
     *
     * Add OR WHERE IN SQL statement portions into Query Builder
     *
     * @param string    $field  Field name
     * @param array     $values Array of values criteria
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function orWhereIn ( $field, $values = [ ], $escape = null )
    {
        return $this->prepareWhereInStatement( $field, $values, false, 'OR ', $escape );
    }

    /**
     * QueryBuilderInterface::whereNotIn
     *
     * Add WHERE NOT IN SQL statement portions into Query Builder
     *
     * @param string    $field  Field name
     * @param array     $values Array of values criteria
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function whereNotIn ( $field, $values = [ ], $escape = null )
    {
        return $this->prepareWhereInStatement( $field, $values, true, 'AND ', $escape );
    }

    /**
     * QueryBuilderInterface::orWhereNotIn
     *
     * Add OR WHERE NOT IN SQL statement portions into Query Builder
     *
     * @param string    $field  Field name
     * @param array     $values Array of values criteria
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function orWhereNotIn ( $field, $values = [ ], $escape = null )
    {
        return $this->prepareWhereInStatement( $field, $values, true, 'OR ', $escape );
    }

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
    public function whereBetween ( $field, array $values = [ ] )
    {
        // TODO: Implement whereBetween() method.
    }

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
    public function orWhereBetween ( $field, array $values = [ ] )
    {
        // TODO: Implement orWhereBetween() method.
    }

    //--------------------------------------------------------------------

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
    public function whereNotBetween ( $field, array $values = [ ] )
    {
        // TODO: Implement whereNotBetween() method.
    }

    //--------------------------------------------------------------------

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
    public function orWhereNotBetween ( $field, array $values = [ ] )
    {
        // TODO: Implement orWhereNotBetween() method.
    }

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
    public function like ( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true, $escape = null )
    {
        return $this->prepareLikeStatement( $field, $match, 'AND ', $wildcard, '', $caseSensitive, $escape );
    }

    /**
     * Internal LIKE
     *
     * @used-by    like()
     * @used-by    orLike()
     * @used-by    notLike()
     * @used-by    orNotLike()
     *
     * @param    mixed  $field
     * @param    string $match
     * @param    string $type
     * @param    string $side
     * @param    string $not
     * @param    bool   $caseSensitive IF true, will force a case-insensitive search
     * @param null|bool $escape        Whether not to try to escape identifiers
     *
     * @return    static
     */
    protected function prepareLikeStatement (
        $field,
        $match = '',
        $type = 'AND ',
        $side = 'both',
        $not = '',
        $escape = null,
        $caseSensitive = false
    ) {
        if ( ! is_array( $field ) ) {
            $field = [ $field => $match ];
        }

        $escape = is_bool( $escape )
            ? $escape
            : $this->protectIdentifiers;

        // lowercase $side in case somebody writes e.g. 'BEFORE' instead of 'before' (doh)
        $side = strtolower( $side );

        foreach ( $field as $fieldName => $fieldValue ) {
            $prefix = ( count( $this->queryBuilderCache[ 'where' ] ) === 0 )
                ? $this->getBracketType( '' )
                : $this->getBracketType( $type );

            if ( $caseSensitive === true ) {
                $fieldValue = strtolower( $fieldValue );
            }

            if ( $side === 'none' ) {
                $bind = $this->bind( $fieldName, $fieldValue );
            } elseif ( $side === 'before' ) {
                $bind = $this->bind( $fieldName, "%$fieldValue" );
            } elseif ( $side === 'after' ) {
                $bind = $this->bind( $fieldName, "$fieldValue%" );
            } else {
                $bind = $this->bind( $fieldName, "%$fieldValue%" );
            }

            $likeStatement = $this->platformPrepareLikeStatement( $prefix, $fieldName, $not, $bind, $caseSensitive );

            // some platforms require an escape sequence definition for LIKE wildcards
            if ( $escape === true && $this->likeEscapeString !== '' ) {
                $likeStatement .= sprintf( $this->likeEscapeString, $this->likeEscapeCharacter );
            }

            $this->queryBuilderCache[ 'where' ][] = [ 'condition' => $likeStatement, 'escape' => $escape ];
        }

        return $this;
    }

    /**
     * Platform independent LIKE statement builder.
     *
     * @param string|null $prefix
     * @param string      $column
     * @param string|null $not
     * @param string      $bind
     * @param bool        $caseSensitive
     *
     * @return string
     */
    public function platformPrepareLikeStatement (
        $prefix = null,
        $column,
        $not = null,
        $bind,
        $caseSensitive = false
    ) {
        $likeStatement = "{$prefix} {$column} {$not} LIKE :{$bind}";

        if ( $caseSensitive === true ) {
            $likeStatement = "{$prefix} LOWER({$column}) {$not} LIKE :{$bind}";
        }

        return $likeStatement;
    }

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
    public function orLike ( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true, $escape = null )
    {
        return $this->prepareLikeStatement( $field, $match, 'OR ', $wildcard, '', $caseSensitive, $escape );
    }

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
    public function notLike ( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true, $escape = null )
    {
        return $this->prepareLikeStatement( $field, $match, 'AND ', $wildcard, 'NOT', $caseSensitive, $escape );
    }

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
    public function orNotLike ( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true, $escape = null )
    {
        return $this->prepareLikeStatement( $field, $match, 'OR ', $wildcard, 'NOT', $caseSensitive, $escape );
    }

    /**
     * QueryBuilderInterface::offset
     *
     * Add OFFSET SQL statement into Query Builder.
     *
     * @param    int $offset OFFSET value
     *
     * @return    static
     */
    public function offset ( $offset )
    {
        if ( ! empty( $offset ) ) {
            $this->queryBuilderCache[ 'offset' ] = (int) $offset;
        }

        return $this;
    }

    //--------------------------------------------------------------------

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
    public function page ( $page = 1, $entries = null )
    {
        $page = (int) intval( $page );

        $entries = (int) ( isset( $entries )
            ? $entries
            : ( $this->queryBuilderCache[ 'limit' ] === false
                ? 5
                : $this->queryBuilderCache[ 'limit' ]
            )
        );

        $offset = ( $page - 1 ) * $entries;

        $this->limit( $entries, $offset );

        return $this;
    }

    //--------------------------------------------------------------------

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
    public function limit ( $limit, $offset = 0 )
    {
        if ( ! is_null( $limit ) ) {
            $this->queryBuilderCache[ 'limit' ] = (int) $limit;
        }

        if ( ! empty( $offset ) ) {
            $this->queryBuilderCache[ 'offset' ] = (int) $offset;
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::groupBy
     *
     * Add GROUP BY SQL statement into Query Builder.
     *
     * @param string    $field
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return $this
     */
    public function groupBy ( $field, $escape = null )
    {
        is_bool( $escape ) || $escape = $this->protectIdentifiers;

        if ( is_string( $field ) ) {
            $field = ( $escape === true )
                ? explode( ',', $field )
                : [ $field ];
        }

        foreach ( $field as $fieldName ) {
            $fieldName = trim( $fieldName );

            if ( $fieldName !== '' ) {
                $fieldName = [ 'field' => $fieldName, 'escape' => $escape ];

                $this->queryBuilderCache[ 'groupBy' ][] = $fieldName;
            }
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::orderBy
     *
     * Add ORDER BY SQL statement portions into Query Builder.
     *
     * @param string    $field
     * @param string    $direction
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return $this
     */
    public function orderBy ( $field, $direction = 'ASC', $escape = null )
    {
        $direction = strtoupper( trim( $direction ) );

        if ( $direction === 'RANDOM' ) {
            $direction = '';

            // Do we have a seed value?
            $field = ctype_digit( (string) $field )
                ? sprintf( $this->sqlRandomKeywords[ 1 ], $field )
                : $this->sqlRandomKeywords[ 0 ];
        } elseif ( empty( $field ) ) {
            return $this;
        } elseif ( $direction !== '' ) {
            $direction = in_array( $direction, [ 'ASC', 'DESC' ], true )
                ? ' ' . $direction
                : '';
        }

        is_bool( $escape ) || $escape = $this->protectIdentifiers;

        if ( $escape === false ) {
            $orderBy[] = [ 'field' => $field, 'direction' => $direction, 'escape' => false ];
        } else {
            $orderBy = [ ];
            foreach ( explode( ',', $field ) as $field ) {
                $orderBy[] = ( $direction === ''
                               && preg_match(
                                   '/\s+(ASC|DESC)$/i',
                                   rtrim( $field ),
                                   $match,
                                   PREG_OFFSET_CAPTURE
                               ) )
                    ? [
                        'field'     => ltrim( substr( $field, 0, $match[ 0 ][ 1 ] ) ),
                        'direction' => ' ' . $match[ 1 ][ 0 ],
                        'escape'    => true,
                    ]
                    : [ 'field' => trim( $field ), 'direction' => $direction, 'escape' => true ];
            }
        }

        $this->queryBuilderCache[ 'orderBy' ] = array_merge( $this->queryBuilderCache[ 'orderBy' ], $orderBy );

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * QueryBuilderInterface::get
     *
     * Perform execution of SQL Query Builder and run ConnectionInterface::query()
     *
     * @param null|int $limit
     * @param null|int $offset
     *
     * @return string|Result
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function get ( $limit = null, $offset = null )
    {
        if ( ! empty( $limit ) ) {
            $this->limit( $limit, $offset );
        }

        $result = $this->isTestMode
            ? $this->getSqlStatement()
            : $this->query( $this->getSqlStatement( false ), $this->queryBuilderCache[ 'binds' ] );

        $this->resetQuery();

        return $result;
    }

    //--------------------------------------------------------------------

    /**
     * Get SELECT query string
     *
     * Compiles a SELECT query string and returns the sql.
     *
     * @param    bool      TRUE: resets QB values; FALSE: leave QB values alone
     *
     * @return    string
     */
    public function getSqlStatement ( $reset = true )
    {
        $reset = ( $this->isTestMode
            ? false
            : $reset );

        $sqlStatementsSequence = [
            'Select',
            'Into',
            'From',
            'Join',
            'Where',
            'GroupBy',
            'Having',
            'OrderBy',
            //'Union',
            'Limit',
        ];

        $sqlStatement = '';

        foreach ( $sqlStatementsSequence as $compileMethod ) {
            $sqlStatement .= "\n" . trim( call_user_func( [ &$this, 'compile' . $compileMethod . 'Statement' ] ) );
        }

        if ( $reset ) {
            $this->resetQuery();
        }

        return trim( $sqlStatement );
    }

    //--------------------------------------------------------------------

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
    public function getWhere ( array $where = [ ], $limit = null, $offset = null )
    {
        $this->where( $where );

        if ( ! empty( $limit ) ) {
            $this->limit( $limit, $offset );
        }

        $result = $this->isTestMode
            ? $this->getSqlStatement()
            : $this->query( $this->getSqlStatement( false ), $this->queryBuilderCache[ 'binds' ] );

        $this->resetQuery();

        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * QueryBuilderInterface::where
     *
     * Add WHERE SQL statement portions into Query Builder
     *
     * @param string|array $field  Field name, array of [field => value] (grouped where)
     * @param null|string  $value  Field criteria or UPPERCASE grouped type AND|OR
     * @param null|bool    $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function where ( $field, $value = null, $escape = null )
    {
        return $this->prepareWhereHavingStatement( $field, $value, 'AND ', $escape, 'where' );
    }

    // --------------------------------------------------------------------

    /**
     * Starts a query group, but ORs the group
     *
     * @return    static
     */
    public function orBracketOpen ()
    {
        return $this->bracketOpen( '', 'OR ' );
    }

    /**
     * Starts a query group.
     *
     * @param    string $not  (Internal use only)
     * @param    string $type (Internal use only)
     *
     * @return    static
     */
    public function bracketOpen ( $not = '', $type = 'AND ' )
    {
        $type = $this->getBracketType( $type );

        $this->queryBuilderCache[ 'bracketOpen' ] = true;
        $prefix = count( $this->queryBuilderCache[ 'where' ] ) === 0
            ? ''
            : $type;
        $where = [
            'condition' => $prefix . $not . str_repeat( ' ', ++$this->queryBuilderCache[ 'bracketCount' ] ) . ' (',
            'escape'    => false,
        ];

        $this->queryBuilderCache[ 'where' ][] = $where;

        return $this;
    }

    /**
     * Starts a query group, but NOTs the group
     *
     * @return    static
     */
    public function notBracketOpen ()
    {
        return $this->bracketOpen( 'NOT ', 'AND ' );
    }

    //--------------------------------------------------------------------

    /**
     * Starts a query group, but OR NOTs the group
     *
     * @return    static
     */
    public function orNotBracketOpen ()
    {
        return $this->bracketOpen( 'NOT ', 'OR ' );
    }

    //--------------------------------------------------------------------

    /**
     * Ends a query group
     *
     * @return    static
     */
    public function bracketClose ()
    {
        $this->queryBuilderCache[ 'bracketOpen' ] = false;
        $where = [
            'condition' => str_repeat( ' ', $this->queryBuilderCache[ 'bracketCount' ]-- ) . ')',
            'escape'    => false,
        ];

        $this->queryBuilderCache[ 'where' ][] = $where;

        return $this;
    }

    /**
     * QueryBuilderInterface::countAll
     *
     * Perform execution of count all records of a table.
     *
     * @access  public
     * @return int|string
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function countAll ()
    {
        $this->count( '*', 'numrows' );
        $sqlStatement = $this->getSqlStatement();

        if ( $this->isSubQueryMode ) {
            return '( ' . $sqlStatement . ' )';
        }

        if ( $this->isTestMode ) {
            return $sqlStatement;
        }

        $result = $this->query( $sqlStatement );

        if ( $result->count() == 0 ) {
            return 0;
        }

        return (int) $result->first()->numrows;
    }

    /**
     * QueryBuilderInterface::count
     *
     * Add SELECT COUNT(field) AS alias statement
     *
     * @param string $field             Field name
     * @param string $alias             Field alias
     * @param bool   $isReturnStatement Whether including into select active record or returning string
     *
     * @return static|string
     */
    public function count ( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'COUNT' );
    }

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
     * @access   public
     */
    public function countAllResults ( $reset = true )
    {
        // ORDER BY usage is often problematic here (most notably
        // on Microsoft SQL Server) and ultimately unnecessary
        // for selecting COUNT(*) ...
        if ( ! empty( $this->queryBuilderCache[ 'orderBy' ] ) ) {
            $orderBy = $this->queryBuilderCache[ 'orderBy' ];
            $this->queryBuilderCache[ 'orderBy' ] = [ ];
        }

        $this->count( '*', 'numrows' );

        if ( $this->queryBuilderCache[ 'distinct' ] ) {

        }

        $sqlStatement = ( $this->queryBuilderCache[ 'distinct' ] === true )
            ? $sqlCountStatement . $this->protectIdentifiers( 'numrows' ) . "\nFROM (\n" .
              $this->compileSelectStatement() . "\n) o2system_count_all_results"
            : $this->compileSelectStatement( $sqlCountStatement . $this->protectIdentifiers( 'numrows' ) );

        if ( $this->isTestMode ) {
            return $sqlStatement;
        }

        $result = $this->query( $sqlStatement, $this->queryBuilderCache[ 'binds' ] );

        if ( $reset === true ) {
            $this->resetSelect();
        } // If we've previously reset the QBOrderBy values, get them back
        elseif ( ! isset( $this->queryBuilderCache[ 'orderBy' ] ) ) {
            $this->queryBuilderCache[ 'orderBy' ] = $orderBy;
        }

        $row = $result->getRow();

        if ( empty( $row ) ) {
            return 0;
        }

        return (int) $row->numrows;
    }

    /**
     * Compile the SELECT statement
     *
     * Generates a query string based on which functions were used.
     * Should not be called directly.
     *
     * @param    bool $selectOverride
     *
     * @return    string
     */
    protected function compileSelectStatement ( $selectOverride = false )
    {
        // Write the "select" portion of the query
        if ( $selectOverride !== false ) {
            $sqlStatement = $selectOverride;
        } else {
            $sqlStatement = ( ! $this->queryBuilderCache[ 'distinct' ] )
                ? $this->sqlStatements[ 'SELECT' ]
                : $this->sqlStatements[ 'SELECT_DISTINCT' ];

            if ( count( $this->queryBuilderCache[ 'select' ] ) === 0 ) {
                $sqlSelectStatement = "*";
            } else {
                // Cycle through the "select" portion of the query and prep each column name.
                // The reason we protect identifiers here rather than in the select() function
                // is because until the user calls the from() function we don't know if there are aliases
                foreach ( $this->queryBuilderCache[ 'select' ] as $selectKey => $selectField ) {
                    $noEscape = isset( $this->queryBuilderCache[ 'noEscape' ] [ $selectKey ] )
                        ? $this->queryBuilderCache[ 'noEscape' ] [ $selectKey ]
                        : null;
                    $this->queryBuilderCache[ 'select' ] [ $selectKey ] = $this->protectIdentifiers(
                        $selectField,
                        false,
                        $noEscape
                    );
                }

                $sqlSelectStatement = "\n\t" . implode( ", \n\t", $this->queryBuilderCache[ 'select' ] );
            }

            $sqlStatement = sprintf( $sqlStatement, $sqlSelectStatement );
        }

        return $sqlStatement;
    }

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
    public function insert ( array $sets = [ ] )
    {
        $this->sets( $sets );

        $sqlStatement = sprintf(
            $this->sqlStatements[ 'INSERT' ],
            $this->protectIdentifiers(
                $this->queryBuilderCache[ 'from' ][ 0 ],
                true,
                $this->protectIdentifiers,
                false
            ),
            implode( ', ', array_keys( $this->queryBuilderCache[ 'sets' ] ) ),
            implode( ', ', array_values( $this->queryBuilderCache[ 'sets' ] ) )
        );

        if ( $this->isTestMode ) {
            return $sqlStatement;
        }

        $this->resetWrite();

        return $this->query( $sqlStatement, $this->queryBuilderCache[ 'binds' ] );
    }

    //--------------------------------------------------------------------

    public function sets ( array $fields, $escape = null )
    {
        foreach ( $fields as $field => $value ) {
            $this->set( $field, $value, $escape );
        }

        return $this;
    }

    //--------------------------------------------------------------------

    public function set ( $field, $value, $escape = null )
    {
        if ( ! is_array( $field ) ) {
            $field = [ $field => $value ];
        }

        $escape = is_bool( $escape )
            ? $escape
            : $this->protectIdentifiers;

        foreach ( $field as $key => $value ) {

            if ( is_array( $value ) || is_object( $value ) ) {
                $value = call_user_func_array( $this->arrayObjectConvension, [ $value ] );
            }

            $this->queryBuilderCache[ 'binds' ][ $key ] = $value;
            $this->queryBuilderCache[ 'sets' ][ $this->protectIdentifiers( $key, false, $escape ) ] = ':' . $key;
        }

        return $this;
    }

    //--------------------------------------------------------------------

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
    public function insertBatch ( array $sets, $batchSize = 1000 )
    {
        // TODO: Implement insertBatch() method.
    }

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
    public function update ( array $sets, array $where = [ ], $limit = null )
    {
        if ( isset( $sets ) ) {
            $this->sets( $sets );
        }

        if ( isset( $where ) ) {
            $this->where( $where );
        }

        $setFields = [ ];
        foreach ( $this->queryBuilderCache[ 'sets' ] as $setKey => $setValue ) {
            $setFields[] = $setKey . ' = ' . $setValue;
        }

        $sqlStatement = sprintf(
            $this->sqlStatements[ 'UPDATE' ],
            $this->protectIdentifiers(
                $this->queryBuilderCache[ 'from' ][ 0 ],
                true,
                $this->protectIdentifiers,
                false
            ),
            implode( ', ', $setFields ),
            $this->compileWhereHavingStatement( 'where' ) . $this->compileOrderByStatement()
        );

        if ( isset( $limit ) ) {
            $sqlStatement .= "\n" . sprintf(
                    $this->sqlStatements[ 'LIMIT' ],
                    $this->queryBuilderCache[ 'limit' ]
                );
        }

        if ( $this->isTestMode ) {
            return $sqlStatement;
        }

        $this->resetWrite();

        return $this->query( $sqlStatement, $this->queryBuilderCache[ 'binds' ] );
    }

    /**
     * Compile ORDER BY
     *
     * Escapes identifiers in ORDER BY statements at execution time.
     *
     * Required so that aliases are tracked properly, regardless of wether
     * orderBy() is called prior to from(), join() and prefixTable is added
     * only if needed.
     *
     * @return    string    SQL statement
     */
    protected function compileOrderByStatement ()
    {
        if ( is_array( $this->queryBuilderCache[ 'orderBy' ] ) && count( $this->queryBuilderCache[ 'orderBy' ] ) > 0 ) {
            for ( $i = 0, $c = count( $this->queryBuilderCache[ 'orderBy' ] ); $i < $c; $i++ ) {
                if ( $this->queryBuilderCache[ 'orderBy' ][ $i ][ 'escape' ] !== false
                     && ! $this->isLiteral(
                        $this->queryBuilderCache[ 'orderBy' ][ $i ][ 'field' ]
                    )
                ) {
                    $this->queryBuilderCache[ 'orderBy' ][ $i ][ 'field' ] = $this->protectIdentifiers(
                        $this->queryBuilderCache[ 'orderBy' ][ $i ][ 'field' ]
                    );
                }

                $this->queryBuilderCache[ 'orderBy' ][ $i ] = $this->queryBuilderCache[ 'orderBy' ][ $i ][ 'field' ]
                                                              . $this->queryBuilderCache[ 'orderBy' ][ $i ][ 'direction' ];
            }

            return $this->queryBuilderCache[ 'orderBy' ] = "\n" . sprintf(
                    $this->sqlStatements[ 'ORDER_BY' ],
                    implode( ', ', $this->queryBuilderCache[ 'orderBy' ] )
                );
        } elseif ( is_string( $this->queryBuilderCache[ 'orderBy' ] ) ) {
            return $this->queryBuilderCache[ 'orderBy' ];
        }

        return '';
    }

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
    public function updateBatch ( array $sets, $index = null, $batchSize = 1000 )
    {
        // TODO: Implement updateBatch() method.
    }

    /**
     * QueryBuilderInterface::replace
     *
     * Execute REPLACE SQL Query
     *
     * @param array $sets Array of data sets [field => value]
     *
     * @return bool
     */
    public function replace ( array $sets = [ ] )
    {
        $this->sets( $sets );

        $sqlStatement = sprintf(
            $this->sqlStatements[ 'REPLACE' ],
            $this->protectIdentifiers(
                $this->queryBuilderCache[ 'from' ][ 0 ],
                true,
                $this->protectIdentifiers,
                false
            ),
            implode( ', ', array_keys( $this->queryBuilderCache[ 'sets' ] ) ),
            implode( ', ', array_values( $this->queryBuilderCache[ 'sets' ] ) )
        );

        if ( $this->isTestMode ) {
            return $sqlStatement;
        }

        $this->resetWrite();

        return $this->query( $sqlStatement, $this->queryBuilderCache[ 'binds' ] );
    }

    /**
     * QueryBuilderInterface::replaceBatch
     *
     * Execute REPLACE batch SQL Query
     *
     * @param array $sets      Array of data sets[][field => value]
     * @param int   $batchSize Maximum batch size
     *
     * @return bool
     */
    public function replaceBatch ( array $sets, $batchSize = 1000 )
    {
        // TODO: Implement replaceBatch() method.
    }

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
    public function delete ( $where = null, $limit = null )
    {
        if ( isset( $where ) ) {
            $this->where( $where );
        }

        $sqlStatement = sprintf(
            $this->sqlStatements[ 'DELETE' ],
            $this->protectIdentifiers(
                $this->queryBuilderCache[ 'from' ][ 0 ],
                true,
                $this->protectIdentifiers,
                false
            ),
            $this->compileWhereHavingStatement( 'where' )
        );

        if ( isset( $limit ) ) {
            $sqlStatement .= "\n" . sprintf(
                    $this->sqlStatements[ 'LIMIT' ],
                    $this->queryBuilderCache[ 'limit' ]
                );
        }

        if ( $this->isTestMode ) {
            return $sqlStatement;
        }

        $this->resetWrite();

        return $this->query( $sqlStatement, $this->queryBuilderCache[ 'binds' ] );
    }

    /**
     * QueryBuilderInterface::deleteBatch
     *
     * Execute DELETE batch SQL Query
     *
     * @param array $where     WHERE IN (field => [match, ...])
     * @param int   $batchSize Maximum batch size
     *
     * @return bool
     */
    public function deleteBatch ( array $where, $batchSize = 1000 )
    {
        // TODO: Implement deleteBatch() method.
    }

    public function binds ( array $binds )
    {
        foreach ( $binds as $field => $value ) {
            $this->bind( $field, $value );
        }

        return $this;
    }

    /**
     * Truncate
     *
     * Compiles a truncate string and runs the query
     * If the database does not support the truncate() command
     * This function maps to "DELETE FROM table"
     *
     * @param    bool    Whether we're in test mode or not.
     *
     * @return bool TRUE on success, FALSE on failure
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function truncate ( $tableName, $escape = null )
    {
        is_bool( $escape ) || $escape = $this->protectIdentifiers;

        if ( isset( $this->sqlStatements[ 'TRUNCATE' ] ) ) {

            if ( $escape ) {
                $tableName = $this->protectIdentifiers( $tableName, true, true );
            }

            $sqlStatement = sprintf( $this->sqlStatements[ 'TRUNCATE' ], $tableName );

            if ( $this->isTestMode === true ) {
                return $sqlStatement;
            }

            $this->resetWrite();

            $this->query( $sqlStatement );

            return true;
        }

        return false;
    }

    protected function compileIntoStatement ()
    {
        return "\n" . $this->queryBuilderCache[ 'into' ];
    }

    protected function compileFromStatement ()
    {
        if ( count( $this->queryBuilderCache[ 'from' ] ) > 0 ) {
            return "\n" . sprintf(
                $this->sqlStatements[ 'FROM' ],
                implode( ',', array_unique( $this->queryBuilderCache[ 'from' ] ) )
            );
        }
    }

    protected function compileJoinStatement ()
    {
        if ( count( $this->queryBuilderCache[ 'join' ] ) > 0 ) {
            return "\n" . implode( "\n", $this->queryBuilderCache[ 'join' ] );
        }
    }

    protected function compileWhereStatement ()
    {
        return $this->compileWhereHavingStatement( 'where' );
    }

    //--------------------------------------------------------------------

    /**
     * Compile WHERE, HAVING statements
     *
     * Escapes identifiers in WHERE and HAVING statements at execution time.
     *
     * Required so that aliases are tracked properly, regardless of whether
     * where(), orWhere(), having(), orHaving are called prior to from(),
     * join() and prefixTable is added only if needed.
     *
     * @param    string $cacheKey 'QBWhere' or 'QBHaving'
     *
     * @return    string    SQL statement
     */
    protected function compileWhereHavingStatement ( $cacheKey )
    {
        if ( count( $this->queryBuilderCache[ $cacheKey ] ) > 0 ) {
            for ( $i = 0, $c = count( $this->queryBuilderCache[ $cacheKey ] ); $i < $c; $i++ ) {
                // Is this condition already compiled?
                if ( is_string( $this->queryBuilderCache[ $cacheKey ][ $i ] ) ) {
                    continue;
                } elseif ( $this->queryBuilderCache[ $cacheKey ][ $i ][ 'escape' ] === false ) {
                    $this->queryBuilderCache[ $cacheKey ][ $i ]
                        = $this->queryBuilderCache[ $cacheKey ][ $i ][ 'condition' ];
                    continue;
                }

                // Split multiple conditions
                $conditions = preg_split(
                    '/((?:^|\s+)AND\s+|(?:^|\s+)OR\s+)/i',
                    $this->queryBuilderCache[ $cacheKey ][ $i ][ 'condition' ],
                    -1,
                    PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
                );

                for ( $ci = 0, $cc = count( $conditions ); $ci < $cc; $ci++ ) {
                    if ( ( $op = $this->getOperator( $conditions[ $ci ] ) ) === false
                         OR
                         ! preg_match(
                             '/^(\(?)(.*)(' . preg_quote( $op, '/' ) . ')\s*(.*(?<!\)))?(\)?)$/i',
                             $conditions[ $ci ],
                             $matches
                         )
                    ) {
                        continue;
                    }

                    // $matches = array(
                    //	0 => '(test <= foo)',	/* the whole thing */
                    //	1 => '(',		/* optional */
                    //	2 => 'test',		/* the field name */
                    //	3 => ' <= ',		/* $op */
                    //	4 => 'foo',		/* optional, if $op is e.g. 'IS NULL' */
                    //	5 => ')'		/* optional */
                    // );

                    if ( ! empty( $matches[ 4 ] ) ) {
                        //$this->isLiteral($matches[4]) OR $matches[4] = $this->protectIdentifiers(trim($matches[4]));
                        $matches[ 4 ] = ' ' . $matches[ 4 ];
                    }

                    $conditions[ $ci ] = $matches[ 1 ] . $this->protectIdentifiers( trim( $matches[ 2 ] ) )
                                         . ' ' . trim( $matches[ 3 ] ) . $matches[ 4 ] . $matches[ 5 ];
                }

                $this->queryBuilderCache[ $cacheKey ][ $i ] = implode( '', $conditions );
            }

            if ( $cacheKey === 'having' ) {
                return "\n" . sprintf(
                    $this->sqlStatements[ 'HAVING' ],
                    implode( "\n", $this->queryBuilderCache[ $cacheKey ] )
                );
            }

            return "\n" . sprintf(
                $this->sqlStatements[ 'WHERE' ],
                implode( "\n", $this->queryBuilderCache[ $cacheKey ] )
            );
        }

        return '';
    }

    //--------------------------------------------------------------------

    protected function compileHavingStatement ()
    {
        return $this->compileWhereHavingStatement( 'having' );
    }

    //--------------------------------------------------------------------

    /**
     * Compile GROUP BY
     *
     * Escapes identifiers in GROUP BY statements at execution time.
     *
     * Required so that aliases are tracked properly, regardless of wether
     * groupBy() is called prior to from(), join() and prefixTable is added
     * only if needed.
     *
     * @return    string    SQL statement
     */
    protected function compileGroupByStatement ()
    {
        if ( count( $this->queryBuilderCache[ 'groupBy' ] ) > 0 ) {
            for ( $i = 0, $c = count( $this->queryBuilderCache[ 'groupBy' ] ); $i < $c; $i++ ) {
                // Is it already compiled?
                if ( is_string( $this->queryBuilderCache[ 'groupBy' ][ $i ] ) ) {
                    continue;
                }

                $this->queryBuilderCache[ 'groupBy' ][ $i ] = ( $this->queryBuilderCache[ 'groupBy' ][ $i ][ 'escape' ]
                                                                === false OR
                                                                $this->isLiteral(
                                                                    $this->queryBuilderCache[ 'groupBy' ][ $i ][ 'field' ]
                                                                ) )
                    ? $this->queryBuilderCache[ 'groupBy' ][ $i ][ 'field' ]
                    : $this->protectIdentifiers( $this->queryBuilderCache[ 'groupBy' ][ $i ][ 'field' ] );
            }

            return "\n" . sprintf(
                $this->sqlStatements[ 'GROUP_BY' ],
                implode( ', ', $this->queryBuilderCache[ 'groupBy' ] )
            );
        }

        return '';
    }

    //--------------------------------------------------------------------

    /**
     * Is literal
     *
     * Determines if a string represents a literal value or a field name
     *
     * @param    string $string
     *
     * @return    bool
     */
    protected function isLiteral ( $string )
    {
        $string = trim( $string );

        if ( empty( $string ) || ctype_digit( $string ) || (string) (float) $string === $string
             || in_array(
                 strtoupper( $string ),
                 [ 'TRUE', 'FALSE' ],
                 true
             )
        ) {
            return true;
        }

        static $stringArray;

        if ( empty( $stringArray ) ) {
            $stringArray = ( $this->escapeCharacter !== '"' )
                ? [ '"', "'" ]
                : [ "'" ];
        }

        return in_array( $string[ 0 ], $stringArray, true );
    }

    //--------------------------------------------------------------------

    protected function compileLimitStatement ()
    {
        if ( $this->queryBuilderCache[ 'limit' ] ) {
            if ( $this->queryBuilderCache[ 'offset' ] ) {
                return sprintf(
                    $this->sqlStatements[ 'LIMIT_OFFSET' ],
                    $this->queryBuilderCache[ 'limit' ],
                    $this->queryBuilderCache[ 'offset' ]
                );
            }

            return "\n" . sprintf(
                $this->sqlStatements[ 'LIMIT' ],
                $this->queryBuilderCache[ 'limit' ]
            );
        }
    }

    //--------------------------------------------------------------------
}