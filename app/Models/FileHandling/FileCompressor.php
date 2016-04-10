<?php

namespace App\Models\FileHandling;

use Exception;

class FileCompressor
{
    /**
     * Amount of bytes to read in only one read operation.
     *
     * @var int
     */
    private $readBufferSize = 1048576; // 1MB

    /**
     * Input file.
     *
     * @var resource
     */
    private $inputFilePath;

    /**
     * Output file.
     *
     * @var resource
     */
    private $outputFilePath;

    /**
     * FileCompressor constructor.
     *
     * @param string $inputFilePath Path to the input file
     * @param string $outputFilePath Path to the output file
     */
    public function __construct($inputFilePath = null, $outputFilePath = null)
    {
        $this->inputFilePath = $inputFilePath;
        $this->outputFilePath = $outputFilePath;
    }

    /**
     * Compress the input file into the output file.
     *
     * @param int $level The compression level from 0 (none) to 9 (high)
     *
     * @return void
     *
     * @throws Exception
     */
    public function compress($level = 9)
    {
        $mode = 'wb' . $level;

        $inputFile = fopen($this->inputFilePath, 'rb');
        if (!$inputFile) {
            throw new Exception('Error opening input file.');
        }

        $outputFile = gzopen($this->outputFilePath, $mode);
        if (!$outputFile) {
            fclose($inputFile);
            throw new Exception('Error opening output file.');
        }

        while (!feof($inputFile)) {
            $chunk = fread($inputFile, $this->readBufferSize);
            gzwrite($outputFile, $chunk);
        }

        gzclose($outputFile);
        fclose($inputFile);
    }

    /**
     * Decompress the input file into the output file.
     *
     * @return void
     *
     * @throws Exception
     */
    public function decompress()
    {
        $inputFile = gzopen($this->inputFilePath, 'rb');
        if (!$inputFile) {
            throw new Exception('Error opening input file.');
        }

        $outputFile = fopen($this->outputFilePath, 'wb');
        if (!$outputFile) {
            gzclose($inputFile);
            throw new Exception('Error opening output file.');
        }

        while (!gzeof($inputFile)) {
            $chunk = gzread($inputFile, $this->readBufferSize);
            fwrite($outputFile, $chunk);
        }

        fclose($outputFile);
        gzclose($inputFile);
    }

    /**
     * @return int
     */
    public function getReadBufferSize()
    {
        return $this->readBufferSize;
    }

    /**
     * @param int $readBufferSize
     */
    public function setReadBufferSize($readBufferSize)
    {
        $this->readBufferSize = $readBufferSize;
    }

    /**
     * @return string
     */
    public function getInputFilePath()
    {
        return $this->inputFilePath;
    }

    /**
     * @param string $inputFilePath
     */
    public function setInputFilePath($inputFilePath)
    {
        $this->inputFilePath = $inputFilePath;
    }

    /**
     * @return string
     */
    public function getOutputFilePath()
    {
        return $this->outputFilePath;
    }

    /**
     * @param string $outputFilePath
     */
    public function setOutputFilePath($outputFilePath)
    {
        $this->outputFilePath = $outputFilePath;
    }

}
