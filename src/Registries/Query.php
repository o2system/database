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

use O2System\Database\Abstracts\AbstractConnection;
use O2System\Database\Abstracts\AbstractDriver;

/**
 * Class Query
 *
 * @package O2System\DB\Registries
 */
class Query
{
    /**
     * Query::$conn
     *
     * The database connection instance.
     *
     * @var AbstractConnection
     */
    private $conn;

    /**
     * Query::$sqlStatement
     *
     * The SQL Statement.
     *
     * @var string
     */
    private $sqlStatement;

    /**
     * Query::$sqlBinds
     *
     * The SQL Statement bindings.
     *
     * @var array
     */
    private $sqlBinds = [ ];

    /**
     * Query::$sqlBindMarker
     *
     * The SQL Statement bindings marker character.
     *
     * @var string
     */
    private $sqlBindMarker = '?';

    /**
     * Query::$sqlFinalStatement
     *
     * The compiled SQL Statement with SQL Statement binders.
     *
     * @var string
     */
    private $sqlFinalStatement;

    /**
     * Query::$startExecutionTime
     *
     * The start time in seconds with microseconds
     * for when this query was executed.
     *
     * @var float
     */
    private $startExecutionTime;

    /**
     * Query::$endExecutionTime
     *
     * The end time in seconds with microseconds
     * for when this query was executed.
     *
     * @var float
     */
    private $endExecutionTime;

    /**
     * Query::$affectedRows
     *
     * Numbers of affected rows.
     *
     * @var int
     */
    private $affectedRows = 0;

    /**
     * Query::$error
     *
     * The query execution error info.
     *
     * @var array
     */
    private $error;

    //--------------------------------------------------------------------

    /**
     * Query::__construct
     *
     * @param AbstractConnection $conn
     */
    public function __construct ( AbstractConnection &$conn )
    {
        $this->conn = $conn;
    }

    //--------------------------------------------------------------------

