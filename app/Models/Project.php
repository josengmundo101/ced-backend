<?php
namespace App\Models;
use App\Models\ProjectDocument;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
    'contract_id',
    'project_name',
    'category',
    'region',
    'lgu',
    'department',
    'implementing_office',
    'fund_source',
    'implementation_type',
    'contractor',
    'project_engineer',
    'year_implemented',
    'amount',
    'revised_amount',
    'location',
    'start_date',
    'end_date',
    'status',
    'remarks',   // <— ADD THIS
    'actual_end_date',
    'created_by',
];



    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

        public function documents()
    {
        return $this->hasMany(ProjectDocument::class);
    }
}