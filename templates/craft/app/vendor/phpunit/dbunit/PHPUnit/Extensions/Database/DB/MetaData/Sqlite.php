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
 * Provides functionality to retrieve meta data from an Sqlite database.
 *
 * @package    DbUnit
 * @author     Mike Lively <m@digitalsandwich.com>
 * @copyright  Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.0.0
 */
class PHPUnit_Extensions_Database_DB_MetaData_Sqlite extends PHPUnit_Extensions_Database_DB_MetaData
{

    protected $columns = array();

    protected $keys = array();

    protected $truncateCommand = 'DELETE FROM';
    /**
     * Returns an array containing the names of all the tables in the database.
     *
     * @return array
     */
    public function getTableNames()
    {
        $query = "
            SELECT name
            FROM sqlite_master
            WHERE
                type='table' AND
                name <> 'sqlite_sequence'
            ORDER BY name
        ";

        $result = $this->pdo->query($query);

        $tableNames = array();

        while ($tableName = $result->fetchColumn(0)) {
            $tableNames[] = $tableName;
        }

        return $tableNames;
    }

    /**
     * Returns an array containing the names of all the columns in the
     * $tableName table,
     *
     * @param string $tableName
     * @return array
     */
    public function getTableColumns($tableName)
    {
        if (!isset($this->columns[$tableName])) {
            $this->loadColumnInfo($tableName);
        }

        return $this->columns[$tableName];
    }

    /**
     * Returns an array containing the names of all the primary key columns in
     * the $tableName table.
     *
     * @param string $tableName
     * @return array
     */
    public function getTablePrimaryKeys($tableName)
    {
        if (!isset($this->keys[$tableName])) {
            $this->loadColumnInfo($tableName);
        }

        return $this->keys[$tableName];
    }

    /**
     * Loads column info from a sqlite database.
     *
     * @param string $tableName
     */
    protected function loadColumnInfo($tableName)
    {
        $query     = "PRAGMA table_info('{$tableName}')";
        $statement = $this->pdo->query($query);

        /* @var $statement PDOStatement */
        $this->columns[$tableName] = array();
        $this->keys[$tableName]    = array();

        while ($columnData = $statement->fetch(PDO::FETCH_NUM)) {
            $this->columns[$tableName][] = $columnData[1];

            if ($columnData[5] == 1) {
                $this->keys[$tableName][] = $columnData[1];
            }
        }
    }
}
