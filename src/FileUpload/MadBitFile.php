<?php

namespace MadBit\SDK\FileUpload;

use MadBit\SDK\Exceptions\MadBitSDKException;

class MadBitFile
{
    /**
     * @var string the path to the file on the system
     */
    protected $path;

    /**
     * @var resource the stream pointing to the file
     */
    protected $stream;

    /**
     * @var int The maximum bytes to read. Defaults to -1 (read all the remaining buffer).
     */
    private $maxLength;

    /**
     * @var int Seek to the specified offset before reading. If this number is negative, no seeking will occur and reading will start from the current position.
     */
    private $offset;

    /**
     * Creates a new MadBitFile entity.
     *
     * @param string $filePath
     * @param int    $maxLength
     * @param int    $offset
     *
     * @throws MadBitSDKException
     */
    public function __construct(string $filePath, int $maxLength = -1, int $offset = -1)
    {
        $this->path = $filePath;
        $this->maxLength = $maxLength;
        $this->offset = $offset;
        $this->open();
    }

    /**
     * Closes the stream when destructed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Opens a stream for the file.
     *
     * @throws MadBitSDKException
     */
    public function open()
    {
        if (!$this->isRemoteFile($this->path) && !is_readable($this->path)) {
            throw new MadBitSDKException('Failed to create MadBitFile entity. Unable to read resource: '.$this->path.'.');
        }

        $this->stream = fopen($this->path, 'r');

        if (!$this->stream) {
            throw new MadBitSDKException('Failed to create MadBitFile entity. Unable to open resource: '.$this->path.'.');
        }
    }

    /**
     * Stops the file stream.
     */
    public function close()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    /**
     * Return the contents of the file.
     *
     * @return string
     */
    public function getContents(): string
    {
        return stream_get_contents($this->stream, $this->maxLength, $this->offset);
    }

    /**
     * Return the name of the file.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return basename($this->path);
    }

    /**
     * Return the path of the file.
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->path;
    }

    /**
     * Return the size of the file.
     *
     * @return int
     */
    public function getSize(): int
    {
        return filesize($this->path);
    }

    /**
     * Return the mimetype of the file.
     *
     * @return string
     */
    public function getMimetype(): string
    {
        return Mimetypes::getInstance()->fromFilename($this->path) ?: 'text/plain';
    }

    /**
     * Returns true if the path to the file is remote.
     *
     * @param string $pathToFile
     *
     * @return bool
     */
    protected function isRemoteFile(string $pathToFile): bool
    {
        return 1 === preg_match('/^(https?):\/\/.*/', $pathToFile);
    }
}
