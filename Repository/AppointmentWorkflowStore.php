<?php
require_once __DIR__ . '/AppointmentReadStore.php';

interface AppointmentWorkflowStore extends AppointmentReadStore
{
    public function findById(int $id): ?array;

    /** Run an operation while a confirmed or already attended appointment is locked against status changes. */
    public function withStudyUploadEligibleAppointment(int $id, callable $operation);

    /** Mark a confirmed appointment attended; returns false when its current state disallows the transition. */
    public function markAttended(int $id): bool;
}
