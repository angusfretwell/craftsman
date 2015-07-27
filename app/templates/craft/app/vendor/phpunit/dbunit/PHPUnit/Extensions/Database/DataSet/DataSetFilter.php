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
 * A dataset decorator that allows filtering out tables and table columns from
 * results.
 *
 * @package    DbUnit
 * @author     Mike Lively <m@digitalsandwich.com>
 * @copyright  2010-2014 Mike Lively <m@digitalsandwich.com>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.0.0
 */
class PHPUnit_Extensions_Database_DataSet_DataSetFilter extends PHPUnit_Extensions_Database_DataSet_AbstractDataSet
{

    /**
     * The dataset being decorated.
     * @var PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected $originalDataSet;

    /**
     * The tables to exclude from the data set.
     * @var Array
     */
    protected $excludeTables = array();

    /**
     * The tables to exclude from the data set.
     * @var Array
     */
    protected $includeTables = array();

    /**
     * The columns to exclude from the data set.
     * @var Array
     */
    protected $excludeColumns = array();

    /**
     * The columns to exclude from the data set.
     * @var Array
     */
    protected $includeColumns = array();

    /**
     * Creates a new filtered data set.
     *
     * The $exclude tables should be an associative array using table names as
     * the key and an array of column names to exclude for the value. If you
     * would like to exclude a full table set the value of the table's entry
     * to the special string '*'.
     *
     * @param PHPUnit_Extensions_Database_DataSet_IDataSet $originalDataSet
     * @param Array $excludeTables @deprecated use set* methods instead.
     */
    public function __construct(PHPUnit_Extensions_Database_DataSet_IDataSet $originalDataSet, array $excludeTables = array())
    {
        $this->originalDataSet = $originalDataSet;

        $tables = array();
        foreach ($excludeTables as $tableName => $values) {
            if (is_array($values)) {
                $this->setExcludeColumnsForTable($tableName, $values);
            } elseif ($values == '*') {
                $tables[] = $tableName;
            } else {
                $this->setExcludeColumnsForTable($tableName, (array)$values);
            }
        }

        $this->addExcludeTables($tables);
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
        $original_tables = $this->originalDataSet->getIterator($reverse);
        $new_tables      = array();

        foreach ($original_tables as $table) {
            /* @var $table PHPUnit_Extensions_Database_DataSet_ITable */
            $tableName = $table->getTableMetaData()->getTableName();

            if ((!in_array($tableName, $this->includeTables) && !empty($this->includeTables)) ||
                    in_array($tableName, $this->excludeTables)
            ) {
                continue;
            } elseif (!empty($this->excludeColumns[$tableName]) || !empty($this->includeColumns[$tableName])) {
                $new_table = new PHPUnit_Extensions_Database_DataSet_TableFilter($table);

                if (!empty($this->includeColumns[$tableName])) {
                    $new_table->addIncludeColumns($this->includeColumns[$tableName]);
                }

                if (!empty($this->excludeColumns[$tableName])) {
                    $new_table->addExcludeColumns($this->excludeColumns[$tableName]);
                }

                $new_tables[] = $new_table;
            } else {
                $new_tables[] = $table;
            }
        }

        return new PHPUnit_Extensions_Database_DataSet_DefaultTableIterator($new_tables);
    }

    /**
     * Adds tables to be included in the data set.
     * @param array $tables
     */
    public function addIncludeTables(Array $tables)
    {
        $this->includeTables = array_unique(array_merge($this->includeTables, $tables));
    }

    /**
     * Adds tables to be included in the data set.
     * @param array $tables
     */
    public function addExcludeTables(Array $tables)
    {
        $this->excludeTables = array_unique(array_merge($this->excludeTables, $tables));
    }

    /**
     * Adds columns to include in the data set for the given table.
     * @param string $table
     * @param Array $columns
     */
    public function setIncludeColumnsForTable($table, Array $columns)
    {
        $this->includeColumns[$table] = $columns;
    }

    /**
     * Adds columns to include in the data set for the given table.
     * @param string $table
     * @param Array $columns
     */
    public function setExcludeColumnsForTable($table, Array $columns)
    {
        $this->excludeColumns[$table] = $columns;
    }
}
