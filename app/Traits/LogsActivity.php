<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            static::logActivity($model, 'created', 'Създаден е запис');
        });

        static::updated(function ($model) {
            if ($model->wasChanged()) {
                $changes = [];
                foreach ($model->getChanges() as $key => $value) {
                    if ($key === 'updated_at') continue;
                    $changes[$key] = [
                        'old' => $model->getOriginal($key),
                        'new' => $value
                    ];
                }
                
                // Only log if we have actual changes other than updated_at
                if (!empty($changes)) {
                    static::logActivity($model, 'updated', 'Редактиран е запис', $changes);
                }
            }
        });

        static::deleted(function ($model) {
            static::logActivity($model, 'deleted', 'Изтрит е запис');
        });
    }

    protected static function logActivity($model, $action, $descriptionPrefix, $properties = null)
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }
        
        $subjectName = class_basename($model);
        if (isset($model->name)) {
            $subjectName .= ' "' . $model->name . '"';
        } elseif (isset($model->name_bg)) {
            $subjectName .= ' "' . $model->name_bg . '"';
        } elseif (isset($model->title)) {
            $subjectName .= ' "' . $model->title . '"';
        } elseif (isset($model->question)) {
            $subjectName .= ' "' . $model->question . '"';
        } elseif (isset($model->client_name)) {
            $subjectName .= ' "' . $model->client_name . '"';
        } elseif (isset($model->client_name_bg)) {
            $subjectName .= ' "' . $model->client_name_bg . '"';
        } else {
            $subjectName .= ' ID: ' . $model->id;
        }

        $description = $descriptionPrefix . ': ' . $subjectName;

        ActivityLog::create([
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'Система / Гост',
            'action' => $action,
            'subject_type' => get_class($model),
            'subject_id' => $model->id,
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}
