<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class Category extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ii=$this->parent_id;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'parent_id' => $this->get_ctg( $this->parent_id),
            'createdAt' => $this->created_at->toDateTimeString(),
            'updatedAt' => $this->updated_at->toDateTimeString(),
        ];
    }
    public  function get_ctg($id){
        $name =null;
        if($id != null) {
            $name1 = DB::table("categories")
                ->select("name")
                ->where('id', '=', $id)
                ->get();
            $name= $name1[0]->name;

        }
        return $name;
    }
}
