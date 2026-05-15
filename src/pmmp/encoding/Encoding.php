<?php

declare(strict_types=1);

namespace pmmp\encoding;

use pocketmine\network\mcpe\NetworkBinaryStream;

class VarInt
{
	public static function readSignedInt(ByteBufferReader $in): int
	{
		return $in->getStream()->getVarInt();
	}

	public static function writeSignedInt(ByteBufferWriter $out, int $value): void
	{
		$out->getStream()->putVarInt($value);
	}

	public static function readUnsignedInt(ByteBufferReader $in): int
	{
		return $in->getStream()->getUnsignedVarInt();
	}

	public static function writeUnsignedInt(ByteBufferWriter $out, int $value): void
	{
		$out->getStream()->putUnsignedVarInt($value);
	}
}

class LE
{
	public static function readFloat(ByteBufferReader $in): float
	{
		return $in->getStream()->getLFloat();
	}

	public static function writeFloat(ByteBufferWriter $out, float $value): void
	{
		$out->getStream()->putLFloat($value);
	}

	public static function readSignedShort(ByteBufferReader $in): int
	{
		return $in->getStream()->getSignedLShort();
	}

	public static function writeSignedShort(ByteBufferWriter $out, int $value): void
	{
		$out->getStream()->putLShort($value);
	}

	public static function readUnsignedShort(ByteBufferReader $in): int
	{
		return $in->getStream()->getLShort();
	}

	public static function writeUnsignedShort(ByteBufferWriter $out, int $value): void
	{
		$out->getStream()->putLShort($value);
	}

	public static function readSignedInt(ByteBufferReader $in): int
	{
		return $in->getStream()->getLInt();
	}

	public static function writeSignedInt(ByteBufferWriter $out, int $value): void
	{
		$out->getStream()->putLInt($value);
	}

	public static function readUnsignedInt(ByteBufferReader $in): int
	{
		return $in->getStream()->getUnsignedVarInt();
	}

	public static function writeUnsignedInt(ByteBufferWriter $out, int $value): void
	{
		$out->getStream()->putUnsignedVarInt($value);
	}

	public static function readUnsignedLong(ByteBufferReader $in): int
	{
		return $in->getStream()->getLLong();
	}

	public static function writeUnsignedLong(ByteBufferWriter $out, int $value): void
	{
		$out->getStream()->putLLong($value);
	}
}

class Byte
{
	public static function readUnsigned(ByteBufferReader $in): int
	{
		return $in->getStream()->getByte();
	}

	public static function writeUnsigned(ByteBufferWriter $out, int $value): void
	{
		$out->getStream()->putByte($value);
	}
}
