<?php

namespace App\Models;


use SplFileObject;

class FileSplitter
{
    /**
     * Split the final part files in these amount of lines.
     *
     * @var int
     */
    private $linesToSplitBy = 1000;

    /**
     * Amount of bytes to read in only one read operation.
     *
     * @var int
     */
    private $readBufferSize = 1048576; // 1MB

    /**
     * Amount of lines used to limit the amount of reads and writes.
     *
     * @var int
     */
    private $estimatedInMemoryLines = 100;

    /**
     * File name pattern to use when creating part files.
     *
     * @var string
     */
    private $partPattern;

    /**
     * Amount of lines that can be added to the current part.
     *
     * @var string
     */
    private $pendingLinesInPart;

    /**
     * Current part number index.
     *
     * @var string
     */
    private $amountOfFilesCreated = 0;

    /**
     * Lines read from input file that are ready to be written to output file.
     *
     * @var array
     */
    private $lines = [];

    /**
     * Input file.
     *
     * @var SplFileObject
     */
    private $inputFile;

    /**
     * Current part file.
     *
     * @var SplFileObject
     */
    private $currentPartFile;

    /**
     * FileSplitter constructor.
     *
     * @param string $inputFilePath Path to the input file
     * @param string $partPattern Pattern used to create part files
     */
    public function __construct($inputFilePath, $partPattern)
    {
        $this->inputFile = new SplFileObject($inputFilePath, 'r');
        $this->inputFile->setFlags(SplFileObject::DROP_NEW_LINE);
        $this->partPattern = $partPattern;
    }

    /**
     * Split file into parts and return the amount of created files.
     *
     * @return int
     */
    public function split()
    {
        while ($this->bufferedRead()) {
            $this->bufferedWrite();
        }
    }

    /**
     * Read from buffer until reaching the end of file or the readBufferSize limit (including the line in which that
     * limit is reached).
     *
     * Also normalize all line endings to \n and merge all extracted lines into the lines buffer.
     *
     * @return void
     */
    private function readToBuffer()
    {
        $buffer = $this->inputFile->fread($this->readBufferSize);
        if (!$this->inputFile->eof()) {
            $buffer .= $this->inputFile->fgets();
        }
        $buffer = preg_replace('~\r\n?~', "\n", $buffer);
        $bufferLines = preg_split('/$\R?^/m', $buffer);

        // Remove last character if it is a line feed
        $lastIndex = count($bufferLines) - 1;
        if ($bufferLines[$lastIndex][mb_strlen($bufferLines[$lastIndex]) - 1] == "\n") {
            $bufferLines[$lastIndex] = substr_replace($bufferLines[$lastIndex] ,"",-1);
        }

        $this->lines = array_merge($this->lines, $bufferLines);
    }

    /**
     * Read from input file into buffer until reaching or exceeding the limit imposed by readStopsAfterExceedingLines
     *
     * @return bool
     */
    private function bufferedRead()
    {
        while (!$this->inputFile->eof() && count($this->lines) < $this->estimatedInMemoryLines) {
            $this->readToBuffer();
        }

        return !($this->inputFile->eof() && empty($this->lines));
    }

    /**
     * Write to output file until reaching the pendingLinesInPart limit or the whole lines buffer.
     *
     * @return bool
     */
    private function bufferedWrite()
    {
        if ($this->pendingLinesInPart == 0) {
            $this->setupNewPart();
        }
        $firstLines = array_splice($this->lines, 0, $this->pendingLinesInPart);
        $firstLinesString = implode("\n", $firstLines) . "\n";
        $this->currentPartFile->fwrite($firstLinesString);

        $linesToWrite = count($firstLines);
        $this->pendingLinesInPart -= $linesToWrite;

        return $linesToWrite;
    }

    /**
     * Prepare a new part file.
     *
     * @return void
     */
    private function setupNewPart()
    {
        $this->amountOfFilesCreated++;
        $this->pendingLinesInPart = $this->linesToSplitBy;
        $partFilePath = sprintf($this->partPattern, $this->amountOfFilesCreated);
        $this->currentPartFile = new SplFileObject($partFilePath, 'w');
    }

    /**
     * @return int
     */
    public function getLinesToSplitBy()
    {
        return $this->linesToSplitBy;
    }

    /**
     * @param int $linesToSplitBy
     */
    public function setLinesToSplitBy($linesToSplitBy)
    {
        $this->linesToSplitBy = $linesToSplitBy;
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
     * @return int
     */
    public function getEstimatedInMemoryLines()
    {
        return $this->estimatedInMemoryLines;
    }

    /**
     * @param int $estimatedInMemoryLines
     */
    public function setEstimatedInMemoryLines($estimatedInMemoryLines)
    {
        $this->estimatedInMemoryLines = $estimatedInMemoryLines;
    }

    /**
     * @return string
     */
    public function getPartPattern()
    {
        return $this->partPattern;
    }

    /**
     * @param string $partPattern
     */
    public function setPartPattern($partPattern)
    {
        $this->partPattern = $partPattern;
    }

    /**
     * @return string
     */
    public function getAmountOfFilesCreated()
    {
        return $this->amountOfFilesCreated;
    }

}
