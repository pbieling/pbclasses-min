<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @author Peter Bieling
 */
class SqlPdoTest extends TestCase {

    const TEST_DATABASE = 'pdo_unittest';
    const TEST_TABLE = 'demo_persons';
    const TEST_TABLE2 = 'pbc__backnavi';

    protected $db;

    public function setUp(): void {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        require_once __DIR__ . '/../PbClasses/autoloader.php';
        //($host = null, $user = null, $passw = null, $db = null, $port = 3306, $dbs = self::DEFAULT_DBS)

        $this->db = new \PbClasses\DB\SqlPdo('localhost', 'root', '4zZ81sOs', self::TEST_DATABASE, 3306, 'mysql');
        $this->db->setDebug();
        $this->db->dropTable(self::TEST_TABLE); //IF EXISTS
        $this->db->dropTable(self::TEST_TABLE2); //IF EXISTS
        $this->createTestTable();
    }

    public function testSimpleQuery() {
        $expectedResult = date('Y-m-d');
        $sql = "SELECT DATE(NOW())";
        $date = $this->db->getSqlQueryVal($sql);
        $this->assertSame($expectedResult, $date);
    }

    public function testSimpleQueryNull() {
        $expectedResult = null;
        $sql = "SELECT NULL";
        $value = $this->db->getSqlQueryVal($sql);
        $this->assertSame($expectedResult, $value);
    }

    public function testShowColumns() {
        $res = $this->db->showColumns(self::TEST_TABLE);
        $serializedExp = 'a:10:{i:0;a:6:{s:5:"Field";s:2:"ID";s:4:"Type";s:3:"int";s:4:"Null";s:2:"NO";s:3:"Key";s:3:"PRI";s:7:"Default";N;s:5:"Extra";s:14:"auto_increment";}i:1;a:6:{s:5:"Field";s:9:"FirstName";s:4:"Type";s:11:"varchar(70)";s:4:"Null";s:2:"NO";s:3:"Key";s:0:"";s:7:"Default";N;s:5:"Extra";s:0:"";}i:2;a:6:{s:5:"Field";s:8:"LastName";s:4:"Type";s:11:"varchar(70)";s:4:"Null";s:2:"NO";s:3:"Key";s:0:"";s:7:"Default";N;s:5:"Extra";s:0:"";}i:3;a:6:{s:5:"Field";s:11:"DateOfBirth";s:4:"Type";s:4:"date";s:4:"Null";s:2:"NO";s:3:"Key";s:0:"";s:7:"Default";N;s:5:"Extra";s:0:"";}i:4;a:6:{s:5:"Field";s:5:"EMail";s:4:"Type";s:11:"varchar(50)";s:4:"Null";s:2:"NO";s:3:"Key";s:0:"";s:7:"Default";N;s:5:"Extra";s:0:"";}i:5;a:6:{s:5:"Field";s:9:"Telephone";s:4:"Type";s:11:"varchar(20)";s:4:"Null";s:2:"NO";s:3:"Key";s:0:"";s:7:"Default";N;s:5:"Extra";s:0:"";}i:6;a:6:{s:5:"Field";s:8:"PostCode";s:4:"Type";s:29:"smallint(5) unsigned zerofill";s:4:"Null";s:2:"NO";s:3:"Key";s:0:"";s:7:"Default";s:5:"00000";s:5:"Extra";s:0:"";}i:7;a:6:{s:5:"Field";s:4:"City";s:4:"Type";s:11:"varchar(50)";s:4:"Null";s:2:"NO";s:3:"Key";s:0:"";s:7:"Default";N;s:5:"Extra";s:0:"";}i:8;a:6:{s:5:"Field";s:6:"Street";s:4:"Type";s:11:"varchar(50)";s:4:"Null";s:2:"NO";s:3:"Key";s:0:"";s:7:"Default";N;s:5:"Extra";s:0:"";}i:9;a:6:{s:5:"Field";s:11:"LuckyPoints";s:4:"Type";s:3:"int";s:4:"Null";s:3:"YES";s:3:"Key";s:0:"";s:7:"Default";s:2:"10";s:5:"Extra";s:0:"";}}';
        $expectedResult = unserialize($serializedExp);
        $this->assertSame($expectedResult, $res);
    }

