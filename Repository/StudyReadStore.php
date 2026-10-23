<?php
interface StudyReadStore
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array;
}
