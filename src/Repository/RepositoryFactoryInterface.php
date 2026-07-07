<?php

declare(strict_types=1);

namespace InteractivityDocs\Repository;

interface RepositoryFactoryInterface
{
    public function createPaperRepository(): PaperRepository;

    public function createBookRepository(): BookRepository;

    public function createPersonRepository(): PersonRepository;

    public function createRelationRepository(string $type): RelationRepositoryInterface;

    public function createRepositoryForPostType(string $postType): ?PostRepositoryInterface;
}
