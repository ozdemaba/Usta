<?php
defined('ABSPATH') || exit;

/**
 * Contract for authoritative procurement adapters.
 * Adapters must return canonical records; they must not write directly to the DB.
 */
interface GOI_Core_Source_Adapter {
 public function id(): string;
 public function manifest(): array;
 public function fetch(array $cursor=[]): array;
 public function normalize(array $record): ?array;
}

final class GOI_Core_Source_Registry {
 private static array $adapters=[];
 public static function register(GOI_Core_Source_Adapter $adapter): void { self::$adapters[$adapter->id()]=$adapter; }
 public static function get(string $id): ?GOI_Core_Source_Adapter { return self::$adapters[$id]??null; }
 public static function all(): array { return self::$adapters; }
}