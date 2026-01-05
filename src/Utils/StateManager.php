<?php

declare(strict_types=1);

namespace DartSass\Utils;

use DartSass\Compilers\CompilerContext;

use function array_pop;

class StateManager
{
    private array $stateStack = [];

    public function __construct(private readonly CompilerContext $context) {}

    public function push(): void
    {
        $this->stateStack[] = [
            'variables'       => $this->context->variableHandler->getVariables(),
            'mixins'          => $this->context->mixinHandler->getMixins(),
            'userFunctions'   => $this->context->functionHandler->getUserFunctions(),
            'loadedModules'   => $this->context->moduleHandler->getLoadedModules(),
            'extends'         => $this->context->extendHandler->getExtends(),
            'positionTracker' => $this->context->positionTracker->getState(),
            'mappings'        => $this->context->mappings,
            'options'         => $this->context->options,
        ];
    }

    public function pop(): void
    {
        $state = array_pop($this->stateStack);

        $this->context->variableHandler->setVariables($state['variables']);
        $this->context->mixinHandler->setMixins($state['mixins']);
        $this->context->functionHandler->setUserFunctions($state['userFunctions']);
        $this->context->moduleHandler->setLoadedModules($state['loadedModules']);
        $this->context->extendHandler->setExtends($state['extends']);
        $this->context->positionTracker->setState($state['positionTracker']);

        $this->context->mappings = $state['mappings'];
        $this->context->options  = $state['options'];
    }
}
