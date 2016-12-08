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

use O2System\Database\Abstracts\AbstractDriver;
use O2System\Database\Interfaces\DriverInterface;
use O2System\Database\Registries\Config;
use O2System\Database\Registries\Query;
use O2System\Kernel\Spl\Exceptions\Runtime\ConnectionException;

/**
 * Class Driver
 *
 * @package O2System\Database\Drivers\MySQL
 */
abstract class Driver extends AbstractDriver implements DriverInterface
{
    /**
     * Driver::$platform
     *
     * Database driver platform name.
     *
     * @var string
     */
    protected $platform = 'MySQL';

    /**
     * Driver::$connID
     *
     * MySQLi Instance
     *
     * @var \mysqli
     */
    protected $connID;

    /**
     * Driver::$resultID
     *
     * MySQLi Result Instance
     *
     * @var \mysqli_result
     */
    protected $resultID;

    /**
     * Driver::$inTransaction
     *
     * Current status of transaction.
     *
     * @var bool
     */
    protected $inTransaction = false;

    // ------------------------------------------------------------------------

    /**
     * Driver::isSupported
     *
     * Check if the platform is supported.
     *
     * @return bool
     */
    public function isSupported ()
    {
        return extension_loaded( 'mysqli' );
    }

    // ------------------------------------------------------------------------

