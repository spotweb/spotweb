<?php

namespace PhpCoveralls\Tests\Bundle\CoverallsBundle\Entity\Git;

use PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Commit;
use PhpCoveralls\Tests\ProjectTestCase;

/**
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Entity\Coveralls
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Commit
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 *
 * @internal
 */
final class CommitTest extends ProjectTestCase
{
    /**
     * @var Commit
     */
    private $object;

    // getId()

    /**
     * @test
     */
    public function shouldNotHaveIdOnConstruction()
    {
        self::assertNull($this->object->getId());
    }

    // getAuthorName()

    /**
     * @test
     */
    public function shouldNotHoveAuthorNameOnConstruction()
    {
        self::assertNull($this->object->getAuthorName());
    }

    // getAuthorEmail()

    /**
     * @test
     */
    public function shouldNotHoveAuthorEmailOnConstruction()
    {
        self::assertNull($this->object->getAuthorEmail());
    }

    // getCommitterName()

    /**
     * @test
     */
    public function shouldNotHoveCommitterNameOnConstruction()
    {
        self::assertNull($this->object->getCommitterName());
    }

    // getCommitterEmail()

    /**
     * @test
     */
    public function shouldNotHoveCommitterEmailOnConstruction()
    {
        self::assertNull($this->object->getCommitterEmail());
    }

    // getMessage()

    /**
     * @test
     */
    public function shouldNotHoveMessageOnConstruction()
    {
        self::assertNull($this->object->getMessage());
    }

    // setId()

    /**
     * @test
     */
    public function shouldSetId()
    {
        $expected = 'id';

        $obj = $this->object->setId($expected);

        self::assertSame($expected, $this->object->getId());
        self::assertSame($obj, $this->object);
    }

    // setAuthorName()

    /**
     * @test
     */
    public function shouldSetAuthorName()
    {
        $expected = 'author_name';

        $obj = $this->object->setAuthorName($expected);

        self::assertSame($expected, $this->object->getAuthorName());
        self::assertSame($obj, $this->object);
    }

    // setAuthorEmail()

    /**
     * @test
     */
    public function shouldSetAuthorEmail()
    {
        $expected = 'author_email';

        $obj = $this->object->setAuthorEmail($expected);

        self::assertSame($expected, $this->object->getAuthorEmail());
        self::assertSame($obj, $this->object);
    }

    // setCommitterName()

    /**
     * @test
     */
    public function shouldSetCommitterName()
    {
        $expected = 'committer_name';

        $obj = $this->object->setCommitterName($expected);

        self::assertSame($expected, $this->object->getCommitterName());
        self::assertSame($obj, $this->object);
    }

    // setCommitterEmail()

    /**
     * @test
     */
    public function shouldSetCommitterEmail()
    {
        $expected = 'committer_email';

        $obj = $this->object->setCommitterEmail($expected);

        self::assertSame($expected, $this->object->getCommitterEmail());
        self::assertSame($obj, $this->object);
    }

    // setMessage()

    /**
     * @test
     */
    public function shouldSetMessage()
    {
        $expected = 'message';

        $obj = $this->object->setMessage($expected);

        self::assertSame($expected, $this->object->getMessage());
        self::assertSame($obj, $this->object);
    }

    // toArray()

    /**
     * @test
     */
    public function shouldConvertToArray()
    {
        $expected = [
            'id' => null,
            'author_name' => null,
            'author_email' => null,
            'committer_name' => null,
            'committer_email' => null,
            'message' => null,
        ];

        self::assertSame($expected, $this->object->toArray());
        self::assertSame(json_encode($expected), (string) $this->object);
    }

    /**
     * @test
     */
    public function shouldConvertToFilledArray()
    {
        $id = 'id';
        $authorName = 'author_name';
        $authorEmail = 'author_email';
        $committerName = 'committer_name';
        $committerEmail = 'committer_email';
        $message = 'message';

        $this->object
            ->setId($id)
            ->setAuthorName($authorName)
            ->setAuthorEmail($authorEmail)
            ->setCommitterName($committerName)
            ->setCommitterEmail($committerEmail)
            ->setMessage($message)
        ;

        $expected = [
            'id' => $id,
            'author_name' => $authorName,
            'author_email' => $authorEmail,
            'committer_name' => $committerName,
            'committer_email' => $committerEmail,
            'message' => $message,
        ];

        self::assertSame($expected, $this->object->toArray());
        self::assertSame(json_encode($expected), (string) $this->object);
    }

    protected function legacySetUp()
    {
        $this->object = new Commit();
    }
}
