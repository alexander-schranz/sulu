<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * @ExclusionPolicy("all")
 */
class Category implements CategoryInterface
{
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
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $defaultLocale;

    /**
     * @var CategoryInterface
     */
    protected $parent;

    /**
     * @var UserInterface
     */
    protected $creator;

    /**
     * @var UserInterface
     */
    protected $changer;

    /**
     * @var Collection|CategoryMetaInterface[]
     */
    protected $meta;

    /**
     * @var Collection|CategoryTranslationInterface[]
     */
    protected $translations;

    /**
     * @var Collection|CategoryInterface[]
     */
    protected $children;

    /**
     * @var string|null
     */
    protected $currentLocale;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->meta = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * @VirtualProperty
     * @SerializedName("locale")
     *
     * @return string|null
     */
    public function getLocale(): ?string
    {
        $translation = $this->getTranslation(true);

        if (!$translation) {
            return null;
        }

        return $translation->getLocale();
    }

    public function setLocale(?string $locale): self
    {
        $this->currentLocale = $locale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * {@inheritdoc}
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * {@inheritdoc}
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @VirtualProperty
     * @SerializedName("created")
     * @Groups({"fullCategory"})
     *
     * {@inheritdoc}
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @VirtualProperty
     * @SerializedName("key")
     * @Groups({"fullCategory","partialCategory"})
     *
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("defaultLocale")
     * @Groups({"fullCategory","partialCategory"})
     *
     * {@inheritdoc}
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * @VirtualProperty
     * @SerializedName("changed")
     * @Groups({"fullCategory"})
     *
     * {@inheritdoc}
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @VirtualProperty
     * @SerializedName("id")
     * @Groups({"fullCategory","partialCategory"})
     *
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setParent(CategoryInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns a the id of the parent category, if one exists.
     * This method is used to serialize the parent-id.
     *
     * @VirtualProperty
     * @Groups({"fullCategory","partialCategory"})
     *
     * @return null|self
     */
    public function getParentId(): ?int
    {
        if (!$this->parent) {
            return null;
        }

        return $this->parent->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * {@inheritdoc}
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setChanged(\DateTime $changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * {@inheritdoc}
     */
    public function addMeta(CategoryMetaInterface $meta)
    {
        $this->meta[] = $meta;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeMeta(CategoryMetaInterface $meta)
    {
        $this->meta->removeElement($meta);
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * {@inheritdoc}
     */
    public function addTranslation(CategoryTranslationInterface $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeTranslation(CategoryTranslationInterface $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * {@inheritdoc}
     */
    public function findTranslationByLocale($locale)
    {
        return $this->translations->filter(
            function(CategoryTranslationInterface $translation) use ($locale) {
                return $translation->getLocale() === $locale;
            }
        )->first();
    }

    /**
     * {@inheritdoc}
     */
    public function addChildren(CategoryInterface $child)
    {
        @trigger_error(__METHOD__ . '() is deprecated since version 1.4 and will be removed in 2.0. Use addChild() instead.', E_USER_DEPRECATED);

        $this->addChild($child);
    }

    /**
     * {@inheritdoc}
     */
    public function addChild(CategoryInterface $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeChildren(CategoryInterface $child)
    {
        @trigger_error(__METHOD__ . '() is deprecated since version 1.4 and will be removed in 2.0. Use removeChild() instead.', E_USER_DEPRECATED);

        $this->removeChild($child);
    }

    /**
     * {@inheritdoc}
     */
    public function removeChild(CategoryInterface $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Returns the name of the Category dependent on the locale.
     *
     * @VirtualProperty
     * @SerializedName("name")
     * @Groups({"fullCategory","partialCategory"})
     *
     * @return string
     */
    public function getName()
    {
        if (null === ($translation = $this->getTranslation(true))) {
            return;
        }

        return $translation->getTranslation();
    }

    /**
     * Returns the description of the Category dependent on the locale.
     *
     * @VirtualProperty
     * @SerializedName("description")
     * @Groups({"fullCategory","partialCategory"})
     *
     * @return string
     */
    public function getDescription()
    {
        if (null === ($translation = $this->getTranslation(true))) {
            return;
        }

        return $translation->getDescription();
    }

    /**
     * Returns the medias of the Category dependent on the locale.
     *
     * @VirtualProperty
     * @SerializedName("medias")
     * @Groups({"fullCategory","partialCategory"})
     *
     * @return string
     */
    public function getMediasRawData()
    {
        if (null === ($translation = $this->getTranslation(true))) {
            return ['ids' => []];
        }

        $ids = [];
        foreach ($translation->getMedias() as $media) {
            $ids[] = $media->getId();
        }

        return ['ids' => $ids];
    }

    /**
     * Returns the medias of the Category dependent on the locale.
     *
     * @return Media[]
     */
    public function getMedias()
    {
        if (null === ($translation = $this->getTranslation(true))) {
            return [];
        }

        $medias = [];
        foreach ($translation->getMedias() as $media) {
            $medias[] = $media->setLocale($this->currentLocale);
        }

        return $medias;
    }
    /**
     * @VirtualProperty
     * @SerializedName("meta")
     * @Groups({"fullCategory","partialCategory"})
     *
     * @return array
     */
    public function getCurrentMeta()
    {
        $arrReturn = [];
        $metaList = $this->getMeta();
        if (null === $metaList) {
            return [];
        }

        foreach ($metaList as $meta) {
            if (!$meta->getLocale() || $meta->getLocale() === $this->currentLocale) {
                array_push(
                    $arrReturn,
                    [
                        'id' => $meta->getId(),
                        'key' => $meta->getKey(),
                        'value' => $meta->getValue(),
                    ]
                );
            }
        }

        return $arrReturn;
    }

    /**
     * Returns the creator of the category.
     *
     * @VirtualProperty
     * @SerializedName("creator")
     * @Groups({"fullCategory"})
     *
     * @return string
     */
    public function getCreatorFullName()
    {
        $strReturn = '';
        $creator = $this->getCreator();
        if ($creator) {
            return $creator->getFullName();
        }

        return $strReturn;
    }

    /**
     * Returns the changer of the category.
     *
     * @VirtualProperty
     * @SerializedName("changer")
     * @Groups({"fullCategory"})
     *
     * @return string
     */
    public function getChangerFullName()
    {
        $strReturn = '';
        $changer = $this->getChanger();
        if ($changer) {
            return $changer->getFullName();
        }

        return $strReturn;
    }

    /**
     * Sets a translation to the entity.
     * If no other translation was assigned before, the translation is added as default.
     *
     * @param CategoryTranslationInterface $translation
     */
    public function setTranslation(CategoryTranslationInterface $translation)
    {
        $translationEntity = $this->getTranslationByLocale($translation->getLocale());

        if (!$translationEntity) {
            $translationEntity = $translation;
            $this->addTranslation($translationEntity);
        }

        $translationEntity->setCategory($this);
        $translationEntity->setTranslation($translation->getTranslation());
        $translationEntity->setLocale($translation->getLocale());

        if (null === $this->getId() && null === $this->getDefaultLocale()) {
            // new entity and new translation
            // save first locale as default
            $this->setDefaultLocale($translationEntity->getLocale());
        }
    }

    /**
     * Takes meta as array and sets it to the entity.
     *
     * @param CategoryMetaInterface[] $metaEntities
     *
     * @return self
     */
    public function setMeta($metaEntities)
    {
        $currentMeta = $this->getMeta();
        foreach ($metaEntities as $singleMeta) {
            $metaEntity = $this->getSingleMetaById($currentMeta, $singleMeta->getId());
            if (!$metaEntity) {
                $metaEntity = $singleMeta;
                $this->addMeta($metaEntity);
            }

            $metaEntity->setCategory($this);
            $metaEntity->setKey($singleMeta->getKey());
            $metaEntity->setValue($singleMeta->getValue());
            $metaEntity->setLocale($singleMeta->getLocale());
        }

        return $this;
    }

    /**
     * Returns the keywords of the category translations.
     *
     * @return string[]
     */
    public function getKeywords()
    {
        $keywords = [];

        $translation = $this->getTranslation(true);

        if (!$translation) {
            return $keywords;
        }

        foreach ($translation->getKeywords() as $keyword) {
            $keywords[] = $keyword->getKeyword();
        }

        return $keywords;
    }

    /**
     * Takes an array of CollectionMeta and returns a single meta for a given id.
     *
     * @param $meta
     * @param $id
     *
     * @return CollectionMeta
     */
    private function getSingleMetaById($meta, $id)
    {
        if (null !== $id) {
            foreach ($meta as $singleMeta) {
                if ($singleMeta->getId() === $id) {
                    return $singleMeta;
                }
            }
        }
    }

    /**
     * Returns an array representation of the object.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'key' => $this->getKey(),
            'name' => $this->getName(),
            'meta' => $this->getCurrentMeta(),
            'keywords' => $this->getKeywords(),
            'defaultLocale' => $this->getDefaultLocale(),
            'creator' => $this->getCreatorFullName(),
            'changer' => $this->getChangerFullName(),
            'created' => $this->getCreated(),
            'changed' => $this->getChanged(),
        ];
    }

    /**
     * Returns the translation with the current locale.
     *
     * @param $withDefault
     *
     * @return CategoryTranslationInterface
     */
    public function getTranslation($withDefault = false)
    {
        $translation = $this->getTranslationByLocale($this->currentLocale);

        if (true === $withDefault && null === $translation && null !== $this->getDefaultLocale()) {
            return $this->getTranslationByLocale($this->getDefaultLocale());
        }

        return $translation;
    }

    /**
     * Returns the translation with the given locale.
     *
     * @param string $locale
     *
     * @return CategoryTranslationInterface
     */
    public function getTranslationByLocale($locale)
    {
        if (null !== $locale) {
            foreach ($this->getTranslations() as $translation) {
                if ($translation->getLocale() == $locale) {
                    return $translation;
                }
            }
        }
    }
}
