<?php

declare(strict_types=1);

namespace DartSass\Handlers;

use DartSass\Modules\ListModule;
use DartSass\Parsers\Nodes\ListNode;

use function array_slice;
use function count;
use function in_array;
use function intdiv;

class ListModuleHandler extends BaseModuleHandler
{
    private const SUPPORTED_FUNCTIONS = [
        'append', 'index', 'is-bracketed', 'join', 'length',
        'nth', 'separator', 'set-nth', 'slash', 'zip',
    ];

    public function __construct(private readonly ListModule $listModule) {}

    public function canHandle(string $functionName): bool
    {
        return in_array($functionName, self::SUPPORTED_FUNCTIONS, true);
    }

    public function handle(string $functionName, array $args): mixed
    {
        $processedArgs = $this->processSpecialArgs($functionName, $args);

        $functionMapping = [
            'is-bracketed' => 'isBracketed',
            'set-nth'      => 'setNth',
        ];

        $methodName = $functionMapping[$functionName] ?? $functionName;

        $result = $this->listModule->$methodName($processedArgs);

        return match (true) {
            $result === null => null,
            default => $result,
        };
    }

    public function getSupportedFunctions(): array
    {
        return self::SUPPORTED_FUNCTIONS;
    }

    public function getModuleNamespace(): string
    {
        return 'list';
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
