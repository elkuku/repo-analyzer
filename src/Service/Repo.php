<?php

namespace App\Service;

use Kbjr\Git\GitRepo;

class Repo extends GitRepo
{
    public function statusObject(): array
    {
        $lines = explode("\n", $this->run('status --porcelain -u'));

        $entries = [];
        foreach ($lines as $line) {
            if (!$line) {
                continue;
            }
            $code = trim(substr($line, 0, 2));
            $entry = substr($line, 3);

            $entries[$code][] = $entry;
        }

        return $entries;
    }
}
