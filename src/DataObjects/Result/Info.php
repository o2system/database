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

namespace O2System\Database\DataObjects\Result;

// ------------------------------------------------------------------------

use O2System\Spl\Datastructures\SplArrayObject;

/**
 * Class Info
 * @package O2System\Database\DataObjects\Result
 */
class Info
{
    protected $entries = 5;
    protected $total;
    protected $numbering;

    // ------------------------------------------------------------------------

    /**
     * Info::__construct
     *
     * @param array $total
     */
    public function __construct( array $total )
    {
        $this->total = new SplArrayObject(array_merge([
            'rows' => 0,
            'founds' => 0,
            'pages' => 0,
        ], $total));
    }

    // ------------------------------------------------------------------------

    /**
     * Info::setEntries
     *
     * @param int $entries
     *
     * @return static
     */
    public function setEntries( $entries )
    {
        $this->entries = (int) $entries;

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Info::getTotal
     *
     * @return \O2System\Spl\Datastructures\SplArrayObject
     */
    public function getTotal()
    {
        $this->total->pages = round( $this->total->rows / $this->entries );

        return $this->total;
    }

    // ------------------------------------------------------------------------

    /**
     * Info::getNumbering
     *
     * @return \O2System\Spl\Datastructures\SplArrayObject
     */
    public function getNumbering()
    {
        $activePage = (input()->get('page') ? input()->get('page') : 1);

        return new SplArrayObject([
           'start' => $start = ($activePage == 1 ? 1 : $activePage * $this->entries + 1),
           'end' =>  $start + $this->entries
        ]);
    }
}