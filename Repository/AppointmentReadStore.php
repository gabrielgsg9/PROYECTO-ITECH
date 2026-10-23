<?php
interface AppointmentReadStore
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array;
}
