<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\DB;
class MonnifyImport implements ToModel
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        //
    }

    public function model(array $row)
    {
        // Replace this with your actual import logic

        $user = DB::table('user')->where(['rolex' => $row[3]])->first();
        if (!empty($user)) {
            if ($user->monify_ref == null) {
                DB::table('user')->where('id', $user->id)->update(['monify_ref' => $row[0]]);
            }
        }
    }
}
