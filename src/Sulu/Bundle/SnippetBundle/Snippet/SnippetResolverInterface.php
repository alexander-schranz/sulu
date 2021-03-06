<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

/**
 * Resolves snippets by UUIDs.
 */
interface SnippetResolverInterface
{
    /**
     * Load snippet and resolves them by UUID.
     *
     * @param string[] $uuids
     * @param string $webspaceKey
     * @param string $locale
     * @param string $shadowLocale
     * @param bool $loadExcerpt
     *
     * @return array
     */
    public function resolve($uuids, $webspaceKey, $locale, $shadowLocale = null, $loadExcerpt = false);
}
