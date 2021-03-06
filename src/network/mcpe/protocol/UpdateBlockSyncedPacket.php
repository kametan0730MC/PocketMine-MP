<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\serializer\NetworkBinaryStream;

class UpdateBlockSyncedPacket extends UpdateBlockPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_BLOCK_SYNCED_PACKET;

	/** @var int */
	public $entityUniqueId = 0;
	/** @var int */
	public $uvarint64_2 = 0;

	protected function decodePayload(NetworkBinaryStream $in) : void{
		parent::decodePayload($in);
		$this->entityUniqueId = $in->getUnsignedVarLong();
		$this->uvarint64_2 = $in->getUnsignedVarLong();
	}

	protected function encodePayload(NetworkBinaryStream $out) : void{
		parent::encodePayload($out);
		$out->putUnsignedVarLong($this->entityUniqueId);
		$out->putUnsignedVarLong($this->uvarint64_2);
	}

	public function handle(PacketHandler $handler) : bool{
		return $handler->handleUpdateBlockSynced($this);
	}
}
