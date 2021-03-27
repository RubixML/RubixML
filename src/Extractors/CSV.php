<?php

namespace Rubix\ML\Extractors;

use Rubix\ML\Exceptions\InvalidArgumentException;
use Rubix\ML\Exceptions\RuntimeException;
use Generator;

use function strlen;
use function fopen;
use function fgetcsv;
use function fputcsv;
use function fclose;
use function array_combine;

/**
 * CSV
 *
 * A plain-text format that use newlines to delineate rows and a user-specified delimiter
 * (usually a comma) to separate the values of each column in a data table. Comma-Separated
 * Values (CSV) format is a common format but suffers from not being able to retain type
 * information - thus, all data is imported as categorical data (strings) by default.
 *
 * > **Note:** This implementation of CSV is based on the definition in RFC 4180.
 *
 * References:
 * [1] Y. Shafranovich. (2005). Common Format and MIME Type for Comma-Separated Values (CSV) Files.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class CSV implements Extractor, Writer
{
    /**
     * The path to the file on disk.
     *
     * @var string
     */
    protected $path;

    /**
     * Does the CSV document have a header as the first row?
     *
     * @var bool
     */
    protected $header;

    /**
     * The character that delineates the values of the columns of the data table.
     *
     * @var string
     */
    protected $delimiter;

    /**
     * The character used to enclose a cell that contains a delimiter in the body.
     *
     * @var string
     */
    protected $enclosure;

    /**
     * @param string $path
     * @param bool $header
     * @param string $delimiter
     * @param string $enclosure
     * @throws \Rubix\ML\Exceptions\InvalidArgumentException
     */
    public function __construct(
        string $path,
        bool $header = false,
        string $delimiter = ',',
        string $enclosure = '"'
    ) {
        if (empty($path)) {
            throw new InvalidArgumentException('Path cannot be empty.');
        }

        if (strlen($delimiter) !== 1) {
            throw new InvalidArgumentException('Delimiter must be'
                . ' a single character, ' . strlen($delimiter) . ' given.');
        }

        if (strlen($enclosure) !== 1) {
            throw new InvalidArgumentException('Enclosure must be'
                . ' a single character, ' . strlen($enclosure) . ' given.');
        }

        $this->path = $path;
        $this->header = $header;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
    }

    /**
     * Write an iterable data table to disk.
     *
     * @param iterable<mixed[]> $iterator
     * @param string[]|null $header
     * @throws \Rubix\ML\Exceptions\RuntimeException
     */
    public function write(iterable $iterator, ?array $header = null) : void
    {
        if (!is_writable(dirname($this->path))) {
            throw new RuntimeException("Path {$this->path} is not writable.");
        }

        $handle = fopen($this->path, 'w');

        if (!$handle) {
            throw new RuntimeException('Could not open file pointer.');
        }

        $line = 0;

        if ($header) {
            $length = fputcsv($handle, $header, $this->delimiter, $this->enclosure);

            ++$line;

            if (!$length) {
                throw new RuntimeException("Could not write header on line $line.");
            }
        }

        foreach ($iterator as $row) {
            $length = fputcsv($handle, $row, $this->delimiter, $this->enclosure);

            ++$line;

            if (!$length) {
                throw new RuntimeException("Could not write row on line $line.");
            }
        }

        fclose($handle);
    }

    /**
     * Return an iterator for the records in the data table.
     *
     * @throws \Rubix\ML\Exceptions\RuntimeException
     * @return \Generator<mixed[]>
     */
    public function getIterator() : Generator
    {
        if (!is_file($this->path)) {
            throw new RuntimeException("Path {$this->path} is not a file.");
        }

        if (!is_readable($this->path)) {
            throw new RuntimeException("Path {$this->path} is not readable.");
        }

        $handle = fopen($this->path, 'r');

        if (!$handle) {
            throw new RuntimeException('Could not open file pointer.');
        }

        $line = 0;

        if ($this->header) {
            $header = fgetcsv($handle, 0, $this->delimiter, $this->enclosure);

            ++$line;

            if (!$header) {
                throw new RuntimeException("Header not found on line $line.");
            }
        }

        while (!feof($handle)) {
            $record = fgetcsv($handle, 0, $this->delimiter, $this->enclosure);

            ++$line;

            if (empty($record)) {
                continue;
            }

            if (isset($header)) {
                $record = array_combine($header, $record);
            }

            if (!$record) {
                throw new RuntimeException("Malformed record on line $line.");
            }

            yield $record;
        }

        fclose($handle);
    }
}
