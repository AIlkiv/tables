<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Tables\Service\ValueObject;

use ArrayAccess;
use JsonSerializable;

/**
 * @template-implements ArrayAccess<string, mixed>
 */
class ViewColumnInformation implements ArrayAccess, JsonSerializable {
	public const KEY_ID = 'columnId';
	public const KEY_ORDER = 'order';

	/** @var array{columndId?: int, order?: int} */
	protected array $data = [];
	protected const KEYS = [
		self::KEY_ID,
		self::KEY_ORDER,
	];

	public function __construct(
		int $columnId,
		int $order,
	) {
		$this->offsetSet(self::KEY_ID, $columnId);
		$this->offsetSet(self::KEY_ORDER, $order);
	}

	public function getOrder(): int {
		return $this->offsetGet(self::KEY_ORDER);
	}

	public static function fromArray(array $data): static {
		$vci = new static($data[self::KEY_ID], $data[self::KEY_ORDER]);
		foreach ($data as $key => $value) {
			$vci[$key] = $value;
		}
		return $vci;
	}

	public function offsetExists(mixed $offset): bool {
		return in_array((string)$offset, self::KEYS);
	}

	public function offsetGet(mixed $offset): mixed {
		return $this->data[$offset] ?? null;
	}

	public function offsetSet(mixed $offset, mixed $value): void {
		if (!$this->offsetExists($offset)) {
			return;
		}
		$this->data[(string)$offset] = $value;
	}

	public function offsetUnset(mixed $offset): void {
		if (!$this->offsetExists($offset)) {
			return;
		}
		unset($this->data[(string)$offset]);
	}

	public function jsonSerialize(): array {
		return $this->data;
	}
}
