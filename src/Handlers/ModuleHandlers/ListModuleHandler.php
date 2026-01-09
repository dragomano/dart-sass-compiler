<?php

declare(strict_types=1);

namespace DartSass\Handlers\ModuleHandlers;

use DartSass\Handlers\SassModule;
use DartSass\Modules\ListModule;
use DartSass\Parsers\Nodes\ListNode;

use function array_slice;
use function count;
use function intdiv;

class ListModuleHandler extends BaseModuleHandler
{
    protected const MODULE_FUNCTIONS = [
        'append',
        'index',
        'is-bracketed',
        'join',
        'length',
        'separator',
        'nth',
        'set-nth',
        'slash',
        'zip',
    ];

    protected const GLOBAL_FUNCTIONS = [
        'append',
        'index',
        'is-bracketed',
        'join',
        'length',
        'list-separator',
        'nth',
        'set-nth',
        'zip',
    ];

    public function __construct(private readonly ListModule $listModule) {}

    public function handle(string $functionName, array $args): mixed
    {
        $processedArgs = $this->processSpecialArgs($functionName, $args);

        $functionMapping = [
            'is-bracketed'   => 'isBracketed',
            'set-nth'        => 'setNth',
            'list-separator' => 'separator',
        ];

        $methodName = $functionMapping[$functionName] ?? $functionName;

        $result = $this->listModule->$methodName($processedArgs);

        return match (true) {
            $result === null => null,
            default => $result,
        };
    }

    public function getModuleNamespace(): SassModule
    {
        return SassModule::LIST;
    }

    private function processSpecialArgs(string $functionName, array $args): array
    {
        return match ($functionName) {
            'append' => $this->processAppendArgs($args),
            'length' => $this->processLengthArgs($args),
            'join'   => $this->processJoinArgs($args),
            'nth'    => $this->processNthArgs($args),
            default  => $this->normalizeArgs($args),
        };
    }

    private function processAppendArgs(array $args): array
    {
        if (count($args) > 2 && ! isset($args['$separator'])) {
            $val = $args[count($args) - 1];

            $listArgs = array_slice($args, 0, count($args) - 1);
            $listNode = new ListNode($listArgs, 0, 'space');

            return [$listNode, $val];
        }

        return $args;
    }

    private function processLengthArgs(array $args): array
    {
        if (count($args) > 1 && ! isset($args['$separator'])) {
            $listNode = new ListNode($args, 0, 'space');

            return [$listNode];
        }

        return $args;
    }

    private function processJoinArgs(array $args): array
    {
        if (count($args) > 2 && ! isset($args['$separator'])) {
            $midPoint  = intdiv(count($args), 2);
            $list1Args = array_slice($args, 0, $midPoint);
            $list2Args = array_slice($args, $midPoint);
            $list1Node = new ListNode($list1Args, 0, 'space');
            $list2Node = new ListNode($list2Args, 0, 'space');

            return [$list1Node, $list2Node];
        }

        return $args;
    }

    private function processNthArgs(array $args): array
    {
        return $args;
    }
}
