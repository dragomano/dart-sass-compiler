<?php

declare(strict_types=1);

namespace DartSass\Utils;

use DartSass\Handlers\ExtendHandler;
use DartSass\Handlers\FunctionHandler;
use DartSass\Handlers\MixinHandler;
use DartSass\Handlers\ModuleHandler;
use DartSass\Handlers\VariableHandler;

use function array_pop;

class StateManager
{
    private array $stateStack = [];

    public function __construct(
        private readonly VariableHandler $variableHandler,
        private readonly MixinHandler $mixinHandler,
        private readonly FunctionHandler $functionHandler,
        private readonly ModuleHandler $moduleHandler,
        private readonly ExtendHandler $extendHandler,
        private readonly PositionTracker $positionTracker
    ) {
    }

    public function push(array $mappings, array $options): void
    {
        $this->stateStack[] = [
            'variables'       => $this->variableHandler->getVariables(),
            'mixins'          => $this->mixinHandler->getMixins(),
            'userFunctions'   => $this->functionHandler->getUserFunctions(),
            'loadedModules'   => $this->moduleHandler->getLoadedModules(),
            'extends'         => $this->extendHandler->getExtends(),
            'positionTracker' => $this->positionTracker->getState(),
            'mappings'        => $mappings,
            'options'         => $options,
        ];
    }

    public function pop(): array
    {
        $state = array_pop($this->stateStack);

        $this->variableHandler->setVariables($state['variables']);
        $this->mixinHandler->setMixins($state['mixins']);
        $this->functionHandler->setUserFunctions($state['userFunctions']);
        $this->moduleHandler->setLoadedModules($state['loadedModules']);
        $this->extendHandler->setExtends($state['extends']);
        $this->positionTracker->setState($state['positionTracker']);

        return [
            'mappings' => $state['mappings'],
            'options'  => $state['options'],
        ];
    }
}
