<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Polyclinic extends Model
{
    use SoftDeletes;
    protected $fillable = ['code', 'name', 'description'];

    protected static function booted()
    {
        static::deleted(function ($polyclinic) {
            foreach ($polyclinic->doctors as $doctor) {
                $doctor->delete(); // This triggers Doctor's booted() which deletes schedules!
            }
        });

        static::restored(function ($polyclinic) {
            foreach ($polyclinic->doctors()->onlyTrashed()->get() as $doctor) {
                $doctor->restore(); // This triggers Doctor's booted() which restores schedules!
            }
        });
    }

    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    public static function getAll()
    {
        return self::all();
    }

    public static function getById($id)
    {
        return self::findOrFail($id);
    }

    public static function storeData($data)
    {
        return self::create($data);
    }

    public static function updateData($id, $data)
    {
        $poly = self::findOrFail($id);
        $poly->update($data);
        return $poly;
    }

    public static function softDeleteData($id)
    {
        return self::findOrFail($id)->delete();
    }

    public static function restoreData($id)
    {
        $poly = self::onlyTrashed()->findOrFail($id);
        return $poly->restore();
    }
}