    public function testInsertWithOneArrayAssoc() {
        $this->db->truncateTable(self::TEST_TABLE);
        $expectedResult = 1;
        $inArr = [
            'FirstName' => 'Larissa',
            'LastName' => 'Seeger',
            'DateOfBirth' => '1990-02-26',
            'EMail' => 'elvira34zz@krauss.com',
            'Telephone' => '+49(0)2351 457157',
            'PostCode' => '04486',
            'City' => 'Remseck am Neckar',
            'Street' => 'Julius-Frey-Gasse 4a'
        ];

        $num = $this->db->insert(self::TEST_TABLE, $inArr);
        $this->assertSame($expectedResult, $num);
    }

    public function testSelectSingleVal() {
        //Clear Table and insert one row.
        $this->insertOneRow();
        $expectedResult = 'Larissa';
        $where = ['ID' => 1];
        $value = $this->db->SelectSingleVal(self::TEST_TABLE, 'FirstName', $where);
        $this->assertSame($expectedResult, $value);
    }

    public function testSelectSingleValFromEmptyTable() {
        $this->db->truncateTable(self::TEST_TABLE);
        $expectedResult = null;
        $where = ['ID' => 1];
        $value = $this->db->SelectSingleVal(self::TEST_TABLE, 'FirstName', $where);
        $this->assertSame($expectedResult, $value);
    }

    public function testselectAssocWithString() {
        //Clear Table and insert one row.
        $this->insertOneRow();

        $expectedResult = [
            'FirstName' => 'Larissa',
            'LastName' => 'Seeger',
            'DateOfBirth' => '1990-02-26',
            'EMail' => 'elvira34zz@krauss.com',
            'Telephone' => '+49(0)2351 457157',
            'PostCode' => '04486',
            'City' => 'Remseck am Neckar',
            'Street' => 'Julius-Frey-Gasse 4a'
        ];
        $what = "FirstName, LastName, DateOfBirth, EMail, 
                 Telephone, PostCode, City, Street";
        $where = ['ID' => 1];
        $resultRows = $this->db->selectAssoc(self::TEST_TABLE, $what, $where);
        $row = $resultRows[0];
        $this->assertSame($expectedResult, $row);
    }

    public function testselectAssocWithStringFromEmptyTable() {
        //Truncate Table 
        $this->db->truncateTable(self::TEST_TABLE);

        $expectedResult = [];
        $what = "FirstName, LastName, DateOfBirth, EMail, 
                 Telephone, PostCode, City, Street";
        $where = ['ID' => 1];
        $resultRows = $this->db->selectAssoc(self::TEST_TABLE, $what, $where);
        $this->assertSame($expectedResult, $resultRows);
    }

    public function testInsertSimpleKeyValArray() {
        $expectedResult = [[
        'FirstName' => 'Jimmy',
        'LastName' => 'Miller'
        ]];

        $inArr = $expectedResult[0];
        $this->db->insert(self::TEST_TABLE, $inArr);
        //Check:
        $what = "FirstName, LastName";
        $where = ['ID' => 1];
        $resultRows = $this->db->selectAssoc(self::TEST_TABLE, $what, $where);
        $this->assertSame($expectedResult, $resultRows);
    }

    public function testInsertSimpleKeyArrayValArray() {
        $expectedResult = [[
        'FirstName' => 'Jimmy',
        'LastName' => 'Miller'
        ]];

        $fieldsArr = ['FirstName', 'LastName'];
        $valuesArr = ['Jimmy', 'Miller'];

        $this->db->insert(self::TEST_TABLE, $fieldsArr, $valuesArr);
        //Check:
        $what = "FirstName, LastName";
        $where = ['ID' => 1];
        $resultRows = $this->db->selectAssoc(self::TEST_TABLE, $what, $where);
        $this->assertSame($expectedResult, $resultRows);
    }

