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
 * Creates a FlatXML dataset based off of a spec string.
 *
 * The format of the spec string is as follows:
 *
 * <filename>
 *
 * The filename should be the location of a flat xml file relative to the
 * current working directory.
 *
 * @package    DbUnit
 * @author     Mike Lively <m@digitalsandwich.com>
 * @copyright  2010-2014 Mike Lively <m@digitalsandwich.com>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de//**
 * @since      Class available since Release 1.0.0
 */
class PHPUnit_Extensions_Database_DataSet_Specs_FlatXml implements PHPUnit_Extensions_Database_DataSet_ISpec
{
    /**
     * Creates Flat XML Data Set from a data set spec.
     *
     * @param string $dataSetSpec
     * @return PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet
     */
    public function getDataSet($dataSetSpec)
    {
        return new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet($dataSetSpec);
    }
}
