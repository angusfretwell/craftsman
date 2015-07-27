<?php
/*
 * This file is part of DBUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * An interface for persisting datasets
 *
 * @package    DbUnit
 * @author     Mike Lively <m@digitalsandwich.com>
 * @copyright  2010-2014 Mike Lively <m@digitalsandwich.com>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.0.0
 */
interface PHPUnit_Extensions_Database_DataSet_IPersistable
{
    /**
     * Writes the given dataset
     *
     * The previous dataset will be overwritten.
     *
     * @param PHPUnit_Extensions_Database_DataSet_IDataSet $dataset
     */
    public function write(PHPUnit_Extensions_Database_DataSet_IDataSet $dataset);
}
