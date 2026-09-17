<?php

namespace App\Filament\Resources\GameStickerResource\Pages;

use App\Filament\Resources\GameStickerResource;
use App\Http\Controllers\GameController;
use Filament\Resources\Pages\CreateRecord;

class CreateGameSticker extends CreateRecord
{
    protected static string $resource = GameStickerResource::class;

    /**
     * Prefill from ?target=…&loc=… so the statistics page can offer "Създай стикер"
     * for a location that was scanned but never registered.
     */
    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $this->form->fill();

        $this->data['target'] = GameController::sanitizeTarget(request()->query('target', 'prom'));

        if (($loc = GameController::sanitizeLoc(request()->query('loc'))) !== null) {
            $this->data['loc'] = $loc;
        }

        $this->callHook('afterFill');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
