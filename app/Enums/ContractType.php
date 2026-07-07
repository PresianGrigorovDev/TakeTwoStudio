<?php

namespace App\Enums;

enum ContractType: string
{
    case Wedding = 'wedding';
    case Prom = 'prom';
    case Event = 'event';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Wedding => 'Сватбен договор',
            self::Prom => 'Абитуриентски договор',
            self::Event => 'Събитиен / Корпоративен договор',
            self::Custom => 'Друг тип договор',
        };
    }

    public function pdfView(): string
    {
        return "pdf.contracts.{$this->value}";
    }

    public function configKey(): string
    {
        return $this->value;
    }
}