    public function testInsertColArrWithKeyValArray() {
        $expectedResult = [[
        'FirstName' => 'Jimmy',
        'LastName' => 'Miller'
        ]];

        $fieldsArr = ['FirstName', 'LastName'];
        $bindArr = [
            'col1' => 'Jimmy',
            'col2' => 'Miller'
        ];

        $this->db->insert(self::TEST_TABLE, $fieldsArr, $bindArr);
        //Check:
        $what = "FirstName, LastName";
        $where = ['ID' => 1];
        $resultRows = $this->db->selectAssoc(self::TEST_TABLE, $what, $where);
        $this->assertSame($expectedResult, $resultRows);
    }

    public function testInsertColArrWithRowsKeyArray() {
        $expectedResult = [
            [
                'FirstName' => 'Jimmy',
                'LastName' => 'Miller'
            ],
            [
                'FirstName' => 'Susan',
                'LastName' => 'Mullingham'
            ]
        ];

        $fieldsArr = ['FirstName', 'LastName'];
        $keysArr = [
            ['Jimmy', 'Miller'],
            ['Susan', 'Mullingham']
        ];

        $this->db->insert(self::TEST_TABLE, $fieldsArr, $keysArr);

        //Check:
        $what = "FirstName, LastName";
        $resultRows = $this->db->selectAssoc(self::TEST_TABLE, $what);
        //echo "\n===========";
        //print_r($resultRows);
        //echo "\n===========\n";
        $this->assertSame($expectedResult, $resultRows);
    }

    public function testOnDuplicateKeyWithInsertStringAndUnbindedUpdateString() {
        //Clear Table and insert one row.
        $this->insertOneRow();
        $colStr = "(Id, LastName)";
        $valueStr = "VALUES (1, 'Winner')";
        //INSERT  INTO demo_persons (Id, LastName) VALUES (1, 'Winner') ON DUPLICATE KEY UPDATE LuckyPoints = LuckyPoints + 1

        $onDuplicate = 'LuckyPoints = LuckyPoints + 1';
        $this->db->insert(self::TEST_TABLE, $colStr, $valueStr, $onDuplicate);

        //Check if LuckyPoints was updaeted.
        $expectedResult = 11;
        $where = ['ID' => 1];
        $value = $this->db->SelectSingleVal(self::TEST_TABLE, 'LuckyPoints', $where);
        $this->assertSame($expectedResult, $value);
    }

    public function testInsertOnDuplicateKeyWithInsertStringAndBindArray() {
        $this->insertTenRows();
        $insertStr = "(Id, LastName) VALUES (:ID, :LastName)";
        $onDuplicate = 'LastName = :DuplEntry';
        $bindArr = [
            'ID' => 5,
            'LastName' => 'LastName5',
            'DuplEntry' => 'NoNewLine-5'
        ];

        $this->db->insert(self::TEST_TABLE, $insertStr, $bindArr, $onDuplicate);
        //Check if LuckyPoints was updaeted.
        $expectedResult = [
            // ['NoNewLine-1'],
            ['NoNewLine-5']
        ];
        // $where = "ID IN (1, 5)";
        $where = "ID = 5";
        $value = $this->db->Select(self::TEST_TABLE, 'LastName', $where);
        $this->assertSame($expectedResult, $value);
    }

    public function testInsertOnDuplicateKeyArrayWithInsertStringAndBindArray() {
        $this->insertTenRows();
        $insertStr = "(Id, LastName) VALUES (:ID, :LastName)";
        // $onDuplicate = 'LastName = :DuplEntry';
        $onDuplicate = ['LastName' => 'NoNewLine-5'];
        $bindArr = [
            'ID' => 5,
            'LastName' => 'LastName5',
        ];

        $this->db->insert(self::TEST_TABLE, $insertStr, $bindArr, $onDuplicate);

        //Check if LuckyPoints was updaeted.
        $expectedResult = [
            // ['NoNewLine-1'],
            ['NoNewLine-5']
        ];

        $where = "ID = 5";
        $value = $this->db->Select(self::TEST_TABLE, 'LastName', $where);
        $this->assertSame($expectedResult, $value);
    }

