<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Command;

use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DebugViewCommand extends Command
{
    protected static $defaultName = 'debug:sulu:admin:views';

    /**
     * @var ViewRegistry
     */
    private $viewRegistry;

    public function __construct(ViewRegistry $viewRegistry)
    {
        parent::__construct();
        $this->viewRegistry = $viewRegistry;
    }

    protected function configure()
    {
        $this->setDescription('Print all available views.')
            ->addArgument('view', InputArgument::OPTIONAL, 'Print a specified view by its name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $viewName = $input->getArgument('view');
        $ui = new SymfonyStyle($input, $output);

        if (!$viewName) {
            return $this->printAll($ui);
        }

        return $this->printSingle($viewName, $ui);
    }

    private function printAll(SymfonyStyle $ui): int
    {
        $dataList = [];

        foreach ($this->viewRegistry->getViews() as $view) {
            $view->getName();

            $dataList[$view->getName()] = [
                'name' => $view->getName(),
                'type' => $view->getType(),
            ];
        }

        ksort($dataList);

        $ui->table([
            'name',
            'type',
        ], $dataList);

        return 0;
    }

    private function printSingle(string $viewName, SymfonyStyle $ui): int
    {
        $view = $this->viewRegistry->findViewByName($viewName);

        $ui->section($view->getName());

        $dataList = [];
        $dataList[] = ['name', $view->getName()];
        $dataList[] = ['type', $view->getType()];
        $dataList[] = ['path', $view->getPath()];
        $dataList[] = ['parent', $view->getParent()];
        $dataList[] = ['options', json_encode($view->getOptions(), JSON_PRETTY_PRINT)];
        $dataList[] = ['attributeDefaults', json_encode($view->getAttributeDefaults(), JSON_PRETTY_PRINT)];
        $dataList[] = ['rerenderAttributes', json_encode($view->getRerenderAttributes(), JSON_PRETTY_PRINT)];

        $children = [];
        foreach ($this->viewRegistry->getViews() as $childView) {
            if ($childView->getParent() === $view->getName()) {
                $children[] = $childView->getName();
            }
        }

        $dataList[] = ['children', implode(PHP_EOL, $children)];

        $ui->table([
            'name',
            'value',
        ], $dataList);

        return 0;
    }
}
