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


use pocketmine\block\utils\BlockDataSerializer;
use pocketmine\item\Fertilizer;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use pocketmine\world\World;

class Bamboo extends Transparent {

	public const NO_LEAVES = 0;
	public const SMALL_LEAVES = 1;
	public const LARGE_LEAVES = 2;

	public const BAMBOO_MAX_LENGTH = 10;

	/** @var bool */
	protected $thick = false; //age in PC, but this is 0/1
	/** @var bool */
	protected $ready = false;
	/** @var int */
	protected $leafSize = self::NO_LEAVES;

	public function __construct(BlockIdentifier $idInfo, string $name, ?BlockBreakInfo $breakInfo = null){
		parent::__construct($idInfo, $name, $breakInfo ?? new BlockBreakInfo(0));
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->thick = ($stateMeta & BlockLegacyMetadata::BAMBOO_FLAG_THICK) !== 0;
		$this->leafSize = BlockDataSerializer::readBoundedInt("leafSize", ($stateMeta >> BlockLegacyMetadata::BAMBOO_LEAF_SIZE_SHIFT) & BlockLegacyMetadata::BAMBOO_LEAF_SIZE_MASK, self::NO_LEAVES, self::LARGE_LEAVES);
		$this->ready = ($stateMeta & BlockLegacyMetadata::BAMBOO_FLAG_READY) !== 0;
	}

	public function writeStateToMeta() : int{
		return ($this->thick ? BlockLegacyMetadata::BAMBOO_FLAG_THICK : 0) | ($this->leafSize << BlockLegacyMetadata::BAMBOO_LEAF_SIZE_SHIFT) | ($this->ready ? BlockLegacyMetadata::BAMBOO_FLAG_READY : 0);
	}

	public function getStateBitmask() : int{
		return 0b1111;
	}

	/**
	 * @return AxisAlignedBB[]
	 */
	protected function recalculateCollisionBoxes() : array{
		//this places the BB at the northwest corner, not the center
		$inset = 1 - (($this->thick ? 3 : 2) / 16);
		return [AxisAlignedBB::one()->trim(Facing::SOUTH, $inset)->trim(Facing::EAST, $inset)];
	}

	private static function getOffsetSeed(int $x, int $y, int $z) : int{
		$p1 = gmp_mul($z, 0x6ebfff5);
		$p2 = gmp_mul($x, 0x2fc20f);
		$p3 = $y;

		$xord = gmp_xor(gmp_xor($p1, $p2), $p3);

		$fullResult = gmp_mul(gmp_add(gmp_mul($xord, 0x285b825), 0xb), $xord);
		return gmp_intval(gmp_and($fullResult, 0xffffffff));
	}

	public function getPosOffset() : ?Vector3{
		$seed = self::getOffsetSeed($this->pos->x, 0, $this->pos->z);
		$retX = (($seed % 12) + 1) / 16;
		$retZ = ((($seed >> 8) % 12) + 1) / 16;
		return new Vector3($retX, 0, $retZ);
	}

	public function getLength(){
		$bambooLengthBelow = 0;
		for ($i=-1;$this->getPos()->getY()+$i>=0;$i--){
			if($this->getPos()->getWorld()->getBlock($this->getPos()->add(0, $i, 0))->getId() === BlockLegacyIds::BAMBOO){
				$bambooLengthBelow++;
			}else{
				break;
			}
		}
		$bambooLengthAbove = 0;
		for ($i=1;$this->getPos()->getY()+$i<=World::Y_MAX;$i++){
			if($this->getPos()->getWorld()->getBlock($this->getPos()->add(0, $i, 0))->getId() === BlockLegacyIds::BAMBOO){
				$bambooLengthAbove++;
			}else{
				break;
			}
		}
		return $bambooLengthBelow + $bambooLengthAbove + 1;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null): bool {
		if($item instanceof Fertilizer){
			$length = $this->getLength();

			if($length < self::BAMBOO_MAX_LENGTH){
				$this->growBamboo($this->pos->getWorld(), $this->getPos(), $length + 1);
			}
			return true;
		}
		return false;
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool{
		$down = $this->getSide(Facing::DOWN)->getId();
		if($down === BlockLegacyIds::GRASS or $down === BlockLegacyIds::DIRT or $down === BlockLegacyIds::PODZOL){
			$block = VanillaBlocks::BAMBOO_SAPLING();
			$tx = $tx->addBlock($this->getPos(), $block);
			return parent::place($tx, $item, $block, $blockClicked, $face, $clickVector, $player);
		}
		return false;
	}

	public function onNearbyBlockChange() : void{
		if($this->getSide(Facing::DOWN) instanceof Air){ //Replace with common break method
			$this->pos->getWorld()->useBreakOn($this->pos);
		}
	}

	public function growBamboo(World $world, Vector3 $xyz, int $height = 2){
		if($world->getBlock($xyz->add(0, -1, 0))->getId() === BlockLegacyIds::BAMBOO){
			$this->growBamboo($world, $xyz->add(0, -1, 0), $height);
		}else{
			for($i=0;$i<$height;$i++){
				$existsBlock =$world->getBlock($xyz->add(0, $i, 0));
				if($existsBlock->canBeReplaced() or $existsBlock instanceof Bamboo or $existsBlock instanceof BambooSapling){
					$bamboo = VanillaBlocks::BAMBOO();
					switch($height){
						case 1:
							$bamboo = VanillaBlocks::BAMBOO_SAPLING();
							break;
						case 2:
							if($height === $i+1){ // top
								$bamboo->leafSize = self::SMALL_LEAVES;
							}else{
								$bamboo->leafSize = self::NO_LEAVES;
							}
							break;
						case 3:
							if($i === 0){
								$bamboo->leafSize = self::NO_LEAVES;
							}else{
								$bamboo->leafSize = self::SMALL_LEAVES;
							}
							break;
						case 4:
							if($height === $i+1){ // top
								$bamboo->leafSize = self::LARGE_LEAVES;
							}elseif($height === $i+2){ // second
								$bamboo->leafSize = self::SMALL_LEAVES;
							}else{
								$bamboo->leafSize = self::NO_LEAVES;
							}
							break;
						default: // 5↑
							if($height === $i+1 or $height === $i+2){ // top or second
								$bamboo->leafSize = self::LARGE_LEAVES;
							}elseif($height === $i+3){ // third
								$bamboo->leafSize = self::SMALL_LEAVES;
							}else{
								$bamboo->leafSize = self::NO_LEAVES;
							}
							break;
					}
					$world->setBlock($xyz->add(0, $i, 0), $bamboo);
				}
			}
		}
	}
	public function onRandomTick(): void{
		if($this->getSide(Facing::DOWN) instanceof Bamboo){
			return; // 成長するのは地面に接してる竹のみ
		}
		$length = $this->getLength();
		if($length < self::BAMBOO_MAX_LENGTH){
			$this->growBamboo($this->pos->getWorld(), $this->getPos(), $length + 1);
		}
	}

	public function ticksRandomly() : bool{
		return true;
	}
}