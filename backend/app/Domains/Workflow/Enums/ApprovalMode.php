<?php

namespace App\Domains\Workflow\Enums;

enum ApprovalMode: string
{
    case Single = 'single';
    case ParallelAny = 'parallel_any';
    case ParallelAll = 'parallel_all';
    case Quorum = 'quorum';
}
