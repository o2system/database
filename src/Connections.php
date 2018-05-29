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

use O2System\Psr\Patterns\Structural\Provider\AbstractProvider;
use O2System\Psr\Patterns\Structural\Provider\ValidationInterface;

/**
 * Class Connections
 *
 * @package O2System\Database
 */
class Connections extends AbstractProvider implements ValidationInterface
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
    public function __construct(Datastructures\Config $config)
    {
        $this->config = $config;
    }

    // ------------------------------------------------------------------------

    public function &loadConnection($connectionOffset)
    {
        $loadConnection[ $connectionOffset ] = false;

        if ( ! $this->exists($connectionOffset) and $this->config->offsetExists($connectionOffset)) {

            $connectionConfig = $this->config->offsetGet($connectionOffset);

            if (is_array($connectionConfig)) {
                new Datastructures\Config($this->config[ $connectionOffset ]);
            }

            $this->createConnection($connectionOffset, $connectionConfig);

            return $this->getObject($connectionOffset);

        } elseif ($this->exists($connectionOffset)) {
            return $this->getObject($connectionOffset);
        }

        return $loadConnection;
    }

    // ------------------------------------------------------------------------

    /**
     * Connections::createConnection
     *
     * Create Item Pool
     *
     * @param string                $connectionOffset
     * @param Datastructures\Config $connectionConfig
     *
     * @return bool|\O2System\Database\Sql\Abstracts\AbstractConnection|\O2System\Database\NoSql\Abstracts\AbstractConnection
     */
    public function &createConnection($connectionOffset, Datastructures\Config $connectionConfig)
    {
        $driverMaps = [
            'mongodb' => '\O2System\Database\NoSql\Drivers\MongoDb\Connection',
            'mysql'   => '\O2System\Database\Sql\Drivers\MySql\Connection',
            'sqlite'  => '\O2System\Database\Sql\Drivers\Sqlite\Connection',
        ];

        if (array_key_exists($connectionConfig->driver, $driverMaps)) {
            if (class_exists($driverClassName = $driverMaps[ $connectionConfig->driver ])) {
                $driverInstance = new $driverClassName($connectionConfig);
                $this->register($driverInstance, $connectionOffset);
            }

            return $this->getObject($connectionOffset);
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Connections::validate
     *
     * Determine if value is meet requirement.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function validate($value)
    {
        if ($value instanceof \O2System\Database\Sql\Abstracts\AbstractConnection || $value instanceof \O2System\Database\NoSql\Abstracts\AbstractConnection) {
            return true;
        }

        return false;
    }
}