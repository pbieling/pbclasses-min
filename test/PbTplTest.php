<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

/**
 * @author Peter Bieling
 */
class PbTplTest extends TestCase {

    protected $t;
    protected $tplFile;

    public function setUp():void 
	{
        require_once __DIR__ . '/../src/autoloader.php';
        $this->tplFile = __DIR__ . '/PbTplTest/test1.tpl';
        $this->t = new \PbClasses\PbTpl($this->tplFile);
    }

    public function testSimpleReplacement() {
        $customer = 'Mr. Miller';
        $expectedResult = 'Dear ' . $customer . '!' . "\r\n";
        $filledTpl = $this->t->fillTpl('main', 'customer', $customer);
       $this->assertSame($expectedResult, $filledTpl);
    }
    
    public function testSimpleReplacementEmptyString() {
        $expectedResult = 'Dear ' . '!' . "\r\n";
        $filledTpl = $this->t->fillTpl('main', 'customer', '');
        $this->assertSame($expectedResult, $filledTpl);
    }
    
    public function testSimpleReplacementEmptyStringWithout3rdParam() {
        $expectedResult = 'Dear ' . '!' . "\r\n";
        $filledTpl = $this->t->fillTpl('main', 'customer');
        $this->assertSame($expectedResult, $filledTpl);
    }
    

    public function testNoReplacement() {
        $resultString = $this->t->fillTpl('no_replacement_tpl');
        $expectedResult = 'ABCDEFG' . "\r\n";
        $this->assertSame($expectedResult, $resultString);
    }

    //no_replacement_tpl_with_whitespace
    public function testNoReplacementWhitespace() {
        $resultString = $this->t->fillTpl('no_replacement_tpl_with_whitespace');
        $expectedResult = '    ABCDEFG   ' . "\r\n";
        // 4         3
        $this->assertEquals($expectedResult, $resultString);
    }

    public function testSimpleReplacementRawPlaceholder() {
        $customer = 'Mr. Miller';
        $expectedResult = 'Dear ' . $customer . '!' . "\r\n";
        $this->t->setConvert(false);
        $filledTpl = $this->t->fillTpl('main', '{CUSTOMER}', $customer);
        $this->assertEquals($expectedResult, $filledTpl);
    }

    public function testSimpleReplacementFromString() {
        $tplString = file_get_contents($this->tplFile);
        $t2 = new \PbClasses\PbTpl($tplString, false);

        $customer = 'Mr. Miller';
        $expectedResult = 'Dear ' . $customer . '!' . "\r\n";
        $filledTpl = $t2->fillTpl('main', 'customer', $customer);
        $this->assertSame($expectedResult, $filledTpl);
    }

    public function testSimpleReplacementFromStringAddIndex() {
        $tplString = 'Dear {CUSTOMER}!';
        $t3 = new \PbClasses\PbTpl($tplString, false, 'main');

        $customer = 'Mr. Miller';
        $expectedResult = 'Dear ' . $customer . '!';
        $filledTpl = $t3->fillTpl('main', 'customer', $customer);
        $this->assertSame($expectedResult, $filledTpl);
    }

    public function testMoreReplacementsInOneTemplateWithArrayAssoc() {
        $searchReplace = array('repl1' => 'a',
            'repl2' => 'b',
            'repl3' => 'c',
            'repl4' => 'd'
        );
        $filledTpl = $this->t->fillTpl('more_replacements', $searchReplace);
        $expectedResult = "AaBbCcDd\r\n";
        $this->assertSame($expectedResult, $filledTpl);
    }

    public function testMoreReplacementsInOneTemplateWithTwoArrays() {
        $search = array('repl1', 'repl2', 'repl3', 'repl4');
        $replace = array('a', 'b', 'c', 'd');
        $filledTpl = $this->t->fillTpl('more_replacements', $search, $replace);
        $expectedResult = "AaBbCcDd\r\n";
        $this->assertSame($expectedResult, $filledTpl);
    }

    public function testMoreReplacementsRowTemplateWithArrayAssoc() {
        $rowArr = array();
        $rowArr[] = array('repl1' => 'a',
            'repl2' => 'b',
            'repl3' => 'c',
            'repl4' => 'd'
        );
        $rowArr[] = array('repl1' => '1',
            'repl2' => '2',
            'repl3' => '3',
            'repl4' => '4'
        );
        $rowArr[] = array('repl1' => '5',
            'repl2' => '6',
            'repl3' => '7',
            'repl4' => '8'
        );

        $filledTpl = $this->t->fillRowTpl('more_replacements', $rowArr);
        $expectedResult = "AaBbCcDd\r\nA1B2C3D4\r\nA5B6C7D8\r\n";
        $this->assertSame($expectedResult, $filledTpl);
    }

    public function testMoreReplacementsRowTemplateWithTwoArrays() {
        $search = array('repl1', 'repl2', 'repl3', 'repl4');
        $rowReplArr = array();
        $rowReplArr[] = array('a',
            'b',
            'c',
            'd'
        );
        $rowReplArr[] = array('1',
            '2',
            '3',
            '4'
        );
        $rowReplArr[] = array('5',
            '6',
            '7',
            '8'
        );
        $filledTpl = $this->t->fillRowTpl('more_replacements', $search, $rowReplArr);
        $expectedResult = "AaBbCcDd\r\nA1B2C3D4\r\nA5B6C7D8\r\n";
        $this->assertSame($expectedResult, $filledTpl);
    }
    
    ///Todo:
    //Methods for manipulation of the template array 
    

}
