<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Reference\Provider;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\ContentType\ReferenceContentTypeInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * This class is also extended by:
 *
 * @see \Sulu\Bundle\PageBundle\Reference\Provider\PageReferenceProvider
 *
 * @internal
 */
class DocumentReferenceProvider implements DocumentReferenceProviderInterface
{
    private ContentTypeManagerInterface $contentTypeManager;

    private StructureManagerInterface $structureManager;

    private ReferenceRepositoryInterface $referenceRepository;

    private DocumentInspector $documentInspector;

    private string $structureType;

    protected string $referenceSecurityContext;

    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        StructureManagerInterface $structureManager,
        ReferenceRepositoryInterface $referenceRepository,
        DocumentInspector $documentInspector,
        string $structureType,
        string $referenceSecurityContext
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
        $this->referenceRepository = $referenceRepository;
        $this->documentInspector = $documentInspector;
        $this->structureType = $structureType;
        $this->referenceSecurityContext = $referenceSecurityContext;
    }

    public function updateReferences(UuidBehavior&TitleBehavior&StructureBehavior $document, string $locale): ReferenceCollector
    {
        $referenceResourceKey = $this->getReferenceResourceKey($document);

        $workflowStage = $document instanceof WorkflowStageBehavior ? (int) $document->getWorkflowStage() : 0;

        $referenceCollector = new ReferenceCollector(
            $this->referenceRepository,
            $referenceResourceKey,
            $document->getUuid(),
            $locale,
            $document->getTitle(),
            $this->getReferenceSecurityContext($document),
            $document->getUuid(),
            $this->getReferenceSecurityObjectType(),
            $workflowStage
        );

        $structure = $document->getStructure();
        $templateStructure = $this->structureManager->getStructure($document->getStructureType(), $this->getStructureType());
        foreach ($templateStructure->getProperties(true) as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());

            if (!$contentType instanceof ReferenceContentTypeInterface) {
                continue;
            }

            $contentType->getReferences($property, $structure->getProperty($property->getName()), $referenceCollector);
        }

        $referenceCollector->persistReferences();

        return $referenceCollector;
    }

    public function removeReferences(UuidBehavior $document, ?string $locale = null): void
    {
        $locales = $locale ? [$locale] : $this->documentInspector->getLocales($document);

        foreach ($locales as $locale) {
            $this->referenceRepository->removeByReferenceResourceKeyAndId(
                $this->getReferenceResourceKey($document),
                $document->getUuid(),
                $locale
            );
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function getReferenceResourceKey(UuidBehavior $document): string
    {
        if (\defined(\get_class($document) . '::RESOURCE_KEY')) {
            return $document::RESOURCE_KEY; // @phpstan-ignore-line PHPStan does not detect the `defined` call
        }

        throw new \RuntimeException('ReferenceResourceKey must be defined');
    }

    private function getReferenceSecurityObjectType(): string
    {
        return SecurityBehavior::class;
    }

    private function getReferenceSecurityContext(StructureBehavior $document): string
    {
        return $this->referenceSecurityContext;
    }

    private function getStructureType(): string
    {
        return $this->structureType;
    }
}
