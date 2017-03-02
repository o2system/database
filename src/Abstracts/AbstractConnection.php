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
use O2System\Database\Registries\Config;
use O2System\Database\Registries\Query;
use O2System\Spl\Exceptions\RuntimeException;
use O2System\Spl\Traits\Collectors\ConfigCollectorTrait;

/**
 * Class AbstractConnection
 *
 * @package O2System\Database\Abstracts
 */
abstract class AbstractConnection
{
    use ConfigCollectorTrait;

    /**
     * AbstractConnection::$platform
     *
     * Database driver platform name.
     *
     * @var string
     */
    protected $platform;

    /**
     * AbstractConnection::$handle
     *
     * Connection handle
     *
     * @var mixed
     */
    protected $handle;

    /**
     * AbstractConnection::$isDebugEnabled
     *
     * Connection debug mode flag.
     *
     * @var bool
     */
    public $isDebugEnabled = true;

    /**
     * AbstractConnection::$persistent
     *
     * Connection persistent mode flag.
     *
     * @var bool
     */
    protected $isPersistent = true;

    /**
     * AbstractConnection::$database
     *
     * Connection database name.
     *
     * @var string
     */
    public $database;

    /**
     * AbstractConnection::$connectTimeStart
     *
     * Microtime when connection was made.
     *
     * @var float
     */
    protected $connectTimeStart;

    /**
     * AbstractConnection::$connectTimeDuration
     *
     * How long it took to establish connection.
     *
     * @var float
     */
    protected $connectTimeDuration;

    /**
     * AbstractConnection::$swapTablePrefix
     *
     * Swap database table prefix.
     *
     * @var string
     */
    public $swapTablePrefix;

    /**
     * AbstractConnection::$isProtectIdentifiers
     *
     * Protect identifiers mode flag.
     *
     * @var bool
     */
    public $isProtectIdentifiers = true;

    /**
     * AbstractConnection::$isTransactionStrict
     *
     * Transaction strict mode enabled flag.
     *
     * @var bool
     */
    protected $isTransactionStrict = false;

    /**
     * AbstractConnection::$isTransactionEnabled
     *
     * Transaction mode enabled flag.
     *
     * @var bool
     */
    protected $isTransactionEnabled = false;

    /**
     * AbstractConnection::$isTransactionFailed
     *
     * Transaction mode failure flag.
     *
     * @var bool
     */
    protected $isTransactionFailed = false;

    /**
     * AbstractConnection::$transactionStatus
     *
     * Transaction status flag.
     *
     * @var bool
     */
    protected $transactionStatus = false;

    /**
     * AbstractConnection::$transactionDepth
     *
     * Transaction depth numbers.
     *
     * @var int
     */
    protected $transactionDepth = 0;

    /**
     * AbstractConnection::$isQueryExecuteDisabled
     *
     * Query execution mode flag.
     *
     * @var bool
     */
    public $isQueryExecuteDisabled = false;

    /**
     * AbstractConnection::$queriesCache
     *
     * Array of query objects that have executed
     * on this connection.
     *
     * @var array
     */
    protected $queriesCache = [ ];

    /**
     * AbstractConnection::$queriesResultCache
     *
     * Array of query objects that have executed
     * on this connection.
     *
     * @var array
     */
    public $queriesResultCache = [ ];

