<?php

/*
 *
 *   _____       _                          _
 *  / ____|     | |                        (_)
 * | (___  _   _| |__  _ __ ___   __ _ _ __ _ _ __   ___
 *  \___ \| | | | '_ \| '_ ` _ \ / _` | '__| | '_ \ / _ \
 *  ____) | |_| | |_) | | | | | | (_| | |  | | | | |  __/
 * |_____/ \__,_|_.__/|_| |_| |_|\__,_|_|  |_|_| |_|\___|
 *
 * This program is private software. No license required.
 * Publication of this program is forbidden and will be punished.
 *
 * @author SEMENNEJO
 * @link vk.com/vk.snikers && t.me/semennejo
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Utils;
use Throwable;

use function get_class;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use const JSON_THROW_ON_ERROR;

class LoginPacket extends DataPacket
{
	public const NETWORK_ID = ProtocolInfo::LOGIN_PACKET;

	/** @var string */
	public $username;
	/** @var int */
	public $protocol;
	/** @var int */
	public $gameEdition;
	/** @var string */
	public $clientUUID;
	/** @var int */
	public $clientId;
	/** @var string */
	public $xuid;
	/** @var string */
	public $identityPublicKey;
	/** @var string */
	public $serverAddress;
	/** @var string */
	public $locale;

	/** @var array */
	public $authInfo = [];
	/** @var array (the "chain" index contains one or more JWTs) */
	public $chainData = [];
	/** @var string */
	public $clientDataJwt;
	/** @var array decoded payload of the clientData JWT */
	public $clientData = [];

	/** @var bool */
	public $isValidProtocol = true; // valid protocol

	/**
	 * This field may be used by plugins to bypass keychain verification. It should only be used for plugins such as
	 * Specter where passing verification would take too much time and not be worth it.
	 *
	 * @var bool
	 */
	public $skipVerification = false;

	public function canBeSentBeforeLogin() : bool
	{
		return true;
	}

	public function mayHaveUnreadBytes() : bool
	{
		return $this->isValidProtocol === false;
	}

	protected function decodePayload() : void
	{
		if ($this->getInt() === 0x0) {
			$this->setOffset($this->getOffset() - 0x2);
		} else {
			$this->setOffset($this->getOffset() - 0x4);
		}
		$this->protocol = $this->getInt();

		if (!in_array($this->protocol, ProtocolInfo::ACCEPTED_PROTOCOLS, true)) {
			$this->isValidProtocol = false;

			return;
		}

		if ($this->protocol < ProtocolInfo::PROTOCOL_137) {
			$this->gameEdition = $this->getByte();
		}

		try {
			$this->decodeConnectionRequest();
		} catch (Throwable $e) {
			if ($this->isValidProtocol) {
				throw $e;
			}

			$logger = \GlobalLogger::get();
			$logger->debug(get_class($e) . " was thrown while decoding connection request in login (protocol version " . ($this->protocol ?? "unknown") . "): " . $e->getMessage());
			foreach (Utils::printableTrace($e->getTrace()) as $line) {
				$logger->debug($line);
			}
		}
	}

	protected function decodeConnectionRequest() : void
	{
		$buffer = new BinaryStream($this->getString());

		$authInfoJsonLength = $buffer->getLInt();
		if($authInfoJsonLength <= 0){
			throw new PacketDecodeException("Length of auth info JSON must be positive");
		}

		try{
			$authInfoJson = $buffer->get($authInfoJsonLength);
			$this->authInfo = json_decode($authInfoJson, associative: true, flags: JSON_THROW_ON_ERROR);
		}catch(\JsonException $e){
			throw new PacketDecodeException("Failed decoding chain data JSON: " . $e->getMessage());
		}

		if($this->protocol >= 900 && isset($this->authInfo["Token"]) && is_string($this->authInfo["Token"])){
			$this->chainData = ["chain" => [$this->authInfo["Token"]]];
			$this->skipVerification = true;
			$this->identityPublicKey = "MHYwEAYHKoZIzj0CAQYFK4EEACIDYgAEA";
			$token = $this->authInfo["Token"];
			$parts = explode(".", $token);
			if(isset($parts[1])){
				$payload = json_decode(base64_decode(strtr($parts[1], "-_", "+/")), true);
				if(isset($payload["extraData"])){
					$this->username = $payload["extraData"]["displayName"] ?? null;
					$this->clientUUID = $payload["extraData"]["identity"] ?? null;
					$this->xuid = $payload["extraData"]["XUID"] ?? "0";
				}
			}
		}else{
			if(isset($this->authInfo["Certificate"]) && is_string($this->authInfo["Certificate"])){
				$certificateData = json_decode($this->authInfo["Certificate"], true);
				if(isset($certificateData["chain"]) && is_array($certificateData["chain"])){
					$chainArray = $certificateData;
				}else{
					throw new PacketDecodeException("Invalid 'chain' data in Certificate field");
				}
			}elseif(isset($this->authInfo["chain"]) && is_array($this->authInfo["chain"])){
				$chainArray = $this->authInfo;
			}else{
				throw new PacketDecodeException("Missing or invalid 'chain' field in chain data");
			}
			$this->chainData = $chainArray;
			foreach($chainArray["chain"] as $chain){
				if(strlen($chain) < 10) continue;
				$webtoken = Utils::decodeJWT($chain);
				if(!is_array($webtoken)) continue;
				if(isset($webtoken["extraData"])){
					$this->username = $webtoken["extraData"]["displayName"] ?? $this->username;
					$this->clientUUID = $webtoken["extraData"]["identity"] ?? $this->clientUUID;
					$this->xuid = $webtoken["extraData"]["XUID"] ?? $this->xuid;
				}
				if(isset($webtoken["identityPublicKey"])){
					$this->identityPublicKey = $webtoken["identityPublicKey"];
				}
			}
		}

		$clientDataLen = $buffer->getLInt();
		$this->clientDataJwt = $buffer->get($clientDataLen);
		try{
			$this->clientData = Utils::decodeJWT($this->clientDataJwt);
		}catch(\Throwable $e){
			\GlobalLogger::get()->warning("Failed to decode clientDataJwt: " . $e->getMessage());
			$this->clientData = [];
		}

		$this->clientId = $this->clientData["ClientRandomId"] ?? null;
		$this->serverAddress = $this->clientData["ServerAddress"] ?? null;
		$this->locale = $this->clientData["LanguageCode"] ?? null;

		if($this->username === null && isset($this->clientData["ThirdPartyName"])){
			$this->username = $this->clientData["ThirdPartyName"];
		}
		if($this->clientUUID === null){
			foreach(["ClientUUID", "SelfSignedId", "DeviceId", "AppSessionId"] as $f){
				if(isset($this->clientData[$f]) && is_string($this->clientData[$f]) && $this->clientData[$f] !== ""){
					$this->clientUUID = $this->clientData[$f];
					break;
				}
			}
		}
		if($this->clientUUID === null && $this->username !== null){
			$hash = md5("offline:" . $this->username);
			$this->clientUUID = substr($hash, 0, 8) . "-" . substr($hash, 8, 4) . "-3" . substr($hash, 13, 3) . "-" . substr($hash, 16, 4) . "-" . substr($hash, 20, 12);
		}
		if(($this->xuid === null || $this->xuid === "") && isset($this->clientData["XUID"])){
			$this->xuid = $this->clientData["XUID"];
		}
		if($this->xuid === null || $this->xuid === ""){
			$this->xuid = "0";
		}
	}

	protected function encodePayload() : void
	{
		//TODO
	}

	public function handle(NetworkSession $session) : bool
	{
		return $session->handleLogin($this);
	}
}
