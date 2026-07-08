<?php

namespace App\Enums;

enum SyncStatus: string
{
    case IDLE    = 'idle';
    case RUNNING = 'running';
    case DONE    = 'done';
    case FAILED  = 'failed';
}