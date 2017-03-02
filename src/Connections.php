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

namespace O2System\Database;

// ------------------------------------------------------------------------

use O2System\Database\Abstracts\AbstractConnection;
use O2System\Psr\Patterns\AbstractObjectRegistryPattern;

/**
 * Class Connections
 *
 * @package O2System\Database
 */
class Connections extends AbstractObjectRegistryPattern
{
    /**
     * Connections::$config
     *
     * @var Registries\Config
     */
    private $config;

    /**
     * Connections::__construct
     *
     * @param Registries\Config $config
     *
     * @return Connections
     */
    public function __construct ( Registries\Config $config )
    {
        $this->config = $config;
    }

    // ------------------------------------------------------------------------

    public function &loadConnection ( $connectionOffset )
    {
        $loadConnection[ $connectionOffset ] = false;

        if ( isset( $this->config[ $connectionOffset ] ) AND $this->has( $connectionOffset ) === false ) {

            $connectionConfig = $this->config[ $connectionOffset ];

            if ( is_array( $connectionConfig ) ) {
                new Registries\Config( $this->config[ $connectionOffset ] );
            }

            $this->createConnection( $connectionOffset, $connectionConfig );

            return $this->get( $connectionOffset );

        } elseif ( $this->has( $connectionOffset ) ) {
            return $this->get( $connectionOffset );
        }

        return $loadConnection;
    }

    // ------------------------------------------------------------------------

    /**
     * Connections::createConnection
     *
     * Create Item Pool
     *
     * @param string            $connectionOffset
     * @param Registries\Config $connectionConfig
     * 
     * @return \O2System\Database\Abstracts\AbstractConnection
     */
    public function &createConnection ( $connectionOffset, Registries\Config $connectionConfig )
    {
        $driverClassName = '\O2System\Database\Drivers\\' . ucfirst(
                str_replace(
                    'sql',
                    'SQL',
                    $connectionConfig->driver
                )
            ) . '\Connection';

        if ( class_exists( $driverClassName ) ) {
            $driverInstance = new $driverClassName( $connectionConfig );

            $this->register( $connectionOffset, $driverInstance );
        }
        
        return $this->get( $connectionOffset );
    }

    // ------------------------------------------------------------------------

    /**
     * Connections::isValid
     *
     * Determine if value is meet requirement.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid ( $value )
    {
        if ( $value instanceof AbstractConnection ) {
            return true;
        }

        return false;
    }
}