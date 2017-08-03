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
     * @var Datastructures\Config
     */
    private $config;

    /**
     * Connections::__construct
     *
     * @param Datastructures\Config $config
     *
     * @return Connections
     */
    public function __construct( Datastructures\Config $config )
    {
        $this->config = $config;
    }

    // ------------------------------------------------------------------------

    public function &loadConnection( $connectionOffset )
    {
        $loadConnection[ $connectionOffset ] = false;

        if ( ! $this->exists( $connectionOffset ) and $this->config->offsetExists( $connectionOffset ) ) {

            $connectionConfig = $this->config->offsetGet( $connectionOffset );

            if ( is_array( $connectionConfig ) ) {
                new Datastructures\Config( $this->config[ $connectionOffset ] );
            }

            $this->createConnection( $connectionOffset, $connectionConfig );

            return $this->getObject( $connectionOffset );

        } elseif ( $this->exists( $connectionOffset ) ) {
            return $this->getObject( $connectionOffset );
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
     * @param Datastructures\Config $connectionConfig
     *
     * @return \O2System\Database\Abstracts\AbstractConnection
     */
    public function &createConnection( $connectionOffset, Datastructures\Config $connectionConfig )
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

            $this->register( $driverInstance, $connectionOffset );
        }

        return $this->getObject( $connectionOffset );
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
    public function isValid( $value )
    {
        if ( $value instanceof AbstractConnection ) {
            return true;
        }

        return false;
    }
}