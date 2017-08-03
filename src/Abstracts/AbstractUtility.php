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

use O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException;

/**
 * Class AbstractUtility
 *
 * @package O2System\Database\Abstracts
 */
abstract class AbstractUtility
{
    /**
     * AbstractUtility::$conn
     *
     * Query Builder database connection instance.
     *
     * @var AbstractConnection
     */
    protected $conn;

    // ------------------------------------------------------------------------

    /**
     * AbstractUtility::__construct.
     *
     * @param \O2System\Database\Abstracts\AbstractConnection $conn
     */
    public function __construct( AbstractConnection $conn )
    {
        $this->conn = $conn;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractUtility::optimizeDatabase
     *
     * Optimize database.
     *
     * @param string $database Database name.
     *
     * @return array
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException
     */
    public function optimizeDatabase( $database )
    {
        $this->conn->setDatabase( $database );

        $result = [];
        foreach ( $this->conn->getTables() as $tableName ) {
            $result[ $tableName ] = $this->optimizeTable( $tableName );
        }

        return $result;
    }

    //--------------------------------------------------------------------

    /**
     * Abstract::optimizeTable
     *
     * Optimize database table.
     *
     * @param string $table Database table name.
     *
     * @return bool|\O2System\Database\Datastructures\Result
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function optimizeTable( $table )
    {
        $table = $this->conn->prefixTable( $table );

        if ( false !== ( $sqlStatement = $this->platformOptimizeTableStatement(
                $this->conn->escapeIdentifiers( $table )
            ) )
        ) {

            return $this->conn->query( $sqlStatement );
        }

        if ( $this->conn->debugEnabled ) {
            // This feature is not available for the database you are using.'
            throw new BadMethodCallException( 'E_DATABASE_FEATURE_UNAVAILABLE' );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractUtility::platformOptimizeTableStatement
     *
     * Generates a platform-specific OPTIMIZE TABLE statement.
     *
     * @param string $table Database table name.
     *
     * @return string|bool Returns FALSE if not supported and string of SQL statement if supported.
     */
    abstract protected function platformOptimizeTableStatement( $table );

    //--------------------------------------------------------------------

    /**
     * AbstractUtility::repairTable
     *
     * Repair database table.
     *
     * @param string $table Database table name.
     *
     * @return bool|\O2System\Database\Datastructures\Result
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function repairTable( $table )
    {
        $table = $this->conn->prefixTable( $table );

        if ( false !== ( $sqlStatement = $this->platformRepairTableStatement(
                $this->conn->escapeIdentifiers( $table )
            ) )
        ) {

            return $this->conn->query( $sqlStatement );
        }

        if ( $this->conn->debugEnabled ) {
            // This feature is not available for the database you are using.'
            throw new BadMethodCallException( 'E_DATABASE_FEATURE_UNAVAILABLE' );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractUtility::platformRepairTableStatement
     *
     * Generates a platform-specific REPAIR TABLE statement.
     *
     * @param string $table Database table name.
     *
     * @return string|bool Returns FALSE if not supported and string of SQL statement if supported.
     */
    abstract protected function platformRepairTableStatement( $table );

    //--------------------------------------------------------------------

    /**
     * AbstractUtility::analyzeTable
     *
     * Analyze database table.
     *
     * @param string $table Database table name.
     *
     * @return bool|\O2System\Database\Datastructures\Result
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function analyzeTable( $table )
    {
        $table = $this->conn->prefixTable( $table );

        if ( false !== ( $sqlStatement = $this->platformRepairTableStatement(
                $this->conn->escapeIdentifiers( $table )
            ) )
        ) {

            return $this->conn->query( $sqlStatement );
        }

        if ( $this->conn->debugEnabled ) {
            // This feature is not available for the database you are using.'
            throw new BadMethodCallException( 'E_DATABASE_FEATURE_UNAVAILABLE' );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractUtility::backup
     *
     * Backup database tables into SQL statement.
     *
     * @param array $options Backup options.
     *
     * @return bool|string
     */
    public function backup( $options = [] )
    {
        // If the parameters have not been submitted as an
        // array then we know that it is simply the table
        // name, which is a valid short cut.
        if ( is_string( $options ) ) {
            $options = [ 'tables' => $options ];
        }

        // Set up our default preferences
        $defaultOptions = [
            'tables'             => [],
            'ignore'             => [],
            'add_drop'           => true,
            'add_insert'         => true,
            'newline'            => "\n",
            'foreign_key_checks' => true,
        ];

        // Did the user submit any preferences? If so set them....
        if ( count( $options ) > 0 ) {
            foreach ( $defaultOptions as $key => $val ) {
                if ( isset( $options[ $key ] ) ) {
                    $defaultOptions[ $key ] = $options[ $key ];
                }
            }
        }

        // Are we backing up a complete database or individual tables?
        // If no table names were submitted we'll fetch the entire table list
        if ( count( $defaultOptions[ 'tables' ] ) === 0 ) {
            $defaultOptions[ 'tables' ] = $this->conn->getTables();
        }

        return $this->platformBackupHandler( $options );
    }

    //--------------------------------------------------------------------

    /**
     * Abstract::platformRepairTableStatement
     *
     * Platform-specific BACKUP handler.
     *
     * @param array $options Backup options.
     *
     * @return string|bool Returns FALSE if not supported and string of SQL statement if supported.
     */
    abstract protected function platformBackupHandler( array $options );

    //--------------------------------------------------------------------

    /**
     * AbstractUtility::platformAnalyzeTableStatement
     *
     * Generates a platform-specific ANALYZE TABLE statement.
     *
     * @param string $table Database table name.
     *
     * @return string|bool Returns FALSE if not supported and string of SQL statement if supported.
     */
    abstract protected function platformAnalyzeTableStatement( $table );
}