    /**
     * AbstractConnection::$queryBuilder
     *
     * Query Builder instance.
     *
     * @var AbstractQueryBuilder
     */
    protected $queryBuilder;

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::__construct
     *
     * @param \O2System\Database\Registries\Config $config
     *
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function __construct ( Config $config )
    {
        $config->merge(
            array_merge(
                [
                    'escapeCharacter'     => '"',
                    'reservedIdentifiers' => [ '*' ],
                    'likeEscapeStatement' => ' ESCAPE \'%s\' ',
                    'likeEscapeCharacter' => '!',
                ],
                $this->getConfig()
            )
        );

        $this->config = $config;

        $this->isDebugEnabled = $config->offsetGet( 'debugEnabled' );
        $this->database = $config->offsetGet( 'database' );

        $this->connect(
            ( $config->offsetExists( 'persistent' )
                ? $this->isPersistent = $config->persistent
                : true
            )
        );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::isSupported
     *
     * Check if the platform is supported.
     *
     * @return bool
     */
    abstract public function isSupported ();

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getPlatform
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
     * AbstractConnection::getVersion
     *
     * Get the version of the database platform of this connection.
     *
     * @return mixed
     */
    public function getPlatformVersion ()
    {
        if ( isset( $this->queriesResultCache[ 'version' ] ) ) {
            return $this->queriesResultCache[ 'version' ];
        }

        return $this->queriesResultCache[ 'version' ] = $this->platformGetPlatformVersionHandler();
    }

    //--------------------------------------------------------------------

    /**
     * AbstractConnection::platformGetPlatformVersionHandler
     *
     * Platform getting version handler.
     *
     * @return mixed
     */
    abstract protected function platformGetPlatformVersionHandler ();

    //--------------------------------------------------------------------

    /**
     * AbstractConnection::connect
     *
     * Establish the connection.
     *
     * @param bool $persistent
     *
     * @return void
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    final public function connect ( $persistent = true )
    {
        /* If an established connection is available, then there's
         * no need to connect and select the database.
         *
         * Depending on the database driver, conn_id can be either
         * boolean TRUE, a resource or an object.
         */
        if ( $this->handle ) {
            return;
        }

        //--------------------------------------------------------------------

        $this->isPersistent = $persistent;
        $this->connectTimeStart = microtime( true );

        // Connect to the database and set the connection ID
        $this->platformConnectHandler( $this->config );

        // No connection resource? Check if there is a failover else throw an error
        if ( ! $this->handle ) {
            // Check if there is a failover set
            if ( ! empty( $this->config[ 'failover' ] ) && is_array( $this->config[ 'failover' ] ) ) {
                // Go over all the failovers
                foreach ( $this->config[ 'failover' ] as $failover ) {

                    // Try to connect
                    $this->platformConnectHandler( $failover = new Config( $failover ) );

                    // If a connection is made break the foreach loop
                    if ( $this->handle ) {
                        $this->config = $failover;
                        break;
                    }
                }
            }

            // We still don't have a connection?
            if ( ! $this->handle ) {
                throw new RuntimeException( 'E_DB_UNABLE_TO_CONNECT', 0, [ $this->platform ] );
            }
        }

        $this->connectTimeDuration = microtime( true ) - $this->connectTimeStart;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::platformConnectHandler
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
     * AbstractConnection::reconnect
     *
     * Keep or establish the connection if no queries have been sent for
     * a length of time exceeding the server's idle timeout.
     *
     * @return void
     */
    public function reconnect ()
    {
        if ( $this->isConnected() === false ) {
            $this->connect( $this->isPersistent );
        }
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::isConnected
     *
     * Determine if the connection is connected
     *
     * @return bool
     */
    final public function isConnected ()
    {
        return (bool) ( $this->handle === false
            ? false
            : true );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractConnection::disconnect
     *
     * Disconnect database connection.
     *
     * @return void
     */
    final public function disconnect ()
    {
        if ( $this->handle ) {
            $this->platformDisconnectHandler();
            $this->handle = false;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::disconnectHandler
     *
     * Driver dependent way method for closing the connection.
     *
     * @return mixed
     */
    abstract protected function platformDisconnectHandler ();

    //--------------------------------------------------------------------

    /**
     * AbstractConnection::getConnectionTimeStart
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
     * AbstractConnection::getConnectTimeDuration
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
     * AbstractConnection::getQueries
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
     * AbstractConnection::getQueriesCount
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
     * AbstractConnection::getLastQuery
     *
     * Returns the last query's statement object.
     *
     * @return Query
     */
    final public function getLatestQuery ()
    {
        return end( $this->queriesCache );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractConnection::transactionStrict
     *
     * Enable/disable Transaction Strict Mode
     *
     * When strict mode is enabled, if you are running multiple groups of
     * transactions, if one group fails all subsequent groups will be
     * rolled back.
     *
     * If strict mode is disabled, each group is treated autonomously,
     * meaning a failure of one group will not affect any others
     *
     * @param    bool $mode = true
     *
     * @return static
     */
    public function transactionStrict ( $mode = true )
    {
        $this->isTransactionStrict = $mode;

        return $this;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractConnection::transactionStart
     *
     * Starting a transaction.
     *
     * @param bool $testMode Testing mode flag.
     *
     * @return bool
     */
    public function transactionStart ( $testMode = false )
    {
        if ( ! $this->isTransactionEnabled ) {
            return false;
        }

        return $this->transactionBegin( $testMode );
    }

    //--------------------------------------------------------------------

    /**
     * AbstractConnection::transactionBegin
     *
     * Begin a transaction.
     *
     * @param bool $testMode Testing mode flag.
     *
     * @return bool
     */
    public function transactionBegin ( $testMode = false )
    {
        if ( ! $this->isTransactionEnabled ) {
            return false;
        } // When transactions are nested we only begin/commit/rollback the outermost ones
        elseif ( $this->transactionDepth > 0 ) {
            $this->transactionDepth++;

            return true;
        }

        // Reset the transaction failure flag.
        // If the $test_mode flag is set to TRUE transactions will be rolled back
        // even if the queries produce a successful result.
        $this->isTransactionFailed = ( $testMode === true );
        if ( $this->platformTransactionBeginHandler() ) {
            $this->transactionDepth++;

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::platformTransactionBeginHandler
     *
     * Platform beginning a transaction handler.
     *
     * @return bool
     */
    abstract protected function platformTransactionBeginHandler ();

    /**
     * AbstractConnection::transactionCommit
     *
     * Commit a transaction.
     *
     * @return bool
     */
    public function transactionCommit ()
    {
        if ( ! $this->isTransactionEnabled OR $this->transactionDepth === 0 ) {
            return false;
        } // When transactions are nested we only begin/commit/rollback the outermost ones
        elseif ( $this->transactionDepth > 1 OR $this->platformTransactionCommitHandler() ) {
            $this->transactionDepth--;

            return true;
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractConnection::platformTransactionCommitHandler
     *
     * Platform committing a transaction handler.
     *
     * @return bool
     */
    abstract protected function platformTransactionCommitHandler ();

    //--------------------------------------------------------------------

    /**
     * AbstractConnection::transactionRollBack
     *
     * RollBack a transaction.
     *
     * @return bool
     */
    public function transactionRollBack ()
    {
        if ( ! $this->isTransactionEnabled OR $this->transactionDepth === 0 ) {
            return false;
        } // When transactions are nested we only begin/commit/rollback the outermost ones
        elseif ( $this->transactionDepth > 1 OR $this->platformTransactionRollBackHandler() ) {
            $this->transactionDepth--;

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::platformTransactionRollBackHandler
     *
     * Platform rolling back a transaction handler.
     *
     * @return bool
     */
    abstract protected function platformTransactionRollBackHandler ();

    //--------------------------------------------------------------------

    /**
     * AbstractConnection::transactionComplete
     *
     * Completing a transaction.
     *
     * @return bool
     */
    public function transactionComplete ()
    {
        if ( ! $this->isTransactionEnabled ) {
            return false;
        }

        // The query() function will set this flag to FALSE in the event that a query failed
        if ( $this->transactionStatus === false OR $this->isTransactionFailed === true ) {
            $this->transactionRollBack();
            // If we are NOT running in strict mode, we will reset
            // the _trans_status flag so that subsequent groups of
            // transactions will be permitted.
            if ( $this->isTransactionStrict === false ) {
                $this->transactionStatus = true;
            }

            // log_message('debug', 'DB Transaction Failure');
            return false;
        }

        return $this->transactionCommit();
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::transactionDisabled
     *
     * This permits transactions to be disabled at run-time.
     *
     * @return static
     */
    public function transactionDisabled ()
    {
        $this->transactionDisabled();

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getTransactionStatus
     *
     * Get transaction status.
     *
     * @return bool
     */
    public function getTransactionStatus ()
    {
        return (bool) $this->isTransactionFailed;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getAffectedRows
     *
     * Get the total number of affected rows from the last query execution.
     *
     * @return int  Returns total number of affected rows
     */
    abstract public function getAffectedRows ();

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getLastInsertId
     *
     * Get last insert id from the last insert query execution.
     *
     * @return int  Returns total number of affected rows
     */
    abstract public function getLastInsertId ();

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::setDatabase
     *
     * Set a specific database table to use.
     *
     * @param string $database Database name.
     *
     * @return static
     */
    public function setDatabase ( $database )
    {
        $this->database = $database;

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::prepareSqlStatement
     *
     * Platform preparing a SQL statement.
     *
     * @param string $sqlStatement SQL Statement to be prepared.
     * @param array  $options      Preparing sql statement options.
     *
     * @return string
     */
    abstract protected function platformPrepareSqlStatement ( $sqlStatement, array $options = [ ] );

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::query
     *
     * @param string $sqlStatement
     * @param array  $binds
     *
     * @return bool|\O2System\Database\Datastructures\Result Returns boolean if the query is contains writing syntax
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function query ( $sqlStatement, array $binds = [ ] )
    {
        if ( empty( $this->handle ) ) {
            $this->connect( $this->isPersistent );
        }

        $query = new Query( $this );

        $query->setStatement( $sqlStatement, $binds );

        if ( ! empty( $this->swapTablePrefix ) AND ! empty( $this->config->tablePrefix ) ) {
            $query->swapTablePrefix( $this->config->tablePrefix, $this->swapTablePrefix );
        }

        $startTime = microtime( true );

        // Run the query for real
        if ( $this->isQueryExecuteDisabled === false ) {

            if ( $query->isWriteSyntax() ) {

                $result = $this->platformExecuteHandler( $query );
                $query->setAffectedRows( $this->getAffectedRows() );
                $query->setDuration( $startTime );

                $this->queriesCache[] = $query;
            } else {

                $rows = $this->platformQueryHandler( $query );
                $query->setDuration( $startTime );

                $this->queriesCache[] = $query;

                $result = new Result( $rows );
            }

            // This will trigger a rollback if transactions are being used
            if ( $this->transactionDepth !== 0 ) {
                $this->transactionStatus = false;
            }

            if ( $this->isDebugEnabled ) {
                // We call this function in order to roll-back queries
                // if transactions are enabled. If we don't call this here
                // the error message will trigger an exit, causing the
                // transactions to remain in limbo.
                while ( $this->transactionDepth !== 0 ) {
                    $transactionDepth = $this->transactionDepth;

                    $this->transactionComplete();

                    if ( $transactionDepth === $this->transactionDepth ) {
                        // 'Database: Failure during an automated transaction commit/rollback!'
                        throw new RuntimeException( 'E_DATABASE_TRANSACTION', $query->getErrorCode() );

                        break;
                    }
                }

                throw new RuntimeException( $query->getErrorMessage(), $query->getErrorCode() );
            }

            return $result;
        }

        // If $this->isQueryExecuteDisabled is true, then we just want to return
        // the actual query object here. There won't be
        // any results to return.
        $query->setDuration( $startTime );

        return $query;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::platformQueryHandler
     *
     * Driver dependent way method for execute the SQL statement.
     *
     * @param Query $query Query object.
     *
     * @return array
     */
    abstract protected function platformQueryHandler ( Query &$query );

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::execute
     *
     * Execute SQL statement against database.
     *
     * @param string $sqlStatement The SQL statement.
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function execute ( $sqlStatement )
    {
        if ( empty( $this->handle ) ) {
            $this->connect();
        }

        $query = new Query( $this );
        $query->setStatement( $sqlStatement );

        $startTime = microtime( true );
        $result = $this->platformExecuteHandler( $query );
        $query->setDuration( $startTime );

        $this->queriesCache[] = $query;

        return (bool) $result;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::executeHandler
     *
     * Driver dependent way method for execute the SQL statement.
     *
     * @param Query $query Query object.
     *
     * @return bool
     */
    abstract protected function platformExecuteHandler ( Query &$query );

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::setTablePrefix
     *
     * @param string $tablePrefix The database table prefix.
     *
     * @return string
     */
    final public function setTablePrefix ( $tablePrefix )
    {
        return $this->config[ 'tablePrefix' ] = $tablePrefix;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractConnection::prefixTable
     *
     * @param string $tableName Database table name.
     *
     * @return string Returns prefixed table name.
     */
    final public function prefixTable ( $tableName )
    {
        $tablePrefix = $this->config[ 'tablePrefix' ];

        if ( empty( $tablePrefix ) ) {
            return $tableName;
        }

        return $tablePrefix . str_replace( $tablePrefix, '', $tableName );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::isDatabaseExists
     *
     * Check if the database exists or not.
     *
     * @param string $databaseName The database name.
     *
     * @return bool Returns false if database doesn't exists.
     */
    final public function isDatabaseExists ( $databaseName )
    {
        $databases = empty( $this->queriesResultCache[ 'databaseNames' ] )
            ? $this->getDatabases()
            : $this->queriesResultCache[ 'databaseNames' ];

        return (bool) in_array( $databaseName, $databases );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getDatabaseList
     *
     * Get list of current connection databases.
     *
     * @return array Returns an array.
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function getDatabases ()
    {
        if ( empty( $this->queriesResultCache[ 'databaseNames' ] ) ) {
            $result = $this->query( 'SHOW DATABASES' );

            print_out( $result );

            if ( $result->count() ) {
                foreach ( $result as $row ) {

                    if ( ! isset( $key ) ) {
                        if ( isset( $row[ 'database' ] ) ) {
                            $key = 'database';
                        } elseif ( isset( $row[ 'Database' ] ) ) {
                            $key = 'Database';
                        } elseif ( isset( $row[ 'DATABASE' ] ) ) {
                            $key = 'DATABASE';
                        } else {
                            /* We have no other choice but to just get the first element's key.
                             * Due to array_shift() accepting its argument by reference, if
                             * E_STRICT is on, this would trigger a warning. So we'll have to
                             * assign it first.
                             */
                            $key = array_keys( $row );
                            $key = array_shift( $key );
                        }
                    }

                    $this->queriesResultCache[ 'databaseNames' ][] = $row->offsetGet( $key );
                }
            }
        }

        return $this->queriesResultCache[ 'databaseNames' ];
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
    public function isTableExists ( $table )
    {
        $table = $this->prefixTable( $table );

        $tables = empty( $this->queriesResultCache[ 'tableNames' ] )
            ? $this->getTables()
            : $this->queriesResultCache[ 'tableNames' ];

        return (bool) in_array( $this->protectIdentifiers( $table, true, false, false ), $tables );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getTables
     *
     * Get list of current database tables.
     *
     * @param bool $prefixLimit If sets TRUE the query will be limit using database table prefix.
     *
     * @return array Returns an array
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function getTables ( $prefixLimit = false )
    {
        if ( empty( $this->queriesResultCache[ 'tableNames' ] ) ) {

            $sqlStatement = 'SHOW TABLES FROM ' . $this->escapeIdentifiers( $this->config[ 'database' ] );

            if ( $prefixLimit !== false && $this->config[ 'tablePrefix' ] !== '' ) {
                return $sqlStatement . " LIKE '" . $this->escapeLikeString( $this->config[ 'tablePrefix' ] ) . "%'";
            }

            $result = $this->query( $sqlStatement );

            if ( $result->count() ) {
                foreach ( $result as $row ) {
                    // Do we know from which column to get the table name?
                    if ( ! isset( $key ) ) {
                        if ( isset( $row[ 'table_name' ] ) ) {
                            $key = 'table_name';
                        } elseif ( isset( $row[ 'TABLE_NAME' ] ) ) {
                            $key = 'TABLE_NAME';
                        } else {
                            /* We have no other choice but to just get the first element's key.
                             * Due to array_shift() accepting its argument by reference, if
                             * E_STRICT is on, this would trigger a warning. So we'll have to
                             * assign it first.
                             */
                            $key = array_keys( $row->getArrayCopy() );
                            $key = array_shift( $key );
                        }
                    }

                    $this->queriesResultCache[ 'tableNames' ][] = $row->offsetGet( $key );
                }
            }
        }

        return $this->queriesResultCache[ 'tableNames' ];
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::isTableExists
     *
     * Check if table exists at current connection database.
     *
     * @param string $field Database table field name.
     * @param string $table Database table name.
     *
     * @return bool
     */
    public function isTableFieldExists ( $field, $table )
    {
        $table = $this->prefixTable( $table );

        $tableFields = empty( $this->queriesResultCache[ 'fieldNames' ][ $table ] )
            ? $this->getTableFields( $table )
            : $this->queriesResultCache[ 'fieldNames' ][ $table ];

        return (bool) in_array( $field, $tableFields );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getTableFields
     *
     * @param string $table The database table name.
     *
     * @return array
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function getTableFields ( $table )
    {
        $table = $this->prefixTable( $table );

        if ( empty( $this->queriesResultCache[ 'tableFields' ][ $table ] ) ) {
            $result = $this->query( 'SHOW COLUMNS FROM ' . $this->protectIdentifiers( $table, true, null, false ) );

            if ( $result->count() ) {
                foreach ( $result as $row ) {
                    // Do we know from where to get the column's name?
                    if ( ! isset( $key ) ) {
                        if ( isset( $row[ 'column_name' ] ) ) {
                            $key = 'column_name';
                        } elseif ( isset( $row[ 'COLUMN_NAME' ] ) ) {
                            $key = 'COLUMN_NAME';
                        } else {
                            /* We have no other choice but to just get the first element's key.
                             * Due to array_shift() accepting its argument by reference, if
                             * E_STRICT is on, this would trigger a warning. So we'll have to
                             * assign it first.
                             */
                            $key = array_keys( $row->getArrayCopy() );
                            $key = array_shift( $key );
                        }
                    }

                    $this->queriesResultCache[ 'tableFields' ][ $table ][ $row->offsetGet( $key ) ] = $row;
                }
            }
        }

        return array_keys( $this->queriesResultCache[ 'tableFields' ][ $table ] );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getTableFieldsMetadata
     *
     * @param string $table The database table name.
     *
     * @return array
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function getTableFieldsMetadata ( $table )
    {
        $table = $this->prefixTable( $table );

        if ( empty( $this->queriesResultCache[ 'tableFields' ][ $table ] ) ) {
            $this->getTableFields( $table );
        }

        return $this->queriesResultCache[ 'tableFields' ][ $table ];
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::escape
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

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::escapeString
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
                [ $this->config[ 'likeEscapeCharacter' ], '%', '_' ],
                [
                    $this->config[ 'likeEscapeCharacter' ] . $this->config[ 'likeEscapeCharacter' ],
                    $this->config[ 'likeEscapeCharacter' ] . '%',
                    $this->config[ 'likeEscapeCharacter' ] . '_',
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
     * AbstractConnection::platformEscapeStringHandler
     *
     * Platform escape string handler.
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
     * AbstractConnection::likeString
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
     * AbstractConnection::protectIdentifiers
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
            $protectIdentifiers = $this->isProtectIdentifiers;
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
        // escape the data or add a prefix.
        //
        // Added exception for single quotes as well, we don't want to alter
        // literal strings.
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

            $aliasedTables = [ ];

            if ( $this->queryBuilder instanceof AbstractQueryBuilder ) {
                $aliasedTables = $this->queryBuilder->getAliasedTables();
            }

            // Does the first segment of the exploded item match
            // one of the aliases previously identified? If so,
            // we have nothing more to do other than escape the item
            //
            // NOTE: The ! empty() condition prevents this method
            //       from breaking when Query Builder isn't enabled.
            if ( ! empty( $aliasedTables ) AND in_array( $parts[ 0 ], $aliasedTables ) ) {
                if ( $protectIdentifiers === true ) {
                    foreach ( $parts as $key => $val ) {
                        if ( ! in_array( $val, $this->config[ 'reservedIdentifiers' ] ) ) {
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
        $item = trim( $item, $this->config[ 'escapeCharacter' ] );

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

        if ( $protectIdentifiers === true && ! in_array( $item, $this->config[ 'reservedIdentifiers' ] ) ) {
            $item = $this->escapeIdentifiers( $item );
        }

        return $item . $alias;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::escapeIdentifiers
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
        if ( $this->config[ 'escapeCharacter' ] === '' OR empty( $item ) OR in_array(
                $item,
                $this->config[ 'reservedIdentifiers' ]
            )
        ) {
            return $item;
        } elseif ( is_array( $item ) ) {
            foreach ( $item as $key => $value ) {
                $item[ $key ] = $this->escapeIdentifiers( $value );
            }

            return $item;
        } // Avoid breaking functions and literal values inside queries
        elseif ( ctype_digit(
                     $item
                 ) OR $item[ 0 ] === "'" OR ( $this->config[ 'escapeCharacter' ] !== '"' && $item[ 0 ] === '"' ) OR
                 strpos( $item, '(' ) !== false
        ) {
            return $item;
        }

        static $pregEscapeCharacters = [ ];

        if ( empty( $pregEscapeCharacters ) ) {
            if ( is_array( $this->config[ 'escapeCharacter' ] ) ) {
                $pregEscapeCharacters = [
                    preg_quote( $this->config[ 'escapeCharacter' ][ 0 ], '/' ),
                    preg_quote( $this->config[ 'escapeCharacter' ][ 1 ], '/' ),
                    $this->config[ 'escapeCharacter' ][ 0 ],
                    $this->config[ 'escapeCharacter' ][ 1 ],
                ];
            } else {
                $pregEscapeCharacters[ 0 ]
                    = $pregEscapeCharacters[ 1 ] = preg_quote( $this->config[ 'escapeCharacter' ], '/' );
                $pregEscapeCharacters[ 2 ] = $pregEscapeCharacters[ 3 ] = $this->config[ 'escapeCharacter' ];
            }
        }

        foreach ( $this->config[ 'reservedIdentifiers' ] as $id ) {
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
     * AbstractConnection::getQueryBuilder
     *
     * Get connection query builder.
     *
     * @return bool
     */
    public function getQueryBuilder ()
    {
        if ( ! $this->queryBuilder instanceof AbstractQueryBuilder ) {
            $className = str_replace( 'Connection', 'QueryBuilder', get_called_class() );

            if ( class_exists( $className ) ) {
                $this->queryBuilder = new $className( $this );
            }
        }

        return $this->queryBuilder;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getQueryBuilder
     *
     * Get connection forge.
     *
     * @return bool
     */
    public function getForge ()
    {
        $className = str_replace( 'Connection', 'Forge', get_called_class() );

        if ( class_exists( $className ) ) {
            return new $className( $this );
        }
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getSchemaBuilder
     *
     * Get connection schema builder.
     *
     * @return bool
     */
    public function getSchemaBuilder ()
    {
        $className = str_replace( 'Connection', 'SchemaBuilder', get_called_class() );

        if ( class_exists( $className ) ) {
            return new $className( $this );
        }
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getQueryBuilder
     *
     * Get connection utility.
     *
     * @return bool
     */
    public function getUtility ()
    {
        $className = str_replace( 'Connection', 'Utility', get_called_class() );

        if ( class_exists( $className ) ) {
            return new $className( $this );
        }
    }
}