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

use O2System\Database\Abstracts\AbstractConnection;
use O2System\Database\Datastructures\Config;
use O2System\Database\Datastructures\Query;
use O2System\Spl\Exceptions\RuntimeException;

/**
 * Class Connection
 *
 * @package O2System\Database\Drivers\MySQL
 */
class Connection extends AbstractConnection
{
    /**
     * Connection::$isDeleteHack
     *
     * DELETE hack flag
     *
     * Whether to use the MySQL "delete hack" which allows the number
     * of affected rows to be shown. Uses a preg_replace when enabled,
     * adding a bit more processing to all queries.
     *
     * @var    bool
     */
    public $isDeleteHack = true;
    /**
     * Connection::$platform
     *
     * Database driver platform name.
     *
     * @var string
     */
    protected $platform = 'MySQL';
    /**
     * Connection::$config
     *
     * Connection configurations.
     *
     * @var array
     */
    protected $config
        = [
            'escapeCharacter'     => '`',
            'reservedIdentifiers' => [ '*' ],
            'likeEscapeStatement' => ' ESCAPE \'%s\' ',
            'likeEscapeCharacter' => '!',
        ];
    /**
     * Connection::$handle
     *
     * MySQLi Connection Instance.
     *
     * @var \mysqli
     */
    protected $handle;

    // ------------------------------------------------------------------------

