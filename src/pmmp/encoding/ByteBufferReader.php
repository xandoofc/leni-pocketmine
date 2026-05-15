<?php

declare(strict_types=1);

namespace pmmp\encoding;

use pocketmine\network\mcpe\NetworkBinaryStream;

class ByteBufferReader
{
	private NetworkBinaryStream $stream;

	public function __construct(NetworkBinaryStream $stream = null)
	{
		$this->stream = $stream ?? new NetworkBinaryStream();
	}

	public static function fromString(string $buffer): self
	{
		$stream = new NetworkBinaryStream($buffer);
		return new self($stream);
	}

	public function getStream(): NetworkBinaryStream
	{
		return $this->stream;
	}

	public function setStream(NetworkBinaryStream $stream): void
	{
		$this->stream = $stream;
	}

	public function getRemainingBytes(): int
	{
		return strlen($this->stream->getBuffer()) - $this->stream->getOffset();
	}

	public function feof(): bool
	{
		return $this->stream->feof();
	}

	public function getOffset(): int
	{
		return $this->stream->getOffset();
	}

	public function setOffset(int $offset): void
	{
		$this->stream->setOffset($offset);
	}
}
