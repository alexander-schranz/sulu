<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Api;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CoreBundle\Entity\ApiEntityWrapper;


class Category extends ApiEntityWrapper
{
    /**
     * Returns the children of a category.
     *
     * @VirtualProperty
     * @SerializedName("children")
     * @Groups({"fullCategory"})
     *
     * @return Category[]
     */
    public function getChildren()
    {
        $children = [];
        if ($this->entity->getChildren()) {
            foreach ($this->entity->getChildren() as $child) {
                $children[] = new self($child, $this->locale);
            }
        }

        return $children;
    }
}
