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
 * The default implementation of a data set.
 *
 * @package    DbUnit
 * @author     Mike Lively <m@digitalsandwich.com>
 * @copyright  2010-2014 Mike Lively <m@digitalsandwich.com>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.0.0
 */
abstract class PHPUnit_Extensions_Database_DataSet_AbstractXmlDataSet extends PHPUnit_Extensions_Database_DataSet_AbstractDataSet
{

    /**
     * @var array
     */
    protected $tables;

    /**
     * @var SimpleXmlElement
     */
    protected $xmlFileContents;

    /**
     * Creates a new dataset using the given tables.
     *
     * @param array $tables
     */
    public function __construct($xmlFile)
    {
        if (!is_file($xmlFile)) {
            throw new InvalidArgumentException(
              "Could not find xml file: {$xmlFile}"
            );
        }

        $libxmlErrorReporting  = libxml_use_internal_errors(TRUE);
        $this->xmlFileContents = simplexml_load_file($xmlFile);

        if (!$this->xmlFileContents) {
            $message = '';

            foreach (libxml_get_errors() as $error) {
                $message .= $error->message;
            }

            throw new RuntimeException($message);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($libxmlErrorReporting);

        $tableColumns = array();
        $tableValues  = array();

        $this->getTableInfo($tableColumns, $tableValues);
        $this->createTables($tableColumns, $tableValues);
    }

    /**
     * Reads the simple xml object and creates the appropriate tables and meta
     * data for this dataset.
     */
    protected abstract function getTableInfo(Array &$tableColumns, Array &$tableValues);

    protected function createTables(Array &$tableColumns, Array &$tableValues)
    {
        foreach ($tableValues as $tableName => $values) {
            $table = $this->getOrCreateTable($tableName, $tableColumns[$tableName]);
            foreach ($values as $value) {
                $table->addRow($value);
            }
        }
    }

    /**
     * Returns the table with the matching name. If the table does not exist
     * an empty one is created.
     *
     * @param string $tableName
     * @return PHPUnit_Extensions_Database_DataSet_ITable
     */
    protected function getOrCreateTable($tableName, $tableColumns)
    {
        if (empty($this->tables[$tableName])) {
            $tableMetaData            = new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData($tableName, $tableColumns);
            $this->tables[$tableName] = new PHPUnit_Extensions_Database_DataSet_DefaultTable($tableMetaData);
        }

        return $this->tables[$tableName];
    }

    /**
     * Creates an iterator over the tables in the data set. If $reverse is
     * true a reverse iterator will be returned.
     *
     * @param bool $reverse
     * @return PHPUnit_Extensions_Database_DataSet_ITableIterator
     */
    protected function createIterator($reverse = FALSE)
    {
        return new PHPUnit_Extensions_Database_DataSet_DefaultTableIterator($this->tables, $reverse);
    }
}
