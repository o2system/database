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
use O2System\Spl\Exceptions\RuntimeException;

/**
 * Class AbstractQueryBuilder
 *
 * @package O2System\Database\Abstracts
 */
abstract class AbstractQueryBuilder
{
    /**
     * AbstractConnection::$isTestQuery
     *
     * If true, no queries will actually be
     * ran against the database.
     *
     * @var bool
     */
    public $isTestQuery = false;
    /**
     * AbstractQueryBuilder::$conn
     *
     * Query Builder database connection instance.
     *
     * @var AbstractConnection
     */
    protected $conn;
    /**
     * AbstractQueryBuilder::$arrayObjectConversionMethod
     *
     * Query Builder insert, update array object value conversion method.
     *
     * @var string
     */
    protected $arrayObjectConversionMethod = 'serialize';
    /**
     * AbstractQueryBuilder::$sqlRandomKeywords
     *
     * ORDER BY random keyword list.
     *
     * @var array
     */
    protected $sqlOrderByRandomKeywords = [ 'RAND()', 'RAND(%d)' ];
    /**
     * AbstractQueryBuilder::$builderCache
     *
     * Query Builder properties cache.
     *
     * @var array
     */
    protected $builderCache
        = [
            'select'        => [],
            'union'         => [],
            'union_all'     => [],
            'into'          => false,
            'distinct'      => false,
            'from'          => [],
            'join'          => [],
            'where'         => [],
            'having'        => [],
            'between'       => [],
            'not_between'   => [],
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
     * AbstractQueryBuilder::isSubQuery
     *
     * Is Sub Query instance flag.
     *
     * @var bool
     */
    protected $isSubQuery = false;


    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::__construct.
     *
     * @param \O2System\Database\Abstracts\AbstractConnection $conn
     */
    public function __construct( AbstractConnection $conn )
    {
        $this->conn = $conn;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::union
     *
     * Add SELECT UNION SQL statement portions into Query Builder.
     *
     * @param \O2System\Database\Abstracts\AbstractQueryBuilder $select
     * @param bool                                              $isUnionAll
     *
     * @return static
     */
    public function union( AbstractQueryBuilder $select, $isUnionAll = false )
    {
        $this->builderCache[ ( $isUnionAll
            ? 'union_all'
            : 'union' ) ]
            = $select->getSqlStatement();

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::getSqlStatement
     *
     * Gets SQL statement.
     *
     * @param bool $reset If sets TRUE the Query Builder cache will be reset.
     *
     * @return    string
     */
    public function getSqlStatement( $reset = true )
    {
        $reset = ( $this->isTestQuery
            ? false
            : $reset );

        $sqlStatementsSequence = [
            'Select',
            'Union',
            'Into',
            'From',
            'Join',
            'Where',
            'GroupBy',
            'Having',
            'Between',
            'NotBetween',
            'OrderBy',
            'Limit',
        ];

        $sqlStatement = '';

        foreach ( $sqlStatementsSequence as $compileMethod ) {
            $sqlStatement .= "\n" . trim( call_user_func( [ &$this, 'compile' . $compileMethod . 'Statement' ] ) );
        }

        if ( $reset ) {
            $this->reset();
        }

        return trim( $sqlStatement );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::reset
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
     * AbstractQueryBuilder::resetGetter
     *
     * Resets the query builder values.  Called by the get() function
     *
     * @return  void
     */
    protected function resetGetter()
    {
        $this->resetRun(
            [
                'select'        => [],
                'union'         => [],
                'union_all'     => [],
                'into'          => false,
                'distinct'      => false,
                'from'          => [],
                'join'          => [],
                'where'         => [],
                'having'        => [],
                'between'       => [],
                'not_between'   => [],
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
     * AbstractQueryBuilder::resetRun
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
            $this->builderCache[ $cacheKey ] = $cacheDefaultValue;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::resetModifier
     *
     * Resets the query builder "modifier" values.
     *
     * Called by the insert() update() insertBatch() updateBatch() and delete() functions
     *
     * @return  void
     */
    protected function resetModifier()
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
                'orderBy'       => [],
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
     * AbstractQueryBuilder::into
     *
     * Add SELECT INTO SQL statement portions into Query Builder.
     *
     * @param string      $table    Table name
     * @param string|null $database Other database name
     *
     * @return static
     */
    public function into( $table, $database = null )
    {
        $this->builderCache[ 'into' ] = $this->conn->protectIdentifiers(
            $table
        ) . empty( $database )
            ? ''
            : ' IN ' . $this->conn->escape( $database );

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::first
     *
     * Add SELECT FIRST(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static
     */
    public function first( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'FIRST' );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::prepareAggregateStatement
     *
     * Prepare string of SQL Aggregate Functions statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     * @param string $type  AVG|COUNT|FIRST|LAST|MAX|MIN|SUM
     *
     * @return string
     */
    protected function prepareAggregateStatement( $field = '', $alias = '', $type = '' )
    {
        $sqlAggregateFunctions = [
            'AVG'   => 'AVG(%s)', // Returns the average value
            'COUNT' => 'COUNT(%s)', // Returns the number of rows
            'FIRST' => 'FIRST(%s)', // Returns the first value
            'LAST'  => 'LAST(%s)', // Returns the largest value
            'MAX'   => 'MAX(%s)', // Returns the largest value
            'MIN'   => 'MIN(%s)', // Returns the smallest value
            'SUM'   => 'SUM(%s)' // Returns the sum
        ];

        if ( $field !== '*' && $this->conn->isProtectIdentifiers ) {
            $field = $this->conn->protectIdentifiers( $field );
        }

        $alias = empty( $alias )
            ? strtolower( $type ) . '_' . $field
            : $alias;
        $sqlStatement = sprintf( $sqlAggregateFunctions[ $type ], $field )
            . ' AS '
            . $this->conn->escapeIdentifiers( $alias );

        $this->select( $sqlStatement );

        return $this;
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
    public function select( $field = '*', $escape = null )
    {
        // If the escape value was not set, we will base it on the global setting
        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        if ( is_string( $field ) ) {
            $field = str_replace( ' as ', ' AS ', $field );

            if ( strpos( $field, '+' ) !== false || strpos( $field, '(' ) !== false ) {
                $field = [ $field ];
            } else {
                $field = explode( ',', $field );
            }

            foreach ( $field as $name ) {
                $name = trim( $name );

                $this->builderCache[ 'select' ][] = $name;
                $this->builderCache[ 'noEscape' ][] = $escape;
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
                    } elseif ( $fieldAlias instanceof AbstractQueryBuilder ) {
                        $fieldName = '( ' . $fieldAlias->getSqlStatement() . ' ) AS ' . $fieldName;
                    }
                }

                $this->select( $fieldName, $escape );
            }
        }

        return $this;
    }

    /**
     * AbstractQueryBuilder::last
     *
     * Add SELECT LAST(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static
     */
    public function last( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'LAST' );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::avg
     *
     * Add SELECT AVG(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static
     */
    public function avg( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'AVG' );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::max
     *
     * Add SELECT MAX(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static
     */
    public function max( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'MAX' );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::min
     *
     * Add SELECT MIN(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static
     */
    public function min( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'MIN' );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::sum
     *
     * Add SELECT SUM(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static
     */
    public function sum( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'SUM' );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::ucase
     *
     * Add SELECT UCASE(field) AS alias statement
     *
     * @see http://www.w3schools.com/sql/sql_func_ucase.asp
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static
     */
    public function ucase( $field, $alias = '' )
    {
        return $this->prepareScalarStatement( $field, $alias, 'UCASE' );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::prepareScalarStatement
     *
     * Prepare string of SQL Scalar Functions statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     * @param string $type  UCASE|LCASE|MID|LEN|ROUND|FORMAT
     *
     * @return static
     */
    protected function prepareScalarStatement( $field = '', $alias = '', $type = '' )
    {
        $sqlScalarFunctions = [
            'UCASE'  => 'UCASE(%s)', // Converts a field to uppercase
            'LCASE'  => 'LCASE(%s)', // Converts a field to lowercase
            'LENGTH' => 'LENGTH(%s)', // Returns the length of a text field
        ];

        $alias = $alias === ''
            ? strtolower( $type ) . '_' . $field
            : $alias;

        if ( $field !== '*' && $this->conn->isProtectIdentifiers ) {
            $field = $this->conn->protectIdentifiers( $field, true );
        }

        $this->select(
            sprintf(
                $sqlScalarFunctions[ $type ],
                $field,
                $this->conn->escapeIdentifiers( $alias )
            )
        );

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::lcase
     *
     * Add SELECT LCASE(field) AS alias statement
     *
     * @see http://www.w3schools.com/sql/sql_func_lcase.asp
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static
     */
    public function lcase( $field, $alias = '' )
    {
        return $this->prepareScalarStatement( $field, $alias, 'LCASE' );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::mid
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
     * @return static
     */
    public function mid( $field, $start = 1, $length = null, $alias = '' )
    {
        if ( $this->conn->isProtectIdentifiers ) {
            $field = $this->conn->protectIdentifiers( $field, true );
        }

        $fields = [
            $field,
            $start,
        ];

        if ( isset( $length ) ) {
            array_push( $fields, intval( $length ) );
        }

        $this->select(
            sprintf(
                'MID(%s)', // Extract characters from a text field
                implode( ',', $fields )
            )
            . ' AS '
            . $this->conn->escapeIdentifiers(
                empty( $alias )
                    ? 'mid_' . $field
                    : $alias
            )
        );

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::len
     *
     * Add SELECT LEN(field) AS alias statement
     *
     * @see http://www.w3schools.com/sql/sql_func_len.asp
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static
     */
    public function len( $field, $alias = '' )
    {
        return $this->prepareScalarStatement( $field, $alias, 'LENGTH' );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::round
     *
     * Add SELECT ROUND(field) AS alias statement
     *
     * @see http://www.w3schools.com/sql/sql_func_round.asp
     *
     * @param string $field    Required. The field to round.
     * @param int    $decimals Required. Specifies the number of decimals to be returned.
     * @param string $alias    Field alias
     *
     * @return static
     */
    public function round( $field, $decimals = 0, $alias = '' )
    {
        $this->select(
            sprintf(
                'ROUND(%s, %s)', // Rounds a numeric field to the number of decimals specified
                $field,
                $decimals
            )
            . ' AS '
            . $this->conn->escapeIdentifiers(
                empty( $alias )
                    ? 'mid_' . $field
                    : $alias
            )
        );

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::format
     *
     * Add SELECT FORMAT(field, format) AS alias statement
     *
     * @see http://www.w3schools.com/sql/sql_func_format.asp
     *
     * @param string $field  Field name.
     * @param string $format Field format.
     * @param string $alias  Field alias.
     *
     * @return static
     */
    public function format( $field, $format, $alias = '' )
    {
        $this->select(
            sprintf(
                'FORMAT(%s, %s)', // Formats how a field is to be displayed
                $field,
                $format
            )
            . ' AS '
            . $this->conn->escapeIdentifiers(
                empty( $alias )
                    ? 'mid_' . $field
                    : $alias
            )
        );

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::now
     *
     * Add / Create SELECT NOW() SQL statement.
     *
     * @return static
     */
    public function now()
    {
        $this->select( 'NOW()' ); // Returns the current date and time

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::extract
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
    public function dateExtract( $field, $unit, $alias = '' )
    {
        $unit = strtoupper( $unit );

        if ( in_array( $unit, $this->getDateTypes() ) ) {

            $fieldName = $field;
            $fieldAlias = $alias;

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
                    'EXTRACT(%s FROM %s)', // Returns a single part of a date/time
                    $unit,
                    $this->conn->protectIdentifiers( $fieldName )
                ) . ' AS ' . $this->conn->escapeIdentifiers( $fieldAlias );

            $this->select( $sqlStatement );
        }

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::getDateTypes
     *
     * Gets Generic SQL Date Types.
     *
     * @return array
     */
    protected function getDateTypes()
    {
        return [
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
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::date
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
    public function date( $field, $alias = '' )
    {
        $this->select(
            sprintf(
                'DATE(%s)', // Extracts the date part of a date or date/time expression
                $field
            )
            . ' AS '
            . $this->conn->escapeIdentifiers(
                empty( $alias )
                    ? 'mid_' . $field
                    : $alias
            )
        );

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::dateAdd
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
    public function dateAdd( $field, $interval, $alias = '' )
    {
        if ( $this->hasDateType( $interval ) ) {

            $this->select(
                sprintf(
                    'DATE_ADD(%s, INTERVAL %s)', // Adds a specified time interval to a date
                    $field,
                    $interval
                )
                . ' AS '
                . $this->conn->escapeIdentifiers(
                    empty( $alias )
                        ? 'date_add_' . $field
                        : $alias
                )
            );

        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::hasDateType
     *
     * Validate whether the string has an SQL Date unit type
     *
     * @param $string
     *
     * @return bool
     */
    protected function hasDateType( $string )
    {
        return (bool)preg_match(
            '/(' . implode( '|\s', $this->getDateTypes() ) . '\s*\(|\s)/i',
            trim( $string )
        );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::dateSub
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
    public function dateSub( $field, $interval, $alias = '' )
    {
        $this->select(
            sprintf(
                'DATE_SUB(%s, INTERVAL %s)', // Subtracts a specified time interval from a date
                $field,
                $interval
            )
            . ' AS '
            . $this->conn->escapeIdentifiers(
                empty( $alias )
                    ? 'date_sub_' . $field
                    : $alias
            )
        );

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::dateDiff
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
    public function dateDiff( array $fields, $alias )
    {
        $dateTimeStart = key( $fields );
        $dateTimeEnd = $fields[ $dateTimeStart ];

        if ( preg_match( "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $dateTimeStart ) ) {
            $dateTimeStart = $this->conn->escape( $dateTimeStart );
        } elseif ( $this->conn->isProtectIdentifiers ) {
            $dateTimeStart = $this->conn->protectIdentifiers( $dateTimeStart );
        }

        if ( preg_match( "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $dateTimeEnd ) ) {
            $dateTimeEnd = $this->conn->escape( $dateTimeEnd );
        } elseif ( $this->conn->isProtectIdentifiers ) {
            $dateTimeEnd = $this->conn->protectIdentifiers( $dateTimeEnd );
        }

        $this->select(
            sprintf(
                'DATEDIFF(%s, %s)', // Returns the number of days between two dates
                $dateTimeStart,
                $dateTimeEnd
            )
            . ' AS '
            . $this->conn->escapeIdentifiers( $alias )
        );

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::distinct
     *
     * Sets a flag which tells the query string compiler to add DISTINCT
     * keyword on SELECT statement
     *
     * @param    bool $distinct
     *
     * @return    static
     */
    public function distinct( $distinct = true )
    {
        $this->builderCache[ 'distinct' ] = is_bool( $distinct )
            ? $distinct
            : true;

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::table
     *
     * Used for modifier query builder process (insert, update, replace, delete).
     *
     * @param string $table Table name.
     *
     * @return  static
     */
    public function table( $table )
    {
        return $this->from( $table, true );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::from
     *
     * Generates FROM SQL statement portions into Query Builder.
     *
     * @param string|array $table
     * @param bool         $overwrite Should we remove the first table existing?
     *
     * @return  static
     */
    public function from( $table, $overwrite = false )
    {
        if ( $overwrite === true ) {
            $this->builderCache[ 'from' ] = [];
            $this->builderCache[ 'aliasedTables' ] = [];
        }

        if ( is_string( $table ) ) {
            $table = explode( ',', $table );
        }

        foreach ( $table as $name ) {
            $name = trim( $name );

            // Extract any aliases that might exist. We use this information
            // in the protectIdentifiers to know whether to add a table prefix
            $this->trackAliases( $name );

            $this->builderCache[ 'from' ][] = $this->conn->protectIdentifiers( $name, true, null, false );
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::trackAliases
     *
     * Used to track SQL statements written with aliased tables.
     *
     * @param   string $table Inspected table name.
     *
     * @return  void
     */
    protected function trackAliases( $table )
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
            if ( ! in_array( $table, $this->builderCache[ 'aliasedTables' ] ) ) {
                $this->builderCache[ 'aliasedTables' ][] = $table;
            }
        }
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::getAliasedTables
     *
     * Returns list of tracked aliased tables.
     *
     * @return array
     */
    public function getAliasedTables()
    {
        if ( empty( $this->builderCache[ 'aliasedTables' ] ) ) {
            return [];
        }

        return $this->builderCache[ 'aliasedTables' ];
    }

    // --------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::join
     *
     * Add JOIN SQL statement portions into Query Builder.
     *
     * @param string    $table     Table name
     * @param null      $condition Join conditions: table.column = other_table.column
     * @param string    $type      UPPERCASE join type LEFT|LEFT_OUTER|RIGHT|RIGHT_OUTER|INNER|OUTER|FULL|JOIN
     * @param null|bool $escape    Whether not to try to escape identifiers
     *
     * @return static
     */
    public function join( $table, $condition = null, $type = 'LEFT', $escape = null )
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

        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        if ( ! $this->hasOperator( $condition ) ) {
            $condition = ' USING (' . ( $escape
                    ? $this->conn->escapeIdentifiers( $condition )
                    : $condition ) . ')';
        } elseif ( $escape === false ) {
            $condition = ' ON ' . $condition;
        } else {
            // Split multiple conditions
            if ( preg_match_all( '/\sAND\s|\sOR\s/i', $condition, $joints, PREG_OFFSET_CAPTURE ) ) {
                $conditions = [];
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
                    ? $match[ 1 ] . $this->conn->protectIdentifiers(
                        $match[ 2 ]
                    ) . $operator . $this->conn->protectIdentifiers( $match[ 3 ] )
                    : $conditions[ $i ];
            }
        }

        // Do we want to escape the table name?
        if ( $escape === true ) {
            $table = $this->conn->protectIdentifiers( $table, true, null, false );
        }

        // Assemble the JOIN statement
        $this->builderCache[ 'join' ][] = $type . 'JOIN ' . $table . $condition;

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::hasOperator
     *
     * Tests whether the string has an SQL operator
     *
     * @param    string
     *
     * @return    bool
     */
    protected function hasOperator( $string )
    {
        return (bool)preg_match(
            '/(<|>|!|=|\sIS NULL|\sIS NOT NULL|\sEXISTS|\sBETWEEN|\sLIKE|\sIN\s*\(|\s)/i',
            trim( $string )
        );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::getOperator
     *
     * Returns the SQL string operator
     *
     * @param    string
     *
     * @return    string
     */
    protected function getOperator( $string )
    {
        static $operator;

        if ( empty( $operator ) ) {

            $likeEscapeString = ( $this->conn->getConfig( 'likeEscapeString' ) !== '' )
                ? '\s+' . preg_quote(
                    trim(
                        sprintf(
                            $this->conn->getConfig( 'likeEscapeString' ),
                            $this->conn->getConfig( 'likeEscapeCharacter' )
                        )
                    ),
                    '/'
                )
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

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orWhere
     *
     * Add OR WHERE SQL statement portions into Query Builder
     *
     * @param string|array $field  Field name, array of [field => value] (grouped where)
     * @param null|string  $value  Field criteria or UPPERCASE grouped type AND|OR
     * @param null|bool    $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function orWhere( $field, $value = null, $escape = null )
    {
        return $this->prepareWhereStatement( $field, $value, 'OR ', $escape, 'where' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::prepareWhereHavingStatement
     *
     * Add WHERE, HAVING SQL statement portion into Query Builder.
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
    protected function prepareWhereStatement( $field, $value = null, $type = 'AND ', $escape = null, $cacheKey )
    {
        if ( ! is_array( $field ) ) {
            $field = [ $field => $value ];
        }

        // If the escape value was not set will base it on the global setting
        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        foreach ( $field as $fieldName => $fieldValue ) {
            $prefix = ( count( $this->builderCache[ $cacheKey ] ) === 0 )
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
            } elseif ( $fieldValue instanceof AbstractQueryBuilder ) {
                $fieldValue = $fieldValue->getSqlStatement();
            }

            $fieldValue = ! is_null( $fieldValue )
                ? ' :' . $fieldBind
                : $fieldValue;

            $this->builderCache[ $cacheKey ][] = [
                'condition' => $prefix . $fieldName . $fieldValue,
                'escape'    => $escape,
            ];
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::getBracketType
     *
     * @used-by    bracketOpen()
     * @used-by    prepareLikeStatement()
     * @used-by    whereHaving()
     * @used-by    prepareWhereInStatement()
     *
     * @param   string $type
     *
     * @return  string
     */
    protected function getBracketType( $type )
    {
        if ( $this->builderCache[ 'bracketOpen' ] ) {
            $type = '';
            $this->builderCache[ 'bracketOpen' ] = false;
        }

        return $type;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::binds
     *
     * @param $field
     * @param $value
     *
     * @return string
     */
    public function bind( $field, $value )
    {
        if ( ! array_key_exists( $field, $this->builderCache[ 'binds' ] ) ) {
            $this->builderCache[ 'binds' ][ $field ] = $value;

            return $field;
        }

        $count = 0;

        while ( array_key_exists( $field . $count, $this->builderCache[ 'binds' ] ) ) {
            ++$count;
        }

        $this->builderCache[ 'binds' ][ $field . '_' . $count ] = $value;

        return $field . '_' . $count;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::having
     *
     * Separates multiple calls with 'AND'.
     *
     * @param    string $field
     * @param    string $value
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return    static
     */
    public function having( $field, $value = null, $escape = null )
    {
        return $this->prepareWhereStatement( $field, $value, 'AND ', $escape, 'having' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orHaving
     *
     * Separates multiple calls with 'OR'.
     *
     * @param    string $field
     * @param    string $value
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return    static
     */
    public function orHaving( $field, $value = null, $escape = null )
    {
        return $this->prepareWhereStatement( $field, $value, 'OR ', $escape, 'having' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::whereBetween
     *
     * Add WHERE BETWEEN SQL statement portions into Query Builder
     *
     * @param string $field  Field name
     * @param array  $values Array of between values
     *
     * @return static
     */
    public function whereBetween( $field, array $values = [], $escape = null )
    {
        return $this->prepareWhereStatement( $field, implode( ' AND ', $values ), 'AND ', $escape, 'between' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orWhereBetween
     *
     * Add OR WHERE BETWEEN SQL statement portions into Query Builder
     *
     * @param string $field  Field name
     * @param array  $values Array of between values
     *
     * @return static
     */
    public function orWhereBetween( $field, array $values = [], $escape = null )
    {
        return $this->prepareWhereStatement( $field, implode( ' AND ', $values ), 'OR ', $escape, 'between' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::whereNotBetween
     *
     * Add WHERE NOT BETWEEN SQL statement portions into Query Builder
     *
     * @param string $field  Field name
     * @param array  $values Array of between values
     *
     * @return static
     */
    public function whereNotBetween( $field, array $values = [], $escape = null )
    {
        return $this->prepareWhereStatement( $field, implode( ' AND ', $values ), 'OR ', $escape, 'not_between' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orWhereNotBetween
     *
     * Add OR WHERE NOT BETWEEN SQL statement portions into Query Builder
     *
     * @param string $field  Field name
     * @param array  $values Array of between values
     *
     * @return static
     */
    public function orWhereNotBetween( $field, array $values = [], $escape = null )
    {
        return $this->prepareWhereStatement( $field, implode( ' OR ', $values ), 'OR ', $escape, 'not_between' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::whereIn
     *
     * Add WHERE IN SQL statement portions into Query Builder
     *
     * @param string    $field  Field name
     * @param array     $values Array of values criteria
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function whereIn( $field, $values = [], $escape = null )
    {
        return $this->prepareWhereInStatement( $field, $values, false, 'AND ', $escape );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::prepareWhereInStatement
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
    protected function prepareWhereInStatement(
        $field = null,
        $values = null,
        $not = false,
        $type = 'AND ',
        $escape = null
    ) {
        if ( $field === null OR $values === null ) {
            return $this;
        }

        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        $fieldKey = $field;

        if ( is_string( $values ) || is_numeric( $values ) ) {
            $values = [ $values ];
        }

        $not = ( $not )
            ? ' NOT'
            : '';

        $prefix = ( count( $this->builderCache[ 'where' ] ) === 0 )
            ? $this->getBracketType( '' )
            : $this->getBracketType( $type );

        if ( is_array( $values ) ) {
            $fieldValue = implode( ', ', array_values( $values ) );

            $fieldBind = $this->bind( $fieldKey, $fieldValue );

            if ( $escape === true ) {
                $fieldKey = $this->conn->protectIdentifiers( $field );
            }

            $whereIn = [
                'condition' => $prefix . $fieldKey . $not . ' IN (:' . $fieldBind . ')',
                'escape'    => false,
            ];

        } elseif ( $values instanceof AbstractQueryBuilder ) {

            if ( $escape === true ) {
                $fieldKey = $this->conn->protectIdentifiers( $field );
            }

            $importBindsPattern = [];
            $importBindsReplacement = [];
            foreach ( $values->builderCache[ 'binds' ] as $bindKey => $bindValue ) {
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

        if ( isset( $whereIn ) ) {
            $this->builderCache[ 'where' ][] = $whereIn;
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orWhereIn
     *
     * Add OR WHERE IN SQL statement portions into Query Builder
     *
     * @param string    $field  Field name
     * @param array     $values Array of values criteria
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function orWhereIn( $field, $values = [], $escape = null )
    {
        return $this->prepareWhereInStatement( $field, $values, false, 'OR ', $escape );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::whereNotIn
     *
     * Add WHERE NOT IN SQL statement portions into Query Builder
     *
     * @param string    $field  Field name
     * @param array     $values Array of values criteria
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function whereNotIn( $field, $values = [], $escape = null )
    {
        return $this->prepareWhereInStatement( $field, $values, true, 'AND ', $escape );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orWhereNotIn
     *
     * Add OR WHERE NOT IN SQL statement portions into Query Builder
     *
     * @param string    $field  Field name
     * @param array     $values Array of values criteria
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function orWhereNotIn( $field, $values = [], $escape = null )
    {
        return $this->prepareWhereInStatement( $field, $values, true, 'OR ', $escape );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::like
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
    public function like( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true, $escape = null )
    {
        return $this->prepareLikeStatement( $field, $match, 'AND ', $wildcard, '', $caseSensitive, $escape );
    }

    //--------------------------------------------------------------------

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
    protected function prepareLikeStatement(
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
            : $this->conn->isProtectIdentifiers;

        // lowercase $side in case somebody writes e.g. 'BEFORE' instead of 'before' (doh)
        $side = strtolower( $side );

        foreach ( $field as $fieldName => $fieldValue ) {
            $prefix = ( count( $this->builderCache[ 'where' ] ) === 0 )
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
            if ( $escape === true && $this->conn->getConfig( 'likeEscapeString' ) !== '' ) {
                $likeStatement .= sprintf(
                    $this->conn->getConfig( 'likeEscapeString' ),
                    $this->conn->getConfig( 'likeEscapeCharacter' )
                );
            }

            $this->builderCache[ 'where' ][] = [ 'condition' => $likeStatement, 'escape' => $escape ];
        }

        return $this;
    }

    //--------------------------------------------------------------------

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
    protected function platformPrepareLikeStatement(
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

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orLike
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
    public function orLike( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true, $escape = null )
    {
        return $this->prepareLikeStatement( $field, $match, 'OR ', $wildcard, '', $caseSensitive, $escape );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::notLike
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
    public function notLike( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true, $escape = null )
    {
        return $this->prepareLikeStatement( $field, $match, 'AND ', $wildcard, 'NOT', $caseSensitive, $escape );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orNotLike
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
    public function orNotLike( $field, $match = '', $wildcard = 'BOTH', $caseSensitive = true, $escape = null )
    {
        return $this->prepareLikeStatement( $field, $match, 'OR ', $wildcard, 'NOT', $caseSensitive, $escape );
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
        if ( ! empty( $offset ) ) {
            $this->builderCache[ 'offset' ] = (int)$offset;
        }

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
        $page = (int)intval( $page );

        $entries = (int)( isset( $entries )
            ? $entries
            : ( $this->builderCache[ 'limit' ] === false
                ? 5
                : $this->builderCache[ 'limit' ]
            )
        );

        $offset = ( $page - 1 ) * $entries;

        $this->limit( $entries, $offset );

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
        if ( ! is_null( $limit ) ) {
            $this->builderCache[ 'limit' ] = (int)$limit;
        }

        if ( ! empty( $offset ) ) {
            $this->builderCache[ 'offset' ] = (int)$offset;
        }

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
     * @return $this
     */
    public function groupBy( $field, $escape = null )
    {
        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        if ( is_string( $field ) ) {
            $field = ( $escape === true )
                ? explode( ',', $field )
                : [ $field ];
        }

        foreach ( $field as $fieldName ) {
            $fieldName = trim( $fieldName );

            if ( $fieldName !== '' ) {
                $fieldName = [ 'field' => $fieldName, 'escape' => $escape ];

                $this->builderCache[ 'groupBy' ][] = $fieldName;
            }
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::orderBy
     *
     * Add ORDER BY SQL statement portions into Query Builder.
     *
     * @param string    $field
     * @param string    $direction
     * @param null|bool $escape Whether not to try to escape identifiers
     *
     * @return $this
     */
    public function orderBy( $field, $direction = 'ASC', $escape = null )
    {
        $direction = strtoupper( trim( $direction ) );

        if ( $direction === 'RANDOM' ) {
            $direction = '';

            // Do we have a seed value?
            $field = ctype_digit( (string)$field )
                ? sprintf( $this->sqlOrderByRandomKeywords[ 1 ], $field )
                : $this->sqlOrderByRandomKeywords[ 0 ];
        } elseif ( empty( $field ) ) {
            return $this;
        } elseif ( $direction !== '' ) {
            $direction = in_array( $direction, [ 'ASC', 'DESC' ], true )
                ? ' ' . $direction
                : '';
        }

        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        if ( $escape === false ) {
            $orderBy[] = [ 'field' => $field, 'direction' => $direction, 'escape' => false ];
        } else {
            $orderBy = [];
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

        $this->builderCache[ 'orderBy' ] = array_merge( $this->builderCache[ 'orderBy' ], $orderBy );

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
     * @return string|Result
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function get( $limit = null, $offset = null )
    {
        if ( ! empty( $limit ) ) {
            $this->limit( $limit, $offset );
        }

        $result = $this->isTestQuery
            ? $this->getSqlStatement()
            : $this->conn->query( $this->getSqlStatement( false ), $this->builderCache[ 'binds' ] );

        $this->reset();

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
     * @return mixed
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function getWhere( array $where = [], $limit = null, $offset = null )
    {
        $this->where( $where );

        if ( ! empty( $limit ) ) {
            $this->limit( $limit, $offset );
        }

        $result = $this->isTestQuery
            ? $this->getSqlStatement()
            : $this->conn->query( $this->getSqlStatement( false ), $this->builderCache[ 'binds' ] );

        $this->reset();

        return $result;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::where
     *
     * Add WHERE SQL statement portions into Query Builder
     *
     * @param string|array $field  Field name, array of [field => value] (grouped where)
     * @param null|string  $value  Field criteria or UPPERCASE grouped type AND|OR
     * @param null|bool    $escape Whether not to try to escape identifiers
     *
     * @return static
     */
    public function where( $field, $value = null, $escape = null )
    {
        return $this->prepareWhereStatement( $field, $value, 'AND ', $escape, 'where' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::countAll
     *
     * Perform execution of count all records of a table.
     *
     * @access  public
     * @return int|string
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function countAll()
    {
        $this->count( '*', 'numrows' );
        $sqlStatement = $this->getSqlStatement();

        if ( $this->isSubQuery ) {
            return '( ' . $sqlStatement . ' )';
        }

        if ( $this->isTestQuery ) {
            return $sqlStatement;
        }

        $result = $this->conn->query( $sqlStatement );

        if ( $result->count() == 0 ) {
            return 0;
        }

        return (int)$result->first()->numrows;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::count
     *
     * Add SELECT COUNT(field) AS alias statement
     *
     * @param string $field Field name
     * @param string $alias Field alias
     *
     * @return static|string
     */
    public function count( $field, $alias = '' )
    {
        return $this->prepareAggregateStatement( $field, $alias, 'COUNT' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::countAllResult
     *
     * Perform execution of count all result from Query Builder along with WHERE, LIKE, HAVING, GROUP BY, and LIMIT SQL
     * statement.
     *
     * @param bool $reset Whether perform reset Query Builder or not
     *
     * @return int
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @access   public
     */
    public function countAllResults( $reset = true )
    {
        // ORDER BY usage is often problematic here (most notably
        // on Microsoft SQL Server) and ultimately unnecessary
        // for selecting COUNT(*) ...
        if ( ! empty( $this->builderCache[ 'orderBy' ] ) ) {
            $orderBy = $this->builderCache[ 'orderBy' ];
            $this->builderCache[ 'orderBy' ] = [];
        }

        $this->count( '*', 'numrows' );

        if ( $this->builderCache[ 'distinct' ] ) {

        }

        $sqlStatement = ( $this->builderCache[ 'distinct' ] === true )
            ? 'COUNT(*)' . $this->conn->protectIdentifiers( 'numrows' ) . "\nFROM (\n" .
            $this->compileSelectStatement() . "\n) o2system_count_all_results"
            : $this->compileSelectStatement( 'COUNT(*)' . $this->conn->protectIdentifiers( 'numrows' ) );

        if ( $this->isTestQuery ) {
            return $sqlStatement;
        }

        $result = $this->conn->query( $sqlStatement, $this->builderCache[ 'binds' ] );

        if ( $reset === true ) {
            $this->resetGetter();
        } elseif ( isset( $orderBy ) ) {
            // If we've previously reset the QBOrderBy values, get them back
            $this->builderCache[ 'orderBy' ] = $orderBy;
        }

        if ( $result->count() == 0 ) {
            return 0;
        }

        return (int)$result->first()->numrows;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::compileSelectStatement
     *
     * Compile the SELECT statement
     *
     * Generates a query string based on which functions were used.
     * Should not be called directly.
     *
     * @param    bool $selectOverride
     *
     * @return    string
     */
    protected function compileSelectStatement( $selectOverride = false )
    {
        // Write the "select" portion of the query
        if ( $selectOverride !== false ) {
            $sqlStatement = $selectOverride;
        } else {
            $sqlStatement = ( ! $this->builderCache[ 'distinct' ] )
                ? 'SELECT %s'
                : 'SELECT DISTINCT %s';

            if ( count( $this->builderCache[ 'select' ] ) === 0 ) {
                $sqlSelectStatement = "*";
            } else {
                // Cycle through the "select" portion of the query and prep each column name.
                // The reason we protect identifiers here rather than in the select() function
                // is because until the user calls the from() function we don't know if there are aliases
                foreach ( $this->builderCache[ 'select' ] as $selectKey => $selectField ) {
                    $noEscape = isset( $this->builderCache[ 'noEscape' ] [ $selectKey ] )
                        ? $this->builderCache[ 'noEscape' ] [ $selectKey ]
                        : null;
                    $this->builderCache[ 'select' ] [ $selectKey ] = $this->conn->protectIdentifiers(
                        $selectField,
                        false,
                        $noEscape
                    );
                }

                $sqlSelectStatement = "\n\t" . implode( ", \n\t", $this->builderCache[ 'select' ] );
            }

            $sqlStatement = sprintf( $sqlStatement, $sqlSelectStatement );
        }

        return $sqlStatement;
    }

    //--------------------------------------------------------------------

    /**
     * Starts a query group, but ORs the group
     *
     * @return    static
     */
    public function orBracketOpen()
    {
        return $this->bracketOpen( '', 'OR ' );
    }

    //--------------------------------------------------------------------

    /**
     * Starts a query group.
     *
     * @param    string $not  (Internal use only)
     * @param    string $type (Internal use only)
     *
     * @return    static
     */
    public function bracketOpen( $not = '', $type = 'AND ' )
    {
        $type = $this->getBracketType( $type );

        $this->builderCache[ 'bracketOpen' ] = true;
        $prefix = count( $this->builderCache[ 'where' ] ) === 0
            ? ''
            : $type;
        $where = [
            'condition' => $prefix . $not . str_repeat( ' ', ++$this->builderCache[ 'bracketCount' ] ) . ' (',
            'escape'    => false,
        ];

        $this->builderCache[ 'where' ][] = $where;

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * Starts a query group, but NOTs the group
     *
     * @return    static
     */
    public function notBracketOpen()
    {
        return $this->bracketOpen( 'NOT ', 'AND ' );
    }

    //--------------------------------------------------------------------

    /**
     * Starts a query group, but OR NOTs the group
     *
     * @return    static
     */
    public function orNotBracketOpen()
    {
        return $this->bracketOpen( 'NOT ', 'OR ' );
    }

    //--------------------------------------------------------------------

    /**
     * Ends a query group
     *
     * @return    static
     */
    public function bracketClose()
    {
        $this->builderCache[ 'bracketOpen' ] = false;

        $where = [
            'condition' => str_repeat( ' ', $this->builderCache[ 'bracketCount' ]-- ) . ')',
            'escape'    => false,
        ];

        $this->builderCache[ 'where' ][] = $where;

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::insert
     *
     * Execute INSERT SQL Query
     *
     * @param   array $sets   An associative array of set values.
     *                        sets[][field => value]
     * @param   bool  $escape Whether to escape values and identifiers
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function insert( array $sets, $escape = null )
    {
        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        $this->set( $sets, null, $escape );

        if ( ! empty( $this->builderCache[ 'sets' ] ) ) {
            $sqlStatement = $this->platformInsertStatement(
                $this->conn->protectIdentifiers(
                    $this->builderCache[ 'from' ][ 0 ],
                    true,
                    $escape,
                    false
                ),
                array_keys( $this->builderCache[ 'sets' ] ),
                array_values( $this->builderCache[ 'sets' ] )
            );

            if ( $this->isTestQuery ) {
                return $sqlStatement;
            }

            $this->resetModifier();

            return $this->conn->query( $sqlStatement, $this->builderCache[ 'binds' ] );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::set
     *
     * Allows key/value pairs to be set for inserting or updating
     *
     * @param string|array $field
     * @param mixed        $value
     * @param null         $escape
     *
     * @return static|array
     */
    public function set( $field, $value = '', $escape = null )
    {
        $field = $this->objectToArray( $field );

        if ( ! is_array( $field ) ) {
            $field = [ $field => $value ];
        }

        $escape = is_bool( $escape )
            ? $escape
            : $this->conn->isProtectIdentifiers;

        foreach ( $field as $key => $value ) {

            if ( is_array( $value ) || is_object( $value ) ) {
                $value = call_user_func_array( $this->arrayObjectConversionMethod, [ $value ] );
            }

            $this->builderCache[ 'binds' ][ $key ] = $value;
            $this->builderCache[ 'sets' ][ $this->conn->protectIdentifiers( $key, false, $escape ) ] = ':' . $key;
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * Object to Array
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @param   object $object
     *
     * @return  array
     */
    protected function objectToArray( $object )
    {
        if ( ! is_object( $object ) ) {
            return $object;
        }

        $array = [];
        foreach ( get_object_vars( $object ) as $key => $value ) {
            // There are some built in keys we need to ignore for this conversion
            if ( ! is_object( $value ) && ! is_array( $value ) && $key !== '_parent_name' ) {
                $array[ $key ] = $value;
            }
        }

        return $array;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::platformInsertStatement
     *
     * Generates a platform-specific insert string from the supplied data.
     *
     * @param string $table  Table name.
     * @param array  $keys   Insert keys.
     * @param array  $values Insert values.
     *
     * @return string
     */
    protected function platformInsertStatement( $table, array $keys, array $values )
    {
        return 'INSERT INTO '
            . $table
            . ' ('
            . implode( ', ', $keys )
            . ') VALUES ('
            . implode( ', ', $values )
            . ')';
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::insertBatch
     *
     * Execute INSERT batch SQL Query
     *
     * @param   array $sets      An associative array of set values.
     *                           sets[][field => value]
     * @param   int   $batchSize Maximum batch size
     * @param   bool  $escape    Whether to escape values and identifiers
     *
     * @return bool
     */
    public function insertBatch( array $sets, $batchSize = 1000, $escape = null )
    {
        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        $this->setInsertReplaceBatch( $sets );

        // Batch this baby
        $affectedRows = 0;
        for ( $i = 0, $total = count( $sets ); $i < $total; $i += $batchSize ) {
            $sql = $this->platformInsertStatement(
                $this->conn->protectIdentifiers( $this->builderCache[ 'from' ][ 0 ], true, $escape, false ),
                $this->builderCache[ 'keys' ],
                array_slice( $this->builderCache[ 'sets' ], $i, $batchSize )
            );

            if ( $this->isTestQuery ) {
                ++$affectedRows;
            } else {
                $this->conn->query( $sql, $this->builderCache[ 'binds' ] );
                $affectedRows += $this->conn->getAffectedRows();
            }
        }

        if ( ! $this->isTestQuery ) {
            $this->resetModifier();
        }

        return $affectedRows;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::setInsertBatch
     *
     * The "setInsertBatch" function.  Allows key/value pairs to be set for batch inserts
     *
     * @param   mixed  $field
     * @param   string $value
     * @param   bool   $escape Whether to escape values and identifiers
     *
     * @return  void
     */
    protected function setInsertReplaceBatch( $field, $value = '', $escape = null )
    {
        $field = $this->batchObjectToArray( $field );

        if ( ! is_array( $field ) ) {
            $field = [ $field => $value ];
        }

        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        $rowKeys = array_keys( $this->objectToArray( current( $field ) ) );
        sort( $rowKeys );

        foreach ( $field as $row ) {
            $row = $this->objectToArray( $row );
            if ( count( array_diff( $rowKeys, array_keys( $row ) ) ) > 0
                || count(
                    array_diff( array_keys( $row ), $rowKeys )
                ) > 0
            ) {
                // batch function above returns an error on an empty array
                $this->builderCache[ 'sets' ][] = [];

                return;
            }

            ksort( $row ); // puts $row in the same order as our keys

            $clean = [];
            foreach ( $row as $key => $value ) {
                $clean[] = ':' . $this->bind( $key, $value );
            }

            $row = $clean;

            $this->builderCache[ 'sets' ][] = '(' . implode( ',', $row ) . ')';
        }

        foreach ( $rowKeys as $rowKey ) {
            $this->builderCache[ 'keys' ][] = $this->conn->protectIdentifiers( $rowKey, false, $escape );
        }
    }

    //--------------------------------------------------------------------

    /**
     * Object to Array
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @param    object
     *
     * @return    array
     */
    protected function batchObjectToArray( $object )
    {
        if ( ! is_object( $object ) ) {
            return $object;
        }

        $array = [];
        $out = get_object_vars( $object );
        $fields = array_keys( $out );

        foreach ( $fields as $field ) {
            // There are some built in keys we need to ignore for this conversion
            if ( $field !== '_parent_name' ) {
                $i = 0;
                foreach ( $out[ $field ] as $data ) {
                    $array[ $i++ ][ $field ] = $data;
                }
            }
        }

        return $array;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::replace
     *
     * Compiles an replace into string and runs the query
     *
     * @param   array $sets   An associative array of set values.
     *                        sets[][field => value]
     * @param   bool  $escape Whether to escape values and identifiers
     *
     * @return bool
     */
    public function replace( array $sets, $escape = null )
    {
        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        $this->set( $sets, null, $escape );

        if ( ! empty( $this->builderCache[ 'sets' ] ) ) {
            $sqlStatement = $this->platformReplaceStatement(
                $this->conn->protectIdentifiers(
                    $this->builderCache[ 'from' ][ 0 ],
                    true,
                    $escape,
                    false
                ),
                array_keys( $this->builderCache[ 'sets' ] ),
                array_values( $this->builderCache[ 'sets' ] )
            );

            if ( $this->isTestQuery ) {
                return $sqlStatement;
            }

            $this->resetModifier();

            return $this->conn->query( $sqlStatement, $this->builderCache[ 'binds' ] );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::platformReplaceStatement
     *
     * Generates a platform-specific update string from the supplied data.
     *
     * @param string $table  Table name.
     * @param array  $keys   Insert keys.
     * @param array  $values Insert values.
     *
     * @return string
     */
    protected function platformReplaceStatement( $table, array $keys, array $values )
    {
        return 'REPLACE INTO ' . $table . ' (' . implode( ', ', $keys ) . ') VALUES (' . implode( ', ', $values ) . ')';
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::replaceBatch
     *
     * Execute REPLACE batch SQL Query
     *
     * @param   array $sets      An associative array of set values.
     *                           sets[][field => value]
     * @param   int   $batchSize Maximum batch size
     * @param   bool  $escape    Whether to escape values and identifiers
     *
     * @return bool
     */
    public function replaceBatch( array $sets, $batchSize = 1000, $escape = null )
    {
        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        $this->setInsertReplaceBatch( $sets );

        // Batch this baby
        $affectedRows = 0;
        for ( $i = 0, $total = count( $sets ); $i < $total; $i += $batchSize ) {
            $sql = $this->platformReplaceStatement(
                $this->conn->protectIdentifiers( $this->builderCache[ 'from' ][ 0 ], true, $escape, false ),
                $this->builderCache[ 'keys' ],
                array_slice( $this->builderCache[ 'sets' ], $i, $batchSize )
            );

            if ( $this->isTestQuery ) {
                ++$affectedRows;
            } else {
                $this->conn->query( $sql, $this->builderCache[ 'binds' ] );
                $affectedRows += $this->conn->getAffectedRows();
            }
        }

        if ( ! $this->isTestQuery ) {
            $this->resetModifier();
        }

        return $affectedRows;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::update
     *
     * Compiles an update string and runs the query.
     *
     * @param   array $sets    An associative array of set values.
     *                         sets[][field => value]
     * @param   array $where   WHERE [field => match]
     * @param   int   $limit   Limit
     * @param   bool  $escape  Whether to escape values and identifiers
     *
     * @return bool
     */
    public function update( array $sets, array $where = [], $limit = null, $escape = null )
    {
        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        $this->set( $sets, null, $escape );
        $this->where( $where );

        if ( ! empty( $this->builderCache[ 'sets' ] ) ) {
            $sqlStatement = $this->platformUpdateStatement(
                $this->conn->protectIdentifiers(
                    $this->builderCache[ 'from' ][ 0 ],
                    true,
                    $escape,
                    false
                ),
                $this->builderCache[ 'sets' ]
            );

            if ( $this->isTestQuery ) {
                return $sqlStatement;
            }

            $this->resetModifier();

            return $this->conn->query( $sqlStatement, $this->builderCache[ 'binds' ] );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::platformUpdateStatement
     *
     * Generates a platform-specific update string from the supplied data.
     *
     * @param   string $table  Table name.
     * @param   array  $sets   An associative array of set values.
     *                         sets[][field => value]
     *
     * @return string
     */
    protected function platformUpdateStatement( $table, array $sets )
    {
        $columns = [];

        foreach ( $sets as $key => $val ) {
            $columns[] = $key . ' = ' . $val;
        }

        return 'UPDATE ' . $table . ' SET ' . implode( ', ', $columns )
            . $this->compileWhereHavingStatement( 'where' )
            . $this->compileOrderByStatement()
            . $this->compileLimitStatement();
    }

    /**
     * AbstractQueryBuilder::compileWhereHavingStatement
     *
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
    protected function compileWhereHavingStatement( $cacheKey )
    {
        if ( count( $this->builderCache[ $cacheKey ] ) > 0 ) {
            for ( $i = 0, $c = count( $this->builderCache[ $cacheKey ] ); $i < $c; $i++ ) {
                // Is this condition already compiled?
                if ( is_string( $this->builderCache[ $cacheKey ][ $i ] ) ) {
                    continue;
                } elseif ( $this->builderCache[ $cacheKey ][ $i ][ 'escape' ] === false ) {
                    $this->builderCache[ $cacheKey ][ $i ]
                        = $this->builderCache[ $cacheKey ][ $i ][ 'condition' ];
                    continue;
                }

                // Split multiple conditions
                $conditions = preg_split(
                    '/((?:^|\s+)AND\s+|(?:^|\s+)OR\s+)/i',
                    $this->builderCache[ $cacheKey ][ $i ][ 'condition' ],
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

                    $conditions[ $ci ] = $matches[ 1 ] . $this->conn->protectIdentifiers( trim( $matches[ 2 ] ) )
                        . ' ' . trim( $matches[ 3 ] ) . $matches[ 4 ] . $matches[ 5 ];
                }

                $this->builderCache[ $cacheKey ][ $i ] = implode( '', $conditions );
            }

            if ( $cacheKey === 'having' ) {
                return "\n" . sprintf(
                        'HAVING %s',
                        implode( "\n", $this->builderCache[ 'having' ] )
                    );
            }

            if ( $cacheKey === 'between' ) {
                return "\n" . sprintf(
                        'BETWEEN %s',
                        implode( "\n", $this->builderCache[ 'between' ] )
                    );
            }

            if ( $cacheKey === 'not_between' ) {
                return "\n" . sprintf(
                        'NOT BETWEEN %s',
                        implode( "\n", $this->builderCache[ 'not_between' ] )
                    );
            }

            return "\n" . sprintf(
                    'WHERE %s',
                    implode( "\n", $this->builderCache[ $cacheKey ] )
                );
        }

        return '';
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::compileOrderByStatement
     *
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
    protected function compileOrderByStatement()
    {
        if ( is_array( $this->builderCache[ 'orderBy' ] ) && count( $this->builderCache[ 'orderBy' ] ) > 0 ) {
            for ( $i = 0, $c = count( $this->builderCache[ 'orderBy' ] ); $i < $c; $i++ ) {
                if ( $this->builderCache[ 'orderBy' ][ $i ][ 'escape' ] !== false
                    && ! $this->isLiteral(
                        $this->builderCache[ 'orderBy' ][ $i ][ 'field' ]
                    )
                ) {
                    $this->builderCache[ 'orderBy' ][ $i ][ 'field' ] = $this->conn->protectIdentifiers(
                        $this->builderCache[ 'orderBy' ][ $i ][ 'field' ]
                    );
                }

                $this->builderCache[ 'orderBy' ][ $i ] = $this->builderCache[ 'orderBy' ][ $i ][ 'field' ]
                    . $this->builderCache[ 'orderBy' ][ $i ][ 'direction' ];
            }

            return $this->builderCache[ 'orderBy' ] = "\n" . sprintf(
                    'ORDER BY %s',
                    implode( ', ', $this->builderCache[ 'orderBy' ] )
                );
        } elseif ( is_string( $this->builderCache[ 'orderBy' ] ) ) {
            return $this->builderCache[ 'orderBy' ];
        }

        return '';
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::isLiteral
     *
     * Determines if a string represents a literal value or a field name
     *
     * @param    string $string
     *
     * @return    bool
     */
    protected function isLiteral( $string )
    {
        $string = trim( $string );

        if ( empty( $string ) || ctype_digit( $string ) || (string)(float)$string === $string
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
            $stringArray = ( $this->conn->getConfig( 'escapeCharacter' ) !== '"' )
                ? [ '"', "'" ]
                : [ "'" ];
        }

        return in_array( $string[ 0 ], $stringArray, true );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::compileLimitStatement
     *
     * @return string
     */
    protected function compileLimitStatement()
    {
        if ( $this->builderCache[ 'limit' ] ) {
            if ( $this->builderCache[ 'offset' ] ) {
                return sprintf(
                    'LIMIT %s,%s',
                    $this->builderCache[ 'limit' ],
                    $this->builderCache[ 'offset' ]
                );
            }

            return "\n" . sprintf(
                    'LIMIT %s',
                    $this->builderCache[ 'limit' ]
                );
        }
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::updateBatch
     *
     * Execute UPDATE batch SQL Query
     *
     * @param array  $sets      Array of data sets[][field => value]
     * @param string $index     Index field
     * @param int    $batchSize Maximum batch size
     * @param bool   $escape    Whether to escape values and identifiers
     *
     * @return bool
     */
    public function updateBatch( array $sets, $index = null, $batchSize = 1000, $escape = null )
    {
        $this->setUpdateBatch( $sets, $index );

        $affectedRows = 0;
        for ( $i = 0, $total = count( $this->builderCache[ 'sets' ] ); $i < $total; $i += $batchSize ) {
            $sql = $this->platformUpdateBatchStatement(
                $this->builderCache[ 'from' ][ 0 ],
                array_slice( $this->builderCache[ 'sets' ], $i, $batchSize ),
                $this->conn->protectIdentifiers( $index )
            );

            if ( $this->isTestQuery ) {
                ++$affectedRows;
            } else {
                $this->conn->query( $sql, $this->builderCache[ 'binds' ] );
                $affectedRows += $this->conn->getAffectedRows();
            }

            $this->builderCache[ 'where' ] = [];
        }

        if ( ! $this->isTestQuery ) {
            $this->resetModifier();
        }

        return $affectedRows;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::setUpdateBatch
     *
     * The "setUpdateBatch" function.  Allows key/value pairs to be set for batch updating
     *
     * @param mixed  $sets
     * @param string $index
     * @param bool   $escape
     *
     * @return static
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    protected function setUpdateBatch( array $sets, $index = '', $escape = null )
    {
        $sets = $this->batchObjectToArray( $sets );

        if ( ! is_array( $sets ) ) {
            // @todo error
        }

        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        foreach ( $sets as $set ) {
            $indexSet = false;
            $cleanSets = [];
            foreach ( $set as $key => $value ) {
                if ( $key === $index ) {
                    $indexSet = true;
                }

                $bind = $this->bind( $key, $value );

                $cleanSets[ $this->conn->protectIdentifiers( $key, false, $escape ) ] = ':' . $bind;
            }

            if ( $indexSet === false ) {
                // 'One or more rows submitted for batch updating is missing the specified index.'
                throw new RuntimeException( 'E_DATABASE_BATCH_UPDATE_MISSING_INDEX' );
            }

            $this->builderCache[ 'sets' ] = $cleanSets;
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::platformUpdateBatchStatement
     *
     * Generates a platform-specific batch update string from the supplied data.
     *
     * @param    string $table  Table name
     * @param    array  $values Update data
     * @param    string $index  WHERE key
     *
     * @return    string
     */
    protected function platformUpdateBatchStatement( $table, $values, $index )
    {
        $ids = [];
        $columns = [];

        foreach ( $values as $key => $value ) {
            $ids[] = $value[ $index ];

            foreach ( array_keys( $value ) as $field ) {
                if ( $field !== $index ) {
                    $columns[ $field ][] = 'WHEN ' . $index . ' = ' . $value[ $index ] . ' THEN ' . $value[ $field ];
                }
            }
        }

        $cases = '';
        foreach ( $columns as $key => $value ) {
            $cases .= $key . " = CASE \n"
                . implode( "\n", $value ) . "\n"
                . 'ELSE ' . $key . ' END, ';
        }

        $this->where( $index . ' IN(' . implode( ',', $ids ) . ')', null, false );

        return 'UPDATE ' . $table . ' SET ' . substr( $cases, 0, -2 ) . $this->compileWhereHavingStatement( 'where' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::delete
     *
     * Compiles a delete string and runs the query
     *
     * @param array $where Where clause.
     * @param int   $limit Limit clause.
     *
     * @return string
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function delete( $where = [], $limit = null )
    {
        $this->where( $where );

        if ( isset( $limit ) ) {
            $this->limit( $limit );
        }

        $sqlStatement = $this->platformDeleteStatement(
            $this->conn->protectIdentifiers(
                $this->builderCache[ 'from' ][ 0 ],
                true,
                $this->conn->isProtectIdentifiers,
                false
            )
        );

        if ( $this->isTestQuery ) {
            return $sqlStatement;
        }

        $this->resetModifier();

        return $this->conn->query( $sqlStatement, $this->builderCache[ 'binds' ] );
    }

    //--------------------------------------------------------------------

    /**
     * Delete statement
     *
     * Generates a platform-specific delete string from the supplied data
     *
     * @param    string    the table name
     *
     * @return    string
     */
    protected function platformDeleteStatement( $table )
    {
        return 'DELETE FROM ' . $table
            . $this->compileWhereHavingStatement( 'where' )
            . $this->compileLimitStatement();
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::flush
     *
     * Compiles a truncate string and runs the query
     * If the database does not support the truncate() command
     * This function maps to "DELETE FROM table"
     *
     * @param bool $escape Whether to table identifiers
     *
     * @return bool TRUE on success, FALSE on failure
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function flush( $table, $escape = null )
    {
        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        if ( $escape ) {
            $table = $this->conn->protectIdentifiers( $table, true, true );
        }

        $sqlStatement = $this->platformDeleteStatement( $table );

        if ( $this->isTestQuery === true ) {
            return $sqlStatement;
        }

        $this->resetModifier();

        return $this->conn->query( $sqlStatement );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::truncate
     *
     * Compiles a truncate string and runs the query
     * If the database does not support the truncate() command
     * This function maps to "DELETE FROM table"
     *
     * @param bool $escape Whether to table identifiers
     *
     * @return bool TRUE on success, FALSE on failure
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function truncate( $table, $escape = null )
    {
        is_bool( $escape ) || $escape = $this->conn->isProtectIdentifiers;

        if ( $escape ) {
            $table = $this->conn->protectIdentifiers( $table, true, true );
        }

        $sqlStatement = sprintf( 'TRUNCATE %s', $table );

        if ( $this->isTestQuery === true ) {
            return $sqlStatement;
        }

        $this->resetModifier();

        return $this->conn->query( $sqlStatement );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::binds
     *
     * @param array $binds
     *
     * @return static
     */
    public function binds( array $binds )
    {
        foreach ( $binds as $field => $value ) {
            $this->bind( $field, $value );
        }

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::subQuery
     *
     * Performs Query Builder sub query mode.
     *
     * @return \O2System\Database\Abstracts\AbstractQueryBuilder
     */
    public function subQuery()
    {
        $subQuery = clone $this;
        $subQuery->reset();
        $subQuery->conn = null;
        $subQuery->isSubQuery = true;

        return $subQuery;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::platformTruncateStatement
     *
     * Generates a platform-specific truncate statement.
     *
     * @param    string    the table name
     *
     * @return    string
     */
    protected function platformTruncateStatement( $table )
    {
        return 'TRUNCATE ' . $table;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::compileIntoStatement
     *
     * @return string
     */
    protected function compileUnionStatement()
    {
        $sqlStatement = '';

        if ( count( $this->builderCache[ 'union' ] ) ) {
            foreach ( $this->builderCache[ 'union' ] as $union ) {
                $sqlStatement .= "\n UNION \n" . $union;
            }
        }

        if ( count( $this->builderCache[ 'union_all' ] ) ) {
            foreach ( $this->builderCache[ 'union_all' ] as $union ) {
                $sqlStatement .= "\n UNION ALL \n" . $union;
            }
        }

        return trim( $sqlStatement );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::compileIntoStatement
     *
     * @return string
     */
    protected function compileIntoStatement()
    {
        return "\n" . $this->builderCache[ 'into' ];
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::compileFromStatement
     *
     * @return string
     */
    protected function compileFromStatement()
    {
        if ( count( $this->builderCache[ 'from' ] ) > 0 ) {
            return "\n" . sprintf(
                    'FROM %s',
                    implode( ',', array_unique( $this->builderCache[ 'from' ] ) )
                );
        }
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::compileJoinStatement
     *
     * @return string
     */
    protected function compileJoinStatement()
    {
        if ( count( $this->builderCache[ 'join' ] ) > 0 ) {
            return "\n" . implode( "\n", $this->builderCache[ 'join' ] );
        }
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::compileWhereStatement
     *
     * @return string
     */
    protected function compileWhereStatement()
    {
        return $this->compileWhereHavingStatement( 'where' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::compileHavingStatement
     *
     * @return string
     */
    protected function compileHavingStatement()
    {
        return $this->compileWhereHavingStatement( 'having' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::compileHavingStatement
     *
     * @return string
     */
    protected function compileBetweenStatement()
    {
        return $this->compileWhereHavingStatement( 'between' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::compileHavingStatement
     *
     * @return string
     */
    protected function compileNotBetweenStatement()
    {
        return $this->compileWhereHavingStatement( 'not_between' );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::compileGroupByStatement
     *
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
    protected function compileGroupByStatement()
    {
        if ( count( $this->builderCache[ 'groupBy' ] ) > 0 ) {
            for ( $i = 0, $c = count( $this->builderCache[ 'groupBy' ] ); $i < $c; $i++ ) {
                // Is it already compiled?
                if ( is_string( $this->builderCache[ 'groupBy' ][ $i ] ) ) {
                    continue;
                }

                $this->builderCache[ 'groupBy' ][ $i ] = ( $this->builderCache[ 'groupBy' ][ $i ][ 'escape' ]
                    === false OR
                    $this->isLiteral(
                        $this->builderCache[ 'groupBy' ][ $i ][ 'field' ]
                    ) )
                    ? $this->builderCache[ 'groupBy' ][ $i ][ 'field' ]
                    : $this->conn->protectIdentifiers( $this->builderCache[ 'groupBy' ][ $i ][ 'field' ] );
            }

            return "\n" . sprintf(
                    'GROUP BY %s',
                    implode( ', ', $this->builderCache[ 'groupBy' ] )
                );
        }

        return '';
    }
}