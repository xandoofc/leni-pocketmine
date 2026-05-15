<?php

declare(strict_types=1);

namespace pmmp\encoding;

use pocketmine\network\mcpe\NetworkBinaryStream;

class ByteBufferWriter
{
	private NetworkBinaryStream $stream;

	public function __construct(NetworkBinaryStream $stream = null)
	{
		$this->stream = $stream ?? new NetworkBinaryStream();
	}

	public function getStream(): NetworkBinaryStream
	{
		return $this->stream;
	}

	public function writeByteArray(string $data): void
	{
		$this->stream->put($data);
	}

	public function getBuffer(): string
	{
		return $this->stream->getBuffer();
	}
}
