<?php

namespace Rubix\ML\Tests\Transformers;

use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\Stateful;
use Rubix\ML\Other\Strategies\Mean;
use Rubix\ML\Transformers\Transformer;
use Rubix\ML\Other\Strategies\KMostFrequent;
use Rubix\ML\Transformers\MissingDataImputer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MissingDataImputerTest extends TestCase
{
    /**
     * @var \Rubix\ML\Datasets\Unlabeled
     */
    protected $dataset;

    /**
     * @var \Rubix\ML\Transformers\MissingDataImputer
     */
    protected $transformer;

    public function setUp() : void
    {
        $this->dataset = new Unlabeled([
            [30, 'friendly'],
            [NAN, 'mean'],
            [50, 'friendly'],
            [60, '?'],
            [10, 'mean'],
        ]);

        $this->transformer = new MissingDataImputer(new Mean(), new KMostFrequent(), '?');
    }

    public function test_build_transformer() : void
    {
        $this->assertInstanceOf(MissingDataImputer::class, $this->transformer);
        $this->assertInstanceOf(Transformer::class, $this->transformer);
        $this->assertInstanceOf(Stateful::class, $this->transformer);
    }

    public function test_fit_transform() : void
    {
        $this->transformer->fit($this->dataset);

        $this->assertTrue($this->transformer->fitted());

        $this->dataset->apply($this->transformer);

        $this->assertThat($this->dataset[1][0], $this->logicalAnd($this->greaterThan(20), $this->lessThan(55)));
        $this->assertContains($this->dataset[3][1], ['friendly', 'mean']);
    }

    public function test_transform_unfitted() : void
    {
        $this->expectException(RuntimeException::class);

        $samples = $this->dataset->samples();

        $this->transformer->transform($samples);
    }
}
