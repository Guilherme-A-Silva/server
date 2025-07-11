<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\lib\Config;

use NCU\Config\IUserConfig;
use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\Lexicon\ConfigLexiconStrictness;
use NCU\Config\Lexicon\IConfigLexicon;
use NCU\Config\ValueType;
use OCP\IAppConfig;

class TestConfigLexicon_I implements IConfigLexicon {
	public const APPID = 'lexicon_test_i';

	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [
			new ConfigLexiconEntry('key1', ValueType::STRING, 'abcde', 'test key', true, IAppConfig::FLAG_SENSITIVE),
			new ConfigLexiconEntry('key2', ValueType::INT, 12345, 'test key', false),
			new ConfigLexiconEntry('key3', ValueType::INT, 12345, 'test key', true, rename: 'old_key3'),
			new ConfigLexiconEntry('key4', ValueType::BOOL, 12345, 'test key', true, rename: 'old_key4', options: ConfigLexiconEntry::RENAME_INVERT_BOOLEAN),
		];
	}

	public function getUserConfigs(): array {
		return [
			new ConfigLexiconEntry('key1', ValueType::STRING, 'abcde', 'test key', true, IUserConfig::FLAG_SENSITIVE),
			new ConfigLexiconEntry('key2', ValueType::INT, 12345, 'test key', false)
		];
	}

}
