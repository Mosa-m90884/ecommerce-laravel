<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Product extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'=> $this->id,
            'title'=>$this->title ,
            'category_id'=>$this->category_id ,
            'tag'=>$this->tag ,
            'rate'=>$this->rate,
            'tred'=>$this->tred,
            'small_description'=>$this->small_description,
            'large_description'=> $this->large_description,
            'regular_price'=> $this->regular_price,
            'discounted_price'=> $this->discounted_price,
            'quantity'=> $this->quantity,
            'created_at'=> $this->created_at,
            'updated_at'=>$this->updated_at,
            'primary_image'=> $this->primary_image,
            'other_image'=>$this->other_image,
            'featured'=> $this->featured];
    }
}
