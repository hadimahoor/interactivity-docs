<?php

declare(strict_types=1);

/*
 * Namespaced function overrides.
 *
 * PersonRepository calls current_time() and error_log() unqualified from within
 * the InteractivityDocs\Repository namespace, so PHP resolves them to these
 * namespaced doubles first. This keeps upsertMain() deterministic and lets us
 * assert on logged errors without a WP test harness.
 */
namespace InteractivityDocs\Repository {

    if (!\function_exists(__NAMESPACE__ . '\\current_time')) {
        function current_time(string $type): string
        {
            return '2026-06-30 12:00:00';
        }
    }

    if (!\function_exists(__NAMESPACE__ . '\\error_log')) {
        function error_log(string $message): bool
        {
            $GLOBALS['__person_repo_logged_errors'][] = $message;
            return true;
        }
    }
}

namespace InteractivityDocs\Tests\Repository {

    use InteractivityDocs\Models\Person;
    use InteractivityDocs\Repository\PersonRepository;
    use Mockery;
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use Mockery\MockInterface;
    use PHPUnit\Framework\TestCase;

    /**
     * Unit tests for PersonRepository.
     *
     * Covers the person-specific behaviour (upsertMain, recalculateCounts,
     * getPostType) as well as the inherited read/guard methods from
     * BasePostRepository (find, findAll, exists, deleteByPostId, and the
     * forbidden insert/update).
     *
     * @covers \InteractivityDocs\Repository\PersonRepository
     */
    final class PersonRepositoryTest extends TestCase
    {
        use MockeryPHPUnitIntegration;

        /** @var \wpdb&MockInterface */
        private $db;

        private PersonRepository $repository;

        protected function setUp(): void
        {
            parent::setUp();

            $GLOBALS['__person_repo_logged_errors'] = [];

            /** @var \wpdb&MockInterface $db */
            $db = Mockery::mock(\wpdb::class);
            $db->prefix = 'wp_';
            // Expose the mock as the global $wpdb so TableNames::person() resolves.
            $GLOBALS['wpdb'] = $db;

            $this->db         = $db;
            $this->repository = new PersonRepository($db);
        }

        protected function tearDown(): void
        {
            unset($GLOBALS['wpdb'], $GLOBALS['__person_repo_logged_errors']);
            parent::tearDown();
        }

        /**
         * Builds a Person test double that satisfies the instanceof check in
         * upsertMain() and returns controllable persisted data.
         *
         * @param array<string, mixed> $data
         * @return Person&MockInterface
         */
        private function makePerson(array $data, int $id = 10, string $createdAt = ''): Person
        {
            /** @var Person&MockInterface $person */
            $person = Mockery::mock(Person::class);
            $person->shouldReceive('toArray')->andReturn($data);
            $person->shouldReceive('getId')->andReturn($id);
            $person->shouldReceive('getCreatedAt')->andReturn($createdAt);

            return $person;
        }

        public function testGetPostTypeReturnsPerson(): void
        {
            self::assertSame('person', $this->repository->getPostType());
        }

        public function testFindReturnsHydratedEntityWhenRowExists(): void
        {
            $row = ['person_id' => 5, 'title' => 'Ada Lovelace', 'slug' => 'ada'];

            $this->db->shouldReceive('prepare')
                ->once()
                ->with(Mockery::type('string'), 5)
                ->andReturn('PREPARED_FIND');
            $this->db->shouldReceive('get_row')
                ->once()
                ->with('PREPARED_FIND', ARRAY_A)
                ->andReturn($row);

            $entity = $this->repository->find(5);

            self::assertInstanceOf(Person::class, $entity);
            self::assertSame(5, $entity->getId());
        }

        public function testFindReturnsNullWhenRowMissing(): void
        {
            $this->db->shouldReceive('prepare')->once()->andReturn('PREPARED_FIND');
            $this->db->shouldReceive('get_row')->once()->andReturn(null);

            self::assertNull($this->repository->find(999));
        }

        public function testFindAllMapsEveryRowToAnEntity(): void
        {
            $rows = [
                ['person_id' => 1, 'title' => 'A'],
                ['person_id' => 2, 'title' => 'B'],
            ];

            $this->db->shouldReceive('get_results')
                ->once()
                ->with(Mockery::type('string'), ARRAY_A)
                ->andReturn($rows);

            $result = $this->repository->findAll();

            self::assertCount(2, $result);
            self::assertContainsOnlyInstancesOf(Person::class, $result);
            self::assertSame([1, 2], array_map(static fn(Person $p): int => $p->getId(), $result));
        }

        public function testFindAllReturnsEmptyArrayWhenNoRows(): void
        {
            $this->db->shouldReceive('get_results')->once()->andReturn(null);

            self::assertSame([], $this->repository->findAll());
        }

