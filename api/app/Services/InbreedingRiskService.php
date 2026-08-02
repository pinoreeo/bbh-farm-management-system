<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\OffspringBirth;

class InbreedingRiskService
{
    /**
     * @return array{status:string, level:string, message:string, relationships:array<int, string>}
     */
    public function evaluate(int $sireId, int $damId): array
    {
        if ($sireId === $damId) {
            return $this->result('blocked', 'high', 'Pejantan dan betina tidak boleh kambing yang sama.', ['same_animal']);
        }

        $sire = Animal::find($sireId);
        $dam = Animal::find($damId);

        if (! $sire || ! $dam) {
            return $this->result('blocked', 'high', 'Pejantan atau betina tidak ditemukan.', ['missing_animal']);
        }

        $sireParents = $this->parentsOf($sireId);
        $damParents = $this->parentsOf($damId);
        $relationships = [];

        if (in_array($sireId, $damParents, true)) {
            return $this->result('blocked', 'high', 'Pejantan terdeteksi sebagai ayah dari betina tersebut. Perkawinan ayah dengan anak diblokir untuk mencegah incest/inbreeding.', ['sire_daughter']);
        }

        if (in_array($damId, $sireParents, true)) {
            return $this->result('blocked', 'high', 'Betina terdeteksi sebagai induk dari pejantan tersebut. Perkawinan induk dengan anak diblokir untuk mencegah incest/inbreeding.', ['dam_son']);
        }

        $sharedParents = array_values(array_intersect($sireParents, $damParents));
        if (count($sharedParents) >= 2) {
            return $this->result('blocked', 'high', 'Terdeteksi saudara kandung dari induk dan pejantan yang sama. Perkawinan ini diblokir.', ['full_sibling']);
        }

        if (count($sharedParents) === 1) {
            $relationships[] = 'half_sibling';
        }

        $sireAncestors = $this->ancestorsOf($sireId, 4);
        $damAncestors = $this->ancestorsOf($damId, 4);

        if (isset($sireAncestors[$damId]) || isset($damAncestors[$sireId])) {
            return $this->result('blocked', 'high', 'Terdeteksi hubungan leluhur langsung antara pejantan dan betina. Perkawinan ini diblokir untuk mencegah incest/inbreeding.', ['direct_ancestor']);
        }

        $commonAncestorIds = array_intersect(array_keys($sireAncestors), array_keys($damAncestors));
        if ($commonAncestorIds !== []) {
            $relationships[] = 'common_ancestor';
        }

        $relationships = array_values(array_unique($relationships));
        if ($relationships !== []) {
            return $this->result('blocked', 'high', 'Terdeteksi hubungan darah dekat antara pejantan dan betina. Betina tidak dapat dimasukkan ke koloni kawin ini untuk mencegah incest/inbreeding.', $relationships);
        }

        return $this->result('clear', 'low', 'Tidak ada hubungan darah dekat yang terdeteksi dari data silsilah yang tersedia.', []);
    }

    /**
     * @return array<int, int>
     */
    private function parentsOf(int $animalId): array
    {
        $birth = OffspringBirth::query()
            ->with('birthEvent')
            ->where('offspring_animal_id', $animalId)
            ->first();

        if (! $birth?->birthEvent) {
            return [];
        }

        return array_values(array_filter([
            $birth->birthEvent->dam_id ? (int) $birth->birthEvent->dam_id : null,
            $birth->birthEvent->sire_id ? (int) $birth->birthEvent->sire_id : null,
        ]));
    }

    /**
     * @return array<int, int>
     */
    private function ancestorsOf(int $animalId, int $maxDepth): array
    {
        $ancestors = [];
        $queue = [[$animalId, 0]];

        while ($queue !== []) {
            [$currentId, $depth] = array_shift($queue);
            if ($depth >= $maxDepth) {
                continue;
            }

            foreach ($this->parentsOf((int) $currentId) as $parentId) {
                $nextDepth = $depth + 1;
                if (! isset($ancestors[$parentId]) || $nextDepth < $ancestors[$parentId]) {
                    $ancestors[$parentId] = $nextDepth;
                    $queue[] = [$parentId, $nextDepth];
                }
            }
        }

        return $ancestors;
    }

    /**
     * @param  array<int, string>  $relationships
     * @return array{status:string, level:string, message:string, relationships:array<int, string>}
     */
    private function result(string $status, string $level, string $message, array $relationships): array
    {
        return compact('status', 'level', 'message', 'relationships');
    }
}
