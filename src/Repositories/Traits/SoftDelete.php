<?php

namespace Railroad\Ecommerce\Repositories\Traits;

use Carbon\Carbon;

trait SoftDelete
{
    /** Soft delete
     *
     * @param integer $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->newQuery()
            ->where('id', $id)
            ->update(
                [
                    'deleted_on' => Carbon::now()->toDateTimeString()
                ]
            );
    }
}