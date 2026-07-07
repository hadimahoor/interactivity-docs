<?php

declare(strict_types=1);

namespace InteractivityDocs\Tests\Unit\Sync;

use InteractivityDocs\Sync\RelationSyncService;
use InteractivityDocs\Repository\RepositoryFactoryInterface;
use InteractivityDocs\Repository\PersonRepository;
use InteractivityDocs\Repository\RelationRepositoryInterface;
use Psr\Log\LoggerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use WP_Mock\Tools\TestCase;

/**
 * Unit tests for RelationSyncService.
 *
 * Tests cover:
 * - Happy path (add/remove relations, recalculate counters)
 * - No-change scenario (skip logic)
 * - Validation errors (invalid post type)
 * - Transaction rollback on failure
 * - Invalid IDs handling (normalizeIds)
 * - Logger invocations
 */
class RelationSyncServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private RepositoryFactoryInterface $repositoryFactory;
    private PersonRepository $personRepository;
    private RelationRepositoryInterface $relationRepository;
    private LoggerInterface $logger;
    private RelationSyncService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->repositoryFactory = Mockery::mock(RepositoryFactoryInterface::class);
        $this->personRepository = Mockery::mock(PersonRepository::class);
        $this->relationRepository = Mockery::mock(RelationRepositoryInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->service = new RelationSyncService($this->repositoryFactory, $this->logger);
    }

    /**
     * Test successful sync with additions and removals.
     */
    public function test_syncPeople_adds_and_removes_relations_successfully(): void
    {
        $postId = 123;
        $postType = 'paper';
        $oldPersonIds = [1, 2, 3];
        $newPersonIds = [2, 3, 4, 5]; // Remove 1, keep 2,3, add 4,5

        $this->repositoryFactory->shouldReceive('createRelationRepository')
            ->once()
            ->with('paper_person')
            ->andReturn($this->relationRepository);

        $this->repositoryFactory->shouldReceive('createRepositoryForPostType')
            ->once()
            ->with('person')
            ->andReturn($this->personRepository);

        $this->relationRepository->shouldReceive('getPersonIdsForObject')
            ->once()
            ->with($postId)
            ->andReturn($oldPersonIds);

        $this->relationRepository->shouldReceive('beginTransaction')
            ->once();

        $this->relationRepository->shouldReceive('delete')
            ->once()
            ->with($postId)
            ->andReturn(true);

        foreach ($newPersonIds as $personId) {
            $this->relationRepository->shouldReceive('insert')
                ->once()
                ->with($postId, $personId)
                ->andReturn(true);
        }

        $this->relationRepository->shouldReceive('commit')
            ->once();

        $affectedIds = [1, 4, 5];
        $this->personRepository->shouldReceive('recalculateCounts')
            ->once()
            ->with(Mockery::on(function ($ids) use ($affectedIds) {
                sort($ids);
                $expected = $affectedIds;
                sort($expected);
                return $ids === $expected;
            }), $postType);

        $this->logger->shouldReceive('info')
            ->once()
            ->with(
                "Synced paper relationships for post 123",
                Mockery::on(function ($context) {
                    return $context['post_id'] === 123
                        && $context['post_type'] === 'paper'
                        && $context['added'] === 2
                        && $context['removed'] === 1;
                })
            );

        $this->service->syncPeople($postId, $newPersonIds, $postType);
    }

    /**
     * Test that sync is skipped when no changes detected.
     */
    public function test_syncPeople_skips_when_no_changes(): void
    {
        $postId = 123;
        $postType = 'book';
        $personIds = [1, 2, 3];

        $this->repositoryFactory->shouldReceive('createRelationRepository')
            ->once()
            ->with('book_person')
            ->andReturn($this->relationRepository);

        $this->repositoryFactory->shouldReceive('createRepositoryForPostType')
            ->once()
            ->with('person')
            ->andReturn($this->personRepository);

        $this->relationRepository->shouldReceive('getPersonIdsForObject')
            ->once()
            ->with($postId)
            ->andReturn($personIds);

        $this->relationRepository->shouldNotReceive('beginTransaction');
        $this->personRepository->shouldNotReceive('recalculateCounts');
        $this->logger->shouldNotReceive('info');

        $this->service->syncPeople($postId, $personIds, $postType);
    }

    /**
     * Test invalid post type throws exception.
     */
    public function test_syncPeople_throws_exception_for_invalid_post_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid post type: invalid. Expected: paper, book");

        $this->service->syncPeople(123, [1, 2], 'invalid');
    }

    /**
     * Test transaction rollback on insert failure.
     */
    public function test_syncPeople_rolls_back_transaction_on_insert_failure(): void
    {
        $postId = 123;
        $postType = 'paper';
        $oldPersonIds = [];
        $newPersonIds = [1, 2];

        $this->repositoryFactory->shouldReceive('createRelationRepository')
            ->once()
            ->with('paper_person')
            ->andReturn($this->relationRepository);

        $this->repositoryFactory->shouldReceive('createRepositoryForPostType')
            ->once()
            ->with('person')
            ->andReturn($this->personRepository);

        $this->relationRepository->shouldReceive('getPersonIdsForObject')
            ->once()
            ->with($postId)
            ->andReturn($oldPersonIds);

        $this->relationRepository->shouldReceive('beginTransaction')
            ->once();

        $this->relationRepository->shouldReceive('insert')
            ->once()
            ->with($postId, 1)
            ->andReturn(true);

        $this->relationRepository->shouldReceive('insert')
            ->once()
            ->with($postId, 2)
            ->andReturn(false);

        $this->relationRepository->shouldReceive('rollback')
            ->once();

        $this->logger->shouldReceive('error')
            ->once()
            ->with(
                Mockery::pattern('/Failed to sync paper relationships/'),
                Mockery::type('array')
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to insert relation 123 -> 2");

        $this->service->syncPeople($postId, $newPersonIds, $postType);
    }

    /**
     * Test transaction rollback on delete failure.
     */
    public function test_syncPeople_rolls_back_transaction_on_delete_failure(): void
    {
        $postId = 123;
        $postType = 'paper';
        $oldPersonIds = [1, 2];
        $newPersonIds = [3];

        $this->repositoryFactory->shouldReceive('createRelationRepository')
            ->once()
            ->with('paper_person')
            ->andReturn($this->relationRepository);

        $this->repositoryFactory->shouldReceive('createRepositoryForPostType')
            ->once()
            ->with('person')
            ->andReturn($this->personRepository);

        $this->relationRepository->shouldReceive('getPersonIdsForObject')
            ->once()
            ->with($postId)
            ->andReturn($oldPersonIds);

        $this->relationRepository->shouldReceive('beginTransaction')
            ->once();

        $this->relationRepository->shouldReceive('delete')
            ->once()
            ->with($postId)
            ->andReturn(false);

        $this->relationRepository->shouldReceive('rollback')
            ->once();

        $this->logger->shouldReceive('error')
            ->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to delete relations for post 123");

        $this->service->syncPeople($postId, $newPersonIds, $postType);
    }

    /**
     * Test normalizeIds filters invalid IDs.
     */
    public function test_syncPeople_normalizes_invalid_ids(): void
    {
        $postId = 123;
        $postType = 'paper';
        $oldPersonIds = [];
        $newPersonIds = [1, 0, -5, 'invalid', null, 2, '3'];

        $this->repositoryFactory->shouldReceive('createRelationRepository')
            ->once()
            ->with('paper_person')
            ->andReturn($this->relationRepository);

        $this->repositoryFactory->shouldReceive('createRepositoryForPostType')
            ->once()
            ->with('person')
            ->andReturn($this->personRepository);

        $this->relationRepository->shouldReceive('getPersonIdsForObject')
            ->once()
            ->with($postId)
            ->andReturn($oldPersonIds);

        $this->relationRepository->shouldReceive('beginTransaction')
            ->once();

        $validIds = [1, 2, 3];
        foreach ($validIds as $personId) {
            $this->relationRepository->shouldReceive('insert')
                ->once()
                ->with($postId, $personId)
                ->andReturn(true);
        }

        $this->relationRepository->shouldReceive('commit')
            ->once();

        $this->personRepository->shouldReceive('recalculateCounts')
            ->once()
            ->with($validIds, $postType);

        $this->logger->shouldReceive('info')
            ->once();

        $this->service->syncPeople($postId, $newPersonIds, $postType);
    }

    /**
     * Test single ID input is normalized to array.
     */
    public function test_syncPeople_handles_single_id_input(): void
    {
        $postId = 123;
        $postType = 'paper';
        $oldPersonIds = [];
        $newPersonId = 5;

        $this->repositoryFactory->shouldReceive('createRelationRepository')
            ->once()
            ->with('paper_person')
            ->andReturn($this->relationRepository);

        $this->repositoryFactory->shouldReceive('createRepositoryForPostType')
            ->once()
            ->with('person')
            ->andReturn($this->personRepository);

        $this->relationRepository->shouldReceive('getPersonIdsForObject')
            ->once()
            ->with($postId)
            ->andReturn($oldPersonIds);

        $this->relationRepository->shouldReceive('beginTransaction')
            ->once();

        $this->relationRepository->shouldReceive('insert')
            ->once()
            ->with($postId, 5)
            ->andReturn(true);

        $this->relationRepository->shouldReceive('commit')
            ->once();

        $this->personRepository->shouldReceive('recalculateCounts')
            ->once()
            ->with([5], $postType);

        $this->logger->shouldReceive('info')
            ->once();

        $this->service->syncPeople($postId, $newPersonId, $postType);
    }

    /**
     * Test empty/null IDs clears all relations.
     */
    public function test_syncPeople_clears_relations_with_empty_ids(): void
    {
        $postId = 123;
        $postType = 'book';
        $oldPersonIds = [1, 2, 3];
        $newPersonIds = null;

        $this->repositoryFactory->shouldReceive('createRelationRepository')
            ->once()
            ->with('book_person')
            ->andReturn($this->relationRepository);

        $this->repositoryFactory->shouldReceive('createRepositoryForPostType')
            ->once()
            ->with('person')
            ->andReturn($this->personRepository);

        $this->relationRepository->shouldReceive('getPersonIdsForObject')
            ->once()
            ->with($postId)
            ->andReturn($oldPersonIds);

        $this->relationRepository->shouldReceive('beginTransaction')
            ->once();

        $this->relationRepository->shouldReceive('delete')
            ->once()
            ->with($postId)
            ->andReturn(true);

        $this->relationRepository->shouldReceive('commit')
            ->once();

        $this->personRepository->shouldReceive('recalculateCounts')
            ->once()
            ->with($oldPersonIds, $postType);

        $this->logger->shouldReceive('info')
            ->once();

        $this->service->syncPeople($postId, $newPersonIds, $postType);
    }

    /**
     * Test service works without logger (null logger).
     */
    public function test_syncPeople_works_without_logger(): void
    {
        $serviceNoLogger = new RelationSyncService($this->repositoryFactory);

        $postId = 123;
        $postType = 'paper';
        $oldPersonIds = [];
        $newPersonIds = [1];

        $this->repositoryFactory->shouldReceive('createRelationRepository')
            ->once()
            ->with('paper_person')
            ->andReturn($this->relationRepository);

        $this->repositoryFactory->shouldReceive('createRepositoryForPostType')
            ->once()
            ->with('person')
            ->andReturn($this->personRepository);

        $this->relationRepository->shouldReceive('getPersonIdsForObject')
            ->once()
            ->with($postId)
            ->andReturn($oldPersonIds);

        $this->relationRepository->shouldReceive('beginTransaction')
            ->once();

        $this->relationRepository->shouldReceive('insert')
            ->once()
            ->with($postId, 1)
            ->andReturn(true);

        $this->relationRepository->shouldReceive('commit')
            ->once();

        $this->personRepository->shouldReceive('recalculateCounts')
            ->once()
            ->with([1], $postType);

        $serviceNoLogger->syncPeople($postId, $newPersonIds, $postType);
    }

    /**
     * Test repository factory failure throws exception.
     */
    public function test_syncPeople_throws_when_relation_repository_creation_fails(): void
    {
        $this->repositoryFactory->shouldReceive('createRelationRepository')
            ->once()
            ->with('paper_person')
            ->andThrow(new \InvalidArgumentException('Invalid relation type: paper_person'));

        $this->logger->shouldReceive('error')
            ->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to create relation repository for: paper_person");

        $this->service->syncPeople(123, [1], 'paper');
    }

    /**
     * Test repository factory failure for person repo throws exception.
     */
    public function test_syncPeople_throws_when_person_repository_creation_fails(): void
    {
        $this->repositoryFactory->shouldReceive('createRelationRepository')
            ->once()
            ->with('paper_person')
            ->andReturn($this->relationRepository);

        $this->repositoryFactory->shouldReceive('createRepositoryForPostType')
            ->once()
            ->with('person')
            ->andReturn(null);

        $this->logger->shouldReceive('error')
            ->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to create PersonRepository");

        $this->service->syncPeople(123, [1], 'paper');
    }

    /**
     * Test duplicate IDs in input are deduplicated.
     */
    public function test_syncPeople_deduplicates_person_ids(): void
    {
        $postId = 123;
        $postType = 'paper';
        $oldPersonIds = [];
        $newPersonIds = [1, 2, 3, 1, 3];

        $this->repositoryFactory->shouldReceive('createRelationRepository')
            ->once()
            ->with('paper_person')
            ->andReturn($this->relationRepository);

        $this->repositoryFactory->shouldReceive('createRepositoryForPostType')
            ->once()
            ->with('person')
            ->andReturn($this->personRepository);

        $this->relationRepository->shouldReceive('getPersonIdsForObject')
            ->once()
            ->with($postId)
            ->andReturn($oldPersonIds);

        $this->relationRepository->shouldReceive('beginTransaction')
            ->once();

        $uniqueIds = [1, 2, 3];
        foreach ($uniqueIds as $personId) {
            $this->relationRepository->shouldReceive('insert')
                ->once()
                ->with($postId, $personId)
                ->andReturn(true);
        }

        $this->relationRepository->shouldReceive('commit')
            ->once();

        $this->personRepository->shouldReceive('recalculateCounts')
            ->once()
            ->with($uniqueIds, $postType);

        $this->logger->shouldReceive('info')
            ->once();

        $this->service->syncPeople($postId, $newPersonIds, $postType);
    }
}