    /**
     * Connection::isSupported
     *
     * Check if the platform is supported.
     *
     * @return bool
     */
    public function isSupported()
    {
        return extension_loaded( 'mysqli' );
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::reconnect
     *
     * Keep or establish the connection if no queries have been sent for
     * a length of time exceeding the server's idle timeout.
     *
     * @return void
     */
    public function reconnect()
    {
        if ( $this->handle !== false && $this->handle->ping() === false ) {
            $this->handle = false;
        }
    }

    //--------------------------------------------------------------------

    /**
     * Connection::getAffectedRows
     *
     * Get the total number of affected rows from the last query execution.
     *
     * @return int  Returns total number of affected rows
     */
    public function getAffectedRows()
    {
        return $this->handle->affected_rows;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::getLastInsertId
     *
     * Get last insert id from the last insert query execution.
     *
     * @return int  Returns total number of affected rows
     */
    public function getLastInsertId()
    {
        return $this->handle->insert_id;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::setDatabase
     *
     * Set a specific database table to use.
     *
     * @param string $database Database name.
     *
     * @return static
     */
    public function setDatabase( $database )
    {
        $database = empty( $database )
            ? $this->database
            : $database;

        if ( $this->handle->select_db( $database ) ) {
            $this->database = $database;
        }

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformGetPlatformVersionHandler
     *
     * Platform getting version handler.
     *
     * @return mixed
     */
    protected function platformGetPlatformVersionHandler()
    {
        return $this->handle->server_info;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformConnectHandler
     *
     * Establish the connection.
     *
     * @param Config $config
     *
     * @return void
     * @throws RuntimeException
     */
    protected function platformConnectHandler( Config $config )
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
            $port = empty( $config->port )
                ? null
                : $config->port;
            $socket = null;
        }

        $flags = ( $config->compress === true )
            ? MYSQLI_CLIENT_COMPRESS
            : 0;
        $this->handle = mysqli_init();

        $this->handle->options( MYSQLI_OPT_CONNECT_TIMEOUT, 10 );

        if ( isset( $config->strictOn ) ) {
            if ( $config->strictOn ) {
                $this->handle->options(
                    MYSQLI_INIT_COMMAND,
                    'SET SESSION sql_mode = CONCAT(@@sql_mode, ",", "STRICT_ALL_TABLES")'
                );
            } else {
                $this->handle->options(
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
            $ssl = [];
            empty( $config->encrypt[ 'ssl_key' ] ) OR $ssl[ 'key' ] = $config->encrypt[ 'ssl_key' ];
            empty( $config->encrypt[ 'ssl_cert' ] ) OR $ssl[ 'cert' ] = $config->encrypt[ 'ssl_cert' ];
            empty( $config->encrypt[ 'ssl_ca' ] ) OR $ssl[ 'ca' ] = $config->encrypt[ 'ssl_ca' ];
            empty( $config->encrypt[ 'ssl_capath' ] ) OR $ssl[ 'capath' ] = $config->encrypt[ 'ssl_capath' ];
            empty( $config->encrypt[ 'ssl_cipher' ] ) OR $ssl[ 'cipher' ] = $config->encrypt[ 'ssl_cipher' ];

            if ( ! empty( $ssl ) ) {
                if ( isset( $config->encrypt[ 'ssl_verify' ] ) ) {
                    if ( $config->encrypt[ 'ssl_verify' ] ) {
                        defined( 'MYSQLI_OPT_SSL_VERIFY_SERVER_CERT' )
                        && $this->handle->options( MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true );
                    }
                    // Apparently (when it exists), setting MYSQLI_OPT_SSL_VERIFY_SERVER_CERT
                    // to FALSE didn't do anything, so PHP 5.6.16 introduced yet another
                    // constant ...
                    //
                    // https://secure.php.net/ChangeLog-5.php#5.6.16
                    // https://bugs.php.net/bug.php?id=68344
                    elseif ( defined( 'MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT' ) ) {
                        $this->handle->options( MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT, true );
                    }
                }

                $flags |= MYSQLI_CLIENT_SSL;
                $this->handle->ssl_set(
                    isset( $ssl[ 'key' ] )
                        ? $ssl[ 'key' ]
                        : null,
                    isset( $ssl[ 'cert' ] )
                        ? $ssl[ 'cert' ]
                        : null,
                    isset( $ssl[ 'ca' ] )
                        ? $ssl[ 'ca' ]
                        : null,
                    isset( $ssl[ 'capath' ] )
                        ? $ssl[ 'capath' ]
                        : null,
                    isset( $ssl[ 'cipher' ] )
                        ? $ssl[ 'cipher' ]
                        : null
                );
            }
        }

        if ( $this->handle->real_connect(
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
                AND version_compare( $this->handle->client_info, '5.7.3', '<=' )
                AND empty( $this->handle->query( "SHOW STATUS LIKE 'ssl_cipher'" )
                    ->fetch_object()->Value )
            ) {
                $this->handle->close();
                // 'MySQLi was configured for an SSL connection, but got an unencrypted connection instead!';
                logger()->error( 'E_DB_CONNECTION_SSL', [ $this->platform ] );

                if ( $config->debug ) {
                    throw new RuntimeException( 'E_DB_CONNECTION_SSL' );
                }

                return;
            }

            if ( ! $this->handle->set_charset( $config->charset ) ) {
                // "Database: Unable to set the configured connection charset ('{$this->charset}')."
                logger()->error( 'E_DB_CONNECTION_CHARSET', [ $config->charset ] );
                $this->handle->close();

                if ( $config->debug ) {
                    // 'Unable to set client connection character set: ' . $this->charset
                    throw new RuntimeException( 'E_DB_CONNECTION_CHARSET', [ $config->charset ] );
                }
            }
        }
    }

    //--------------------------------------------------------------------

    /**
     * Connection::disconnectHandler
     *
     * Driver dependent way method for closing the connection.
     *
     * @return mixed
     */
    protected function platformDisconnectHandler()
    {
        $this->handle->close();
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformTransactionBeginHandler
     *
     * Platform beginning a transaction handler.
     *
     * @return bool
     */
    protected function platformTransactionBeginHandler()
    {
        $this->handle->autocommit( false );

        if ( $this->handle->commit() ) {
            $this->handle->autocommit( true );

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformTransactionCommitHandler
     *
     * Platform committing a transaction handler.
     *
     * @return bool
     */
    protected function platformTransactionCommitHandler()
    {
        if ( $this->handle->commit() ) {
            $this->handle->autocommit( true );

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformTransactionRollBackHandler
     *
     * Platform rolling back a transaction handler.
     *
     * @return bool
     */
    protected function platformTransactionRollBackHandler()
    {
        if ( $this->handle->rollback() ) {
            $this->handle->autocommit( true );

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::prepareSqlStatement
     *
     * Platform preparing a SQL statement.
     *
     * @param string $sqlStatement SQL Statement to be prepared.
     * @param array  $options      Preparing sql statement options.
     *
     * @return string
     */
    protected function platformPrepareSqlStatement( $sqlStatement, array $options = [] )
    {
        // mysqli_affected_rows() returns 0 for "DELETE FROM TABLE" queries. This hack
        // modifies the query so that it a proper number of affected rows is returned.
        if ( $this->isDeleteHack === true && preg_match( '/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sqlStatement ) ) {
            return trim( $sqlStatement ) . ' WHERE 1=1';
        }

        return $sqlStatement;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformQueryHandler
     *
     * Driver dependent way method for execute the SQL statement.
     *
     * @param Query $query Query object.
     *
     * @return array
     */
    protected function platformQueryHandler( Query &$query )
    {
        $rows = [];

        if ( false !== ( $result = $this->handle->query( $query->getFinalStatement() ) ) ) {
            $rows = $result->fetch_all( MYSQLI_ASSOC );
        } else {
            $query->setError( $this->handle->errno, $this->handle->error );
        }

        return $rows;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::executeHandler
     *
     * Driver dependent way method for execute the SQL statement.
     *
     * @param Query $query Query object.
     *
     * @return bool
     */
    protected function platformExecuteHandler( Query &$query )
    {
        if ( false !== $this->handle->query( $query->getFinalStatement() ) ) {
            return true;
        }

        // Set query error information
        $query->setError( $this->handle->errno, $this->handle->error );

        return false;

    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformEscapeStringHandler
     *
     * Platform escape string handler.
     *
     * @param string $string
     *
     * @return string
     */
    protected function platformEscapeStringHandler( $string )
    {
        return $this->handle->real_escape_string( $string );
    }
}