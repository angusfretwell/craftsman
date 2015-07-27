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
 * @package    DbUnit
 * @author     Mike Lively <m@digitalsandwich.com>
 * @copyright  Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.0.0
 */
class Extensions_Database_DataSet_CompositeDataSetTest extends PHPUnit_Framework_TestCase
{
    protected $expectedDataSet1;
    protected $expectedDataSet2;
    protected $expectedDataSet3;

    public function setUp()
    {
        $table1MetaData = new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData(
            'table1', array('table1_id', 'column1', 'column2', 'column3', 'column4')
        );
        $table2MetaData = new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData(
            'table2', array('table2_id', 'column5', 'column6', 'column7', 'column8')
        );

        $table3MetaData = new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData(
            'table3', array('table3_id', 'column9', 'column10', 'column11', 'column12')
        );

        $table1 = new PHPUnit_Extensions_Database_DataSet_DefaultTable($table1MetaData);
        $table2 = new PHPUnit_Extensions_Database_DataSet_DefaultTable($table2MetaData);
        $table3 = new PHPUnit_Extensions_Database_DataSet_DefaultTable($table3MetaData);

        $table1->addRow(array(
            'table1_id' => 1,
            'column1' => 'tgfahgasdf',
            'column2' => 200,
            'column3' => 34.64,
            'column4' => 'yghkf;a  hahfg8ja h;'
        ));
        $table1->addRow(array(
            'table1_id' => 2,
            'column1' => 'hk;afg',
            'column2' => 654,
            'column3' => 46.54,
            'column4' => '24rwehhads'
        ));
        $table1->addRow(array(
            'table1_id' => 3,
            'column1' => 'ha;gyt',
            'column2' => 462,
            'column3' => 1654.4,
            'column4' => 'asfgklg'
        ));

        $table2->addRow(array(
            'table2_id' => 1,
            'column5' => 'fhah',
            'column6' => 456,
            'column7' => 46.5,
            'column8' => 'fsdb, ghfdas'
        ));
        $table2->addRow(array(
            'table2_id' => 2,
            'column5' => 'asdhfoih',
            'column6' => 654,
            'column7' => 'blah',
            'column8' => '43asd "fhgj" sfadh'
        ));
        $table2->addRow(array(
            'table2_id' => 3,
            'column5' => 'ajsdlkfguitah',
            'column6' => 654,
            'column7' => 'blah',
            'column8' => 'thesethasdl
asdflkjsadf asdfsadfhl "adsf, halsdf" sadfhlasdf'
        ));

        $table3->addRow(array(
            'table3_id' => 1,
            'column9' => 'sfgsda',
            'column10' => 16,
            'column11' => 45.57,
            'column12' => 'sdfh .ds,ajfas asdf h'
        ));
        $table3->addRow(array(
            'table3_id' => 2,
            'column9' => 'afdstgb',
            'column10' => 41,
            'column11' => 46.645,
            'column12' => '87yhasdf sadf yah;/a '
        ));
        $table3->addRow(array(
            'table3_id' => 3,
            'column9' => 'gldsf',
            'column10' => 46,
            'column11' => 123.456,
            'column12' => '0y8hosnd a/df7y olgbjs da'
        ));


        $this->expectedDataSet1 = new PHPUnit_Extensions_Database_DataSet_DefaultDataSet(array($table1, $table2));
        $this->expectedDataSet2 = new PHPUnit_Extensions_Database_DataSet_DefaultDataSet(array($table3));
        $this->expectedDataSet3 = new PHPUnit_Extensions_Database_DataSet_DefaultDataSet(array($table1, $table2, $table3));
    }

    public function testCompositeDataSet()
    {
        $actual = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array($this->expectedDataSet1, $this->expectedDataSet2));

        PHPUnit_Extensions_Database_TestCase::assertDataSetsEqual($this->expectedDataSet3, $actual);
    }

    public function testCompatibleTablesInDifferentDataSetsNonDuplicateRows()
    {
        $compatibleTable = new PHPUnit_Extensions_Database_DataSet_DefaultTable(
            $this->expectedDataSet3->getTable("table3")->getTableMetaData()
        );

        $compatibleTable->addRow(array(
            'table3_id' => 4,
            'column9' => 'asdasd',
            'column10' => 17,
            'column11' => 42.57,
            'column12' => 'askldja'
        ));

        $compositeDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array(
            new PHPUnit_Extensions_Database_DataSet_DefaultDataSet(array($compatibleTable)),
            $this->expectedDataSet2
        ));

        $this->assertEquals(4, $compositeDataSet->getTable("table3")->getRowCount());
    }

    /**
     * @expectedException           InvalidArgumentException
     * @expectedExceptionMessage    There is already a table named table3 with different table definition
     */
    public function testExceptionOnIncompatibleTablesSameTableNames()
    {
        $inCompatibleTableMetaData = new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData(
            'table3', array('table3_id', 'column13', 'column14', 'column15', 'column16')
        );

        $inCompatibleTable = new PHPUnit_Extensions_Database_DataSet_DefaultTable($inCompatibleTableMetaData);
        $inCompatibleTable->addRow(array(
            'column13' => 'asdasda asdasd',
            'column14' => 'aiafsjas asd',
            'column15' => 'asdasdasd',
            'column16' => 2141
        ));

        $compositeDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array(
            $this->expectedDataSet2,
            new PHPUnit_Extensions_Database_DataSet_DefaultDataSet(array($inCompatibleTable))
        ));
    }


    /**
     * @expectedException           InvalidArgumentException
     * @expectedExceptionMessage    There is already a table named table3 with different table definition
     */
    public function testExceptionOnIncompatibleTablesSameTableNames2()
    {
        $inCompatibleTableMetaData = new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData(
            'table3', array('table3_id', 'column13', 'column14', 'column15', 'column16')
        );

        $inCompatibleTable = new PHPUnit_Extensions_Database_DataSet_DefaultTable($inCompatibleTableMetaData);
        $inCompatibleTable->addRow(array(
            'column13' => 'asdasda asdasd',
            'column14' => 'aiafsjas asd',
            'column15' => 'asdasdasd',
            'column16' => 2141
        ));

        $compositeDataSet = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array(
            new PHPUnit_Extensions_Database_DataSet_DefaultDataSet(array($inCompatibleTable)),
            $this->expectedDataSet2
        ));
    }
}