        public function testUpsertMainReturnsTrueOnSuccessfulQuery(): void
        {
            $person = $this->makePerson([
                'title'       => 'Ada',
                'slug'        => 'ada',
                'post_status' => 'publish',
                'gender'      => 'female',
                'image'       => 'ada.jpg',
                'paper_count' => 3,
                'book_count'  => 1,
                'like_count'  => 0,
                'view_count'  => 42,
            ]);

            $this->db->shouldReceive('prepare')->once()->withAnyArgs()->andReturn('PREPARED_UPSERT');
            $this->db->shouldReceive('hide_errors')->once();
            $this->db->shouldReceive('query')->once()->with('PREPARED_UPSERT')->andReturn(1);

            self::assertTrue($this->repository->upsertMain($person));
            self::assertSame([], $GLOBALS['__person_repo_logged_errors']);
        }

        public function testUpsertMainReturnsFalseAndLogsErrorOnQueryFailure(): void
        {
            $person = $this->makePerson([
                'title'       => 'Ada',
                'slug'        => 'ada',
                'post_status' => 'publish',
            ]);

            $this->db->last_error = 'Duplicate column';
            $this->db->shouldReceive('prepare')->once()->withAnyArgs()->andReturn('PREPARED_UPSERT');
            $this->db->shouldReceive('hide_errors')->once();
            $this->db->shouldReceive('query')->once()->andReturn(false);

            self::assertFalse($this->repository->upsertMain($person));
            self::assertNotEmpty($GLOBALS['__person_repo_logged_errors']);
            self::assertStringContainsString('Duplicate column', $GLOBALS['__person_repo_logged_errors'][0]);
        }

        public function testUpsertMainThrowsWhenEntityIsNotPerson(): void
        {
            $notAPerson = Mockery::mock(\InteractivityDocs\Models\BaseEntity::class);

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Entity must be an instance of Person');

            $this->repository->upsertMain($notAPerson);
        }

        public function testInsertIsForbidden(): void
        {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('upsertMain()');

            $this->repository->insert(Mockery::mock(Person::class));
        }

        public function testUpdateIsForbidden(): void
        {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('upsertMain()');

            $this->repository->update(Mockery::mock(Person::class));
        }

        public function testRecalculateCountsReturnsEarlyForEmptyIds(): void
        {
            $this->db->shouldNotReceive('query');

            $this->repository->recalculateCounts([], 'paper');
        }

        public function testRecalculateCountsThrowsForUnsupportedPostType(): void
        {
            $this->db->shouldNotReceive('query');

            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Unsupported post type: comment');

            $this->repository->recalculateCounts([1, 2], 'comment');
        }

        /**
         * @dataProvider recalculatePostTypeProvider
         */
        public function testRecalculateCountsRunsUpdateForSupportedType(string $postType, string $expectedColumn): void
        {
            $this->db->shouldReceive('query')
                ->once()
                ->with(Mockery::on(static function (string $sql) use ($expectedColumn): bool {
                    return str_contains($sql, $expectedColumn)
                        && str_contains($sql, 'IN (1,2,3)');
                }))
                ->andReturn(3);

            $this->repository->recalculateCounts([1, 2, 3], $postType);
        }

        /**
         * @return array<string, array{0:string, 1:string}>
         */
        public static function recalculatePostTypeProvider(): array
        {
            return [
                'paper counts' => ['paper', 'paper_count'],
                'book counts'  => ['book', 'book_count'],
            ];
        }

        public function testRecalculateCountsCastsIdsToIntToPreventInjection(): void
        {
            // Non-int values must be coerced to 0 via intval, never interpolated raw.
            $this->db->shouldReceive('query')
                ->once()
                ->with(Mockery::on(static fn(string $sql): bool => str_contains($sql, 'IN (0,7)')))
                ->andReturn(0);

            /** @phpstan-ignore-next-line intentionally passing dirty input */
            $this->repository->recalculateCounts(['DROP TABLE', 7], 'paper');
        }

        public function testExistsReturnsTrueWhenCountPositive(): void
        {
            $this->db->shouldReceive('prepare')->once()->with(Mockery::type('string'), 5)->andReturn('PREPARED');
            $this->db->shouldReceive('get_var')->once()->with('PREPARED')->andReturn('2');

            self::assertTrue($this->repository->exists(5));
        }

        public function testExistsReturnsFalseWhenCountZero(): void
        {
            $this->db->shouldReceive('prepare')->once()->andReturn('PREPARED');
            $this->db->shouldReceive('get_var')->once()->andReturn('0');

            self::assertFalse($this->repository->exists(5));
        }

        public function testDeleteByPostIdDelegatesToWpdbDelete(): void
        {
            $this->db->shouldReceive('delete')
                ->once()
                ->with('wp_person', ['person_id' => 5], ['%d'])
                ->andReturn(1);

            self::assertTrue($this->repository->deleteByPostId(5));
        }
    }
}
