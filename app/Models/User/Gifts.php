<?php

namespace App\Models\User;

use AWS\CRT\HTTP\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Gifts extends Model
{
    use HasFactory;
    protected $table = 'gifts';
    public static function list($request)
    {
        $search = $request->search;
        return self::where('status', 1)
            ->when($search, function ($query, $search) {
                $query->where('title', 'LIKE', "%{$search}%");
            })->paginate(10)->map(function ($item) {

                $item->image = asset('/public/cms-images/gift/' . $item->image);

                return $item;
            });
    }
    public static function Details($giftId)
    {
        try {
            $result = self::find($giftId);
            if ($result) {
                $result->image = asset('/public/cms-images/gift/' . $result->image);
            }
            return $result;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
