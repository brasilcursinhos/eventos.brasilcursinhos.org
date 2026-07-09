<?php

function disableCache(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

function enableCache(int $seconds=3600): void
{
    header("Cache-Control: public, max-age={$seconds}");
    header('Pragma: cache');
}