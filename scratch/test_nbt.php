<?php

require_once "vendor/autoload.php";

use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;

$nbt = new CompoundTag("root");
$nbt->setInt("test", 123);

$stream = new NetworkLittleEndianNBTStream();
$buffer = $stream->write($nbt);

echo "Length: " . strlen($buffer) . "\n";
echo "Hex: " . bin2hex($buffer) . "\n";
