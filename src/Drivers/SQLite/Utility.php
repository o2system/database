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

namespace O2System\Database\Drivers\SQLite;

// ------------------------------------------------------------------------

use O2System\Database\Abstracts\AbstractUtility;

/**
 * Class Utility
 *
 * @package O2System\Database\Drivers\SQLite
 */
class Utility extends AbstractUtility
{
    /**
     * AbstractUtility::platformOptimizeTableStatement
     *
     * Generates a platform-specific OPTIMIZE TABLE statement.
     *
     * @param string $table Database table name.
     *
     * @return string|bool Returns FALSE if not supported and string of SQL statement if supported.
     */
    protected function platformOptimizeTableStatement( $table )
    {
        return "OPTIMIZE TABLE " . $table;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractUtility::platformRepairTableStatement
     *
     * Generates a platform-specific REPAIR TABLE statement.
     *
     * @param string $table Database table name.
     *
     * @return string|bool Returns FALSE if not supported and string of SQL statement if supported.
     */
    protected function platformRepairTableStatement( $table )
    {
        return "REPAIR TABLE " . $table;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractUtility::platformAnalyzeTableStatement
     *
     * Generates a platform-specific ANALYZE TABLE statement.
     *
     * @param string $table Database table name.
     *
     * @return string|bool Returns FALSE if not supported and string of SQL statement if supported.
     */
    protected function platformAnalyzeTableStatement( $table )
    {
        return "ANALYZE TABLE " . $table;
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
    protected function platformBackupHandler( array $options )
    {
        // TODO: Implement platformBackupHandler() method.
    }
}