<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Hateoas\Configuration\Annotation\Embedded;
use Hateoas\Configuration\Annotation\Relation;
use Hateoas\Configuration\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * @ExclusionPolicy("all")
 * FIXME Remove limit = 9999 after create cget without pagination
 * @Relation(
 *      "all",
 *      href = @Route(
 *          "sulu_media.cget_media",
 *          parameters = { "collection" = "expr(object.getId())", "limit" = 9999, "locale" = "expr(object.getLocale())" }
 *      )
 * )
 * @Relation(
 *      "filterByTypes",
 *      href = @Route(
 *          "sulu_media.cget_media",
 *          parameters = {
 *              "collection" = "expr(object.getId())",
 *              "types" = "{types}",
 *              "locale" = "expr(object.getLocale())"
 *          }
 *      )
 * )
 * @Relation(
 *      "self",
 *      href = @Route(
 *          "sulu_media.get_collection",
 *          parameters = { "id" = "expr(object.getId())", "locale" = "expr(object.getLocale())" }
 *      )
 * )
 * @Relation(
 *      "children",
 *      href = @Route(
 *          "sulu_media.get_collection",
 *          parameters = { "id" = "expr(object.getId())", "depth" = 1, "sortBy": "title", "locale" = "expr(object.getLocale())" }
 *      )
 * )
 * @Relation(
 *     name = "collections",
 *     embedded = @Embedded(
 *         "expr(object.getCurrentChildren())",
 *         xmlElementName = "collections"
 *     )
 * )
 * @Relation(
 *     name = "parent",
 *     embedded = @Embedded(
 *         "expr(object.getCurrentParent())",
 *         xmlElementName = "parent"
 *     )
 * )
 * @Relation(
 *     name = "breadcrumb",
 *     embedded = @Embedded(
 *         "expr(object.getBreadcrumb())",
 *         xmlElementName = "breadcrumb"
 *     )
 * )
 *
 * Collection.
 */