    public function testOnDuplicateKeyWithInsertArrayAndUnbindedUpdateString() {
        //Clear Table and insert one row.
        $this->insertOneRow();
        $inArr = [
            'ID' => 1,
            'FirstName' => 'Tony',
            'LastName' => 'Winner',
            'DateOfBirth' => '1980-02-26',
            'EMail' => 'tony@xy-sdkfdl.com',
            'Telephone' => '+49(0)555 45337',
            'PostCode' => '01245',
            'City' => 'Dinkelbrück',
            'Street' => 'Huberstr. 5'
        ];

        $onDuplicate = 'LuckyPoints = LuckyPoints + 1';
        $this->db->insert(self::TEST_TABLE, $inArr, '', $onDuplicate);

        //Check if LuckyPoints was updaeted.
        $expectedResult = 11;
        $where = ['ID' => 1];
        $value = $this->db->SelectSingleVal(self::TEST_TABLE, 'LuckyPoints', $where);
        $this->assertSame($expectedResult, $value);
    }

    public function testOnDuplicateKeyWithInsertArrayAndUpdateArray() {
        //Clear Table and insert one row.
        $this->insertOneRow();
        $inArr = [
            'ID' => 1,
            'FirstName' => 'Tony',
            'LastName' => 'Winner',
            'DateOfBirth' => '1980-02-26',
            'EMail' => 'tony@xy-sdkfdl.com',
            'Telephone' => '+49(0)555 45337',
            'PostCode' => '01245',
            'City' => 'Dinkelbrück',
            'Street' => 'Huberstr. 5'
        ];
        $onDuplicate = ['LastName' => 'NoNewLine-1',
            'LuckyPoints' => null];
        $this->db->insert(self::TEST_TABLE, $inArr, '', $onDuplicate);

        //Check if LuckyPoints was updaeted.
        $expectedResult = [
            [
                'LastName' => 'NoNewLine-1',
                'LuckyPoints' => null
            ]
        ];
        $what = 'LastName, LuckyPoints';
        $where = ['ID' => 1];
        $value = $this->db->selectAssoc(self::TEST_TABLE, $what, $where);
        $this->assertSame($expectedResult, $value);
    }

    public function testOnDuplicateKeyWithInsertArrayAndUpdateArrayAndBackticks() {
        //Clear Table and insert one row.
        $this->insertOneRow();
        $inArr = [
            'ID' => 1,
            '`FirstName`' => 'Tony',
            '`LastName`' => 'Winner',
            'DateOfBirth' => '1980-02-26',
            'EMail' => 'tony@xy-sdkfdl.com',
            'Telephone' => '+49(0)555 45337',
            'PostCode' => '01245',
            'City' => 'Dinkelbrück',
            'Street' => 'Huberstr. 5'
        ];
        $onDuplicate = ['`LastName`' => 'NoNewLine-1',
            '`LuckyPoints`' => null];
        $this->db->insert(self::TEST_TABLE, $inArr, '', $onDuplicate);

        //Check if LuckyPoints was updaeted.
        $expectedResult = [
            [
                'LastName' => 'NoNewLine-1',
                'LuckyPoints' => null
            ]
        ];
        $what = 'LastName, LuckyPoints';
        $where = ['ID' => 1];
        $value = $this->db->selectAssoc(self::TEST_TABLE, $what, $where);
        $this->assertSame($expectedResult, $value);
    }

