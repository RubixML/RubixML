<?php

namespace Rubix\Engine\Transformers;

use Rubix\Engine\Datasets\Dataset;

class OneHotEncoder implements Transformer
{
    /**
     * The set of unique possible categories of the training set.
     *
     * @var array
     */
    protected $categories = [
        //
    ];

    /**
     * The user specified columns to encode. If this is null, the transformer
     * will encode all categorical feature columns.
     *
     * @var array
     */
    protected $columns = [
        //
    ];

    /**
     * @param  array|null  $columns
     * @return void
     */
    public function __construct(?array $columns = null)
    {
        $this->columns = $columns;
    }

    /**
     * Build the list of categories.
     *
     * @param  \Rubix\Engine\Datasets\Dataset  $dataset
     * @return void
     */
    public function fit(Dataset $dataset) : void
    {
        if (!isset($this->columns)) {
            $this->columns = array_keys(array_filter($dataset->columnTypes(),
                function ($type) {
                    return $type === self::CATEGORICAL;
                }));
        }

        $position = 0;

        foreach ($dataset->samples() as $sample) {
            foreach ($this->columns as $column) {
                if (!isset($this->categories[$column][$sample[$column]])) {
                    $this->categories[$column][$sample[$column]]
                        = $position++;
                }
            }
        }
    }

    /**
     * Convert a sample into a vector where categorical values are either 1 or 0
     * depending if a category is present in the sample or not. Continuous data,
     * if present, is unmodified but moved to the front of the vector.
     *
     * @param  array  $samples
     * @return void
     */
    public function transform(array &$samples) : void
    {
        foreach ($samples as &$sample) {
            $vector = [];

            foreach ($this->columns as $column) {
                $temp = array_fill_keys($this->categories[$column], 0);

                if (isset($this->categories[$column][$sample[$column]])) {
                    $temp[$this->categories[$column][$sample[$column]]] = 1;
                }

                $vector = array_merge($vector, $temp);

                unset($sample[$column]);
            }

            $sample = array_merge($sample, $vector);
        }
    }
}