<?php

namespace App\Services;

interface ApiInterface
{
    public function read(): void;

    public function create(): void;

    public function update(): void;

    public function delete(): void;
}