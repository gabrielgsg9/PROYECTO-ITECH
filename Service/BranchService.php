<?php
require_once __DIR__ . '/../Repository/BranchRepository.php';

/** Validaciones de sucursales independientes del adaptador de persistencia. */
class BranchService
{
    private BranchRepository $repository;

    public function __construct(?BranchRepository $repository = null)
    {
        $this->repository = $repository ?? new BranchRepository();
    }

    public function all(): array
    {
        return $this->repository->all();
    }

    public function findById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function create(string $name, string $address): array
    {
        [$name, $address] = $this->validate($name, $address);
        return $this->repository->create(['nombre' => $name, 'direccion' => $address, 'activo' => true]);
    }

    public function update(int $id, string $name, string $address): ?array
    {
        if ($id < 1) {
            throw new InvalidArgumentException('La sucursal indicada no es válida.');
        }
        [$name, $address] = $this->validate($name, $address);
        return $this->repository->update($id, $name, $address);
    }

    public function setActive(int $id, bool $active): bool
    {
        if ($id < 1) {
            throw new InvalidArgumentException('La sucursal indicada no es válida.');
        }
        return $this->repository->setActive($id, $active);
    }

    private function validate(string $name, string $address): array
    {
        $name = trim($name);
        $address = trim($address);
        if ($name === '' || $address === '') {
            throw new InvalidArgumentException('Completá el nombre y la dirección.');
        }
        if (mb_strlen($name, 'UTF-8') > 150 || mb_strlen($address, 'UTF-8') > 255) {
            throw new InvalidArgumentException('El nombre admite hasta 150 caracteres y la dirección hasta 255.');
        }
        return [$name, $address];
    }
}
