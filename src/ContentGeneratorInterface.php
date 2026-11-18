<?php

declare(strict_types=1);

namespace App;

interface ContentGeneratorInterface
{
    public function generateBrief(array $idea, array $business): array;
}
