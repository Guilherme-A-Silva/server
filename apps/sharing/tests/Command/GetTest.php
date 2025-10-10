<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

use OC\Files\Config\UserMountCache;
use OCA\Sharing\Command\Get;
use OCA\Sharing\Features\NoteShareFeature;
use OCA\Sharing\Manager;
use OCA\Sharing\Model\Share;
use OCA\Sharing\RecipientTypes\UserShareRecipientType;
use OCA\Sharing\Registry;
use OCA\Sharing\SourceTypes\NodeShareSourceType;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Sabre\VObject\UUIDUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Test\TestCase;

/**
 * @group DB
 */
class GetTest extends TestCase {
	private Registry $registry;

	private IUser $user1;

	private IUser $user2;

	private Command $command;

	private Input $input;

	private Output $output;

	protected function setUp(): void {
		parent::setUp();

		$this->registry = Server::get(Registry::class);
		$this->registry->clear();

		$this->user1 = Server::get(IUserManager::class)->createUser('user1', 'password');
		$this->user2 = Server::get(IUserManager::class)->createUser('user2', 'password');

		$this->command = Server::get(Get::class);
		$this->input = $this->createMock(Input::class);
		$this->output = $this->createMock(Output::class);
	}

	protected function tearDown(): void {
		$manager = Server::get(Manager::class);
		foreach ($manager->list(null, null, false, false) as $share) {
			$manager->delete(null, $share->id, false, false);
		}

		$this->user1->delete();
		$this->user2->delete();

		Server::get(UserMountCache::class)->clear();

		parent::tearDown();
	}

	public function testExecute(): void {
		$this->registry->registerSourceType(new NodeShareSourceType());
		$this->registry->registerRecipientType(new UserShareRecipientType());
		$this->registry->registerFeature(new NoteShareFeature());

		$sourceNode = Server::get(IRootFolder::class)->getUserFolder($this->user1->getUID())->newFile('foo.txt', 'bar');

		$data = [
			'id' => UUIDUtil::getUUID(),
			'creator' => $this->user1->getUID(),
			'source_type' => NodeShareSourceType::class,
			'sources' => [(string)$sourceNode->getId()],
			'recipient_type' => UserShareRecipientType::class,
			'recipients' => [$this->user2->getUID()],
			'properties' => [NoteShareFeature::class => ['text' => ['abc']]],
		];
		Server::get(Manager::class)->insert($this->user1, Share::fromArray($data));

		$this->input
			->expects($this->once())
			->method('getArgument')
			->with('id')
			->willReturn($data['id']);

		$out = '';
		$this->output
			->expects($this->once())
			->method('writeln')
			->willReturnCallback(function (string $messages) use (&$out): void {
				$out .= $messages . "\n";
			});

		$this->assertEquals(0, $this->command->execute($this->input, $this->output));
		$this->assertEquals($data, json_decode($out, true, 512, JSON_THROW_ON_ERROR));
	}
}