    /**
     * Query::setStatement
     *
     * Sets the raw query string to use for this statement.
     *
     * @param string $sqlStatement The SQL Statement.
     * @param array  $sqlBinds     The SQL Statement bindings.
     *
     * @return Query
     */
    public function setStatement ( $sqlStatement, array $sqlBinds = [ ] )
    {
        $this->sqlStatement = $sqlStatement;
        $this->sqlBinds = $sqlBinds;

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * Query::setBinds
     *
     * Will store the variables to bind into the query later.
     *
     * @param array $sqlBinds
     *
     * @return Query
     */
    public function setBinds ( array $sqlBinds )
    {
        $this->sqlBinds = $sqlBinds;

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * Query::setDuration
     *
     * Records the execution time of the statement using microtime(true)
     * for it's start and end values. If no end value is present, will
     * use the current time to determine total duration.
     *
     * @param int      $start
     * @param int|null $end
     *
     * @return Query
     */
    public function setDuration ( $start, $end = null )
    {
        $this->startExecutionTime = $start;

        if ( is_null( $end ) ) {
            $end = microtime( true );
        }

        $this->endExecutionTime = $end;

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * Query::getStartExecutionTime
     *
     * Returns the start time in seconds with microseconds.
     *
     * @param bool $numberFormat
     * @param int  $decimals
     *
     * @return mixed
     */
    public function getStartExecutionTime ( $numberFormat = false, $decimals = 6 )
    {
        if ( ! $numberFormat ) {
            return $this->startExecutionTime;
        }

        return number_format( $this->startExecutionTime, $decimals );
    }

    //--------------------------------------------------------------------

    /**
     * Query::getExecutionDuration
     *
     * Returns the duration of this query during execution, or null if
     * the query has not been executed yet.
     *
     * @param int $decimals The accuracy of the returned time.
     *
     * @return mixed
     */
    public function getExecutionDuration ( $decimals = 6 )
    {
        return number_format( ( $this->endExecutionTime - $this->startExecutionTime ), $decimals );
    }

    //--------------------------------------------------------------------

    /**
     * Query::setErrorInfo
     *
     * Stores the occurred error information when the query was executed.
     *
     * @param int    $errorCode
     * @param string $errorMessage
     *
     * @return $this
     */
    public function setError ( $errorCode, $errorMessage )
    {
        $this->error[ $errorCode ] = $errorMessage;

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * Query::getErrorCode
     *
     * Get the query error information.
     *
     * @return bool|int Returns FALSE when there is no error.
     */
    public function getErrorCode ()
    {
        if ( $this->hasError() ) {
            return key( $this->error );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * Query::hasError
     *
     * Check if the latest query execution has an error.
     *
     * @return bool
     */
    public function hasError ()
    {
        return ! empty( $this->error );
    }

    //--------------------------------------------------------------------

    /**
     * Query::getErrorMessage
     *
     * Get the query error information.
     *
     * @return bool|string Returns FALSE when there is no error.
     */
    public function getErrorMessage ()
    {
        if ( $this->hasError() ) {
            return (string) reset( $this->error );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * Query::setAffectedRows
     *
     * Sets numbers of affected rows.
     *
     * @param int $affectedRows Numbers of affected rows,
     */
    public function setAffectedRows( $affectedRows ) {

    }

    /**
     * Query::isWriteSyntax
     *
     * Determines if the SQL statement is a write-syntax query or not.
     *
     * @return bool
     */
    public function isWriteSyntax ()
    {
        return (bool) preg_match(
            '/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|TRUNCATE|LOAD|COPY|ALTER|RENAME|GRANT|REVOKE|LOCK|UNLOCK|REINDEX)\s/i',
            $this->sqlStatement
        );
    }

    //--------------------------------------------------------------------

    /**
     * Query::replacePrefix
     *
     * Replace all table prefix with new prefix.
     *
     * @param string $search
     * @param string $replace
     *
     * @return mixed
     */
    public function swapTablePrefix ( $search, $replace )
    {
        $sql = empty( $this->sqlFinalStatement ) ? $this->sqlStatement : $this->sqlFinalStatement;

        $this->sqlFinalStatement = preg_replace( '/(\W)' . $search . '(\S+?)/', '\\1' . $replace . '\\2', $sql );

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * Query::getSqlStatement
     *
     * Get the original SQL statement.
     *
     * @return string   The SQL statement string.
     */
    public function getSqlStatement ()
    {
        return $this->sqlStatement;
    }

    //--------------------------------------------------------------------

    /**
     * Query::__toString
     *
     * Convert this query into compiled SQL Statement string.
     *
     * @return string
     */
    public function __toString ()
    {
        return (string) $this->getFinalStatement();
    }

    //--------------------------------------------------------------------

    /**
     * Query::getQuery
     *
     * Returns the final, processed query string after binding, etal
     * has been performed.
     *
     * @return string
     */
    public function getFinalStatement ()
    {
        if ( empty( $this->sqlFinalStatement ) ) {
            $this->sqlFinalStatement = $this->sqlStatement;
        }

        $this->compileSqlBinds();

        return $this->sqlFinalStatement;
    }

    //--------------------------------------------------------------------

    /**
     * Query::compileSqlBinds
     *
     * Escapes and inserts any binds into the final SQL statement object.
     *
     * @return void
     */
    protected function compileSqlBinds ()
    {
        $sqlStatement = $this->sqlFinalStatement;

        $hasSqlBinders = strpos( $sqlStatement, ':' ) !== false;

        if ( empty( $this->sqlBinds ) || empty( $this->sqlBindMarker ) ||
             ( strpos( $sqlStatement, $this->sqlBindMarker ) === false &&
               $hasSqlBinders === false )
        ) {
            return;
        }

        if ( ! is_array( $this->sqlBinds ) ) {
            $sqlBinds = [ $this->sqlBinds ];
            $bindCount = 1;
        } else {
            $sqlBinds = $this->sqlBinds;
            $bindCount = count( $sqlBinds );
        }

        // Reverse the binds so that duplicate named binds
        // will be processed prior to the original binds.
        if ( ! is_numeric( key( array_slice( $sqlBinds, 0, 1 ) ) ) ) {
            $sqlBinds = array_reverse( $sqlBinds );
        }

        // We'll need marker length later
        $markerLength = strlen( $this->sqlBindMarker );

        if ( $hasSqlBinders ) {
            $sqlStatement = $this->replaceNamedBinds( $sqlStatement, $sqlBinds );
        } else {
            $sqlStatement = $this->replaceSimpleBinds( $sqlStatement, $sqlBinds, $bindCount, $markerLength );
        }

        $this->sqlFinalStatement = $sqlStatement;
    }

    //--------------------------------------------------------------------

    /**
     * Query::matchNamedBinds
     *
     * Match bindings.
     *
     * @param string $sqlStatement
     * @param array  $sqlBinds
     *
     * @return string
     */
    protected function replaceNamedBinds ( $sqlStatement, array $sqlBinds )
    {
        foreach ( $sqlBinds as $bindSearch => $bindReplace ) {
            $escapedValue = $this->conn->escape( $bindReplace );

            // In order to correctly handle backlashes in saved strings
            // we will need to preg_quote, so remove the wrapping escape characters
            // otherwise it will get escaped.
            if ( is_array( $bindReplace ) ) {
                foreach ( $bindReplace as &$bindReplaceItem ) {
                    $bindReplaceItem = preg_quote( $bindReplaceItem );
                }

                $escapedValue = '(' . implode( ',', $escapedValue ) . ')';
            } else {
                $escapedValue = preg_quote( trim( $escapedValue, $this->conn->getEscapeCharacter() ) );
            }

            if ( preg_match( "/\(.+?\)/", $bindSearch ) ) {
                $bindSearch = str_replace( '(', '\(', str_replace( ')', '\)', $bindSearch ) );
            }

            $sqlStatement = preg_replace( '/:' . $bindSearch . '(?!\w)/', $escapedValue, $sqlStatement );
        }

        return $sqlStatement;
    }

    //--------------------------------------------------------------------

    /**
     * Query::matchSimpleBinds
     *
     * Match bindings
     *
     * @param string $sqlStatement
     * @param array  $sqlBinds
     * @param int    $bindCount
     * @param int    $markerLength
     *
     * @return string
     */
    protected function replaceSimpleBinds ( $sqlStatement, array $sqlBinds, $bindCount, $markerLength )
    {
        // Make sure not to replace a chunk inside a string that happens to match the bind marker
        if ( $chunk = preg_match_all( "/'[^']*'/i", $sqlStatement, $matches ) ) {
            $chunk = preg_match_all(
                '/' . preg_quote( $this->sqlBindMarker, '/' ) . '/i',
                str_replace(
                    $matches[ 0 ],
                    str_replace( $this->sqlBindMarker, str_repeat( ' ', $markerLength ), $matches[ 0 ] ),
                    $sqlStatement,
                    $chunk
                ),
                $matches,
                PREG_OFFSET_CAPTURE
            );

            // Bind values' count must match the count of markers in the query
            if ( $bindCount !== $chunk ) {
                return $sqlStatement;
            }
        } // Number of binds must match bindMarkers in the string.
        else if ( ( $chunk = preg_match_all(
                '/' . preg_quote( $this->sqlBindMarker, '/' ) . '/i',
                $sqlStatement,
                $matches,
                PREG_OFFSET_CAPTURE
            ) ) !== $bindCount
        ) {
            return $sqlStatement;
        }

        do {
            $chunk--;
            $escapedValue = $this->conn->escape( $sqlBinds[ $chunk ] );
            if ( is_array( $escapedValue ) ) {
                $escapedValue = '(' . implode( ',', $escapedValue ) . ')';
            }
            $sqlStatement = substr_replace( $sqlStatement, $escapedValue, $matches[ 0 ][ $chunk ][ 1 ], $markerLength );
        }
        while ( $chunk !== 0 );

        return $sqlStatement;
    }
}