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

namespace pocketmine\block;

use pocketmine\item\Fertilizer;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class BambooSapling extends Flowable {

	public function __construct(BlockIdentifier $idInfo, string $name, ?BlockBreakInfo $breakInfo = null){
		parent::__construct($idInfo, $name, $breakInfo ?? new BlockBreakInfo(0));
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null): bool {
		if($item instanceof Fertilizer){
			$bamboo = VanillaBlocks::BAMBOO();
			$this->getPos()->getWorld()->setBlock($this->getPos(), $bamboo);
			$bamboo->growBamboo($this->getPos()->getWorld(), $this->getPos()->asVector3(), 2);
			return true;
		}
		return false;
	}

	public function onRandomTick(): void{
		$bamboo = VanillaBlocks::BAMBOO();
		$this->getPos()->getWorld()->setBlock($this->getPos(), $bamboo);
		$bamboo->growBamboo($this->getPos()->getWorld(), $this->getPos()->asVector3(), 2);
	}

	public function ticksRandomly() : bool{
		return true;
	}
}