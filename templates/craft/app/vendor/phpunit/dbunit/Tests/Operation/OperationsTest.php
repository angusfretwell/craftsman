<?php
/*
 * This file is part of DBUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'DatabaseTestUtility.php';

/**
 * @package    DbUnit
 * @author     Mike Lively <m@digitalsandwich.com>
 * @copyright  Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.0.0
 */
class Extensions_Database_Operation_OperationsTest extends PHPUnit_Extensions_Database_TestCase
{
    protected function setUp()
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('PDO/SQLite is required to run this test.');
        }

        parent::setUp();
    }

    public function getConnection()
    {
        return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection(DBUnitTestUtility::getSQLiteMemoryDB(), 'sqlite');
    }

    public function getDataSet()
    {
        return new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/OperationsTestFixture.xml');
    }

    public function testDelete()
    {
        $deleteOperation = new PHPUnit_Extensions_Database_Operation_Delete();

        $deleteOperation->execute($this->getConnection(), new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/DeleteOperationTest.xml'));

        $this->assertDataSetsEqual(new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/DeleteOperationResult.xml'), $this->getConnection()->createDataSet());
    }

    public function testDeleteAll()
    {
        $deleteAllOperation = new PHPUnit_Extensions_Database_Operation_DeleteAll();

        $deleteAllOperation->execute($this->getConnection(), new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/DeleteAllOperationTest.xml'));

        $expectedDataSet = new PHPUnit_Extensions_Database_DataSet_DefaultDataSet(array(
            new PHPUnit_Extensions_Database_DataSet_DefaultTable(
                new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData('table1',
                    array('table1_id', 'column1', 'column2', 'column3', 'column4'))
            ),
            new PHPUnit_Extensions_Database_DataSet_DefaultTable(
                new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData('table2',
                    array('table2_id', 'column5', 'column6', 'column7', 'column8'))
            ),
            new PHPUnit_Extensions_Database_DataSet_DefaultTable(
                new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData('table3',
                    array('table3_id', 'column9', 'column10', 'column11', 'column12'))
            ),
        ));

        $this->assertDataSetsEqual($expectedDataSet, $this->getConnection()->createDataSet());
    }

    public function testTruncate()
    {
        $truncateOperation = new PHPUnit_Extensions_Database_Operation_Truncate();

        $truncateOperation->execute($this->getConnection(), new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/DeleteAllOperationTest.xml'));

        $expectedDataSet = new PHPUnit_Extensions_Database_DataSet_DefaultDataSet(array(
            new PHPUnit_Extensions_Database_DataSet_DefaultTable(
                new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData('table1',
                    array('table1_id', 'column1', 'column2', 'column3', 'column4'))
            ),
            new PHPUnit_Extensions_Database_DataSet_DefaultTable(
                new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData('table2',
                    array('table2_id', 'column5', 'column6', 'column7', 'column8'))
            ),
            new PHPUnit_Extensions_Database_DataSet_DefaultTable(
                new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData('table3',
                    array('table3_id', 'column9', 'column10', 'column11', 'column12'))
            ),
        ));

        $this->assertDataSetsEqual($expectedDataSet, $this->getConnection()->createDataSet());
    }

    public function testInsert()
    {
        $insertOperation = new PHPUnit_Extensions_Database_Operation_Insert();

        $insertOperation->execute($this->getConnection(), new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/InsertOperationTest.xml'));

        $this->assertDataSetsEqual(new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/InsertOperationResult.xml'), $this->getConnection()->createDataSet());
    }

    public function testUpdate()
    {
        $updateOperation = new PHPUnit_Extensions_Database_Operation_Update();

        $updateOperation->execute($this->getConnection(), new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/UpdateOperationTest.xml'));

        $this->assertDataSetsEqual(new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/UpdateOperationResult.xml'), $this->getConnection()->createDataSet());
    }

    public function testReplace()
    {
        $replaceOperation = new PHPUnit_Extensions_Database_Operation_Replace();

        $replaceOperation->execute($this->getConnection(), new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/ReplaceOperationTest.xml'));

        $this->assertDataSetsEqual(new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/ReplaceOperationResult.xml'), $this->getConnection()->createDataSet());
    }

    public function testInsertEmptyTable()
    {
        $insertOperation = new PHPUnit_Extensions_Database_Operation_Insert();

        $insertOperation->execute($this->getConnection(), new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/EmptyTableInsertTest.xml'));

        $this->assertDataSetsEqual(new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/EmptyTableInsertResult.xml'), $this->getConnection()->createDataSet());
    }
    public function testInsertAllEmptyTables()
    {
        $insertOperation = new PHPUnit_Extensions_Database_Operation_Insert();

        $insertOperation->execute($this->getConnection(), new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/AllEmptyTableInsertTest.xml'));

        $this->assertDataSetsEqual(new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(dirname(__FILE__).'/../_files/XmlDataSets/AllEmptyTableInsertResult.xml'), $this->getConnection()->createDataSet());
    }
}
