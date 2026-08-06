<?php

namespace App\Services;

use App\Enums\ContractType;
use App\Models\TeamMember;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfInstance;

class ContractPdfService
{
    public function generate(array $data): DomPdfInstance
    {
        $contractType = ContractType::from($data['contract_type']);

        $executors = TeamMember::whereIn('id', $data['executor_ids'] ?? [])->get();

        $logoPath = public_path('css/img/logo-tts-white.webp');

        $viewData = [
            'contractType' => $contractType,
            'data' => $data,
            'executors' => $executors,
            'logoPath' => $logoPath,
            'sections' => $this->resolveSections($data, $contractType),
            'contractNumber' => $data['contract_number'] ?: $this->generateContractNumber($contractType),
            'contractDate' => $data['contract_date'],
        ];

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($contractType->pdfView(), $viewData);
        } elseif (class_exists('Barryvdh\DomPDF\Facade')) {
            /** @var mixed $facadeClass */
            $facadeClass = 'Barryvdh\DomPDF\Facade';
            $pdf = $facadeClass::loadView($contractType->pdfView(), $viewData);
        } else {
            $pdf = app('dompdf.wrapper')->loadView($contractType->pdfView(), $viewData);
        }

        return $pdf
            ->setPaper('a4')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', true)
            ->setOption('dpi', 150);
    }

    private function resolveSections(array $data, ContractType $type): array
    {
        $configSections = config("contracts.{$type->configKey()}", []);

        $sections = [];
        foreach ($configSections as $key => $defaultText) {
            // Skip sections that user explicitly toggled off
            $includeKey = "include_{$key}";
            if (array_key_exists($includeKey, $data) && !$data[$includeKey]) {
                continue;
            }

            if (($data["use_custom_{$key}"] ?? false) && !empty($data["section_{$key}"])) {
                $sections[$key] = $data["section_{$key}"];
            } else {
                $sections[$key] = $defaultText;
            }
        }

        return $sections;
    }

    private function generateContractNumber(ContractType $type): string
    {
        $prefix = match ($type) {
            ContractType::Wedding => 'TTS-W',
            ContractType::Prom => 'TTS-P',
            ContractType::Event => 'TTS-E',
            ContractType::Custom => 'TTS-C',
        };

        return $prefix . '-' . now()->format('Y-md-His');
    }

    public function getFilename(array $data): string
    {
        $type = ContractType::from($data['contract_type']);
        $date = $data['contract_date'] ?? now()->format('Y-m-d');

        return 'TTS-' . strtoupper($type->value) . "-{$date}.pdf";
    }
}