    public function testReplaceRow() {
        //Clear Table and insert one row.
        $this->insertOneRow();
        $replaceArr = [
            'ID' => 1,
            '`FirstName`' => 'Tony',
            '`LastName`' => 'Winner',
            'DateOfBirth' => '1980-02-26',
            'EMail' => 'tony@xy-sdkfdl.com',
            'Telephone' => '+49(0)555 45337',
            'PostCode' => '01245',
            'City' => 'Dinkelbrück',
            'Street' => 'Huberstr. 5'
        ];
        $this->db->replace(self::TEST_TABLE, $replaceArr);

        //Check if LuckyPoints was updaeted.
        $expectedResult = [
            [
                'LastName' => 'Winner',
                'Street' => 'Huberstr. 5'
            ]
        ];
        $what = 'LastName, Street';
        $where = ['ID' => 1];
        $value = $this->db->selectAssoc(self::TEST_TABLE, $what, $where);
        $this->assertSame($expectedResult, $value);
    }

    public function testselectAssocWithArray() {
        //Clear Table and insert one row.
        $this->insertOneRow();

        $expectedResult = [
            'FirstName' => 'Larissa',
            'LastName' => 'Seeger',
            'DateOfBirth' => '1990-02-26',
            'EMail' => 'elvira34zz@krauss.com',
            'Telephone' => '+49(0)2351 457157',
            'PostCode' => '04486',
            'City' => 'Remseck am Neckar',
            'Street' => 'Julius-Frey-Gasse 4a'
        ];
        $what = ['FirstName', 'LastName', 'DateOfBirth', 'EMail',
            'Telephone', 'PostCode', 'City', 'Street'];
        $where = ['ID' => 1];
        $resultRows = $this->db->selectAssoc(self::TEST_TABLE, $what, $where);
        $row = $resultRows[0];
        $this->assertSame($expectedResult, $row);
    }

    public function testselectAssocWithArrayAndWhereString() {
        //Clear Table and insert one row.
        $this->insertOneRow();

        $expectedResult = [
            'FirstName' => 'Larissa',
            'LastName' => 'Seeger',
            'DateOfBirth' => '1990-02-26',
            'EMail' => 'elvira34zz@krauss.com',
            'Telephone' => '+49(0)2351 457157',
            'PostCode' => '04486',
            'City' => 'Remseck am Neckar',
            'Street' => 'Julius-Frey-Gasse 4a'
        ];
        $what = ['FirstName', 'LastName', 'DateOfBirth', 'EMail',
            'Telephone', 'PostCode', 'City', 'Street'];
        $where = "FirstName = :FirstName AND City LIKE :CityPart";
        $bindArr = ['CityPart' => 'Remseck%', 'FirstName' => 'Larissa'];
        $resultRows = $this->db->selectAssoc(self::TEST_TABLE, $what, $where, $bindArr);
        $row = $resultRows[0];
        $this->assertSame($expectedResult, $row);
    }

    public function testUpdateOneValue() {
        //Clear Table and insert one row.
        $this->insertOneRow();
        $expectedResult = 'James';
        $where = ['ID' => 1];
        // $this->db->Update(self::TEST_TABLE, ['FirstName'], ['James'], $where);

        $this->db->Update(self::TEST_TABLE, ['FirstName' => 'James'], '', $where);

        $value = $this->db->SelectSingleVal(self::TEST_TABLE, 'FirstName', $where);
        $this->assertSame($expectedResult, $value);
    }

    public function testUpdateWithFieldArrAndValueArr() {
        //Clear Table and insert one row.
        $this->insertOneRow();

        $expectedResult = [['FirstName' => 'James', 'City' => 'Hamburg']];
        $where = ['ID' => 1];
        $fieldArr = ['FirstName', 'City'];
        $updArr = ['James', 'Hamburg'];
        $this->db->Update(self::TEST_TABLE, $fieldArr, $updArr, $where);
        //Check the update:
        $result = $this->db->SelectAssoc(self::TEST_TABLE, 'FirstName, City', $where);
        $this->assertSame($expectedResult, $result);
    }

