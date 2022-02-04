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

namespace pocketmine\data\bedrock\blockstate;

use pocketmine\block\utils\BellAttachmentType;
use pocketmine\block\utils\CoralType;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\utils\SlabType;
use pocketmine\block\utils\TreeType;
use pocketmine\data\bedrock\blockstate\BlockStateStringValues as StringValues;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\CompoundTag;

final class BlockStateWriter{

	private CompoundTag $states;

	public function __construct(
		private string $id
	){
		$this->states = CompoundTag::create();
	}

	public static function create(string $id) : self{
		return new self($id);
	}

	/** @return $this */
	public function writeBool(string $name, bool $value) : self{
		$this->states->setByte($name, $value ? 1 : 0);
		return $this;
	}

	/** @return $this */
	public function writeInt(string $name, int $value) : self{
		$this->states->setInt($name, $value);
		return $this;
	}

	/** @return $this */
	public function writeString(string $name, string $value) : self{
		$this->states->setString($name, $value);
		return $this;
	}

	/** @return $this */
	public function writeFacingDirection(int $value) : self{
		$this->writeInt(BlockStateNames::FACING_DIRECTION, match($value){
			Facing::DOWN => 0,
			Facing::UP => 1,
			Facing::NORTH => 2,
			Facing::SOUTH => 3,
			Facing::WEST => 4,
			Facing::EAST => 5,
			default => throw new BlockStateSerializeException("Invalid Facing $value")
		});
		return $this;
	}

	/** @return $this */
	public function writeEndRodFacingDirection(int $value) : self{
		//end rods are stupid in bedrock and have everything except up/down the wrong way round
		return $this->writeFacingDirection(Facing::axis($value) !== Axis::Y ? Facing::opposite($value) : $value);
	}

	/** @return $this */
	public function writeHorizontalFacing(int $value) : self{
		if($value === Facing::UP || $value === Facing::DOWN){
			throw new BlockStateSerializeException("Y-axis facing is not allowed");
		}

		return $this->writeFacingDirection($value);
	}

	/** @return $this */
	public function writeWeirdoHorizontalFacing(int $value) : self{
		$this->writeInt(BlockStateNames::WEIRDO_DIRECTION, match($value){
			Facing::EAST => 0,
			Facing::WEST => 1,
			Facing::SOUTH => 2,
			Facing::NORTH => 3,
			default => throw new BlockStateSerializeException("Invalid horizontal facing $value")
		});
		return $this;
	}

	/** @return $this */
	public function writeLegacyHorizontalFacing(int $value) : self{
		$this->writeInt(BlockStateNames::DIRECTION, match($value){
			Facing::SOUTH => 0,
			Facing::WEST => 1,
			Facing::NORTH => 2,
			Facing::EAST => 3,
			default => throw new BlockStateSerializeException("Invalid horizontal facing $value")
		});
		return $this;
	}

	/**
	 * This is for trapdoors, because Mojang botched the conversion in 1.13
	 * @return $this
	 */
	public function write5MinusHorizontalFacing(int $value) : self{
		return $this->writeInt(BlockStateNames::DIRECTION, match($value){
			Facing::EAST => 0,
			Facing::WEST => 1,
			Facing::SOUTH => 2,
			Facing::NORTH => 3,
			default => throw new BlockStateSerializeException("Invalid horizontal facing $value")
		});
	}

	/** @return $this */
	public function writeColor(DyeColor $color) : self{
		$this->writeString(BlockStateNames::COLOR, match($color->id()){
			DyeColor::BLACK()->id() => StringValues::COLOR_BLACK,
			DyeColor::BLUE()->id() => StringValues::COLOR_BLUE,
			DyeColor::BROWN()->id() => StringValues::COLOR_BROWN,
			DyeColor::CYAN()->id() => StringValues::COLOR_CYAN,
			DyeColor::GRAY()->id() => StringValues::COLOR_GRAY,
			DyeColor::GREEN()->id() => StringValues::COLOR_GREEN,
			DyeColor::LIGHT_BLUE()->id() => StringValues::COLOR_LIGHT_BLUE,
			DyeColor::LIGHT_GRAY()->id() => StringValues::COLOR_SILVER,
			DyeColor::LIME()->id() => StringValues::COLOR_LIME,
			DyeColor::MAGENTA()->id() => StringValues::COLOR_MAGENTA,
			DyeColor::ORANGE()->id() => StringValues::COLOR_ORANGE,
			DyeColor::PINK()->id() => StringValues::COLOR_PINK,
			DyeColor::PURPLE()->id() => StringValues::COLOR_PURPLE,
			DyeColor::RED()->id() => StringValues::COLOR_RED,
			DyeColor::WHITE()->id() => StringValues::COLOR_WHITE,
			DyeColor::YELLOW()->id() => StringValues::COLOR_YELLOW,
			default => throw new BlockStateSerializeException("Invalid Color " . $color->name())
		});
		return $this;
	}