    /**
     * Driver::platformConnectHandler
     *
     * Establish the connection.
     *
     * @param Config $config
     *
     * @return void
     * @throws ConnectionException
     */
    public function platformConnectHandler ( Config $config )
    {
        // Do we have a socket path?
        if ( $config->hostname[ 0 ] === '/' ) {
            $hostname = null;
            $port = null;
            $socket = $config->hostname;
        } else {
            $hostname = ( $config->persistent === true )
                ? 'p:' . $config->hostname
                : $config->hostname;
            $port = empty( $config->port ) ? null : $config->port;
            $socket = null;
        }

        $flags = ( $config->compress === true ) ? MYSQLI_CLIENT_COMPRESS : 0;
        $this->connID = mysqli_init();

        $this->connID->options( MYSQLI_OPT_CONNECT_TIMEOUT, 10 );

        if ( isset( $config->strictOn ) ) {
            if ( $config->strictOn ) {
                $this->connID->options(
                    MYSQLI_INIT_COMMAND,
                    'SET SESSION sql_mode = CONCAT(@@sql_mode, ",", "STRICT_ALL_TABLES")'
                );
            } else {
                $this->connID->options(
                    MYSQLI_INIT_COMMAND,
                    'SET SESSION sql_mode =
					REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
					@@sql_mode,
					"STRICT_ALL_TABLES,", ""),
					",STRICT_ALL_TABLES", ""),
					"STRICT_ALL_TABLES", ""),
					"STRICT_TRANS_TABLES,", ""),
					",STRICT_TRANS_TABLES", ""),
					"STRICT_TRANS_TABLES", "")'
                );
            }
        }

        if ( is_array( $config->encrypt ) ) {
            $ssl = [ ];
            empty( $config->encrypt[ 'ssl_key' ] ) OR $ssl[ 'key' ] = $config->encrypt[ 'ssl_key' ];
            empty( $config->encrypt[ 'ssl_cert' ] ) OR $ssl[ 'cert' ] = $config->encrypt[ 'ssl_cert' ];
            empty( $config->encrypt[ 'ssl_ca' ] ) OR $ssl[ 'ca' ] = $config->encrypt[ 'ssl_ca' ];
            empty( $config->encrypt[ 'ssl_capath' ] ) OR $ssl[ 'capath' ] = $config->encrypt[ 'ssl_capath' ];
            empty( $config->encrypt[ 'ssl_cipher' ] ) OR $ssl[ 'cipher' ] = $config->encrypt[ 'ssl_cipher' ];

            if ( ! empty( $ssl ) ) {
                if ( isset( $config->encrypt[ 'ssl_verify' ] ) ) {
                    if ( $config->encrypt[ 'ssl_verify' ] ) {
                        defined( 'MYSQLI_OPT_SSL_VERIFY_SERVER_CERT' ) &&
                        $this->connID->options( MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true );
                    }
                    // Apparently (when it exists), setting MYSQLI_OPT_SSL_VERIFY_SERVER_CERT
                    // to FALSE didn't do anything, so PHP 5.6.16 introduced yet another
                    // constant ...
                    //
                    // https://secure.php.net/ChangeLog-5.php#5.6.16
                    // https://bugs.php.net/bug.php?id=68344
                    elseif ( defined( 'MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT' ) ) {
                        $this->connID->options( MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT, true );
                    }
                }

                $flags |= MYSQLI_CLIENT_SSL;
                $this->connID->ssl_set(
                    isset( $ssl[ 'key' ] ) ? $ssl[ 'key' ] : null,
                    isset( $ssl[ 'cert' ] ) ? $ssl[ 'cert' ] : null,
                    isset( $ssl[ 'ca' ] ) ? $ssl[ 'ca' ] : null,
                    isset( $ssl[ 'capath' ] ) ? $ssl[ 'capath' ] : null,
                    isset( $ssl[ 'cipher' ] ) ? $ssl[ 'cipher' ] : null
                );
            }
        }

        if ( $this->connID->real_connect(
            $hostname,
            $config->username,
            $config->password,
            $config->database,
            $port,
            $socket,
            $flags
        )
        ) {
            // Prior to version 5.7.3, MySQL silently downgrades to an unencrypted connection if SSL setup fails
            if (
                ( $flags & MYSQLI_CLIENT_SSL )
                && version_compare( $this->connID->client_info, '5.7.3', '<=' )
                && empty( $this->connID->query( "SHOW STATUS LIKE 'ssl_cipher'" )
                                       ->fetch_object()->Value )
            ) {
                $this->connID->close();
                // 'MySQLi was configured for an SSL connection, but got an unencrypted connection instead!';
                logger()->error( 'E_DB_CONNECTION_SSL', [ $this->platform ] );

                if ( $config->debug ) {
                    throw new ConnectionException( 'E_DB_CONNECTION_SSL' );
                }

                return;
            }

            if ( ! $this->connID->set_charset( $config->charset ) ) {
                // "Database: Unable to set the configured connection charset ('{$this->charset}')."
                logger()->error( 'E_DB_CONNECTION_CHARSET', [ $config->charset ] );
                $this->connID->close();

                if ( $config->debug ) {
                    // 'Unable to set client connection character set: ' . $this->charset
                    throw new ConnectionException( 'E_DB_CONNECTION_CHARSET', [ $config->charset ] );
                }
            }
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Driver::reconnect
     *
     * Keep or establish the connection if no queries have been sent for
     * a length of time exceeding the server's idle timeout.
     *
     * @return void
     */
    public function reconnect ()
    {
        if ( $this->connID !== false && $this->connID->ping() === false ) {
            $this->connID = false;
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Driver::transactionBegin
     *
     * Starts a transaction.
     *
     * @return bool
     */
    public function transactionBegin ()
    {
        $this->connID->autocommit( false );

        $this->inTransaction = $this->connID->begin_transaction( MYSQLI_TRANS_START_READ_ONLY );

        return $this->inTransaction;
    }

    // ------------------------------------------------------------------------

    /**
     * Driver::transactionCommit
     *
     * Commit a transaction.
     *
     * @return bool
     */
    public function transactionCommit ()
    {
        if ( ! $this->connID->commit() ) {
            if ( $this->transactionRollBack() ) {
                return $this->connID->autocommit( true );
            }

            return false;
        }

        return true;
    }

    //--------------------------------------------------------------------

    /**
     * Driver::transactionRollBack
     *
     * RollBack a transaction.
     *
     * @return bool
     */
    public function transactionRollBack ()
    {
        return $this->connID->rollback();
    }

    // ------------------------------------------------------------------------

    /**
     * Driver::getTransactionStatus
     *
     * Get transaction status.
     *
     * @return mixed
     */
    public function getTransactionStatus ()
    {
        return $this->inTransaction;
    }

    /**
     * Driver::getAffectedRows
     *
     * Get the total number of affected rows from the last query execution.
     *
     * @return int  Returns total number of affected rows
     */
    public function getAffectedRows ()
    {
        return $this->connID->affected_rows;
    }

    // ------------------------------------------------------------------------

    /**
     * Driver::getLastInsertId
     *
     * Get last insert id from the last insert query execution.
     *
     * @return int  Returns total number of affected rows
     */
    public function getLastInsertId ()
    {
        return $this->connID->insert_id;
    }

    // ------------------------------------------------------------------------

    /**
     * ActiveRecordInterface::setDatabase
     *
     * Set database table to be used by these connection.
     *
     * @param string $databaseName The database name.
     *
     * @return bool
     */
    public function setDatabase ( $databaseName )
    {
        if ( $databaseName === '' ) {
            $databaseName = $this->config->database;
        }

        if ( $this->connID->select_db( $databaseName ) ) {
            $this->config->database = $databaseName;

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * ActiveRecordInterface::getDatabases
     *
     * Get list of current connection databases.
     *
     * @return array Returns an array contains \O2System\DB\Datastructures\Metadata\Database.
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

    // ------------------------------------------------------------------------

    /**
     * ActiveRecordInterface::getTables
     *
     * Get list of current database tables.
     *
     * @return array Returns an array
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function getTables ( $prefixLimit = false )
    {
        if ( empty( $this->activeRecordCache[ 'tableNames' ] ) ) {

            $sqlStatement = 'SHOW TABLES FROM ' . $this->escapeIdentifiers(
                    $this->config->offsetGet( 'database' )
                );

            if ( $prefixLimit !== false && $this->config->offsetGet( 'tablePrefix' ) !== '' ) {
                $sqlStatement .= " LIKE '" . $this->escapeLikeString(
                        $this->config->offsetGet( 'tablePrefix' )
                    ) . "%'";
            }

            $result = $this->execute( $sqlStatement );

            $this->activeRecordCache[ 'tableNames' ] = [ ];

            if ( $result->count() ) {
                foreach ( $result as $row ) {
                    $this->activeRecordCache[ 'tableNames' ][] = $row->getValues()[ 0 ];
                }
            }
        }

        return $this->activeRecordCache[ 'tableNames' ];
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getTableFieldsList
     *
     * @param string $table The database table name.
     *
     * @return array
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function getTableFields ( $table )
    {
        if ( empty( $this->activeRecordCache[ 'fieldNames' ][ $table ] ) ) {

            $result = $this->execute(
                'SHOW COLUMNS FROM ' . $this->protectIdentifiers(
                    $table,
                    true,
                    null,
                    false
                )
            );

            if ( $result->count() ) {
                foreach ( $result as $row ) {
                    $this->activeRecordCache[ 'fieldNames' ][ $table ][] = $row->offsetGet( 'Field' );
                }
            }
        }

        return $this->activeRecordCache[ 'fieldNames' ][ $table ];
    }

    // ------------------------------------------------------------------------

    public function getTableFieldsMetadata ( $table )
    {
        if ( empty( $this->activeRecordCache[ 'fieldsMetadata' ][ $table ] ) ) {

            $result = $this->execute(
                'SHOW COLUMNS FROM ' . $this->protectIdentifiers(
                    $table,
                    true,
                    null,
                    false
                )
            );

            if ( $result->count() ) {
                foreach ( $result as $row ) {
                    $this->activeRecordCache[ 'fieldsMetadata' ][ $table ][ $row->offsetGet( 'Field' ) ] = $row;
                }
            }
        }

        return $this->activeRecordCache[ 'fieldsMetadata' ][ $table ];
    }

    // ------------------------------------------------------------------------

    /**
     * Driver::disconnectHandler
     *
     * Driver dependent way method for closing the connection.
     *
     * @return mixed
     */
    protected function platformDisconnectHandler ()
    {
        $this->connID->close();
    }

    // ------------------------------------------------------------------------

    /**
     * Platform-dependant string escape
     *
     * @param    string $string
     *
     * @return    string
     */
    protected function platformEscapeStringHandler ( $string )
    {
        if ( is_bool( $string ) ) {
            return $string;
        }

        return $this->connID->real_escape_string( $string );
    }

    // ------------------------------------------------------------------------

    /**
     * Driver::executeHandler
     *
     * Driver dependent way method for execute the SQL statement.
     *
     * @param Query $query The SQL statement.
     *
     * @return bool|\mysqli_result
     */
    protected function platformExecuteHandler ( Query &$query )
    {
        if ( false !== $this->connID->query( $query->getFinalStatement() ) ) {
            return true;
        } else {
            $query->setError( $this->connID->errno, $this->connID->error );
        }

        return false;
    }

    // ------------------------------------------------------------------------

    protected function platformQueryHandler ( Query &$query )
    {
        $rows = [ ];

        if ( false !== ( $result = $this->connID->query( $query->getFinalStatement() ) ) ) {
            $rows = $result->fetch_all( MYSQLI_ASSOC );
        } else {
            $query->setError( $this->connID->errno, $this->connID->error );
        }

        return $rows;
    }

    // ------------------------------------------------------------------------
}