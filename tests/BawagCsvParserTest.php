<?php

class BawagCsvParserTest extends PHPUnit_Framework_TestCase {

    private $csv;

    protected function setUp() {
        $this->csv = '1234567890;Abbuchung Einzugsermächtigung OG/000001246|VERBUND 31000 0010081;17.07.2007;17.07.2007;-58,00;EUR' . "\n";
        $this->csv .= '1234567890;BAUHAUS 1100 0794P K1 14.07. UM 12.07 VD/000001246;16.07.2007;14.07.2007;-16,67;EUR' . "\n";
        $this->csv .= '1234567890;A.T.U. 1230 0620P K1 07.07. UM 12.06 VD/000001246;09.07.2007;07.07.2007;-5,99;EUR' . "\n";
        $this->csv .= '1234567890;GEHAELTER 4/07 VD/000001246 11000 DIE Firma;24.04.2007;25.04.2007;+350,00;EUR' . "\n";
        $this->csv .= 'AT611904300234573201;/SOMETHING/SOMETHING//SOMETHING REIMBURSE//SOMETHING      VB/000009999 BRDDDEEE AT611904300234573201 MAX MUSTER;01.01.2016;01.01.2016;+1900,00;EUR' . "\n";
    }

    /**
     * @expectedException Exception
     */
    public function testArrayAsArgument() {
        $result = \BawagCsvParser\BawagCsvParser::parse([]);
    }

    /**
     * @expectedException Exception
     */
    public function testObjectAsArgument() {
        $result = \BawagCsvParser\BawagCsvParser::parse(new \stdClass());
    }

    /**
     * @expectedException Exception
     */
    public function testNoContent() {
        $result = \BawagCsvParser\BawagCsvParser::parse('');
    }

    /**
     * @expectedException Exception
     */
    public function testNoCsv() {
        $result = \BawagCsvParser\BawagCsvParser::parse('This is not a csv.');
    }

    public function testArrayLength() {
        $result = \BawagCsvParser\BawagCsvParser::parse($this->csv);

        $this->assertEquals(5, count($result));
    }

    public function testObjectStructureCorrect() {
        $result = \BawagCsvParser\BawagCsvParser::parse($this->csv);

        $this->assertObjectHasAttribute('postingLineId', $result[0]);
        $this->assertObjectHasAttribute('account', $result[0]);
        $this->assertObjectHasAttribute('text', $result[0]);
        $this->assertObjectHasAttribute('postingDate', $result[0]);
        $this->assertObjectHasAttribute('valueDate', $result[0]);
        $this->assertObjectHasAttribute('amount', $result[0]);
        $this->assertObjectHasAttribute('currency', $result[0]);
        $this->assertObjectHasAttribute('comment', $result[0]);
        $this->assertObjectHasAttribute('contraAccount', $result[0]);
        $this->assertObjectHasAttribute('contraBic', $result[0]);
        $this->assertObjectHasAttribute('contraName', $result[0]);
    }

    /*public function test() {
        $result = \BawagCsvParser\BawagCsvParser::parse($this->csv);

        $this->assertEquals(array(

        ), $result);
    }*/

}