	/** @return $this */
	public function writeCoralFacing(int $value) : self{
		$this->writeInt(BlockStateNames::CORAL_DIRECTION, match($value){
			Facing::WEST => 0,
			Facing::EAST => 1,
			Facing::NORTH => 2,
			Facing::SOUTH => 3,
			default => throw new BlockStateSerializeException("Invalid horizontal facing $value")
		});
		return $this;
	}

	/** @return $this */
	public function writeFacingWithoutDown(int $value) : self{
		if($value === Facing::DOWN){
			throw new BlockStateSerializeException("Invalid facing DOWN");
		}
		$this->writeFacingDirection($value);
		return $this;
	}

	/** @return $this */
	public function writeFacingWithoutUp(int $value) : self{
		if($value === Facing::UP){
			throw new BlockStateSerializeException("Invalid facing UP");
		}
		$this->writeFacingDirection($value);
		return $this;
	}

	/** @return $this */
	public function writePillarAxis(int $axis) : self{
		$this->writeString(BlockStateNames::PILLAR_AXIS, match($axis){
			Axis::X => StringValues::PILLAR_AXIS_X,
			Axis::Y => StringValues::PILLAR_AXIS_Y,
			Axis::Z => StringValues::PILLAR_AXIS_Z,
			default => throw new BlockStateSerializeException("Invalid axis $axis")
		});
		return $this;
	}

	/** @return $this */
	public function writeSlabPosition(SlabType $slabType) : self{
		$this->writeBool(BlockStateNames::TOP_SLOT_BIT, match($slabType->id()){
			SlabType::TOP()->id() => true,
			SlabType::BOTTOM()->id() => false,
			default => throw new BlockStateSerializeException("Invalid slab type " . $slabType->name())
		});
		return $this;
	}

	/** @return $this */
	public function writeTorchFacing(int $facing) : self{
		//TODO: horizontal directions are flipped (MCPE bug: https://bugs.mojang.com/browse/MCPE-152036)
		$this->writeString(BlockStateNames::TORCH_FACING_DIRECTION, match($facing){
			Facing::UP => StringValues::TORCH_FACING_DIRECTION_TOP,
			Facing::SOUTH => StringValues::TORCH_FACING_DIRECTION_NORTH,
			Facing::NORTH => StringValues::TORCH_FACING_DIRECTION_SOUTH,
			Facing::EAST => StringValues::TORCH_FACING_DIRECTION_WEST,
			Facing::WEST => StringValues::TORCH_FACING_DIRECTION_EAST,
			default => throw new BlockStateSerializeException("Invalid Torch facing $facing")
		});
		return $this;
	}

	/** @return $this */
	public function writeTreeType(TreeType $treeType) : self{
		$this->writeString(BlockStateNames::WOOD_TYPE, match($treeType->id()){
			TreeType::OAK()->id() => StringValues::WOOD_TYPE_OAK,
			TreeType::SPRUCE()->id() => StringValues::WOOD_TYPE_SPRUCE,
			TreeType::BIRCH()->id() => StringValues::WOOD_TYPE_BIRCH,
			TreeType::JUNGLE()->id() => StringValues::WOOD_TYPE_JUNGLE,
			TreeType::ACACIA()->id() => StringValues::WOOD_TYPE_ACACIA,
			TreeType::DARK_OAK()->id() => StringValues::WOOD_TYPE_DARK_OAK,
			default => throw new BlockStateSerializeException("Invalid Tree type " . $treeType->name())
		});
		return $this;
	}

	/** @return $this */
	public function writeCoralType(CoralType $coralType) : self{
		$this->writeString(BlockStateNames::CORAL_COLOR, match($coralType->id()){
			CoralType::TUBE()->id() => StringValues::CORAL_COLOR_BLUE,
			CoralType::BRAIN()->id() => StringValues::CORAL_COLOR_PINK,
			CoralType::BUBBLE()->id() => StringValues::CORAL_COLOR_PURPLE,
			CoralType::FIRE()->id() => StringValues::CORAL_COLOR_RED,
			CoralType::HORN()->id() => StringValues::CORAL_COLOR_YELLOW,
			default => throw new BlockStateSerializeException("Invalid Coral type " . $coralType->name())
		});
		return $this;
	}

	/** @return $this */
	public function writeBellAttachmentType(BellAttachmentType $attachmentType) : self{
		$this->writeString(BlockStateNames::ATTACHMENT, match($attachmentType->id()){
			BellAttachmentType::FLOOR()->id() => StringValues::ATTACHMENT_STANDING,
			BellAttachmentType::CEILING()->id() => StringValues::ATTACHMENT_HANGING,
			BellAttachmentType::ONE_WALL()->id() => StringValues::ATTACHMENT_SIDE,
			BellAttachmentType::TWO_WALLS()->id() => StringValues::ATTACHMENT_MULTIPLE,
			default => throw new BlockStateSerializeException("Invalid Bell attachment type " . $attachmentType->name())
		});
		return $this;
	}

	public function getBlockStateData() : BlockStateData{
		return new BlockStateData($this->id, $this->states, BlockStateData::CURRENT_VERSION);
	}
}