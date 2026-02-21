<?php

declare(strict_types=1);

namespace DartSass\Parsers\Nodes;

enum NodeType: string
{
    case AT_ROOT             = 'at-root';
    case AT_RULE             = 'at_rule';
    case COLOR               = 'color';
    case COMMENT             = 'comment';
    case CONDITION           = 'condition';
    case CONTAINER           = 'container';
    case CSS_CUSTOM_PROPERTY = 'css_custom_property';
    case CSS_PROPERTY        = 'css_property';
    case DEBUG               = 'debug';
    case EACH                = 'each';
    case ERROR               = 'error';
    case FOR                 = 'for';
    case FORWARD             = 'forward';
    case FUNCTION            = 'function';
    case HEX_COLOR           = 'hex_color';
    case IDENTIFIER          = 'identifier';
    case IF                  = 'if';
    case INCLUDE             = 'include';
    case INTERPOLATION       = 'interpolation';
    case KEYFRAMES           = 'keyframes';
    case LIST                = 'list';
    case MAP                 = 'map';
    case MEDIA               = 'media';
    case MIXIN               = 'mixin';
    case NULL                = 'null';
    case NUMBER              = 'number';
    case OPERATION           = 'operation';
    case OPERATOR            = 'operator';
    case PROPERTY_ACCESS     = 'property_access';
    case RETURN              = 'return';
    case RULE                = 'rule';
    case SELECTOR            = 'selector';
    case STRING              = 'string';
    case SUPPORTS            = 'supports';
    case UNARY               = 'unary';
    case UNKNOWN             = 'unknown';
    case USE                 = 'use';
    case VARIABLE            = 'variable';
    case WARN                = 'warn';
    case WHILE               = 'while';
}