class Collection implements CollectionInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $style;

    /**
     * @var int
     */
    protected $lft;

    /**
     * @var int
     */
    protected $rgt;

    /**
     * @var int
     */
    protected $depth;

    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \DateTime
     */
    protected $changed;

    /**
     * @var CollectionType
     */
    protected $type;

    /**
     * @var UserInterface
     */
    protected $changer;

    /**
     * @var UserInterface
     */
    protected $creator;

    /**
     * @var string
     */
    private $key;

    /**
     * @var DoctrineCollection|CollectionMeta[]
     */
    private $meta;

    /**
     * @var DoctrineCollection|MediaInterface[]
     */
    private $media;

    /**
     * @var DoctrineCollection|CollectionInterface[]
     */
    private $children;

    /**
     * @var CollectionInterface
     */
    private $parent;

    /**
     * @var CollectionMeta
     */
    private $defaultMeta;

    /**
     * @var string|null
     */
    private $currentLocale;


    /**
     * @var array|null
     */
    protected $currentPreview;

    /**
     * @var array
     */
    protected $currentProperties = [];

    /**
     * @var array|null
     */
    protected $currentBreadcrumb;

    /**
     * @var int
     */
    protected $currentMediaCount = 0;

    /**
     * @var int
     */
    protected $currentSubCollectionCount = 0;

    /**
     * @var self|null
     */
    protected $currentParent;

    /**
     * @var array|null
     */
    protected $currentChildren;

    public function __construct()
    {
        $this->meta = new ArrayCollection();
        $this->media = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * @VirtualProperty
     * @SerializedName("locale")
     *
     * @return string
     */
    public function getLocale(): ?string
    {
        return $this->currentLocale;
    }

    public function setLocale(?string $locale): self
    {
        $this->currentLocale = $locale;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("id")
     *
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return CollectionInterface
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return CollectionInterface
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    public function getCreatorName(): ?string
    {
        $user = $this->getCreator();
        if ($user) {
            return $user->getFullName();
        }

        return null;
    }

    /**
     * Set style.
     *
     * @param string $style
     *
     * @return CollectionInterface
     */
    public function setStyle($style)
    {
        if (!is_string($style)) {
            $style = json_encode($style);
        }

        $this->style = $style;

        return $this;
    }

    /**
     * Get style.
     *
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @VirtualProperty
     * @SerializedName("style")
     *
     * @return array
     */
    public function getStyleData()
    {
        if (!$this->style) {
            return [];
        }

        return json_decode($this->style, true);
    }

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return CollectionInterface
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft.
     *
     * @return int
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt.
     *
     * @param int $rgt
     *
     * @return CollectionInterface
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt.
     *
     * @return int
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set depth.
     *
     * @param int $depth
     *
     * @return CollectionInterface
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Get depth.
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @VirtualProperty
     * @SerializedName("created")
     *
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @VirtualProperty
     * @SerializedName("changed")
     *
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Set type.
     *
     * @param CollectionType $type
     *
     * @return CollectionInterface
     */
    public function setType(CollectionType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("type")
     *
     * Get type.
     *
     * @return CollectionType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @VirtualProperty
     * @SerializedName("key")
     *
     * Set key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get key.
     *
     * @param string $key
     *
     * @return CollectionInterface
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return DoctrineCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Indicates if sub collections exists.
     *
     * @VirtualProperty
     * @SerializedName("hasChildren")
     *
     * @return bool
     */
    public function getHasChildren()
    {
        if ($this->currentSubCollectionCount > 0) {
            return true;
        }

        if (null !== ($children = $this->getChildren())) {
            return $children->count() > 0;
        }

        return false;
    }

    /**
     * @param DoctrineCollection $children
     */
    public function setChildren(DoctrineCollection $children)
    {
        $this->children = $children;
    }

    /**
     * Set parent.
     *
     * @param CollectionInterface $parent
     *
     * @return CollectionInterface
     */
    public function setParent(CollectionInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return CollectionInterface|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function getParentId(): ?int
    {
        if (!$this->parent) {
            return null;
        }

        return $this->parent->getId();
    }

    /**
     * Add meta.
     *
     * @param CollectionMeta $meta
     *
     * @return Collection
     */
    public function addMeta(CollectionMeta $meta)
    {
        $this->meta[] = $meta;

        return $this;
    }

    /**
     * Remove meta.
     *
     * @param CollectionMeta $meta
     */
    public function removeMeta(CollectionMeta $meta)
    {
        $this->meta->removeElement($meta);
    }

    /**
     * Get meta.
     *
     * @return DoctrineCollection|CollectionMeta[]
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Add media.
     *
     * @param MediaInterface $media
     *
     * @return Collection
     */
    public function addMedia(MediaInterface $media)
    {
        $this->media[] = $media;

        return $this;
    }

    /**
     * Remove media.
     *
     * @param MediaInterface $media
     */
    public function removeMedia(MediaInterface $media)
    {
        $this->media->removeElement($media);
    }

    /**
     * Get media.
     *
     * @return DoctrineCollection|MediaInterface[]
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Add children.
     *
     * @param CollectionInterface $children
     *
     * @return Collection
     */
    public function addChildren(CollectionInterface $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Add children.
     *
     * @param CollectionInterface $child
     *
     * @return Collection
     */
    public function addChild(CollectionInterface $child)
    {
        $this->addChildren($child);

        return $this;
    }

    /**
     * Remove children.
     *
     * @param CollectionInterface $children
     */
    public function removeChildren(CollectionInterface $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Remove children.
     *
     * @param CollectionInterface $child
     */
    public function removeChild(CollectionInterface $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Set defaultMeta.
     *
     * @param CollectionMeta $defaultMeta
     *
     * @return Collection
     */
    public function setDefaultMeta(CollectionMeta $defaultMeta = null)
    {
        $this->defaultMeta = $defaultMeta;

        return $this;
    }

    /**
     * Get defaultMeta.
     *
     * @return CollectionMeta
     */
    public function getDefaultMeta()
    {
        return $this->defaultMeta;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.media.collections';
    }

    /**
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription(?string $description): self
    {
        $this->getCurrentMeta(true)->setDescription($description);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("description")
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        $meta = $this->getCurrentMeta();
        if ($meta) {
            return $meta->getDescription();
        }

        return null;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(?string $title): self
    {
        $this->getCurrentMeta(true)->setTitle($title);

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("title")
     *
     * @return string
     */
    public function getTitle(): ?string
    {
        $meta = $this->getCurrentMeta();
        if ($meta) {
            return $meta->getTitle();
        }

        return null;
    }

    /**
     * @VirtualProperty
     * @SerializedName("locked")
     *
     * @return string
     */
    public function getLocked()
    {
        $type = $this->getType();

        return !$type || SystemCollectionManagerInterface::COLLECTION_TYPE === $type->getKey();
    }

    /**
     * @param bool $create
     *
     * @return CollectionMeta
     */
    private function getCurrentMeta($create = false)
    {
        $locale = $this->getLocale();

        // get meta only with this locale
        $metaCollectionFiltered = $this->meta->filter(
            function($meta) use ($locale) {
                /** @var CollectionMeta $meta */
                if ($meta->getLocale() == $locale) {
                    return true;
                }

                return false;
            }
        );

        // check if meta was found
        if ($metaCollectionFiltered->isEmpty()) {
            if ($create) {
                // create when not found
                $meta = new CollectionMeta();
                $meta->setLocale($locale);
                $meta->setCollection($this);
                $this->addMeta($meta);

                return $meta;
            }

            // return first when create false
            return $this->getDefaultMeta();
        }

        // return exists
        return $metaCollectionFiltered->first();
    }

    /**
     * @VirtualProperty
     * @SerializedName("breadcrumb")
     */
    public function getBreadcrumb(): ?array
    {
        return $this->currentBreadcrumb;
    }

    /**
     * @param array|null $breadcrumb
     */
    public function setBreadcrumb(?array $breadcrumb): self
    {
        $this->currentBreadcrumb = $breadcrumb;

        return $this;
    }

    /**
     * @param array $properties
     *
     * @return $this
     */
    public function setProperties(array $properties): self
    {
        $this->currentProperties = $properties;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("properties")
     *
     * @return array
     */
    public function getProperties(): array
    {
        return $this->currentProperties;
    }

    /**
     * @param array $children
     *
     * @return $this
     */
    public function setCurrentChildren(array $children): self
    {
        $this->currentChildren = $children;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("children")
     *
     * @return array
     */
    public function getCurrentChildren(): ?array
    {
        return $this->currentChildren;
    }

    /**
     * @param self|null $parent
     *
     * @return $this
     */
    public function setCurrentParent(?self $parent): self
    {
        $this->currentParent = $parent;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("parent")
     *
     * @return self|null
     */
    public function getCurrentParent(): ?self
    {
        return $this->currentParent;
    }

    /**
     * @VirtualProperty
     * @SerializedName("preview")
     *
     * @return array|null
     */
    public function getPreview(): ?array
    {
        return $this->currentPreview;
    }

    /**
     * @param array|null $preview
     *
     * @return $this
     */
    public function setPreview(?array $preview): self
    {
        $this->currentPreview = $preview;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("mediaCount")
     */
    public function getMediaCount(): int
    {
        return $this->currentMediaCount;
    }

    public function setMediaCount(int $mediaCount): self
    {
        $this->currentMediaCount = $mediaCount;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("subCollectionCount")
     *
     * @return int The number of sub collections contained by the collection
     */
    public function getSubCollectionCount(): int
    {
        return $this->currentSubCollectionCount;
    }

    public function setSubCollectionCount(int $subCollectionCount): self
    {
        $this->currentSubCollectionCount = $subCollectionCount;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("objectCount")
     *
     * Returns the total number of all types of sub objects of this collection.
     *
     * @return int
     */
    public function getObjectCount()
    {
        return $this->getMediaCount() + $this->getSubCollectionCount();
    }
}