    public function testUpdateWithFieldArrAndBindArr() {
        //Clear Table and insert one row.
        $this->insertOneRow();

        $expectedResult = [['FirstName' => 'James', 'City' => 'Hamburg']];
        $where = ['ID' => 1];
        $fieldArr = ['FirstName', 'City'];
        $bindArr = ['colName' => 'James', 'colCity' => 'Hamburg'];
        $this->db->Update(self::TEST_TABLE, $fieldArr, $bindArr, $where);
        //Check the update:
        $result = $this->db->SelectAssoc(self::TEST_TABLE, 'FirstName, City', $where);
        $this->assertSame($expectedResult, $result);
    }

    public function testUpdateWithFieldArrWithBindArrAndWhereStrWithWhereBindArr() {
        //Clear Table and insert one row.
        $this->insertOneRow();

        $expectedResult = [['FirstName' => 'Tusnelda', 'City' => 'Hamburg']];
        $where = "ID = :where_ID AND FirstName LIKE :where_FirstName";
        $whereBindArr = [
            'where_ID' => 1,
            'where_FirstName' => 'Larissa%'];

        $fieldArr = ['FirstName', 'City'];
        $bindArr = ['colName' => 'Tusnelda', 'colCity' => 'Hamburg'];
        $this->db->Update(self::TEST_TABLE, $fieldArr, $bindArr, $where, $whereBindArr);
        //Check the update:
        $result = $this->db->SelectAssoc(self::TEST_TABLE, 'FirstName, City', ['ID' => 1]);
        $this->assertSame($expectedResult, $result);
    }

    public function testUpdateWithKeyValueArray() {
        //Clear Table and insert one row.
        $this->insertOneRow();

        $expectedResult = [['FirstName' => 'James', 'City' => 'Hamburg']];
        $where = ['ID' => 1];
        $keyValueArr = ['FirstName' => 'James', 'City' => 'Hamburg'];
        $this->db->Update(self::TEST_TABLE, $keyValueArr, '', $where);
        //Check the update:
        $result = $this->db->SelectAssoc(self::TEST_TABLE, 'FirstName, City', $where);
        $this->assertSame($expectedResult, $result);
    }

    public function testUpdateIgnoreWithKeyValueArray() {
        //Clear Table and insert one row.
        $this->insertTenRows();

        $expectedResult = 0;
        $where = ['ID' => 1];
        //The ID already exists. The number of updated rows should be 0.
        $keyValueArr = ['ID' => 5, 'FirstName' => 'James', 'City' => 'Hamburg'];
        $num = $this->db->UpdateIgnore(self::TEST_TABLE, $keyValueArr, '', $where);
        $this->assertSame($expectedResult, $num);
    }

    public function testDeleteWithArray() {
        //Clear Table and insert one row.
        $this->insertTenRows();

        $expectedResult = [];
        $where = ['ID' => 5];

        $this->db->Delete(self::TEST_TABLE, $where);
        //Check the update:
        $result = $this->db->SelectAssoc(self::TEST_TABLE, 'FirstName, City', $where);
        $this->assertSame($expectedResult, $result);
    }

    public function testLastInserdId() {
        $expectedResult = 10;
        $this->insertTenRows();
        $lastId = $this->db->insertId();
        $this->assertEquals($expectedResult, $lastId);
    }

    public function testHasDatabae() {
        $expectedResult = true;
        $res = $this->db->hasDatabase(self::TEST_DATABASE);
        $this->assertSame($expectedResult, $res);
    }

    public function testGetDatabaseList() {
        $expectedResult = true;
        $res = $this->db->getDatabaseList();
        $this->assertSame($expectedResult, in_array(self::TEST_DATABASE, $res));
    }

