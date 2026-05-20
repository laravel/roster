<?php

declare(strict_types=1);

namespace Laravel\Roster\Enums;

enum Editor: string
{
    case PHPSTORM = 'phpstorm';
    case VSCODE = 'vscode';
    case ZED = 'zed';
    case SUBLIME_TEXT = 'sublime-text';
}
