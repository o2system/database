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

use O2System\Database\Datastructures\Result;
use O2System\Database\Interfaces\ActiveRecordInterface;
use O2System\Database\Interfaces\DriverInterface;
use O2System\Database\Interfaces\QueryBuilderInterface;
use O2System\Database\Registries\Config;
use O2System\Database\Registries\Query;
use O2System\Database\Traits\QueryBuilderTrait;
use O2System\Kernel\Spl\Exceptions\Runtime\ConnectionException;

/**
 * Class AbstractDriver
 *
 * @package O2System\Database\Abstracts
 */
abstract class AbstractDriver implements
    DriverInterface,
    QueryBuilderInterface
{
    use QueryBuilderTrait;

    /**
     * If true, no queries will actually be
     * ran against the database.
     *
     * @var bool
     */
    public $isTestMode = false;

    /**
     * Swap Prefix
     *
     * @var    string
     */
    public $swapTablePrefix;

    /**
     * AbstractDriver::$platform
     *
     * Database driver platform name.
     *
     * @var string
     */
    protected $platform;

    /**
     * AbstractDriver::$config
     *
     * Database driver configuration
     *
     * @var Config
     */
    protected $config;

    /**
     * Microtime when connection was made
     *
     * @var float
     */
    protected $connectTimeStart;

    /**
     * How long it took to establish connection.
     *
     * @var float
     */
    protected $connectTimeDuration;

    /**
     * Connection ID
     *
     * @var    object|resource
     */
    protected $connID = false;

    /**
     * Result ID
     *
     * @var    object|resource
     */
    protected $resultID = false;

    /**
     * Array of query objects that have executed
     * on this connection.
     *
     * @var array
     */
    protected $queriesCache = [ ];

    /**
     * List of reserved identifiers
     *
     * Identifiers that must NOT be escaped.
     *
     * @var    string[]
     */
    protected $reservedIdentifiers = [ '*' ];

    /**
     * Identifier escape character
     *
     * @var    string
     */
    protected $escapeCharacter = '"';

    /**
     * ESCAPE statement string
     *
     * @var    string
     */
    protected $likeEscapeString = " ESCAPE '%s' ";

    /**
     * ESCAPE character
     *
     * @var    string
     */
    protected $likeEscapeCharacter = '!';

    /**
     * Holds previously looked up data
     * for performance reasons.
     *
     * @var array
     */
    protected $activeRecordCache = [ ];

    // ------------------------------------------------------------------------

    /**
     * AbstractDriver::__construct
     *
     * @param \O2System\Database\Registries\Config $config
     *
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function __construct ( Config $config )
    {
        $this->config = $config;

        $this->connect(
            ( $config->offsetExists( 'persistent' )
                ? $config->persistent
                : $config->persistent
            )
        );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractDriver::connect
     *
     * Establish the connection.
     *
     * @param bool $persistent
     *
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     *
     * @return void
     */
    final public function connect ( $persistent = true )
    {
        /* If an established connection is available, then there's
         * no need to connect and select the database.
         *
         * Depending on the database driver, conn_id can be either
         * boolean TRUE, a resource or an object.
         */
        if ( $this->connID ) {
            return;
        }

        //--------------------------------------------------------------------

        $this->connectTimeStart = microtime( true );

        // Connect to the database and set the connection ID
        $this->platformConnectHandler( $this->config );

        // No connection resource? Check if there is a failover else throw an error
        if ( ! $this->connID ) {
            // Check if there is a failover set
            if ( ! empty( $this->config[ 'failover' ] ) && is_array( $this->config[ 'failover' ] ) ) {
                // Go over all the failovers
                foreach ( $this->config[ 'failover' ] as $failover ) {

                    // Try to connect
                    $this->platformConnectHandler( $failover = new Config( $failover ) );

                    // If a connection is made break the foreach loop
                    if ( $this->connID ) {
                        $this->config = $failover;
                        break;
                    }
                }
            }

            // We still don't have a connection?
            if ( ! $this->connID ) {
                throw new ConnectionException( 'E_DB_UNABLE_TO_CONNECT', 0, [ $this->platform ] );
            }
        }

        $this->connectTimeDuration = microtime( true ) - $this->connectTimeStart;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractDriver::platformConnectHandler
     *
     * Driver dependent way method for open the connection.
     *
     * @param Config $config The connection configuration.
     *
     * @return mixed
     */
    abstract protected function platformConnectHandler ( Config $config );

    // ------------------------------------------------------------------------

    /**
     * AbstractDriver::isSupported
     *
     * Check if the platform is supported.
     *
     * @return bool
     */
    abstract public function isSupported ();

    // ------------------------------------------------------------------------

    /**
     * AbstractDriver::getPlatform
     *
     * Get the name of the database platform of this connection.
     *
     * @return string The name of the database platform.
     */
    public function getPlatform ()
    {
        return $this->platform;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractDriver::isConnected
     *
     * Determine if the connection is connected
     *
     * @return bool
     */
    final public function isConnected ()
    {
        return (bool) ( $this->connID === false
            ? false
            : true );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractDriver::disconnect
     *
     * Disconnect database connection.
     *
     * @return void
     */
    final public function disconnect ()
    {
        if ( $this->connID ) {
            $this->platformDisconnectHandler();
            $this->connID = false;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractDriver::disconnectHandler
     *
     * Driver dependent way method for closing the connection.
     *
     * @return mixed
     */
    abstract protected function platformDisconnectHandler ();

    //--------------------------------------------------------------------

    /**
     * AbstractDriver::getConnectionTimeStart
     *
     * Returns the time we started to connect to this database in
     * seconds with microseconds.
     *
     * Used by the Debug Toolbar's timeline.
     *
     * @return float
     */
    final public function getConnectTimeStart ()
    {
        return (int) $this->connectTimeStart;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractDriver::getConnectTimeDuration
     *
     * Returns the number of seconds with microseconds that it took
     * to connect to the database.
     *
     * Used by the Debug Toolbar's timeline.
     *
     * @param int $decimals
     *
     * @return mixed
     */
    final public function getConnectTimeDuration ( $decimals = 6 )
    {
        return number_format( $this->connectTimeDuration, $decimals );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractDriver::getQueries
     *
     * Returns Queries Collections
     *
     * @return array
     */
    final public function getQueries ()
    {
        return $this->queriesCache;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractDriver::getQueriesCount
     *
     * Returns the total number of queries that have been performed
     * on this connection.
     *
     * @return int
     */
    final public function getQueriesCount ()
    {
        return (int) count( $this->queriesCache );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractDriver::getLastQuery
     *
     * Returns the last query's statement object.
     *
     * @return Query
     */
    final public function getLastQuery ()
    {
        return end( $this->queriesCache );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractDriver::query
     *
     * @param string $sqlStatement
     * @param array  $binds
     *
     * @return \O2System\Database\Datastructures\Result|\O2System\Database\Registries\Query
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function query ( $sqlStatement, array $binds = [ ] )
    {
        if ( empty( $this->connID ) ) {
            $this->connect();
        }

        $query = new Query( $this );

        $query->setStatement( $sqlStatement, $binds );

        if ( ! empty( $this->swapTablePrefix ) && ! empty( $this->config->tablePrefix ) ) {
            $query->replacePrefix( $this->config->tablePrefix, $this->swapTablePrefix );
        }

        if ( $this->isTestMode ) {
            return $query->getFinalStatement();
        } elseif ( $query->isWriteSyntax() ) {
            return $this->execute( $query->getFinalStatement() );
        } else {
            $startTime = microtime( true );
            $rows = $this->platformQueryHandler( $query );
            $query->setDuration( $startTime );

            $this->queriesCache[] = $query;

            return new Result( $rows );
        }
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::execute
     *
     * Execute SQL statement against database.
     *
     * @param string $sqlStatement The SQL statement.
     *
     * @return bool
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function execute ( $sqlStatement )
    {
        if ( empty( $this->connID ) ) {
            $this->connect();
        }

        $query = new Query( $this );
        $query->setStatement( $sqlStatement );

        $startTime = microtime( true );
        $result = $this->platformExecuteHandler( $query );
        $query->setDuration( $startTime );

        $this->queriesCache[] = $query;


        return $result;
    }

    /**
     * AbstractConnection::executeHandler
     *
     * Driver dependent way method for execute the SQL statement.
     *
     * @param Query $query Query object.
     *
     * @return array
     */
    abstract protected function platformExecuteHandler ( Query &$query );

    // ------------------------------------------------------------------------

    /**
     * AbstractDriver::platformQueryHandler
     *
     * Driver dependent way method for execute the SQL statement.
     *
     * @param Query $query Query object.
     *
     * @return array
     */
    abstract protected function platformQueryHandler ( Query &$query );

    // ------------------------------------------------------------------------

    public function getEscapeCharacter ()
    {
        return $this->escapeCharacter;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractDriver::escape
     *
     * Escape string
     *
     * @param $string
     *
     * @return int|string
     */
    final public function escape ( $string )
    {
        if ( is_array( $string ) ) {
            $string = array_map( [ &$this, 'escape' ], $string );

            return $string;
        } else if ( is_string( $string ) OR ( is_object( $string ) && method_exists( $string, '__toString' ) ) ) {
            return "'" . $this->escapeString( $string ) . "'";
        } else if ( is_bool( $string ) ) {
            return ( $string === false )
                ? 0
                : 1;
        } else if ( $string === null ) {
            return 'NULL';
        }

        return $string;
    }

    /**
     * AbstractDriver::escapeString
     *
     * Escape String
     *
     * @param string|\string[] $string
     * @param bool             $like
     *
     * @return array|string|\string[]
     */
    final public function escapeString ( $string, $like = false )
    {
        if ( is_array( $string ) ) {
            foreach ( $string as $key => $value ) {
                $string[ $key ] = $this->escapeString( $value, $like );
            }

            return $string;
        }

        $string = $this->platformEscapeStringHandler( $string );

        // escape LIKE condition wildcards
        if ( $like === true ) {
            $string = str_replace(
                [ $this->likeEscapeCharacter, '%', '_' ],
                [
                    $this->likeEscapeCharacter . $this->likeEscapeCharacter,
                    $this->likeEscapeCharacter . '%',
                    $this->likeEscapeCharacter . '_',
                ],
                $string
            );
        }

        // fixed escaping string bugs !_
        $string = str_replace( '!_', '_', $string );

        return $string;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::escapeHandler
     *
     * Driver independent string escape.
     *
     * Will likely be overridden in child classes.
     *
     * @param string $string
     *
     * @return string
     */
    protected function platformEscapeStringHandler ( $string )
    {
        return str_replace( "'", "''", remove_invisible_characters( $string ) );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractDriver::likeString
     *
     * Escape Like String
     *
     * @param $string
     *
     * @return array|string|\string[]
     */
    final public function escapeLikeString ( $string )
    {
        return $this->escapeString( $string, true );
    }

    // ------------------------------------------------------------------------

    /**
     * ActiveRecordInterface::setTablePrefix
     *
     * @param string $tablePrefix The database table prefix.
     *
     * @return string
     */
    final public function setTablePrefix ( $tablePrefix )
    {
        return $this->config->tablePrefix = $tablePrefix;
    }

    //--------------------------------------------------------------------

    /**
     * ActiveRecordInterface::setSwapTablePrefix
     *
     * @param string $tablePrefix The database table prefix.
     *
     * @return string
     */
    final public function setSwapTablePrefix ( $tablePrefix )
    {
        return $this->swapTablePrefix = $tablePrefix;
    }

    // ------------------------------------------------------------------------

    /**
     * ActiveRecordInterface::prefixTable
     *
     * @param string $tableName Database table name.
     *
     * @return string Returns prefixed table name.
     */
    final public function prefixTable ( $tableName )
    {
        return $this->config->tablePrefix . $tableName;
    }

    // ------------------------------------------------------------------------

    /**
     * ActiveRecordInterface::isDatabaseExists
     *
     * Check if the database exists or not.
     *
     * @param string $databaseName The database name.
     *
     * @return bool Returns false if database doesn't exists.
     */
    final public function isDatabaseExists ( $databaseName )
    {
        $databases = empty( $this->activeRecordCache[ 'databaseNames' ] )
            ? $this->getDatabases()
            : $this->activeRecordCache[ 'databaseNames' ];

        return (bool) in_array( $databaseName, $databases );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getDatabaseList
     *
     * Get list of current connection databases.
     *
     * @return array Returns an array.
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function getDatabases ()
    {
        if ( empty( $this->activeRecordCache[ 'databaseNames' ] ) ) {
            $result = $this->execute( 'SHOW DATABASES' );

            if ( $result->count() ) {
                foreach ( $result as $row ) {
                    $this->activeRecordCache[ 'databaseNames' ][] = $row->offsetGet( 'Database' );
                }
            }
        }

        return $this->activeRecordCache[ 'databaseNames' ];
    }

    /**
     * AbstractConnection::isTableExists
     *
     * Check if table exists at current connection database.
     *
     * @param $table
     *
     * @return bool
     */
    public function isTableExists ( $table )
    {
        $tables = empty( $this->activeRecordCache[ 'tableNames' ] )
            ? $this->getTables()
            : $this->activeRecordCache[ 'tableNames' ];

        return (bool) in_array( $this->protectIdentifiers( $table, true, false, false ), $tables );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractDriver::protectIdentifiers
     *
     * This function is used extensively by the Query Builder class, and by
     * a couple functions in this class.
     * It takes a column or table name (optionally with an alias) and inserts
     * the table prefix onto it. Some logic is necessary in order to deal with
     * column names that include the path. Consider a query like this:
     *
     * SELECT hostname.database.table.column AS c FROM hostname.database.table
     *
     * Or a query with aliasing:
     *
     * SELECT m.member_id, m.member_name FROM members AS m
     *
     * Since the column name can include up to four segments (host, DB, table, column)
     * or also have an alias prefix, we need to do a bit of work to figure this out and
     * insert the table prefix (if it exists) in the proper position, and escape only
     * the correct identifiers.
     *
     * @param    string|array
     * @param    bool
     * @param    mixed
     * @param    bool
     *
     * @return    string
     */
    final public function protectIdentifiers (
        $item,
        $prefixSingle = false,
        $protectIdentifiers = null,
        $fieldExists = true
    ) {
        if ( ! is_bool( $protectIdentifiers ) ) {
            $protectIdentifiers = $this->protectIdentifiers;
        }

        if ( is_array( $item ) ) {
            $escapedArray = [ ];
            foreach ( $item as $key => $value ) {
                $escapedArray[ $this->protectIdentifiers( $key ) ] = $this->protectIdentifiers(
                    $value,
                    $prefixSingle,
                    $protectIdentifiers,
                    $fieldExists
                );
            }

            return $escapedArray;
        }

        // This is basically a bug fix for queries that use MAX, MIN, etc.
        // If a parenthesis is found we know that we do not need to
        // escape the data or add a prefix. There's probably a more graceful
        // way to deal with this, but I'm not thinking of it -- Rick
        //
        // Added exception for single quotes as well, we don't want to alter
        // literal strings. -- Narf
        if ( strcspn( $item, "()'" ) !== strlen( $item ) ) {
            return $item;
        }

        // Convert tabs or multiple spaces into single spaces
        $item = preg_replace( '/\s+/', ' ', trim( $item ) );

        // If the item has an alias declaration we remove it and set it aside.
        // Note: strripos() is used in order to support spaces in table names
        if ( $offset = strripos( $item, ' AS ' ) ) {
            $alias = ( $protectIdentifiers )
                ? substr( $item, $offset, 4 ) . $this->escapeIdentifiers( substr( $item, $offset + 4 ) )
                : substr( $item, $offset );
            $item = substr( $item, 0, $offset );
        } elseif ( $offset = strrpos( $item, ' ' ) ) {
            $alias = ( $protectIdentifiers )
                ? ' ' . $this->escapeIdentifiers( substr( $item, $offset + 1 ) )
                : substr( $item, $offset );
            $item = substr( $item, 0, $offset );
        } else {
            $alias = '';
        }

        // Break the string apart if it contains periods, then insert the table prefix
        // in the correct location, assuming the period doesn't indicate that we're dealing
        // with an alias. While we're at it, we will escape the components
        if ( strpos( $item, '.' ) !== false ) {
            $parts = explode( '.', $item );

            // Does the first segment of the exploded item match
            // one of the aliases previously identified? If so,
            // we have nothing more to do other than escape the item
            //
            // NOTE: The ! empty() condition prevents this method
            //       from breaking when QB isn't enabled.
            if ( ! empty( $this->qb_aliased_tables ) && in_array( $parts[ 0 ], $this->qb_aliased_tables ) ) {
                if ( $protectIdentifiers === true ) {
                    foreach ( $parts as $key => $val ) {
                        if ( ! in_array( $val, $this->reservedIdentifiers ) ) {
                            $parts[ $key ] = $this->escapeIdentifiers( $val );
                        }
                    }

                    $item = implode( '.', $parts );
                }

                return $item . $alias;
            }

            // Is there a table prefix defined in the config file? If not, no need to do anything
            if ( $this->config->tablePrefix !== '' ) {
                // We now add the table prefix based on some logic.
                // Do we have 4 segments (hostname.database.table.column)?
                // If so, we add the table prefix to the column name in the 3rd segment.
                if ( isset( $parts[ 3 ] ) ) {
                    $i = 2;
                }
                // Do we have 3 segments (database.table.column)?
                // If so, we add the table prefix to the column name in 2nd position
                elseif ( isset( $parts[ 2 ] ) ) {
                    $i = 1;
                }
                // Do we have 2 segments (table.column)?
                // If so, we add the table prefix to the column name in 1st segment
                else {
                    $i = 0;
                }

                // This flag is set when the supplied $item does not contain a field name.
                // This can happen when this function is being called from a JOIN.
                if ( $fieldExists === false ) {
                    $i++;
                }

                // Verify table prefix and replace if necessary
                if ( $this->swapTablePrefix !== '' && strpos( $parts[ $i ], $this->swapTablePrefix ) === 0 ) {
                    $parts[ $i ] = preg_replace(
                        '/^' . $this->swapTablePrefix . '(\S+?)/',
                        $this->config->tablePrefix . '\\1',
                        $parts[ $i ]
                    );
                } // We only add the table prefix if it does not already exist
                elseif ( strpos( $parts[ $i ], $this->config->tablePrefix ) !== 0 ) {
                    $parts[ $i ] = $this->config->tablePrefix . $parts[ $i ];
                }

                // Put the parts back together
                $item = implode( '.', $parts );
            }

            if ( $protectIdentifiers === true ) {
                $item = $this->escapeIdentifiers( $item );
            }

            return $item . $alias;
        }

        // In some cases, especially 'from', we end up running through
        // protect_identifiers twice. This algorithm won't work when
        // it contains the escapeChar so strip it out.
        $item = trim( $item, $this->escapeCharacter );

        // Is there a table prefix? If not, no need to insert it
        if ( $this->config->tablePrefix !== '' ) {
            // Verify table prefix and replace if necessary
            if ( $this->swapTablePrefix !== '' && strpos( $item, $this->swapTablePrefix ) === 0 ) {
                $item = preg_replace(
                    '/^' . $this->swapTablePrefix . '(\S+?)/',
                    $this->config->tablePrefix . '\\1',
                    $item
                );
            } // Do we prefix an item with no segments?
            elseif ( $prefixSingle === true && strpos( $item, $this->config->tablePrefix ) !== 0 ) {
                $item = $this->config->tablePrefix . $item;
            }
        }

        if ( $protectIdentifiers === true && ! in_array( $item, $this->reservedIdentifiers ) ) {
            $item = $this->escapeIdentifiers( $item );
        }

        return $item . $alias;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractDriver::escapeIdentifiers
     *
     * Escape the SQL Identifiers
     *
     * This function escapes column and table names
     *
     * @param    mixed
     *
     * @return    mixed
     */
    final public function escapeIdentifiers ( $item )
    {
        if ( $this->escapeCharacter === '' OR empty( $item ) OR in_array( $item, $this->reservedIdentifiers ) ) {
            return $item;
        } elseif ( is_array( $item ) ) {
            foreach ( $item as $key => $value ) {
                $item[ $key ] = $this->escapeIdentifiers( $value );
            }

            return $item;
        } // Avoid breaking functions and literal values inside queries
        elseif ( ctype_digit(
                     $item
                 ) OR $item[ 0 ] === "'" OR ( $this->escapeCharacter !== '"' && $item[ 0 ] === '"' ) OR
                 strpos( $item, '(' ) !== false
        ) {
            return $item;
        }

        static $pregEscapeCharacters = [ ];

        if ( empty( $pregEscapeCharacters ) ) {
            if ( is_array( $this->escapeCharacter ) ) {
                $pregEscapeCharacters = [
                    preg_quote( $this->escapeCharacter[ 0 ], '/' ),
                    preg_quote( $this->escapeCharacter[ 1 ], '/' ),
                    $this->escapeCharacter[ 0 ],
                    $this->escapeCharacter[ 1 ],
                ];
            } else {
                $pregEscapeCharacters[ 0 ] = $pregEscapeCharacters[ 1 ] = preg_quote( $this->escapeCharacter, '/' );
                $pregEscapeCharacters[ 2 ] = $pregEscapeCharacters[ 3 ] = $this->escapeCharacter;
            }
        }

        foreach ( $this->reservedIdentifiers as $id ) {
            if ( strpos( $item, '.' . $id ) !== false ) {
                return preg_replace(
                    '/'
                    . $pregEscapeCharacters[ 0 ]
                    . '?([^'
                    . $pregEscapeCharacters[ 1 ]
                    . '\.]+)'
                    . $pregEscapeCharacters[ 1 ]
                    . '?\./i',
                    $pregEscapeCharacters[ 2 ] . '$1' . $pregEscapeCharacters[ 3 ] . '.',
                    $item
                );
            }
        }

        return preg_replace(
            '/'
            . $pregEscapeCharacters[ 0 ]
            . '?([^'
            . $pregEscapeCharacters[ 1 ]
            . '\.]+)'
            . $pregEscapeCharacters[ 1 ]
            . '?(\.)?/i',
            $pregEscapeCharacters[ 2 ] . '$1' . $pregEscapeCharacters[ 3 ] . '$2',
            $item
        );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::isTableExists
     *
     * Check if table exists at current connection database.
     *
     * @param $table
     *
     * @return bool
     */
    public function isTableFieldExists ( $field, $table )
    {
        $tableFields = empty( $this->activeRecordCache[ 'fieldNames' ][ $table ] )
            ? $this->getTableFields( $table )
            : $this->activeRecordCache[ 'fieldNames' ][ $table ];

        return (bool) in_array( $field, $tableFields );
    }

    // ------------------------------------------------------------------------
}