    public function testGetTableList() {
        //Clear Table and insert one row.
        $this->insertOneRow();
        $expectedResult = [self::TEST_TABLE];
        //$res = $this->db->getTableList(self::TEST_DATABASE);
        $res = $this->db->getTableList(self::TEST_DATABASE);
        $this->assertSame($expectedResult, $res);
    }
    
    
    public function testQuote() {
        //Clear Table and insert one row.
        $this->insertOneRow();
        $expectedResult = "o'Connor";
        $where = ['ID' => 1];
        // $this->db->Update(self::TEST_TABLE, ['FirstName'], ['James'], $where);

        $this->db->Update(self::TEST_TABLE, ['LastName' => "o'Connor"], '', $where);
        
        
        $where2 = "LastName = '" . $this->db->escape("o'Connor") . "'";
        $value = $this->db->SelectSingleVal(self::TEST_TABLE, 'LastName', $where2);
        $this->assertSame($expectedResult, $value);
    }
    
    //Should work like mysql_real_escape. Uses pdo->quote() without quotes.
    //Use only if the use of bind parameters is not possible.
    public function testEscape() {
        //Clear Table and insert one row.
        $this->insertOneRow();
        $expectedResult = "o'Connor";
        $where = ['ID' => 1];
        // $this->db->Update(self::TEST_TABLE, ['FirstName'], ['James'], $where);

        $this->db->Update(self::TEST_TABLE, ['LastName' => "o'Connor"], '', $where);
        
        
        $where2 = 'LastName = '  . $this->db->quote("o'Connor");
            
        $value = $this->db->SelectSingleVal(self::TEST_TABLE, 'LastName', $where2);
        $this->assertSame($expectedResult, $value);
    }   
    

