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
    public function __construct(array $total)
    {
        $this->total = new SplArrayObject(array_merge([
            'rows'   => 0,
            'founds' => 0,
            'pages'  => 0,
        ], $total));

        $this->setEntries($this->entries);
    }

    // ------------------------------------------------------------------------

    /**
     * Info::getEntries
     *
     * @return \O2System\Spl\Datastructures\SplArrayObject
     */
    public function getEntries()
    {
        return $this->entries;
    }

    // ------------------------------------------------------------------------

    /**
     * Info::setEntries
     *
     * @param int $entries
     *
     * @return static
     */
    public function setEntries($entries)
    {
        $this->entries = (int)$entries;

        $this->total->pages = @ceil($this->total->rows / $this->entries);

        $activePage = $this->getActivePage();

        $this->numbering = new SplArrayObject([
            'start' => $start = ($activePage == 1 ? 1 : ($activePage - 1) * $this->entries + 1),
            'end'   => ($start + $this->total->founds) - 1,
        ]);

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Info::getTotal
     *
     * @return \O2System\Spl\Datastructures\SplArrayObject
     */
    public function getTotal($offset = null)
    {
        if (isset($offset)) {
            return $this->total->offsetGet($offset);
        }

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
        return $this->numbering;
    }

    public function getActivePage()
    {
        return (input()->get('page') ? input()->get('page') : 1);
    }
}