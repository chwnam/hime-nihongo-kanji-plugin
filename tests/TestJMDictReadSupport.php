<?php

namespace HimeNihongo\KanjiPlugin\Tests;

use Exception;
use HimeNihongo\KanjiPlugin\Supports\DB\JMDictReadSupport;
use SimpleXMLElement;
use WP_UnitTestCase;

class TestJMDictReadSupport extends WP_UnitTestCase
{
    private JMDictReadSupport $support;

    public function setUp(): void
    {
        $this->support = new JMDictReadSupport();
    }

    public function test_parseOneNode_k_ele(): void
    {
        $xml = <<< PHP_EOL
<entry>
    <k_ele>
      <keb>何の単語</keb>
      <ke_inf>&amp;inf1;</ke_inf>
      <ke_inf>&amp;inf2;</ke_inf>
      <ke_inf>&amp;inf3;</ke_inf>
      <ke_pri>&amp;pri1;</ke_pri>
      <ke_pri>&amp;pri2;</ke_pri>
      <ke_pri>&amp;pri3;</ke_pri>
    </k_ele>
</entry>
PHP_EOL;

        $output = $this->support->parseOneNode_k_ele(new SimpleXMLElement($xml));

        $this->assertIsArray($output);
        $this->assertCount(1, $output);

        $this->assertArrayHasKey('keb', $output[0]);
        $this->assertEquals("何の単語", $output[0]['keb']);

        $this->assertArrayHasKey('keb_len', $output[0]);
        $this->assertEquals(4, $output[0]['keb_len']);

        $this->assertArrayHasKey('ke_inf', $output[0]);
        $this->assertEquals("inf1,inf2,inf3", $output[0]['ke_inf']);

        $this->assertArrayHasKey('ke_pri', $output[0]);
        $this->assertEquals("pri1,pri2,pri3", $output[0]['ke_pri']);
    }

    public function test_parseOneNode_r_ele(): void
    {
        $xml = <<< PHP_EOL
<entry>
    <r_ele>
      <reb>なんのよみかたそのいち</reb>
      <re_inf>&amp;inf;</re_inf>
      <re_pri>&amp;pri;</re_pri>
    </r_ele>
    <r_ele>
      <reb>なんのよみかたそのに</reb>
      <re_restr>何の単語</re_restr>
    </r_ele>
    <r_ele>
      <reb>ナンノタンゴ</reb>
      <re_nokanji/>
    </r_ele>
</entry>
PHP_EOL;

        $output = $this->support->parseOneNode_r_ele(new SimpleXMLElement($xml));

        $this->assertIsArray($output);
        $this->assertCount(3, $output);

        $this->assertArrayHasKey('reb', $output[0]);
        $this->assertEquals('なんのよみかたそのいち', $output[0]['reb']);
        $this->assertFalse($output[0]['re_nokanji']);
        $this->assertEquals('inf', $output[0]['re_inf']);
        $this->assertEquals('pri', $output[0]['re_pri']);
        $this->assertEquals([], $output[0]['re_restr']);

        $this->assertArrayHasKey('reb', $output[1]);
        $this->assertEquals('なんのよみかたそのに', $output[1]['reb']);
        $this->assertFalse($output[1]['re_nokanji']);
        $this->assertEquals('', $output[1]['re_inf']);
        $this->assertEquals('', $output[1]['re_pri']);
        $this->assertEquals(['何の単語'], $output[1]['re_restr']);

        $this->assertArrayHasKey('reb', $output[2]);
        $this->assertEquals('ナンノタンゴ', $output[2]['reb']);
        $this->assertTrue($output[2]['re_nokanji']); // true
        $this->assertEquals('', $output[2]['re_inf']);
        $this->assertEquals('', $output[2]['re_pri']);
        $this->assertEquals([], $output[2]['re_restr']);
    }

    public function test_parseOneNode_sense(): void
    {
        $xml = <<< PHP_EOL
<entry>
    <sense>
      <pos>&amp;unc;</pos>
      <xref>同の字点</xref>
      <gloss g_type="expl">kanji repetition mark</gloss>
    </sense>
    <sense>
      <stagk>遇う</stagk>
      <pos>&amp;v5u;</pos>
      <pos>&amp;vt;</pos>
      <misc>&amp;uk;</misc>
      <gloss>to treat</gloss>
      <gloss>to handle</gloss>
      <gloss>to deal with</gloss>
    </sense>
    <sense>
      <stagr>あそこ</stagr>
      <stagr>あすこ</stagr>
      <stagr>アソコ</stagr>
      <pos>&amp;n;</pos>
      <misc>&amp;col;</misc>
      <misc>&amp;uk;</misc>
      <misc>&amp;euph;</misc>
      <gloss>genitals</gloss>
      <gloss>private parts</gloss>
      <gloss>nether regions</gloss>
    </sense>
</entry>
PHP_EOL;

        $output = $this->support->parseOneNode_sense(new SimpleXMLElement($xml));

        $this->assertIsArray($output);
        $this->assertCount(3, $output);

        $this->assertEquals([], $output[0]['stagk']);
        $this->assertEquals([], $output[0]['stagr']);
        $this->assertEquals('unc', $output[0]['pos']);
        $this->assertEquals('', $output[0]['field']);
        $this->assertEquals('', $output[0]['misc']);
        $this->assertEquals('kanji repetition mark', $output[0]['gloss']);

        $this->assertEquals(['遇う'], $output[1]['stagk']);
        $this->assertEquals([], $output[1]['stagr']);
        $this->assertEquals('v5u,vt', $output[1]['pos']);
        $this->assertEquals('', $output[1]['field']);
        $this->assertEquals('uk', $output[1]['misc']);
        $this->assertEquals("to treat\nto handle\nto deal with", $output[1]['gloss']);

        $this->assertEquals([], $output[2]['stagk']);
        $this->assertEquals(['あそこ', 'あすこ', 'アソコ'], $output[2]['stagr']);
        $this->assertEquals('n', $output[2]['pos']);
        $this->assertEquals('', $output[2]['field']);
        $this->assertEquals('col,uk,euph', $output[2]['misc']);
        $this->assertEquals("genitals\nprivate parts\nnether regions", $output[2]['gloss']);
    }
}
