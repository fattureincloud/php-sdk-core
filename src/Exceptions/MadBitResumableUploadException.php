<?php

namespace MadBit\SDK\Exceptions;

class MadBitResumableUploadException extends MadBitSDKException
{
    protected $startOffset;

    protected $endOffset;

    /**
     * @return null|int
     */
    public function getStartOffset()
    {
        return $this->startOffset;
    }

    /**
     * @param null|int $startOffset
     */
    public function setStartOffset(int $startOffset)
    {
        $this->startOffset = $startOffset;
    }

    /**
     * @return null|int
     */
    public function getEndOffset()
    {
        return $this->endOffset;
    }

    /**
     * @param null|int $endOffset
     */
    public function setEndOffset(int $endOffset)
    {
        $this->endOffset = $endOffset;
    }
}
