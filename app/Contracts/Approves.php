<?php

namespace App\Contracts;

interface Approves
{
    public function moduleCode(): string;
    public function subjectId(): int;
    public function flowName(): string;
    public function firstPayload(): array;
    public function onFinalApproved(): void;
    public function actorEmployeeId(): int;
    public function targetEmployeeId(): int;
}