    public function testMultipleSqlQueries() {
        $sqlMultiQuery = <<<'EOD'
                SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `pbc__backnavi`
--
CREATE TABLE `pbc__backnavi` (
  `ID` int UNSIGNED NOT NULL,
  `ParentID` int UNSIGNED NOT NULL DEFAULT '0',
  `Label` varchar(100) NOT NULL DEFAULT '',
  `URL` varchar(200) NOT NULL DEFAULT '',
  `AltTpl` varchar(50) NOT NULL DEFAULT '',
  `HelpID` varchar(10) NOT NULL DEFAULT '',
  `Ord` smallint UNSIGNED NOT NULL DEFAULT '0',
  `Hide` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `NoPage` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `AccLevel` tinyint UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `pbc__backnavi`
--

INSERT INTO `pbc__backnavi` (`ID`, `ParentID`, `Label`, `URL`, `AltTpl`, `HelpID`, `Ord`, `Hide`, `NoPage`, `AccLevel`) VALUES
(1, 0, 'Übersicht', 'index.php', '', '', 0, 0, 0, 0),
(2, 0, 'Master', 'master/index.php', '', '', 2, 0, 1, 3),
(3, 2, 'Task-Tabelle', 'admin/task-table.php', '', '', 4, 1, 0, 3),
(4, 2, 'User-Verwaltung', 'master/edit-user.php', '', '', 1, 0, 0, 3),
(5, 2, 'Seitenverwaltung', 'master/page-creator.php', '', '', 0, 0, 0, 3);
EOD;
        $this->db->multiQuery($sqlMultiQuery);
        //Check if table and entries exists: 
        $expectedResult = 'Seitenverwaltung';
        $value = $this->db->SelectSingleVal('pbc__backnavi', 'Label', ['ID' => 5]);
        $this->assertSame($expectedResult, $value);
    }

    protected function createTestTable() {
        $this->db->dropTable('demo_persons'); //IF EXISTS

        $sql1 = "CREATE TABLE  IF NOT EXISTS `demo_persons`  (
          `ID` int NOT NULL,
          `FirstName` varchar(70) NOT NULL,
          `LastName` varchar(70) NOT NULL,
          `DateOfBirth` date NOT NULL,
          `EMail` varchar(50) NOT NULL,
          `Telephone` varchar(20) NOT NULL,
          `PostCode` smallint(5) UNSIGNED ZEROFILL NOT NULL DEFAULT '00000',
          `City` varchar(50) NOT NULL,
          `Street` varchar(50) NOT NULL,
          `LuckyPoints` int NULL DEFAULT 10
          ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

        $sql2 = 'ALTER TABLE `demo_persons`
  ADD PRIMARY KEY (`ID`)';

        $sql3 = 'ALTER TABLE `demo_persons`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT';

        try {
            //$this->db->beginTransaction();

            $this->db->query($sql1);
            $this->db->query($sql2);
            $this->db->query($sql3);

            // $this->db->commit();
        } catch (\PDOException $e) {
            // $this->db->rollback();
            echo 'PDO Exception: ' . $e->getMessage() . "\r\n";
        } catch (\Exception $exc) {
            echo $exc->getMessage();
        }
    }

    protected function insertOneRow() {
        $this->db->truncateTable('demo_persons');

        $sql = "INSERT INTO `demo_persons` (`ID`, `FirstName`, `LastName`, `DateOfBirth`, `EMail`, `Telephone`, `PostCode`, `City`, `Street`, `LuckyPoints`) VALUES
(1, 'Larissa', 'Seeger', '1990-02-26', 'elvira34zz@krauss.com', '+49(0)2351 457157', 04486, 'Remseck am Neckar', 'Julius-Frey-Gasse 4a', 10)";
        $this->db->query($sql);
    }

    protected function insertTenRows() {
        $this->db->truncateTable('demo_persons');

        $sql = "INSERT INTO `demo_persons` (`ID`, `FirstName`, `LastName`, `DateOfBirth`, `EMail`, `Telephone`, `PostCode`, `City`, `Street`, `LuckyPoints`) VALUES
(1, 'Larissa', 'Seeger', '1990-02-26', 'elvira34zz@krauss.com', '+49(0)2351 457157', 04486, 'Remseck am Neckar', 'Julius-Frey-Gasse 4a', 10),
(2, 'Joanna', 'Nowak', '1982-12-18', 'hannelore43zz@hubner.com', '08722 20217', 20963, 'Werdau', 'Weberweg 11a', 10),
(3, 'Margareta', 'Fiedler', '1982-04-10', 'fwagnerzz@gxmail.com', '09934 98557', 07829, 'Lahr/Schwarzwald', 'Hans-Martin-Merkel-Straße 5', 10),
(4, 'Ursel', 'Lechner', '1977-03-23', 'bertram.stevenzz@zimmer.com', '01747 99439', 21597, 'Neunkirchen', 'Hans-Josef-Stock-Weg 3', 10),
(5, 'Hans-Gerd', 'Bittner', '1937-07-24', 'noll.detlefzz@raab.de', '(07789) 36048', 19766, 'Wiehl', 'Doris-Moritz-Straße 4', 10),
(6, 'Hella', 'Zimmermann', '1932-03-12', 'kvogtzz@sauter.com', '05387 58883', 44575, 'Kempen', 'Ariane-Fink-Weg 159', 10),
(7, 'Danuta', 'Huber', '1949-10-29', 'rmackzz@hein.de', '0369636035', 43392, 'Selm', 'Silke-Herold-Ring 6/9', 10),
(8, 'Anny', 'Mack', '1982-12-20', 'guenter.klosezz@roder.de', '03194 821919', 63864, 'Wuppertal', 'Wolf-Kremer-Weg 4a', 10),
(9, 'Karina', 'Beer', '1978-12-01', 'kheimzz@wabx.de', '0498110557', 52031, 'Leinfelden-Echterdingen', 'Hans Georg-Ernst-Straße 93c', 10),
(10, 'Verena', 'Wild', '1932-07-16', 'jkuhnzz@gmyz.de', '00378 83995', 65535, 'Kamen', 'Dörrplatz 34a', 10)";

        try {
            // $this->db->beginTransaction(); 
            $this->db->query($sql);
            // echo 'INSERT-ID: ' . $this->db->insertId() . "\n";
            // $this->db->commit();
        } catch (\Exception $exc) {
            $exc->getMessage();
            //$this->db->rollback();
        }
    }
}
