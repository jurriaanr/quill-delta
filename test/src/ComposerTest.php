<?php
/**
 * User: Jurriaan Ruitenberg
 * Date: 25-7-2018
 * Time: 09:37
 */

class ComposerTest extends PHPUnit_Framework_TestCase
{
    use \Madewithlove\PhpunitSnapshots\SnapshotAssertions;

    public function testComposer()
    {
        $ops = json_decode(file_get_contents('test/test.json'), JSON_OBJECT_AS_ARRAY);

        $composer = new \Oberon\Quill\Delta\Composer();
        $composed = $composer->compose($ops);

        $this->assertEqualsSnapshot($composed->getOps());
    }

    public function testComposerMb()
    {
        $ops = json_decode(file_get_contents('test/mb-test.json'), JSON_OBJECT_AS_ARRAY);

        $composer = new \Oberon\Quill\Delta\Composer();
        $composed = $composer->compose($ops);

        $this->assertEqualsSnapshot($composed->getOps());
    }

}
