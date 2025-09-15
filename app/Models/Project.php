<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id','project_id','project_name','category','region','lgu','department',
        'implementing_office','fund_source','implementation_type','amount','revised_amount',
        'location','start_date','end_date','project_engineer','contractor','year_implemented',
        'status','progress_percentage','created_by'
    ];

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}